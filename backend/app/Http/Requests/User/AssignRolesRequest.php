<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;
use App\Models\Role;
use Illuminate\Validation\Validator;

class AssignRolesRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is enforced by the route's permission:users.update middleware;
        // privilege-escalation is additionally guarded in validateRoleLevels().
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'uuid', 'exists:roles,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateRoleLevels($validator);
        });
    }

    /**
     * Validate that user can only assign roles at or below their level.
     * Prevents privilege escalation.
     */
    protected function validateRoleLevels(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $currentUser = $this->user();
        $currentUserLevel = $currentUser->getRoleLevel();

        // Admin can assign any role
        if ($currentUserLevel === 1) {
            return;
        }

        $roleIds = $this->input('roles', []);
        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            // Can only assign roles with equal or lower privilege (higher level number)
            if ($role->level < $currentUserLevel) {
                $validator->errors()->add(
                    'roles',
                    "You cannot assign the '{$role->display_name}' role. Insufficient privileges."
                );
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be specified.',
            'roles.array' => 'Roles must be an array.',
            'roles.min' => 'At least one role must be specified.',
            'roles.*.uuid' => 'Invalid role ID format.',
            'roles.*.exists' => 'One or more selected roles do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'roles' => 'roles',
            'roles.*' => 'role',
        ];
    }
}
