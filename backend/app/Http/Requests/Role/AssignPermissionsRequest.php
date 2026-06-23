<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseRequest;

class AssignPermissionsRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is enforced by the route's permission:roles.update middleware.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'uuid', 'exists:permissions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'permissions.required' => 'Permissions list is required.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.uuid' => 'Invalid permission ID format.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'permissions' => 'permissions',
            'permissions.*' => 'permission',
        ];
    }
}
