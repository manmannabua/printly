<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\BaseRequest;
use App\Models\PriceRule;
use Illuminate\Validation\Rule;

class UpdatePriceRuleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'attribute' => ['sometimes', 'required', 'string', Rule::in(PriceRule::SUPPORTED_ATTRIBUTES)],
            'match_value' => ['sometimes', 'required', 'string', 'max:50'],
            'modifier_type' => ['sometimes', 'required', 'in:per_page,per_job,multiplier'],
            'amount_cents' => ['nullable', 'integer'],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
