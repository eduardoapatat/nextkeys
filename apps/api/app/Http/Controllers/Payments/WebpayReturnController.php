<?php

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\CancelPaymentAttempt;
use App\Actions\Payments\ConfirmPaymentAttempt;
use App\Enums\PaymentOrigin;
use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Payments\Transbank\WebpayReturn;
use App\Payments\Transbank\WebpayReturnKind;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Where Webpay sends the customer back. It commits in this same request
 * (the commit window is under a minute) and then redirects to the result
 * page; nothing is delivered on the browser's word alone.
 */
class WebpayReturnController extends Controller
{
    public function __invoke(Request $request, ConfirmPaymentAttempt $confirmPaymentAttempt, CancelPaymentAttempt $cancelPaymentAttempt): RedirectResponse
    {
        $return = WebpayReturn::fromRequest($request);

        $attempt = match ($return->kind) {
            WebpayReturnKind::Paid => PaymentAttempt::query()->where('token', $return->token)->first(),
            WebpayReturnKind::Cancelled => $cancelPaymentAttempt->handle((string) $return->token, (string) $return->buyOrder),
            WebpayReturnKind::TimedOut => PaymentAttempt::query()->where('buy_order', $return->buyOrder)->first(),
            WebpayReturnKind::Invalid => null,
        };

        if ($attempt === null) {
            return redirect()->away(Config::string('payments.result_url').'?estado=desconocido');
        }

        $status = $return->kind === WebpayReturnKind::Paid
            ? $confirmPaymentAttempt->handle($attempt)
            : $attempt->status;

        if ($attempt->origin === PaymentOrigin::Panel) {
            return to_route('orders.show', $attempt->order);
        }

        return redirect()->away(Config::string('payments.result_url')."/{$attempt->order->reference}?estado={$status->value}");
    }
}
