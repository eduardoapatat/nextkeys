<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

defineProps<{ product: ProductSummary }>();
</script>

<template>
    <dl>
        <dt>Tipo</dt>
        <dd>{{ product.type.name }}</dd>
        <template v-if="product.activation_service">
            <dt>Servicio de activación</dt>
            <dd>{{ product.activation_service.name }}</dd>
        </template>
        <dt>Plataformas</dt>
        <dd>
            {{ product.platforms.map((platform) => platform.name).join(', ') }}
        </dd>
        <template v-if="product.region">
            <dt>Región</dt>
            <dd>{{ product.region.name }}</dd>
        </template>
        <dt>Precio</dt>
        <dd>
            {{ formatClp(product.price) }}
            <template v-if="!product.in_stock"> · Sin stock</template>
        </dd>
    </dl>
</template>
