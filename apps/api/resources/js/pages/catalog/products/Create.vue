<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ProductForm from '@/components/catalog/ProductForm.vue';
import type { ProductFormOptions } from '@/types';
import Heading from '@/components/Heading.vue';

defineProps<ProductFormOptions>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: ProductController.index() },
            { title: 'Nuevo', href: ProductController.create() },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo producto" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Nuevo producto"
                description="Elige el tipo primero: define qué campos aplican."
            />
        </div>

        <ProductForm :options="$props" />
    </div>
</template>
