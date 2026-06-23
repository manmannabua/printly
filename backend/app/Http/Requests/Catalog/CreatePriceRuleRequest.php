<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\BaseRequest;
use App\Models\PriceRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class CreatePriceRuleRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'attribute' => ['required', 'string', Rule::in(PriceRule::SUPPORTED_ATTRIBUTES)],
            'match_value' => ['required', 'string', 'max:50'],
            'modifier_type' => ['required', 'in:per_page,per_job,multiplier'],
            'amount_cents' => ['nullable', 'integer'],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type = $this->input('modifier_type');
            if (in_array($type, ['per_page', 'per_job'], true) && $this->input('amount_cents') === null) {
                $v->errors()->add('amount_cents', 'amount_cents is required for per_page / per_job rules.');
            }
            if ($type === 'multiplier' && $this->input('multiplier') === null) {
                $v->errors()->add('multiplier', 'multiplier is required for multiplier rules.');
            }
        });
    }
}
