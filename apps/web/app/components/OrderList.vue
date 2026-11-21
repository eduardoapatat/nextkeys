<script setup lang="ts">
import type { Order } from '~/api/schemas';

defineProps<{ orders: Order[] }>();
</script>

<template>
    <table class="w-full text-left">
        <thead
            class="font-display text-sm tracking-wider text-chalk-faint uppercase"
        >
            <tr class="border-b border-line">
                <th scope="col" class="py-2 pr-4 font-bold">Pedido</th>
                <th
                    scope="col"
                    class="hidden py-2 pr-4 font-bold sm:table-cell"
                >
                    Fecha
                </th>
                <th scope="col" class="py-2 pr-4 font-bold">Estado</th>
                <th scope="col" class="py-2 text-right font-bold">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="order in orders"
                :key="order.reference"
                class="border-b border-line"
            >
                <td class="py-3 pr-4">
                    <NuxtLink
                        :to="`/pedidos/${order.reference}`"
                        class="font-mono text-crt hover:text-chalk"
                    >
                        {{ order.reference }}
                    </NuxtLink>
                </td>
                <td
                    class="tabular hidden py-3 pr-4 text-chalk-muted sm:table-cell"
                >
                    {{ formatDateTime(order.created_at) }}
                </td>
                <td class="py-3 pr-4">
                    <span
                        class="rounded-control px-2 py-0.5 text-sm"
                        :class="
                            ['paid', 'completed'].includes(order.status.slug)
                                ? 'bg-ok-deep text-ok'
                                : order.status.slug === 'pending'
                                  ? 'bg-crt-deep text-crt'
                                  : 'bg-panel-2 text-chalk-muted'
                        "
                        >{{ order.status.name }}</span
                    >
                </td>
                <td class="tabular py-3 text-right text-chalk">
                    {{ formatClp(order.total) }}
                </td>
            </tr>
        </tbody>
    </table>
</template>
