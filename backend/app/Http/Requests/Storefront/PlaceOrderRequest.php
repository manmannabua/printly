<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\BaseRequest;

class PlaceOrderRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Guest contact is required at the storefront so the customer can be
            // reached and can track the order (keyed by phone, planning §4.1).
            'customer' => ['required', 'array'],
            'customer.name' => ['nullable', 'string', 'max:120'],
            'customer.phone' => ['required_without:customer.email', 'nullable', 'string', 'max:30'],
            'customer.email' => ['required_without:customer.phone', 'nullable', 'email', 'max:160'],

            'pay_method' => ['nullable', 'in:gcash,card,maya,cash_on_pickup'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.file_ids' => ['nullable', 'array'],
            'items.*.file_ids.*' => ['uuid'],

            'items.*.spec' => ['nullable', 'array'],
            'items.*.spec.page_count' => ['nullable', 'integer', 'min:1'],
            'items.*.spec.paper_size' => ['nullable', 'string', 'max:50'],
            'items.*.spec.color' => ['nullable', 'in:color,bw'],
            'items.*.spec.duplex' => ['nullable', 'boolean'],
            'items.*.spec.copies' => ['nullable', 'integer', 'min:1'],

            'items.*.selections' => ['nullable', 'array'],
            'items.*.selections.*.option_id' => ['required_with:items.*.selections', 'uuid'],
            'items.*.selections.*.choice' => ['required_with:items.*.selections', 'string'],
        ];
    }
}
