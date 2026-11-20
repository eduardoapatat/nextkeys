<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import OrderController from '@/actions/App/Http/Controllers/Orders/OrderController';
import OrdersTable from '@/components/data-tables/OrdersTable.vue';
import type { OrderRow } from '@/components/data-tables/OrdersTable.vue';
import Heading from '@/components/Heading.vue';
import type { ServerDataTable } from '@/lib/data-table-server';

defineProps<{
    orders: ServerDataTable<OrderRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pedidos', href: OrderController.index() }],
    },
});
</script>

<template>
    <Head title="Pedidos" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <Heading
            title="Pedidos"
            description="Compras de los clientes, con su estado y lo reservado."
        />

        <OrdersTable :orders="orders" />
    </div>
</template>
