<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ChileRegion;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * What the storefront needs to ship: the flat fee and the regions with their
 * communes, for the address form (docs/domain/shipping.md).
 */
class ShippingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [
            'cost' => config()->integer('orders.shipping_cost'),
            'regions' => array_map(fn (ChileRegion $region): array => [
                'code' => $region->value,
                'name' => $region->label(),
                'communes' => $region->communes(),
            ], ChileRegion::cases()),
        ]]);
    }
}
