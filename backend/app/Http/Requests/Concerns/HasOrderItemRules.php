<?php

namespace App\Http\Requests\Concerns;

trait HasOrderItemRules
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function orderItemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.file_ids' => ['nullable', 'array'],
            'items.*.file_ids.*' => ['uuid'],
            'items.*.file_tokens' => ['nullable', 'array'],
            'items.*.file_tokens.*' => ['string', 'max:120'],

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
