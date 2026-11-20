<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ProductsTable from '@/components/data-tables/ProductsTable.vue';
import type { Product } from '@/components/data-tables/ProductsTable.vue';
import type { ServerDataTable } from '@/lib/data-table-server';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    products: ServerDataTable<Product>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Productos', href: ProductController.index() }],
    },
});
</script>

<template>
    <Head title="Productos" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Productos"
                description="Lo que se vende: cada forma de vender un título."
            />
            <Button as-child>
                <Link :href="ProductController.create()">Nuevo producto</Link>
            </Button>
        </div>

        <ProductsTable :products="products" />
    </div>
</template>
