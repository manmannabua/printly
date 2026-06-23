<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\BaseRequest;

class UpdatePriceRuleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'attribute' => ['sometimes', 'required', 'string', 'max:50'],
            'match_value' => ['sometimes', 'required', 'string', 'max:50'],
            'modifier_type' => ['sometimes', 'required', 'in:per_page,per_job,multiplier'],
            'amount_cents' => ['nullable', 'integer'],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
