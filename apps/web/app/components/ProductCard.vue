<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * A product in a listing, like a key store's card: the cover with its
 * platform band, the title, type and region, where it is activated, and at
 * the foot the price with "Agregar". The title links the whole card; the
 * add button sits above that link.
 */
withDefaults(
    defineProps<{
        product: ProductSummary;
        headingLevel?: 'h2' | 'h3';
        eager?: boolean;
    }>(),
    { headingLevel: 'h3', eager: false },
);
</script>

<template>
    <article class="group relative flex h-full flex-col gap-3">
        <CoverTilt>
            <ProductCover :product="product" :eager="eager" />
        </CoverTilt>

        <div class="flex flex-1 flex-col gap-1">
            <component
                :is="headingLevel"
                class="font-sans text-base leading-snug font-semibold tracking-normal normal-case"
            >
                <NuxtLink
                    :to="`/${product.slug}`"
                    class="text-fg after:absolute after:inset-0 after:rounded-card hover:text-brand focus-visible:outline-none after:focus-visible:outline-2 after:focus-visible:outline-offset-4 after:focus-visible:outline-brand"
                >
                    {{ product.title.name }}
                </NuxtLink>
            </component>
            <p class="text-sm text-fg-muted">
                {{ product.type.name }}
                <template v-if="product.region">
                    · {{ product.region.name }}</template
                >
            </p>
            <p
                v-if="product.activation_service"
                class="truncate text-sm text-fg-faint"
            >
                Se activa en {{ product.activation_service.name }}
            </p>

            <div
                class="relative z-10 mt-auto flex items-center justify-between gap-3 pt-2"
            >
                <p
                    class="font-display text-2xl leading-none font-extrabold text-fg"
                >
                    <data :value="product.price" class="tabular">{{
                        formatClp(product.price)
                    }}</data>
                    <span
                        v-if="!product.in_stock"
                        class="mt-1 block font-sans text-xs font-medium text-fg-faint"
                        >Sin stock</span
                    >
                </p>
                <QuickAdd :product="product" variant="primary" compact />
            </div>
        </div>
    </article>
</template>
