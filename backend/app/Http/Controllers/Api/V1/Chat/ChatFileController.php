<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\ChatMessageAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves chat attachments. The attachment UUID is the bearer (same access model
 * as the order code), so this is reachable by both store staff and the guest
 * customer without separate auth.
 */
class ChatFileController extends BaseController
{
    public function show(string $id): StreamedResponse
    {
        $attachment = ChatMessageAttachment::findOrFail($id);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->name,
            ['Content-Type' => $attachment->mime ?? 'application/octet-stream'],
        );
    }
}
