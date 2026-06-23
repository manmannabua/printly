<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    Order::STATUS_PAID,
                    Order::STATUS_ACCEPTED,
                    Order::STATUS_IN_PROGRESS,
                    Order::STATUS_READY,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                    Order::STATUS_REJECTED,
                    Order::STATUS_FAILED,
                    Order::STATUS_REFUNDED,
                ]),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
