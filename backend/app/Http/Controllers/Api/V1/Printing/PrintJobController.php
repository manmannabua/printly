<?php

namespace App\Http\Controllers\Api\V1\Printing;

use App\Events\PrintJobUpdated;
use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Resources\PrintJobResource;
use App\Models\PrintJob;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Owner-facing view of a store's print jobs, and a manual retry for failed ones.
 */
class PrintJobController extends BaseController
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $query = PrintJob::query()->where('store_id', $store->id)->with(['order', 'orderFile', 'printer']);
        $this->applyFilters($query, $request, ['exact' => ['status', 'printer_id', 'order_id']]);
        $this->applySorting($query, $request, ['status', 'created_at'], 'created_at', 'desc');

        return $this->paginateOrAll($query, $request, PrintJobResource::class);
    }

    /**
     * Re-queue an errored job so an agent will pick it up again.
     */
    public function retry(Store $store, PrintJob $printJob): JsonResponse
    {
        $this->authorizeStore($store);
        abort_unless($printJob->store_id === $store->id, 404, 'Print job not found.');

        abort_unless($printJob->status === PrintJob::STATUS_ERROR, 422, 'Only failed jobs can be retried.');

        $printJob->update([
            'status' => PrintJob::STATUS_QUEUED,
            'error' => null,
            'sent_at' => null,
            'printed_at' => null,
        ]);
        PrintJobUpdated::dispatch($printJob);

        return $this->success(new PrintJobResource($printJob->load(['order', 'orderFile', 'printer'])), 'Print job re-queued.');
    }
}
