<?php

namespace App\Services;

use App\Models\OrderFile;
use Illuminate\Support\Str;

class PublicUploadTokenService
{
    public const TTL_HOURS = 6;

    public function issue(OrderFile $file): string
    {
        $token = Str::random(48);

        $file->forceFill([
            'upload_token_hash' => hash('sha256', $token),
            'upload_token_expires_at' => now()->addHours(self::TTL_HOURS),
        ])->save();

        return $token;
    }

    public function isValid(OrderFile $file, ?string $token): bool
    {
        if (
            empty($token)
            || empty($file->upload_token_hash)
            || ! $file->upload_token_expires_at
            || $file->upload_token_expires_at->isPast()
        ) {
            return false;
        }

        return hash_equals($file->upload_token_hash, hash('sha256', $token));
    }
}
