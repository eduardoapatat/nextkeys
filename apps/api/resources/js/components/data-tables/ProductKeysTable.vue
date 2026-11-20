<script lang="ts">
/**
 * Rows only include the visible columns, so most fields are optional. The
 * code is never part of a row, only its hint (`XXXXX-XXXXX-X7QF2`).
 */
export type ProductKeyRow = {
    id: number;
    code_hint?: string;
    status?: string;
    created_at?: string;
};
</script>

<script setup lang="ts">
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { useServerDataTable } from '@/composables/useServerDataTable';
import type { DataTableFeatures } from '@/lib/data-table-features';
import type { ServerDataTable } from '@/lib/data-table-server';

const props = withDefaults(
    defineProps<{
        keys: ServerDataTable<ProductKeyRow>;
        /** Name of the Inertia prop, so visits only reload this table. */
        prop?: string;
    }>(),
    { prop: 'keys' },
);

const columnHelper = createColumnHelper<DataTableFeatures, ProductKeyRow>();

const columns = columnHelper.columns([
    columnHelper.accessor('code_hint', {
        size: 260,
        cell: ({ cell }) =>
            h('span', { class: 'font-mono text-sm' }, cell.getValue() ?? ''),
    }),
    columnHelper.accessor('status', { size: 140 }),
    columnHelper.accessor('created_at', { size: 200 }),
]);

const { table } = useServerDataTable({
    table: () => props.keys,
    prop: props.prop,
    columns,
});
</script>

<template>
    <DataTable :table="table" aria-label="Claves del producto">
        <DataTableToolbar :table="table" />
    </DataTable>
</template>
