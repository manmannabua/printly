<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Order\UploadOrderFileRequest;
use App\Http\Resources\OrderFileResource;
use App\Jobs\AnalyzeOrderFile;
use App\Models\OrderFile;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderFileController extends BaseController
{
    /**
     * Accept a print file, store it privately, and queue analysis.
     * Returns immediately with analysis_status=pending — the client polls show().
     */
    public function store(UploadOrderFileRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $upload = $request->file('file');
        $path = $upload->store("orders/{$store->id}", 'private');

        $file = OrderFile::create([
            'store_id' => $store->id,
            'original_name' => $upload->getClientOriginalName(),
            'mime' => $upload->getClientMimeType(),
            'size_bytes' => $upload->getSize(),
            'storage_path' => $path,
            'analysis_status' => OrderFile::ANALYSIS_PENDING,
        ]);

        AnalyzeOrderFile::dispatch($file->id);

        return $this->success(new OrderFileResource($file), 'File uploaded; analysis queued.', 201);
    }

    /**
     * Poll a file's analysis status / specs.
     */
    public function show(Store $store, OrderFile $orderFile): JsonResponse
    {
        $this->authorizeStore($store);
        abort_unless($orderFile->store_id === $store->id, 404, 'File not found.');

        return $this->success(new OrderFileResource($orderFile));
    }

    /**
     * Stream the original file to staff for manual printing. Private disk, so it
     * is never publicly reachable — only members of the owning store may download.
     */
    public function download(Store $store, OrderFile $orderFile): StreamedResponse
    {
        $this->authorizeStore($store);
        abort_unless($orderFile->store_id === $store->id, 404, 'File not found.');

        $disk = Storage::disk('private');
        abort_unless($disk->exists($orderFile->storage_path), 404, 'File is no longer available.');

        return $disk->download($orderFile->storage_path, $orderFile->original_name);
    }
}
