<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JobPosting;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Str;

$dept = Department::first();
$pos = Position::first();

$existing = JobPosting::where('title', 'Test Engineer')->first();
if ($existing) {
    echo "EXISTS:" . $existing->id . "\n";
    exit;
}

$job = JobPosting::create([
    'id' => Str::uuid(),
    'title' => 'Test Engineer',
    'description' => 'Test description for email verification feature',
    'requirements' => 'Test requirements',
    'department_id' => $dept?->id,
    'position_id' => $pos?->id,
    'employment_type' => 'full_time',
    'status' => 'open',
    'location' => 'Remote',
    'salary_min' => 30000,
    'salary_max' => 50000,
    'show_salary' => true,
    'slug' => 'test-engineer-' . Str::random(6),
    'created_by' => '798da153-abd7-4326-922f-27293e7c6045',
]);
echo "CREATED:" . $job->id . "\n";
