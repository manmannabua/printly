<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoleRequest extends BaseRequest
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
        $roleId = $this->route('role')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($roleId),
                'regex:/^[a-z][a-z0-9_-]*$/',
            ],
            'display_name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:3'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateSystemRole($validator);
            $this->validateRoleLevel($validator);
        });
    }

    /**
     * Prevent modification of system role names.
     */
    protected function validateSystemRole(Validator $validator): void
    {
        $role = $this->route('role');
        $systemRoles = ['admin', 'employee'];

        // Prevent renaming system roles
        if (in_array($role->name, $systemRoles) && $this->has('name') && $this->input('name') !== $role->name) {
            $validator->errors()->add(
                'name',
                'System role names cannot be changed.'
            );
        }

        // Prevent changing level of system roles
        if (in_array($role->name, $systemRoles) && $this->has('level') && (int) $this->input('level') !== $role->level) {
            $validator->errors()->add(
                'level',
                'System role levels cannot be changed.'
            );
        }
    }

    /**
     * Validate that user can only set roles to equal or lower privilege level.
     * Prevents privilege escalation.
     */
    protected function validateRoleLevel(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        if (!$this->has('level')) {
            return;
        }

        $currentUser = $this->user();
        $currentUserLevel = $currentUser->getRoleLevel();
        $newRoleLevel = (int) $this->input('level');

        // Admin can set any level
        if ($currentUserLevel === 1) {
            return;
        }

        // Can only set roles to equal or lower privilege (higher or equal level number)
        if ($newRoleLevel < $currentUserLevel) {
            $validator->errors()->add(
                'level',
                'You cannot set a role to a higher privilege level than your own.'
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
