<?php

namespace App\Services;

use App\Models\PriceRule;
use App\Models\Product;
use InvalidArgumentException;

/**
 * Computes an itemized price for a product.
 *
 * Two pricing modes (see planning/01-data-model-and-architecture.md §4.2):
 *  - file_based:  derived from analyzed file specs (page count, paper size,
 *                 color, duplex) + the product's price_rules.
 *  - spec_based:  base price + selected product_option deltas (quote/manual).
 *
 * All money is integer centavos. Every quote returns an itemized breakdown so
 * the customer sees exactly what they pay and disputes can be audited.
 */
class PricingService
{
    /**
     * Quote a file_based product (e.g. document printing).
     *
     * @param  array{page_count:int, paper_size?:string, color?:string, duplex?:bool, copies?:int}  $spec
     * @return array{total_cents:int, copies:int, breakdown:array<int, array<string, mixed>>}
     */
    public function quoteFileBased(Product $product, array $spec): array
    {
        $pageCount = max(0, (int) ($spec['page_count'] ?? 0));
        $copies = max(1, (int) ($spec['copies'] ?? 1));

        if ($pageCount < 1) {
            throw new InvalidArgumentException('page_count must be at least 1.');
        }

        // Attribute values the file presents, matched against price_rules.
        $attributes = array_filter([
            'paper_size' => $spec['paper_size'] ?? null,
            'color' => $spec['color'] ?? null,
            'duplex' => isset($spec['duplex']) ? ($spec['duplex'] ? 'duplex' : 'simplex') : null,
        ], fn ($v) => $v !== null);

        $rules = $product->relationLoaded('priceRules')
            ? $product->priceRules
            : $product->priceRules()->get();

        $breakdown = [];

        // 1) Base per-page price.
        $perPageCents = (int) $product->base_price_cents;
        $breakdown[] = [
            'label' => 'Base per page',
            'type' => 'per_page',
            'amount_cents' => $perPageCents,
        ];

        $multiplier = 1.0;
        $perJobCents = 0;

        // 2) Apply matching rules.
        foreach ($rules as $rule) {
            $matchAttr = $attributes[$rule->attribute] ?? null;
            if ($matchAttr === null || $matchAttr !== $rule->match_value) {
                continue;
            }

            switch ($rule->modifier_type) {
                case PriceRule::MOD_PER_PAGE:
                    $perPageCents += (int) $rule->amount_cents;
                    $breakdown[] = $this->ruleLine($rule, (int) $rule->amount_cents);
                    break;
                case PriceRule::MOD_PER_JOB:
                    $perJobCents += (int) $rule->amount_cents;
                    $breakdown[] = $this->ruleLine($rule, (int) $rule->amount_cents);
                    break;
                case PriceRule::MOD_MULTIPLIER:
                    $multiplier *= (float) $rule->multiplier;
                    $breakdown[] = [
                        'label' => $this->ruleLabel($rule),
                        'type' => 'multiplier',
                        'multiplier' => (float) $rule->multiplier,
                    ];
                    break;
            }
        }

        // 3) Combine: ((perPage * pages * multiplier) + perJob) * copies.
        $pagesCost = (int) round($perPageCents * $pageCount * $multiplier);
        $perUnit = $pagesCost + $perJobCents;
        $total = $perUnit * $copies;

        $breakdown[] = [
            'label' => "Pages × {$pageCount}" . ($multiplier !== 1.0 ? " × {$multiplier}" : ''),
            'type' => 'subtotal',
            'amount_cents' => $pagesCost,
        ];
        if ($perJobCents > 0) {
            $breakdown[] = ['label' => 'Per-job charges', 'type' => 'subtotal', 'amount_cents' => $perJobCents];
        }
        if ($copies > 1) {
            $breakdown[] = ['label' => "Copies × {$copies}", 'type' => 'multiplier', 'multiplier' => $copies];
        }

        return [
            'total_cents' => $total,
            'copies' => $copies,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Quote a spec_based product (e.g. tarpaulin / t-shirt).
     *
     * @param  array<int, array{option_id:string, choice:string}>  $selections
     * @param  int  $quantity
     * @return array{total_cents:int, quantity:int, breakdown:array<int, array<string, mixed>>}
     */
    public function quoteSpecBased(Product $product, array $selections, int $quantity = 1): array
    {
        $quantity = max(1, $quantity);

        $options = $product->relationLoaded('options')
            ? $product->options
            : $product->options()->get();

        $breakdown = [[
            'label' => 'Base price',
            'type' => 'base',
            'amount_cents' => (int) $product->base_price_cents,
        ]];

        $perUnit = (int) $product->base_price_cents;

        foreach ($selections as $selection) {
            $option = $options->firstWhere('id', $selection['option_id'] ?? null);
            if (! $option) {
                continue;
            }
            $choice = collect($option->choices ?? [])
                ->firstWhere('label', $selection['choice'] ?? null);
            if (! $choice) {
                continue;
            }
            $delta = (int) ($choice['price_delta_cents'] ?? 0);
            $perUnit += $delta;
            $breakdown[] = [
                'label' => "{$option->name}: {$choice['label']}",
                'type' => 'option',
                'amount_cents' => $delta,
            ];
        }

        if ($quantity > 1) {
            $breakdown[] = ['label' => "Quantity × {$quantity}", 'type' => 'multiplier', 'multiplier' => $quantity];
        }

        return [
            'total_cents' => $perUnit * $quantity,
            'quantity' => $quantity,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ruleLine(PriceRule $rule, int $amountCents): array
    {
        return [
            'label' => $this->ruleLabel($rule),
            'type' => $rule->modifier_type,
            'amount_cents' => $amountCents,
        ];
    }

    private function ruleLabel(PriceRule $rule): string
    {
        return ucfirst(str_replace('_', ' ', $rule->attribute)) . ': ' . $rule->match_value;
    }
}
