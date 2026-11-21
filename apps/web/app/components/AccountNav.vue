<script setup lang="ts">
const customer = useCustomer();
const cart = useCart();
</script>

<template>
    <header>
        <nav aria-label="Principal">
            <NuxtLink to="/">NextKeys</NuxtLink>
            ·
            <NuxtLink to="/carrito">Carrito ({{ cart.count.value }})</NuxtLink>
            ·
            <template v-if="customer">
                <NuxtLink to="/biblioteca">Mi biblioteca</NuxtLink>
                ·
            </template>
            <NuxtLink v-if="customer" to="/cuenta">{{
                customer.name
            }}</NuxtLink>
            <NuxtLink v-else to="/cuenta/ingresar">Ingresar</NuxtLink>
        </nav>
    </header>
</template>
