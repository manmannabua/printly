<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->user->roles()->attach(Role::where('name', 'admin')->first()->id, [
        'id' => (string) Str::uuid(),
        'created_at' => now(),
    ]);
});

it('stores an uploaded image and returns its path and url', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/uploads', [
        'file' => UploadedFile::fake()->image('logo.png', 200, 200),
        'folder' => 'developers',
    ])->assertCreated();

    $path = $response->json('data.path');

    expect($path)->toStartWith('developers/');
    Storage::disk('public')->assertExists($path);
    expect($response->json('data.url'))->toContain('/storage/developers/');
});

it('rejects an unsupported folder', function () {
    $this->actingAs($this->user)->postJson('/api/v1/uploads', [
        'file' => UploadedFile::fake()->image('x.png'),
        'folder' => 'secrets',
    ])->assertStatus(422)->assertJsonValidationErrors(['folder']);
});

it('rejects a disallowed file type', function () {
    $this->actingAs($this->user)->postJson('/api/v1/uploads', [
        'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
        'folder' => 'projects',
    ])->assertStatus(422)->assertJsonValidationErrors(['file']);
});

it('requires authentication to upload', function () {
    $this->postJson('/api/v1/uploads', [
        'file' => UploadedFile::fake()->image('x.png'),
        'folder' => 'projects',
    ])->assertUnauthorized();
});

it('deletes a previously uploaded file', function () {
    $path = UploadedFile::fake()->image('cover.jpg')->store('projects', 'public');
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->user)
        ->deleteJson('/api/v1/uploads', ['path' => $path])
        ->assertOk();

    Storage::disk('public')->assertMissing($path);
});

it('refuses to delete outside the allowed folders', function () {
    $this->actingAs($this->user)
        ->deleteJson('/api/v1/uploads', ['path' => '../../.env'])
        ->assertStatus(422);
});
