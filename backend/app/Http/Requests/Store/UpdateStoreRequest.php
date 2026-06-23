<?php

namespace App\Http\Requests\Store;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        $storeId = $this->route('store')?->id ?? $this->route('store');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('stores', 'slug')->ignore($storeId)],
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
}
