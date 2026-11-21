<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PlaceOrder
{
    private const int REFERENCE_ATTEMPTS = 5;

    public function __construct(private ReserveOrderStock $reserveOrderStock) {}

    /**
     * Creates a pending order and holds its stock until `expires_at`. Either
     * every line is reserved or nothing is written.
     *
     * @param  list<OrderLineData>  $lines
     *
     * @throws ValidationException
     */
    public function handle(User $customer, array $lines): Order
    {
        $products = $this->validate($customer, $lines);

        try {
            return DB::transaction(function () use ($customer, $lines, $products): Order {
                $this->ensureNoPendingOrder($customer);

                $order = $this->createOrder($customer, $lines, $products);

                $order->items()->createMany(array_map(function (OrderLineData $line) use ($products): array {
                    $product = $products->findOrFail($line->productId);

                    return [
                        'product_id' => $product->id,
                        'quantity' => $line->quantity,
                        'unit_price' => $product->price,
                        'line_total' => $product->price * $line->quantity,
                        'title_name' => $product->title->name,
                        'product_type' => $product->type,
                    ];
                }, $lines));

                $this->reserveOrderStock->handle($order);

                return $order;
            });
        } catch (InsufficientStock $exception) {
            $index = array_search($exception->item->product_id, array_map(fn (OrderLineData $line): int => $line->productId, $lines), true);

            throw ValidationException::withMessages([
                "items.{$index}.quantity" => "No hay stock suficiente de {$exception->item->title_name}.",
            ]);
        }
    }

    /**
     * @param  list<OrderLineData>  $lines
     * @return Collection<int, Product> The ordered products, published.
     *
     * @throws ValidationException
     */
    private function validate(User $customer, array $lines): Collection
    {
        if ($customer->role !== UserRole::Customer) {
            throw ValidationException::withMessages(['user' => 'Solo los clientes pueden hacer pedidos.']);
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'El carrito está vacío.']);
        }

        $maxQuantity = Config::integer('orders.max_quantity_per_line');
        $products = Product::query()
            ->published()
            ->with('title')
            ->whereKey(array_map(fn (OrderLineData $line): int => $line->productId, $lines))
            ->get();

        $errors = [];
        $seen = [];

        foreach ($lines as $index => $line) {
            if (isset($seen[$line->productId])) {
                $errors["items.{$index}.product_id"] = 'Este producto ya está en el pedido.';
            } elseif ($products->find($line->productId) === null) {
                $errors["items.{$index}.product_id"] = 'Este producto no está disponible.';
            }

            if ($line->quantity < 1 || $line->quantity > $maxQuantity) {
                $errors["items.{$index}.quantity"] = "La cantidad debe estar entre 1 y {$maxQuantity}.";
            }

            $seen[$line->productId] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $products;
    }

    /**
     * One pending order per customer, so nobody holds stock with several
     * checkouts (docs/domain/orders.md, "Storefront checkout"). The customer's
     * row lock serializes two checkouts of the same customer.
     *
     * @throws ValidationException
     */
    private function ensureNoPendingOrder(User $customer): void
    {
        User::query()->whereKey($customer->id)->lockForUpdate()->first();

        $pending = $customer->orders()->where('status', OrderStatus::Pending)->value('reference');

        if (is_string($pending)) {
            throw ValidationException::withMessages([
                'order' => "Ya tienes un pedido pendiente ({$pending}): págalo o cancélalo antes de hacer otro.",
            ]);
        }
    }

    /**
     * Inserts the order, drawing a new reference on a collision. Each attempt
     * runs in a savepoint: a failed insert would otherwise abort the whole
     * Postgres transaction.
     *
     * @param  list<OrderLineData>  $lines
     * @param  Collection<int, Product>  $products
     */
    private function createOrder(User $customer, array $lines, Collection $products): Order
    {
        $total = array_sum(array_map(
            fn (OrderLineData $line): int => $products->findOrFail($line->productId)->price * $line->quantity,
            $lines,
        ));

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn (): Order => Order::query()->create([
                    'user_id' => $customer->id,
                    'reference' => Order::generateReference(),
                    'status' => OrderStatus::Pending,
                    'total' => $total,
                    'expires_at' => now()->addMinutes(Config::integer('orders.reservation_minutes')),
                ]));
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::REFERENCE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }
}
