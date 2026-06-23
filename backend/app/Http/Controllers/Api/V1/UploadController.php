<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UploadController extends BaseController
{
    /**
     * Folders the API is allowed to write to / delete from, on the public disk.
     *
     * @var array<int, string>
     */
    private const ALLOWED_FOLDERS = ['developers', 'projects', 'brokerages', 'agents', 'project-media', 'unit-types'];

    /**
     * Store an uploaded image or document and return its path + public URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,webp,gif,pdf',
            ],
            'folder' => ['required', Rule::in(self::ALLOWED_FOLDERS)],
        ]);

        $path = $request->file('file')->store($validated['folder'], 'public');

        return $this->created([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'name' => $request->file('file')->getClientOriginalName(),
            'size' => $request->file('file')->getSize(),
        ], 'File uploaded successfully.');
    }

    /**
     * Delete a previously uploaded file from the public disk.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
        ]);

        $path = $validated['path'];
        $folder = explode('/', $path)[0] ?? '';

        // Guard against traversal and out-of-scope deletions.
        if (str_contains($path, '..') || !in_array($folder, self::ALLOWED_FOLDERS, true)) {
            return $this->error('Invalid file path.', 422);
        }

        Storage::disk('public')->delete($path);

        return $this->success(null, 'File deleted successfully.');
    }
}
