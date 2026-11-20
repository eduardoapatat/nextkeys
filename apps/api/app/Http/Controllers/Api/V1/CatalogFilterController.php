<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\Storefront\ListCatalogFilters;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CatalogFilterController extends Controller
{
    public function __invoke(ListCatalogFilters $listCatalogFilters): JsonResponse
    {
        return response()->json(['data' => $listCatalogFilters->handle()]);
    }
}
