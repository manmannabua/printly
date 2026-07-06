<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Http\Requests\Order\UploadOrderFileRequest;
use App\Http\Resources\OrderFileResource;
use App\Jobs\AnalyzeOrderFile;
use App\Models\OrderFile;
use App\Services\PublicUploadTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontFileController extends BaseController
{
    use ResolvesStorefront;

    public function __construct(private readonly PublicUploadTokenService $tokens) {}

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
        $file->upload_token = $this->tokens->issue($file);

        AnalyzeOrderFile::dispatch($file->id);

        return $this->success(new OrderFileResource($file), 'File uploaded; analysis queued.', 201);
    }

    public function show(Request $request, string $slug, OrderFile $orderFile): JsonResponse
    {
        $store = $this->activeStore($slug);
        abort_unless($orderFile->store_id === $store->id, 404, 'File not found.');
        abort_unless($this->tokens->isValid($orderFile, $request->query('token')), 404, 'File not found.');

        return $this->success(new OrderFileResource($orderFile));
    }
}
