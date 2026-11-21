<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Actions\Orders\OrderBusy;
use App\Actions\Orders\OrderCannotBePaid;
use App\Actions\Orders\OrderCannotBePaidReason;
use App\Actions\Payments\StartPaymentAttempt;
use App\Enums\PaymentOrigin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Starts a Webpay payment of the customer's order. The storefront posts the
 * token to the returned URL with a plain form (never an iframe).
 */
class CustomerPaymentController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function __invoke(#[CurrentUser] User $customer, string $reference, StartPaymentAttempt $startPaymentAttempt): JsonResponse
    {
        $order = CustomerOrderController::find($customer, $reference);

        try {
            $redirect = $startPaymentAttempt->handle($order, PaymentOrigin::Storefront);
        } catch (OrderCannotBePaid $exception) {
            throw ValidationException::withMessages(['order' => match ($exception->reason) {
                OrderCannotBePaidReason::AlreadyPaid => 'El pedido ya está pagado.',
                OrderCannotBePaidReason::Cancelled => 'El pedido no se puede pagar: está cancelado.',
                OrderCannotBePaidReason::OutOfStock => 'El pedido no se puede pagar: expiró y ya no hay stock para reservarlo.',
            }]);
        } catch (OrderBusy) {
            throw ValidationException::withMessages(['order' => 'El pedido se está procesando. Intenta de nuevo en unos segundos.']);
        } catch (PaymentGatewayException) {
            return response()->json(['message' => 'No pudimos conectar con Webpay. Intenta de nuevo.'], 503);
        }

        return response()->json(['data' => ['url' => $redirect->url, 'token' => $redirect->token]], 201);
    }
}
