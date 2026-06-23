<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;

class CreateOrderRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Optional guest/customer block (created/keyed by phone if no id).
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'customer' => ['nullable', 'array'],
            'customer.name' => ['nullable', 'string', 'max:120'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:160'],

            'pay_method' => ['nullable', 'in:gcash,card,maya,cash_on_pickup'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.file_ids' => ['nullable', 'array'],
            'items.*.file_ids.*' => ['uuid'],

            // file_based spec
            'items.*.spec' => ['nullable', 'array'],
            'items.*.spec.page_count' => ['nullable', 'integer', 'min:1'],
            'items.*.spec.paper_size' => ['nullable', 'string', 'max:50'],
            'items.*.spec.color' => ['nullable', 'in:color,bw'],
            'items.*.spec.duplex' => ['nullable', 'boolean'],
            'items.*.spec.copies' => ['nullable', 'integer', 'min:1'],

            // spec_based selections
            'items.*.selections' => ['nullable', 'array'],
            'items.*.selections.*.option_id' => ['required_with:items.*.selections', 'uuid'],
            'items.*.selections.*.choice' => ['required_with:items.*.selections', 'string'],
        ];
    }
}
