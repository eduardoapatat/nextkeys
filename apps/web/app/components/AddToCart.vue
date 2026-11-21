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
    <form aria-label="Agregar al carrito" @submit.prevent="add">
        <p v-if="!product.in_stock">Sin stock.</p>
        <template v-else>
            <label for="quantity">Cantidad</label>
            <select id="quantity" v-model.number="quantity">
                <option v-for="n in maxQuantityPerLine" :key="n" :value="n">
                    {{ n }}
                </option>
            </select>
            <button type="submit">Agregar al carrito</button>
            <p v-if="added" role="status">
                Agregado al carrito.
                <NuxtLink to="/carrito">Ver el carrito</NuxtLink>
            </p>
        </template>
    </form>
</template>
