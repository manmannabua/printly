<?php

namespace Database\Seeders;

use App\Models\PriceRule;
use App\Models\PrintAgent;
use App\Models\Printer;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A ready-to-use demo shop so the whole flow works out of the box:
 * storefront (/s/campus-print-hub) → quote → order → queue board.
 *
 * Idempotent — safe to re-run. All money is integer centavos.
 */
class DemoStoreSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::firstOrCreate(
            ['slug' => 'campus-print-hub'],
            [
                'name' => 'Campus Print Hub',
                // "Auto" plan so auto-print works out of the box (it's the plan
                // that unlocks the print-agent bridge — see config/plans.php).
                'plan' => 'auto',
                'status' => 'active',
                'timezone' => 'Asia/Manila',
                'currency' => 'PHP',
                'address' => 'Ground Floor, University Belt, Manila',
                'settings' => [
                    'accepts_guest' => true,
                    'pay_on_pickup_allowed' => true,
                    'auto_print' => true,
                ],
            ],
        );

        // Put the demo store on the Auto plan (self-healing for a store seeded
        // before the subscription module existed) so auto-print — which the Auto
        // plan unlocks — works out of the box, and record its subscription.
        app(\App\Services\SubscriptionService::class)->changePlan($store, 'auto', 'seed');

        $this->seedStaff($store);
        $this->seedDocumentPrinting($store);
        $this->seedTarpaulin($store);
        $this->seedPrinting($store);
    }

    /**
     * Auto-print bridge: a demo agent with a fixed dev token so the reference
     * agent (tools/print-agent) connects out of the box, plus one printer.
     *
     * DEV ONLY — a real agent's token is random and shown once in the UI.
     */
    public const DEMO_AGENT_TOKEN = 'pa_demo_campus_print_hub_0000000000000000';

    private function seedPrinting(Store $store): void
    {
        $agent = PrintAgent::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Front counter PC'],
            [
                'token_hash' => PrintAgent::hashToken(self::DEMO_AGENT_TOKEN),
                'is_active' => true,
            ],
        );

        Printer::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'HP LaserJet (front counter)'],
            [
                'print_agent_id' => $agent->id,
                'capabilities' => ['sizes' => ['A4', 'Letter', 'Legal'], 'color' => false],
                'is_active' => true,
            ],
        );
    }

    private function seedStaff(Store $store): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'owner@printly.test'],
            [
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->attachRole($owner, 'store_owner');
        $this->attachToStore($store, $owner, 'owner');

        $staff = User::firstOrCreate(
            ['email' => 'staff@printly.test'],
            [
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->attachRole($staff, 'staff');
        $this->attachToStore($store, $staff, 'staff');
    }

    /**
     * File-based document printing — the v1 core (paper). Base price is per page;
     * price_rules adjust for colour, paper size, and duplex.
     */
    private function seedDocumentPrinting(Store $store): void
    {
        $type = ProductType::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Document Printing'],
            [
                'pricing_mode' => ProductType::PRICING_FILE_BASED,
                'fulfillment' => 'manual',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );

        // Short bond (Letter/A4). ₱2 per page B&W; colour +₱3/page; long bond +₱1/page.
        $shortBond = Product::firstOrCreate(
            ['store_id' => $store->id, 'product_type_id' => $type->id, 'name' => 'Bond Paper Printing'],
            ['base_price_cents' => 200, 'is_active' => true, 'sort_order' => 0],
        );

        $this->priceRule($shortBond, 'color', 'color', PriceRule::MOD_PER_PAGE, 300);
        $this->priceRule($shortBond, 'paper_size', 'Legal', PriceRule::MOD_PER_PAGE, 100);
        // Duplex saves paper — small per-page discount.
        $this->priceRule($shortBond, 'duplex', 'duplex', PriceRule::MOD_PER_PAGE, -50);

        // Photo paper — flat higher per-page, colour premium baked in.
        $photo = Product::firstOrCreate(
            ['store_id' => $store->id, 'product_type_id' => $type->id, 'name' => 'Photo Paper Printing'],
            ['base_price_cents' => 1500, 'is_active' => true, 'sort_order' => 1],
        );

        $this->priceRule($photo, 'paper_size', 'A4', PriceRule::MOD_PER_PAGE, 500);
    }

    /**
     * Spec-based tarpaulin — base price + option deltas (size), quantity-multiplied.
     */
    private function seedTarpaulin(Store $store): void
    {
        $type = ProductType::firstOrCreate(
            ['store_id' => $store->id, 'name' => 'Tarpaulin'],
            [
                'pricing_mode' => ProductType::PRICING_SPEC_BASED,
                'fulfillment' => 'manual',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $tarp = Product::firstOrCreate(
            ['store_id' => $store->id, 'product_type_id' => $type->id, 'name' => 'Custom Tarpaulin'],
            ['base_price_cents' => 15000, 'is_active' => true, 'sort_order' => 0],
        );

        ProductOption::firstOrCreate(
            ['store_id' => $store->id, 'product_id' => $tarp->id, 'name' => 'Size'],
            [
                'choices' => [
                    ['label' => '2x3 ft', 'price_delta_cents' => 0],
                    ['label' => '3x4 ft', 'price_delta_cents' => 10000],
                    ['label' => '4x6 ft', 'price_delta_cents' => 25000],
                ],
                'sort_order' => 0,
            ],
        );
    }

    private function priceRule(Product $product, string $attribute, string $matchValue, string $modType, int $amountCents): void
    {
        PriceRule::firstOrCreate(
            ['product_id' => $product->id, 'attribute' => $attribute, 'match_value' => $matchValue],
            [
                'store_id' => $product->store_id,
                'modifier_type' => $modType,
                'amount_cents' => $amountCents,
            ],
        );
    }

    private function attachRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && ! $user->roles()->where('roles.id', $role->id)->exists()) {
            $user->roles()->attach($role->id, ['id' => (string) Str::uuid()]);
        }
    }

    private function attachToStore(Store $store, User $user, string $role): void
    {
        if (! $store->users()->where('users.id', $user->id)->exists()) {
            $store->users()->attach($user->id, [
                'id' => (string) Str::uuid(),
                'role' => $role,
            ]);
        }
    }
}
