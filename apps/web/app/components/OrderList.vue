<script setup lang="ts">
import type { Order } from '~/api/schemas';

defineProps<{ orders: Order[] }>();
</script>

<template>
    <table>
        <thead>
            <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Fecha</th>
                <th scope="col">Estado</th>
                <th scope="col">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="order in orders" :key="order.reference">
                <td>
                    <NuxtLink :to="`/pedidos/${order.reference}`">
                        {{ order.reference }}
                    </NuxtLink>
                </td>
                <td>{{ formatDateTime(order.created_at) }}</td>
                <td>{{ order.status.name }}</td>
                <td>{{ formatClp(order.total) }}</td>
            </tr>
        </tbody>
    </table>
</template>
