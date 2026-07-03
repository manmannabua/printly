<?php

namespace App\Http\Controllers\Api\V1\Printing;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Printing\SavePrinterRequest;
use App\Http\Resources\PrinterResource;
use App\Models\AuditLog;
use App\Models\Printer;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Owner-facing management of a store's physical printers (name, capabilities,
 * which agent drives them). Capabilities gate auto-print routing.
 */
class PrinterController extends BaseController
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $query = Printer::query()->where('store_id', $store->id)->with('agent');
        $this->applyFilters($query, $request, ['search' => ['name'], 'exact' => ['is_active', 'print_agent_id']]);
        $this->applySorting($query, $request, ['name', 'created_at'], 'name', 'asc');

        return $this->paginateOrAll($query, $request, PrinterResource::class);
    }

    public function store(SavePrinterRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $this->prepared($request, $store);

        $printer = $store->printers()->create($data);
        AuditLog::log($printer, 'created', null, ['name' => $printer->name]);

        return $this->success(new PrinterResource($printer->load('agent')), 'Printer added.', 201);
    }

    public function show(Store $store, Printer $printer): JsonResponse
    {
        $this->ensureOwned($store, $printer);

        return $this->success(new PrinterResource($printer->load('agent')));
    }

    public function update(SavePrinterRequest $request, Store $store, Printer $printer): JsonResponse
    {
        $this->ensureOwned($store, $printer);
        $old = $printer->only(['name', 'print_agent_id', 'capabilities', 'is_active']);
        $printer->update($this->prepared($request, $store));
        AuditLog::log($printer, 'updated', $old, $printer->only(['name', 'print_agent_id', 'capabilities', 'is_active']));

        return $this->success(new PrinterResource($printer->load('agent')), 'Printer updated.');
    }

    public function destroy(Store $store, Printer $printer): JsonResponse
    {
        $this->ensureOwned($store, $printer);
        $snapshot = $printer->only(['name']);
        $printer->delete();
        AuditLog::log($printer, 'deleted', $snapshot);

        return $this->success(null, 'Printer removed.');
    }

    /**
     * Validated payload; ensures a linked agent belongs to the same store.
     *
     * @return array<string, mixed>
     */
    private function prepared(SavePrinterRequest $request, Store $store): array
    {
        $data = $request->validated();

        if (! empty($data['print_agent_id'])) {
            $owned = $store->printAgents()->whereKey($data['print_agent_id'])->exists();
            if (! $owned) {
                throw ValidationException::withMessages([
                    'print_agent_id' => 'That agent does not belong to this store.',
                ]);
            }
        }

        return $data;
    }

    private function ensureOwned(Store $store, Printer $printer): void
    {
        $this->authorizeStore($store);
        abort_unless($printer->store_id === $store->id, 404, 'Printer not found.');
    }
}
