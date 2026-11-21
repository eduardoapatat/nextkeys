<script setup lang="ts">
import { fetchOrders } from '~/api/orders';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Mis pedidos' });

const route = useRoute();
const { data: orders } = await useAsyncData('orders', () => fetchOrders());
</script>

<template>
    <main class="mx-auto max-w-4xl px-4 py-8 md:py-10">
        <h1 class="text-5xl text-fg uppercase md:text-6xl">Mis pedidos</h1>

        <p
            v-if="route.query.estado === 'desconocido'"
            role="alert"
            class="mt-6 rounded-card border border-danger/50 bg-danger-deep px-4 py-3 text-fg"
        >
            No pudimos identificar el pago. Si se cobró, el pedido aparece abajo
            como pagado; si no, puedes reintentarlo desde el pedido.
        </p>

        <EmptyState
            v-if="orders && orders.data.length === 0"
            message="Aún no tienes pedidos."
            class="mt-8"
        >
            <UiButton :to="catalogLink()" variant="primary"
                >Ver el catálogo</UiButton
            >
        </EmptyState>
        <OrderList v-else-if="orders" :orders="orders.data" class="mt-8" />
    </main>
</template>
