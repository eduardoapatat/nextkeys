<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * "Programas": the licenses (Windows, Office, antivirus), apart from the
 * games, on the same product cards.
 */
defineProps<{ products: ProductSummary[] }>();
</script>

<template>
    <section aria-labelledby="programs-heading">
        <div
            class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-3"
        >
            <div>
                <h2
                    id="programs-heading"
                    class="text-4xl text-fg uppercase md:text-5xl"
                >
                    Programas
                </h2>
                <p class="mt-1 text-fg-muted">
                    Windows, Office y antivirus, con su clave de activación.
                </p>
            </div>
            <UiButton
                :to="catalogLink({ type: ['license'] })"
                variant="ghost"
                size="sm"
                >Ver todas las licencias<UiIcon name="arrowRight" :size="16"
            /></UiButton>
        </div>
        <ul
            class="mt-6 grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-3 lg:grid-cols-4"
        >
            <li v-for="product in products" :key="product.slug">
                <ProductCard :product="product" />
            </li>
        </ul>
    </section>
</template>
