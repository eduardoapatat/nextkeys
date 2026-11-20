<script lang="ts">
/**
 * Rows only include the visible columns, so most fields are optional.
 */
export type OrderRow = {
    reference: string;
    customer?: string;
    email?: string;
    status?: string;
    total?: number;
    created_at?: string;
    expires_at?: string;
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import OrderController from '@/actions/App/Http/Controllers/Orders/OrderController';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useServerDataTable } from '@/composables/useServerDataTable';
import type { DataTableFeatures } from '@/lib/data-table-features';
import type { ServerDataTable } from '@/lib/data-table-server';

const props = withDefaults(
    defineProps<{
        orders: ServerDataTable<OrderRow>;
        /** Name of the Inertia prop, so visits only reload this table. */
        prop?: string;
    }>(),
    { prop: 'orders' },
);

const columnHelper = createColumnHelper<DataTableFeatures, OrderRow>();

const columns = columnHelper.columns([
    columnHelper.accessor('reference', { size: 130 }),
    columnHelper.accessor('status', { size: 120 }),
    columnHelper.accessor('total', { size: 120 }),
    columnHelper.display({
        id: 'actions',
        size: 64,
        header: () => h('span', { class: 'sr-only' }, 'Acciones'),
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end' }, [
                h(
                    Button,
                    { variant: 'ghost', size: 'icon', asChild: true },
                    () =>
                        h(
                            Link,
                            {
                                href: OrderController.show(
                                    row.original.reference,
                                ).url,
                                'aria-label': `Ver pedido ${row.original.reference}`,
                            },
                            () => h(Eye, { class: 'size-4' }),
                        ),
                ),
            ]),
    }),
]);

const { table } = useServerDataTable({
    table: () => props.orders,
    prop: props.prop,
    columns,
});
</script>

<template>
    <DataTable :table="table" aria-label="Pedidos">
        <DataTableToolbar :table="table" />
    </DataTable>
</template>
