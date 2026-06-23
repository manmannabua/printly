<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\BaseRequest;

class CreateProductTypeRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'pricing_mode' => ['required', 'in:file_based,spec_based'],
            'fulfillment' => ['nullable', 'in:manual,auto'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
