<script setup lang="ts">
import type { ProductDetail } from '~/api/schemas';

const props = defineProps<{ product: ProductDetail }>();

const cart = useCart();
const quantity = ref(1);
const added = ref(false);

function add() {
    cart.add(props.product.slug, quantity.value);
    added.value = true;
}
</script>

<template>
    <form
        aria-label="Agregar al carrito"
        class="flex flex-col gap-3"
        @submit.prevent="add"
    >
        <p
            v-if="!product.in_stock"
            class="rounded-control border border-line-strong px-4 py-3 text-fg-muted"
        >
            Sin stock.
        </p>
        <template v-else>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <label for="quantity" class="text-sm text-fg-faint"
                        >Cantidad</label
                    >
                    <select
                        id="quantity"
                        v-model.number="quantity"
                        class="tabular h-13 w-20 rounded-control border border-line-strong bg-surface px-3 text-lg text-fg focus:border-brand focus:outline-none"
                    >
                        <option
                            v-for="n in maxQuantityPerLine"
                            :key="n"
                            :value="n"
                        >
                            {{ n }}
                        </option>
                    </select>
                </div>
                <UiButton type="submit" variant="primary" size="lg">
                    <UiIcon name="cart" />Agregar al carrito
                </UiButton>
            </div>
            <p v-if="added" role="status" class="text-fg-muted">
                <UiIcon name="check" :size="16" class="inline text-ok" />
                Agregado al carrito.
                <NuxtLink to="/carrito" class="text-brand underline"
                    >Ver el carrito</NuxtLink
                >
            </p>
        </template>
    </form>
</template>
