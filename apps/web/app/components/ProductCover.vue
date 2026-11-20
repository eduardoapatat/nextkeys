<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

defineProps<{ product: ProductSummary }>();
</script>

<template>
    <img
        v-if="product.cover_url"
        :src="product.cover_url"
        :alt="`Carátula de ${product.title.name}`"
        loading="lazy"
    />
    <p v-else>Sin carátula</p>
</template>
