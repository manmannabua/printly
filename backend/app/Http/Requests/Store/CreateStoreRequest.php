<?php

namespace App\Http\Requests\Store;

use App\Http\Requests\BaseRequest;

class CreateStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'unique:stores,slug', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'plan' => ['nullable', 'in:starter,pro,auto'],
            'status' => ['nullable', 'in:active,suspended,trial'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug must be lowercase letters, numbers, and hyphens only.',
            'slug.unique' => 'A store with this slug already exists.',
        ];
    }
}
