<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\Storefront\ListCatalogFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogFiltersRequest;
use Illuminate\Http\JsonResponse;

class CatalogFilterController extends Controller
{
    public function __invoke(CatalogFiltersRequest $request, ListCatalogFilters $listCatalogFilters): JsonResponse
    {
        return response()->json(['data' => $listCatalogFilters->handle($request->filters())]);
    }
}
