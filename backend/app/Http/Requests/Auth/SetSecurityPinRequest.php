<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class SetSecurityPinRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'digits:4'],
            'current_password' => ['required_without:current_pin', 'string'],
            'current_pin' => ['required_without:current_password', 'string', 'digits:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'A 4-digit PIN is required.',
            'pin.digits' => 'PIN must be exactly 4 digits.',
            'current_password.required_without' => 'Current password is required when setting a PIN for the first time.',
            'current_pin.required_without' => 'Current PIN is required when changing your PIN.',
            'current_pin.digits' => 'Current PIN must be exactly 4 digits.',
        ];
    }
}
