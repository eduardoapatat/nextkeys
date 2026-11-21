<script setup lang="ts">
import { fetchOrders } from '~/api/orders';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Mis pedidos' });

const route = useRoute();
const { data: orders } = await useAsyncData('orders', () => fetchOrders());
</script>

<template>
    <main>
        <h1>Mis pedidos</h1>

        <p v-if="route.query.estado === 'desconocido'" role="alert">
            No pudimos identificar el pago. Si se cobró, el pedido aparece abajo
            como pagado; si no, puedes reintentarlo desde el pedido.
        </p>

        <p v-if="orders && orders.data.length === 0">
            Aún no tienes pedidos. <NuxtLink to="/">Ver el catálogo</NuxtLink>
        </p>
        <OrderList v-else-if="orders" :orders="orders.data" />
    </main>
</template>
