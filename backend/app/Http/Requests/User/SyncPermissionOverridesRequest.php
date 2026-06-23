<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SyncPermissionOverridesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            'overrides' => ['present', 'array'],
            'overrides.*.permission_id' => ['required', 'uuid', 'exists:permissions,id'],
            'overrides.*.type' => ['required', 'in:grant,deny'],
            'overrides.*.reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
