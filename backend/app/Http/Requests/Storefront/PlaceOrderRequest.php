<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\Concerns\HasOrderItemRules;

class PlaceOrderRequest extends BaseRequest
{
    use HasOrderItemRules;

    public function rules(): array
    {
        return array_merge([
            'customer' => ['required', 'array'],
            'customer.name' => ['nullable', 'string', 'max:120'],
            'customer.phone' => ['required_without:customer.email', 'nullable', 'string', 'max:30'],
            'customer.email' => ['required_without:customer.phone', 'nullable', 'email', 'max:160'],

            'pay_method' => ['nullable', 'in:gcash,card,maya,cash_on_pickup'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $this->orderItemRules());
    }
}
