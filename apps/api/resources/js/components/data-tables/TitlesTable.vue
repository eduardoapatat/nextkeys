<script lang="ts">
/**
 * Rows only include the visible columns, so most fields are optional.
 */
export type Title = {
    id: number;
    name?: string;
    description?: string;
    products_count?: number;
};
</script>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';
import TitleController from '@/actions/App/Http/Controllers/Catalog/TitleController';
import ConfirmDeleteButton from '@/components/catalog/ConfirmDeleteButton.vue';
import { DataTable, DataTableToolbar } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { useServerDataTable } from '@/composables/useServerDataTable';
import type { DataTableFeatures } from '@/lib/data-table-features';
import type { ServerDataTable } from '@/lib/data-table-server';

const props = withDefaults(
    defineProps<{
        titles: ServerDataTable<Title>;
        /** Name of the Inertia prop, so visits only reload this table. */
        prop?: string;
    }>(),
    { prop: 'titles' },
);

const columnHelper = createColumnHelper<DataTableFeatures, Title>();

const columns = columnHelper.columns([
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
                                href: TitleController.edit(row.original.id).url,
                                'aria-label': `Editar ${row.original.name ?? 'título'}`,
                            },
                            () => h(Pencil, { class: 'size-4' }),
                        ),
                ),
                h(ConfirmDeleteButton, {
                    action: TitleController.destroy(row.original.id),
                    subject: `el título ${row.original.name ?? ''}`.trim(),
                }),
            ]),
    }),
]);

const { table } = useServerDataTable({
    table: () => props.titles,
    prop: props.prop,
    columns,
});
</script>

<template>
    <DataTable :table="table" aria-label="Títulos">
        <DataTableToolbar :table="table" />
    </DataTable>
</template>
