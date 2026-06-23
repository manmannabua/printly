<?php

use App\Jobs\AnalyzeOrderFile;
use App\Models\OrderFile;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\FileAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first()->id, [
        'id' => (string) Str::uuid(), 'created_at' => now(),
    ]);

    $this->store = Store::create([
        'name' => 'Campus Print Hub', 'slug' => 'campus-print-hub', 'plan' => 'pro', 'status' => 'active',
    ]);
});

it('stores an upload on the private disk and queues analysis', function () {
    Storage::fake('private');
    Queue::fake();

    $res = $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$this->store->id}/files", [
            'file' => UploadedFile::fake()->create('thesis.pdf', 100, 'application/pdf'),
        ])
        ->assertCreated()
        ->assertJsonPath('data.analysis_status', 'pending');

    $file = OrderFile::find($res->json('data.id'));
    expect($file->store_id)->toBe($this->store->id);
    Storage::disk('private')->assertExists($file->storage_path);
    Queue::assertPushed(AnalyzeOrderFile::class);
});

it('streams a stored file back to a store member for printing', function () {
    Storage::fake('private');

    $path = "orders/{$this->store->id}/job.pdf";
    Storage::disk('private')->put($path, '%PDF-1.4 fake');
    $file = OrderFile::create([
        'store_id' => $this->store->id,
        'original_name' => 'job.pdf',
        'mime' => 'application/pdf',
        'size_bytes' => 12,
        'storage_path' => $path,
        'analysis_status' => OrderFile::ANALYSIS_DONE,
    ]);

    $this->actingAs($this->admin)
        ->get("/api/v1/stores/{$this->store->id}/files/{$file->id}/download")
        ->assertOk()
        ->assertDownload('job.pdf');
});

it('rejects an unsupported file type', function () {
    Storage::fake('private');

    $this->actingAs($this->admin)
        ->postJson("/api/v1/stores/{$this->store->id}/files", [
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('analyzes a PDF: page count and paper size', function () {
    Storage::fake('private');

    // A real, parseable 2-page A4 PDF.
    $pdf = Pdf::loadHtml('<div>page one</div><div style="page-break-before: always;">page two</div>')
        ->setPaper('a4')
        ->output();
    $path = "orders/{$this->store->id}/sample.pdf";
    Storage::disk('private')->put($path, $pdf);

    $file = OrderFile::create([
        'store_id' => $this->store->id,
        'original_name' => 'sample.pdf',
        'mime' => 'application/pdf',
        'size_bytes' => strlen($pdf),
        'storage_path' => $path,
        'analysis_status' => OrderFile::ANALYSIS_PENDING,
    ]);

    app(FileAnalysisService::class)->analyze($file->refresh());

    expect($file->refresh()->analysis_status)->toBe('done')
        ->and($file->page_count)->toBe(2)
        ->and($file->paper_size)->toBe('A4');
});

it('marks analysis failed when the stored file is missing', function () {
    Storage::fake('private');

    $file = OrderFile::create([
        'store_id' => $this->store->id,
        'original_name' => 'gone.pdf',
        'mime' => 'application/pdf',
        'size_bytes' => 1,
        'storage_path' => 'orders/missing.pdf',
        'analysis_status' => OrderFile::ANALYSIS_PENDING,
    ]);

    app(FileAnalysisService::class)->analyze($file);

    expect($file->refresh()->analysis_status)->toBe('failed')
        ->and($file->analysis_error)->not->toBeNull();
});
