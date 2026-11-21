<?php

namespace App\Payments\Transbank;

use Illuminate\Http\Request;

/**
 * What Webpay sent the customer back with, by GET or POST
 * (docs/domain/payments.md, "Return route"). Only `token_ws` without
 * `TBK_TOKEN` may lead to a commit.
 */
final readonly class WebpayReturn
{
    private function __construct(
        public WebpayReturnKind $kind,
        public ?string $token = null,
        public ?string $buyOrder = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $tokenWs = self::input($request, 'token_ws');
        $tbkToken = self::input($request, 'TBK_TOKEN');
        $buyOrder = self::input($request, 'TBK_ORDEN_COMPRA');

        return match (true) {
            $tbkToken !== null && $buyOrder !== null => new self(WebpayReturnKind::Cancelled, $tbkToken, $buyOrder),
            $tbkToken === null && $tokenWs !== null && preg_match('/^[0-9a-f]{64}$/', $tokenWs) === 1 => new self(WebpayReturnKind::Paid, $tokenWs),
            $tbkToken === null && $tokenWs === null && $buyOrder !== null => new self(WebpayReturnKind::TimedOut, buyOrder: $buyOrder),
            default => new self(WebpayReturnKind::Invalid),
        };
    }

    private static function input(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
