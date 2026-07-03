<?php

namespace App\Http\Controllers\Api\V1\Printing;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Printing\SavePrintAgentRequest;
use App\Http\Resources\PrintAgentResource;
use App\Models\AuditLog;
use App\Models\PrintAgent;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Owner-facing management of a store's print agents. The plaintext bearer token
 * is returned exactly once (on create / regenerate) and only its sha256 hash is
 * stored — the owner installs it into the local agent.
 */
class PrintAgentController extends BaseController
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $query = PrintAgent::query()->where('store_id', $store->id)->withCount('printers');
        $this->applyFilters($query, $request, ['search' => ['name'], 'exact' => ['is_active']]);
        $this->applySorting($query, $request, ['name', 'last_seen_at', 'created_at'], 'created_at', 'desc');

        return $this->paginateOrAll($query, $request, PrintAgentResource::class);
    }

    public function store(SavePrintAgentRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $plain = PrintAgent::newToken();
        $agent = $store->printAgents()->create([
            'name' => $request->validated('name'),
            'token_hash' => PrintAgent::hashToken($plain),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $agent->plain_token = $plain;

        AuditLog::log($agent, 'created', null, ['name' => $agent->name]);

        return $this->success(new PrintAgentResource($agent), 'Print agent created. Copy the token now — it will not be shown again.', 201);
    }

    public function show(Store $store, PrintAgent $printAgent): JsonResponse
    {
        $this->ensureOwned($store, $printAgent);

        return $this->success(new PrintAgentResource($printAgent->load('printers')->loadCount('printers')));
    }

    public function update(SavePrintAgentRequest $request, Store $store, PrintAgent $printAgent): JsonResponse
    {
        $this->ensureOwned($store, $printAgent);
        $old = $printAgent->only(['name', 'is_active']);
        $printAgent->update($request->validated());
        AuditLog::log($printAgent, 'updated', $old, $printAgent->only(['name', 'is_active']));

        return $this->success(new PrintAgentResource($printAgent), 'Print agent updated.');
    }

    public function destroy(Store $store, PrintAgent $printAgent): JsonResponse
    {
        $this->ensureOwned($store, $printAgent);
        $snapshot = $printAgent->only(['name']);
        $printAgent->delete();
        AuditLog::log($printAgent, 'deleted', $snapshot);

        return $this->success(null, 'Print agent removed.');
    }

    /**
     * Issue a new token (invalidates the old one). Returned once.
     */
    public function regenerateToken(Store $store, PrintAgent $printAgent): JsonResponse
    {
        $this->ensureOwned($store, $printAgent);

        $plain = PrintAgent::newToken();
        $printAgent->update(['token_hash' => PrintAgent::hashToken($plain)]);
        $printAgent->plain_token = $plain;
        AuditLog::log($printAgent, 'token_regenerated');

        return $this->success(new PrintAgentResource($printAgent), 'New token issued. Copy it now — it will not be shown again.');
    }

    private function ensureOwned(Store $store, PrintAgent $printAgent): void
    {
        $this->authorizeStore($store);
        abort_unless($printAgent->store_id === $store->id, 404, 'Print agent not found.');
    }
}
