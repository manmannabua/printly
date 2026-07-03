<?php

namespace App\Http\Requests\Printing;

use App\Http\Requests\BaseRequest;

class SavePrintAgentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
