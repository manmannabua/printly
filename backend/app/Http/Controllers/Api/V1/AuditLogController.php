<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Filter by user
        if ($request->has('user_id')) {
            $query->forUser($request->input('user_id'));
        }

        // Filter by action
        if ($request->has('action')) {
            $query->byAction($request->input('action'));
        }

        // Filter by model type
        if ($request->has('auditable_type')) {
            $query->forModel($request->input('auditable_type'));
        }

        // Filter by auditable id (specific record)
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->input('auditable_id'));
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->forDateRange($request->input('date_from'), $request->input('date_to'));
        }

        // Search by action or model type
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%");
            });
        }

        // Sort (newest first by default)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'action', 'auditable_type'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = min($request->input('per_page', 15), 100);
        $auditLogs = $query->paginate($perPage);

        return $this->paginated($auditLogs->through(fn ($log) => new AuditLogResource($log)));
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return $this->success(new AuditLogResource($auditLog));
    }
}
