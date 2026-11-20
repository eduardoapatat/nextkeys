<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/Catalog/ProductController';
import ProductKeyController from '@/actions/App/Http/Controllers/Catalog/ProductKeyController';
import ProductForm from '@/components/catalog/ProductForm.vue';
import type { ProductFormOptions, ProductFormValues } from '@/types';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<
    ProductFormOptions & {
        product: ProductFormValues;
    }
>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Productos', href: ProductController.index() }],
    },
});
</script>

<template>
    <Head title="Editar producto" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Editar producto" />
            <Button
                v-if="props.product.type !== 'physical'"
                variant="outline"
                as-child
            >
                <Link :href="ProductKeyController.index(props.product.id)">
                    Gestionar claves
                </Link>
            </Button>
        </div>

        <ProductForm :options="props" :product="props.product" />
    </div>
</template>
