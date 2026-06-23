<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseController
{
    /**
     * High-level counts for the admin dashboard.
     *
     * Placeholder during the skeleton phase — Printly domain metrics
     * (stores, orders, queue depth, revenue) are wired once those modules land.
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
        ]);
    }
}
