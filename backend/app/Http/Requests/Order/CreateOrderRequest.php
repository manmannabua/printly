<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\Concerns\HasOrderItemRules;

class CreateOrderRequest extends BaseRequest
{
    use HasOrderItemRules;

    public function rules(): array
    {
        return array_merge([
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'customer' => ['nullable', 'array'],
            'customer.name' => ['nullable', 'string', 'max:120'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:160'],

            'pay_method' => ['nullable', 'in:gcash,card,maya,cash_on_pickup'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $this->orderItemRules());
    }
}
