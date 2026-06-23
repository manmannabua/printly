<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Http\Requests\Order\UploadOrderFileRequest;
use App\Http\Resources\OrderFileResource;
use App\Jobs\AnalyzeOrderFile;
use App\Models\OrderFile;
use Illuminate\Http\JsonResponse;

class StorefrontFileController extends BaseController
{
    use ResolvesStorefront;

    /**
     * Guest upload of a print file to a store, before checkout. Same pipeline as
     * the staff upload — stored privately, analysis queued — but public + throttled.
     */
    public function store(UploadOrderFileRequest $request, string $slug): JsonResponse
    {
        $store = $this->activeStore($slug);

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
     * Poll a file's analysis status. Scoped to the store so a guest can only
     * read files belonging to the store they uploaded to.
     */
    public function show(string $slug, OrderFile $orderFile): JsonResponse
    {
        $store = $this->activeStore($slug);
        abort_unless($orderFile->store_id === $store->id, 404, 'File not found.');

        return $this->success(new OrderFileResource($orderFile));
    }
}
