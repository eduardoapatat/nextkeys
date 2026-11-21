<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed in storefront customer. Never exposes the id or the role.
 *
 * @property User $resource
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array{name: string, email: string, email_verified: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'email_verified' => $this->resource->hasVerifiedEmail(),
        ];
    }
}
