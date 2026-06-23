<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class VerifySecurityPinRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'digits:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'PIN is required.',
            'pin.digits' => 'PIN must be exactly 4 digits.',
        ];
    }
}
