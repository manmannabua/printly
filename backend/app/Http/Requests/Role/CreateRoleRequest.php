<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class CreateRoleRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is enforced by the route's permission:roles.create middleware.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'unique:roles,name',
                'regex:/^[a-z][a-z0-9_-]*$/',
            ],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'level' => ['required', 'integer', 'min:1', 'max:3'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateRoleLevel($validator);
        });
    }

    /**
     * Validate that user can only create roles at or below their level.
     * Prevents privilege escalation.
     */
    protected function validateRoleLevel(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $currentUser = $this->user();
        $currentUserLevel = $currentUser->getRoleLevel();
        $newRoleLevel = (int) $this->input('level');

        // Admin can create any role
        if ($currentUserLevel === 1) {
            return;
        }

        // Can only create roles with equal or lower privilege (higher or equal level number)
        if ($newRoleLevel < $currentUserLevel) {
            $validator->errors()->add(
                'level',
                'You cannot create a role with higher privileges than your own.'
            );
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'name.regex' => 'Role name must start with a letter and contain only lowercase letters, numbers, hyphens, and underscores.',
            'display_name.required' => 'Display name is required.',
            'level.required' => 'Role level is required.',
            'level.min' => 'Role level must be between 1 and 3.',
            'level.max' => 'Role level must be between 1 and 3.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'role name',
            'display_name' => 'display name',
            'description' => 'description',
            'level' => 'role level',
        ];
    }
}
