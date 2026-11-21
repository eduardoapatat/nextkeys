<script setup lang="ts">
const customer = useCustomer();
</script>

<template>
    <header>
        <nav aria-label="Principal">
            <NuxtLink to="/">NextKeys</NuxtLink>
            ·
            <NuxtLink v-if="customer" to="/cuenta">{{
                customer.name
            }}</NuxtLink>
            <NuxtLink v-else to="/cuenta/ingresar">Ingresar</NuxtLink>
        </nav>
    </header>
</template>
