<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ActivationService;
use App\Models\Platform;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A reference entry (platform, region, activation service) as the storefront
 * sees it: its slug and its name.
 *
 * @property-read Platform|Region|ActivationService $resource
 */
class ReferenceResource extends JsonResource
{
    /**
     * @return array{slug: string, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
        ];
    }
}
