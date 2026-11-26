<?php

use App\Actions\Orders\OrderLineData;
use App\Actions\Orders\PlaceOrder;
use App\Enums\ProductKeyStatus;
use App\Models\Product;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
 * `UPDATE … WHERE id IN (SELECT … LIMIT n FOR UPDATE SKIP LOCKED)` lets
 * Postgres rescan the subquery for each outer row under some plans and update
 * more than n keys. These plan settings force such a plan, whatever the
 * table statistics happen to be.
 */
it('reserves exactly the quantity of keys, whatever plan Postgres picks', function (array $settings) {
    $product = Product::factory()->published()->create();
    ProductKey::factory()->for($product)->count(8)->create();

    foreach ($settings as $setting) {
        DB::statement("SET LOCAL {$setting} = off");
    }

    $order = app(PlaceOrder::class)->handle(User::factory()->create(), [new OrderLineData($product->id, 2)]);

    expect($order->items()->sole()->keys()->count())->toBe(2)
        ->and(ProductKey::query()->where('status', ProductKeyStatus::Available)->count())->toBe(6);
})->with([
    'nested loop semi join' => [['enable_hashagg', 'enable_hashjoin', 'enable_mergejoin', 'enable_material', 'enable_sort']],
    'default' => [[]],
]);
