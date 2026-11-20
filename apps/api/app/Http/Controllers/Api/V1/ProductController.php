<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\Storefront\FindPublishedProduct;
use App\Actions\Catalog\Storefront\ListPublishedProducts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request, ListPublishedProducts $listPublishedProducts): AnonymousResourceCollection
    {
        return ProductResource::collection($listPublishedProducts->handle($request->filters()));
    }

    public function show(string $slug, FindPublishedProduct $findPublishedProduct): ProductDetailResource
    {
        return new ProductDetailResource($findPublishedProduct->handle($slug));
    }
}
