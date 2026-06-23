<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as BaseValidateCsrfToken;

class ValidateCsrfToken extends BaseValidateCsrfToken
{
    /**
     * Public token-gated routes that have no session — skip CSRF.
     */
    protected $except = [
        'api/public/*',
    ];
}
