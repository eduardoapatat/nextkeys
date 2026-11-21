<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/** Adds one unit from a listing; the product page has the quantity picker. */
const props = withDefaults(
    defineProps<{
        product: ProductSummary;
        variant?: 'primary' | 'secondary';
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { variant: 'secondary', size: 'sm' },
);

const cart = useCart();
const added = ref(false);

function add() {
    cart.add(props.product.slug, 1);
    added.value = true;
}
</script>

<template>
    <div class="flex flex-col items-start gap-1">
        <UiButton
            :variant="variant"
            :size="size"
            :disabled="!product.in_stock"
            :aria-label="`Agregar ${product.title.name} al carrito`"
            @click="add"
        >
            <UiIcon v-if="added" name="check" :size="18" />
            <UiIcon v-else name="cart" :size="18" />
            {{ product.in_stock ? 'Agregar al carrito' : 'Sin stock' }}
        </UiButton>
        <p v-if="added" role="status" class="text-sm text-chalk-muted">
            Agregado.
            <NuxtLink to="/carrito" class="text-crt underline"
                >Ver carrito</NuxtLink
            >
        </p>
    </div>
</template>
