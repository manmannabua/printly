<?php

use App\Http\Controllers\Api\Agent\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Print Agent API (/api/agent) — token auth (AuthenticatePrintAgent)
|--------------------------------------------------------------------------
| The local in-store agent polls for jobs, downloads files, prints, and
| reports status. No session/CSRF — the bearer token is the sole credential.
*/

Route::get('me', [AgentController::class, 'me'])->name('me');
Route::get('jobs', [AgentController::class, 'jobs'])->name('jobs');
Route::get('jobs/{printJob}/file', [AgentController::class, 'file'])->name('jobs.file');
Route::patch('jobs/{printJob}', [AgentController::class, 'updateJob'])->name('jobs.update');
