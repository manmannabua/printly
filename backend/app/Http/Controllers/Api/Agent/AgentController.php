<?php

namespace App\Http\Controllers\Api\Agent;

use App\Events\PrintJobUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\PrinterResource;
use App\Models\OrderFile;
use App\Models\PrintAgent;
use App\Models\PrintJob;
use App\Services\PrintRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The API a local print agent talks to (planning §5.3). Token-authenticated via
 * the AuthenticatePrintAgent middleware; the agent polls for jobs, downloads
 * each file, prints, and reports status back.
 */
class AgentController extends Controller
{
    public function __construct(private readonly PrintRoutingService $routing) {}

    /** Resolve the agent bound by the auth middleware. */
    private function agent(Request $request): PrintAgent
    {
        return $request->attributes->get('print_agent');
    }

    /**
     * Agent bootstrap: who am I, which printers do I drive.
     */
    public function me(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => ['id' => $agent->id, 'name' => $agent->name, 'store_id' => $agent->store_id],
                'printers' => PrinterResource::collection($agent->printers()->where('is_active', true)->get()),
            ],
        ]);
    }

    /**
     * Claim and return queued jobs for this agent's printers. Claiming
     * (queued → sent) is atomic so a job is handed to exactly one poll, and the
     * owning order advances to in_progress.
     */
    public function jobs(Request $request): JsonResponse
    {
        $agent = $this->agent($request);
        $printerIds = $agent->printers()->pluck('id');

        if ($printerIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $staleBefore = now()->subSeconds(PrintJob::STALE_AFTER_SECONDS);

        $claimed = DB::transaction(function () use ($printerIds, $staleBefore) {
            // Fresh jobs, plus jobs an earlier poll claimed but never finished
            // (agent crashed) — reclaimed once they go stale so nothing sticks.
            $jobs = PrintJob::whereIn('printer_id', $printerIds)
                ->where(function ($q) use ($staleBefore) {
                    $q->where('status', PrintJob::STATUS_QUEUED)
                        ->orWhere(fn ($s) => $s->whereIn('status', PrintJob::ACTIVE)
                            ->where('sent_at', '<', $staleBefore));
                })
                ->lockForUpdate()
                ->get();

            foreach ($jobs as $job) {
                $reclaim = $job->status !== PrintJob::STATUS_QUEUED;
                $job->update([
                    'status' => PrintJob::STATUS_SENT,
                    'sent_at' => now(),
                    'attempts' => $reclaim ? $job->attempts + 1 : $job->attempts,
                ]);
            }

            return $jobs;
        });

        $orders = [];
        foreach ($claimed as $job) {
            PrintJobUpdated::dispatch($job);
            $orders[$job->order_id] = $job->order;
        }
        foreach ($orders as $order) {
            if ($order) {
                $this->routing->syncOrderFromJobs($order);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $claimed->map(fn (PrintJob $job) => $this->jobPayload($job))->values(),
        ]);
    }

    /**
     * Stream the file for a job the agent owns.
     */
    public function file(Request $request, PrintJob $printJob): StreamedResponse
    {
        $this->assertOwnedJob($request, $printJob);

        $file = $printJob->orderFile;
        $disk = Storage::disk('private');
        abort_unless($file && $disk->exists($file->storage_path), 404, 'File is no longer available.');

        return $disk->download($file->storage_path, $file->original_name);
    }

    /**
     * Report job progress: printing | done | error. Folds back into the order.
     */
    public function updateJob(Request $request, PrintJob $printJob): JsonResponse
    {
        $this->assertOwnedJob($request, $printJob);

        $data = $request->validate([
            'status' => ['required', 'in:printing,done,error'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        $attrs = ['status' => $data['status']];
        if ($data['status'] === PrintJob::STATUS_DONE) {
            $attrs['printed_at'] = now();
            $attrs['error'] = null;
        }
        if ($data['status'] === PrintJob::STATUS_ERROR) {
            $attrs['error'] = $data['error'] ?? 'Agent reported an error.';
            $attrs['attempts'] = $printJob->attempts + 1;
        }

        $printJob->update($attrs);
        PrintJobUpdated::dispatch($printJob);

        if ($printJob->order) {
            $this->routing->syncOrderFromJobs($printJob->order);
        }

        return response()->json(['success' => true, 'data' => $this->jobPayload($printJob->fresh())]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(PrintJob $job): array
    {
        $file = $job->orderFile;

        return [
            'id' => $job->id,
            'status' => $job->status,
            'copies' => $job->copies,
            'printer_id' => $job->printer_id,
            'order_code' => $job->order?->code,
            'file' => [
                'name' => $file?->original_name,
                'mime' => $file?->mime,
                'paper_size' => $file?->paper_size,
                'color' => (bool) ($file?->is_color ?? false),
                'page_count' => $file?->page_count,
                'download_url' => route('agent.jobs.file', $job->id),
            ],
        ];
    }

    private function assertOwnedJob(Request $request, PrintJob $job): void
    {
        $agent = $this->agent($request);
        $ownsPrinter = $job->printer_id !== null
            && $agent->printers()->whereKey($job->printer_id)->exists();

        abort_unless($ownsPrinter, 404, 'Print job not found.');
    }
}
