<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseController extends Controller
{
    /**
     * Return a success response.
     */
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message = 'Error', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Return a created response.
     */
    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Apply search and exact-match filters to a query from request parameters.
     *
     * Config keys:
     *   'search' => 'scopeName' | ['col1', 'col2']  — search filter
     *   'exact'  => ['status', 'department_id', ...]  — exact-match filters
     */
    protected function applyFilters(Builder $query, Request $request, array $config): void
    {
        if ($request->filled('search') && isset($config['search'])) {
            $search = $config['search'];
            if (is_string($search)) {
                $query->{$search}($request->input('search'));
            } else {
                $term = $request->input('search');
                $query->where(function ($q) use ($search, $term) {
                    foreach ($search as $col) {
                        $q->orWhere($col, 'like', "%{$term}%");
                    }
                });
            }
        }

        foreach ($config['exact'] ?? [] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
    }

    /**
     * Apply sorting to a query with allowed-column validation.
     */
    protected function applySorting(Builder $query, Request $request, array $allowed, string $default = 'created_at', string $defaultDir = 'desc'): void
    {
        $sortBy = $request->input('sort_by', $default);
        $sortDir = $request->input('sort_dir', $defaultDir);

        if (!in_array($sortBy, $allowed)) {
            $sortBy = $default;
        }

        $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
    }

    /**
     * Paginate the query or return all results. Enforces a max per_page of 100.
     */
    protected function paginateOrAll(Builder $query, Request $request, string $resourceClass): JsonResponse
    {
        if ($request->boolean('all')) {
            return $this->success($resourceClass::collection($query->get()));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $this->paginated($query->paginate($perPage)->through(fn ($m) => new $resourceClass($m)));
    }
}
