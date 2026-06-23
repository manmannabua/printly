<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends BaseRequest
{
    public function rules(): array
    {
        $storeId = $this->route('store')?->id ?? $this->route('store');

        return [
            'product_type_id' => [
                'required', 'uuid',
                Rule::exists('product_types', 'id')->where('store_id', $storeId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'base_price_cents' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
