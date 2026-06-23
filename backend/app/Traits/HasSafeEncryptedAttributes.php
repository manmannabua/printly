<?php

namespace App\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

trait HasSafeEncryptedAttributes
{
    public function fromEncryptedString($value)
    {
        try {
            return static::currentEncrypter()->decrypt($value, false);
        } catch (DecryptException $e) {
            Log::warning('Encrypted attribute decrypt failed', [
                'model' => static::class,
                'id' => $this->getKey(),
            ]);

            return null;
        }
    }
}
