<?php

namespace App\Http\Requests\Api\V1\Orders;

use App\Actions\Orders\OrderLineData;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * The storefront checkout: cart lines by product slug. Quantities, duplicates
 * and stock are checked by `PlaceOrder`.
 */
class PlaceCustomerOrderRequest extends FormRequest
{
    /**
     * The keys arrive by email: a customer pays only with a verified email.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasVerifiedEmail();
    }

    /**
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): never
    {
        throw new AuthorizationException('Verifica tu correo antes de pagar.');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:10'],
            'items.*.slug' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer'],
            'shipping_address' => ['nullable', 'string'],
        ];
    }

    /**
     * The lines with each slug resolved to a published product.
     *
     * @return list<OrderLineData>
     *
     * @throws ValidationException when a slug is not on sale
     */
    public function lines(): array
    {
        /** @var list<array{slug: string, quantity: int}> $items */
        $items = array_values($this->array('items'));
        /** @var array<string, int> $productIds */
        $productIds = Product::query()
            ->published()
            ->whereIn('slug', array_column($items, 'slug'))
            ->pluck('id', 'slug')
            ->all();

        $errors = [];

        foreach ($items as $index => $item) {
            if (! isset($productIds[$item['slug']])) {
                $errors["items.{$index}.slug"] = 'Este producto ya no está disponible.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return array_map(
            fn (array $item): OrderLineData => new OrderLineData($productIds[$item['slug']], (int) $item['quantity']),
            $items,
        );
    }

    /**
     * The customer's saved address by its `public_id`, or null when none was
     * sent. `PlaceOrder` decides whether the order needs one.
     *
     * @throws ValidationException when it is not one of the customer's addresses
     */
    public function shippingAddress(User $customer): ?CustomerAddress
    {
        if (! $this->filled('shipping_address')) {
            return null;
        }

        return $customer->addresses()->where('public_id', $this->string('shipping_address')->toString())->first()
            ?? throw ValidationException::withMessages(['shipping_address' => 'Esta dirección no existe.']);
    }
}
