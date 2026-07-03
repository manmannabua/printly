<?php

namespace App\Http\Requests\Printing;

use App\Http\Requests\BaseRequest;

class SavePrinterRequest extends BaseRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:120'],
            'print_agent_id' => ['nullable', 'uuid'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.sizes' => ['nullable', 'array'],
            'capabilities.sizes.*' => ['string', 'max:50'],
            'capabilities.color' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
