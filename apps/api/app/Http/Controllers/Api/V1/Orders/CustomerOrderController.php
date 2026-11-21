<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\PlaceCustomerOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The signed in customer's orders. Another customer's reference is a 404,
 * like one that does not exist.
 */
class CustomerOrderController extends Controller
{
    private const int PER_PAGE = 10;

    public function index(#[CurrentUser] User $customer): AnonymousResourceCollection
    {
        return OrderResource::collection(
            $customer->orders()->with(['items.product', 'paymentAttempts'])->latest('id')->paginate(self::PER_PAGE),
        );
    }

    public function store(PlaceCustomerOrderRequest $request, #[CurrentUser] User $customer, PlaceOrder $placeOrder): JsonResponse
    {
        $order = $placeOrder->handle($customer, $request->lines());

        return (new OrderResource($order->load(['items.product', 'paymentAttempts'])))->response()->setStatusCode(201);
    }

    public function show(#[CurrentUser] User $customer, string $reference): OrderResource
    {
        return new OrderResource(self::find($customer, $reference));
    }

    public function cancel(#[CurrentUser] User $customer, string $reference, CancelOrder $cancelOrder): OrderResource
    {
        $order = self::find($customer, $reference);

        $cancelOrder->handle($order);

        return new OrderResource($order->refresh()->load(['items.product', 'paymentAttempts']));
    }

    /**
     * The customer's own order by its reference, or a 404.
     */
    public static function find(User $customer, string $reference): Order
    {
        return $customer->orders()
            ->with(['items.product', 'paymentAttempts'])
            ->where('reference', $reference)
            ->firstOrFail();
    }
}
