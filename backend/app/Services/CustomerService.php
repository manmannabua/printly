<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    /**
     * Resolve a customer id from an explicit id or an inline guest/contact block.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveForOrder(array $data): ?string
    {
        if (! empty($data['customer_id'])) {
            return $data['customer_id'];
        }

        $customer = $data['customer'] ?? null;
        if (! is_array($customer) || (empty($customer['phone']) && empty($customer['email']))) {
            return null;
        }

        return $this->resolveGuest($customer);
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    public function resolveGuest(array $customer): string
    {
        $existing = ! empty($customer['phone'])
            ? Customer::where('phone', $customer['phone'])->first()
            : null;

        if ($existing) {
            return $existing->id;
        }

        return Customer::create([
            'name' => $customer['name'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'email' => $customer['email'] ?? null,
            'is_guest' => true,
        ])->id;
    }
}
