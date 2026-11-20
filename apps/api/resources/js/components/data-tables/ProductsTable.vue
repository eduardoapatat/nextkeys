<script lang="ts">
/**
 * Rows only include the visible columns, so most fields are optional.
 */
export type Product = {
    id: number;
    title?: string;
    type?: string;
    activation_service?: string | null;
    region?: string | null;
    platforms?: string[];
    price?: number;
    available_stock?: number;
    is_published?: boolean;
    slug?: string;
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ConfirmDeleteButton from '@/components/catalog/ConfirmDeleteButton.vue';
import {
    DataTable,
    DataTableNumberCell,
    DataTableToolbar,
} from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useServerDataTable } from '@/composables/useServerDataTable';
import type { DataTableFeatures } from '@/lib/data-table-features';
import type { ServerDataTable } from '@/lib/data-table-server';

const props = withDefaults(
    defineProps<{
        products: ServerDataTable<Product>;
        /** Name of the Inertia prop, so visits only reload this table. */
        prop?: string;
    }>(),
    { prop: 'products' },
);

const columnHelper = createColumnHelper<DataTableFeatures, Product>();

const columns = columnHelper.columns([
    columnHelper.accessor('type', { size: 120 }),
    columnHelper.accessor('region', { size: 130 }),
    columnHelper.accessor('platforms', { size: 200 }),
    columnHelper.accessor('price', { size: 110 }),
    columnHelper.accessor('is_published', { size: 110 }),
    columnHelper.accessor('available_stock', {
        size: 100,
        cell: ({ cell }) =>
            cell.getValue() === 0
                ? h('span', { class: 'text-destructive' }, 'Sin stock')
                : h(DataTableNumberCell, { cell }),
    }),
    columnHelper.display({
        id: 'actions',
        size: 96,
        header: () => h('span', { class: 'sr-only' }, 'Acciones'),
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end gap-1' }, [
                h(
                    Button,
                    { variant: 'ghost', size: 'icon', asChild: true },
                    () =>
                        h(
                            Link,
                            {
                                href: ProductController.edit(row.original.id)
                                    .url,
                                'aria-label': `Editar ${row.original.slug ?? 'producto'}`,
                            },
                            () => h(Pencil, { class: 'size-4' }),
                        ),
                ),
                h(ConfirmDeleteButton, {
                    action: ProductController.destroy(row.original.id),
                    subject: `el producto ${row.original.slug ?? ''}`.trim(),
                }),
            ]),
    }),
]);

const { table } = useServerDataTable({
    table: () => props.products,
    prop: props.prop,
    columns,
});
</script>

<template>
    <DataTable :table="table" aria-label="Productos">
        <DataTableToolbar :table="table" />
    </DataTable>
</template>
