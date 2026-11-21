<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/** The spec plate of a product: every fact in its own labeled row. */
defineProps<{ product: ProductSummary }>();
</script>

<template>
    <dl
        class="grid grid-cols-[minmax(0,9rem)_1fr] border-t border-line text-sm"
    >
        <dt class="border-b border-line py-2.5 text-chalk-faint">Tipo</dt>
        <dd class="border-b border-line py-2.5 text-chalk">
            {{ product.type.name }}
        </dd>
        <dt class="border-b border-line py-2.5 text-chalk-faint">
            Plataformas
        </dt>
        <dd class="border-b border-line py-2.5 text-chalk">
            {{ product.platforms.map((platform) => platform.name).join(', ') }}
        </dd>
        <template v-if="product.region">
            <dt class="border-b border-line py-2.5 text-chalk-faint">Región</dt>
            <dd class="border-b border-line py-2.5 text-chalk">
                {{ product.region.name }}
            </dd>
        </template>
        <template v-if="product.activation_service">
            <dt class="border-b border-line py-2.5 text-chalk-faint">
                Servicio de activación
            </dt>
            <dd class="border-b border-line py-2.5 text-chalk">
                {{ product.activation_service.name }}
            </dd>
        </template>
    </dl>
</template>
