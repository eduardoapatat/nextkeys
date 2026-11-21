<script setup lang="ts">
/**
 * The yellow price sticker stuck on a box. The price always comes from the
 * API (CLP); there are no invented "before" prices.
 */
withDefaults(
    defineProps<{ price: number; inStock?: boolean; size?: 'sm' | 'lg' }>(),
    { inStock: true, size: 'sm' },
);
</script>

<template>
    <p
        class="inline-flex flex-col items-start rounded-control font-display leading-none font-extrabold shadow-tag"
        :class="[
            inStock ? 'bg-accent text-accent-fg' : 'bg-surface-2 text-fg-muted',
            size === 'lg'
                ? '-rotate-2 px-3 py-2 text-3xl'
                : '-rotate-3 px-2 py-1 text-lg',
        ]"
    >
        <data :value="price" class="tabular">{{ formatClp(price) }}</data>
        <span
            v-if="!inStock"
            class="mt-0.5 text-xs font-medium tracking-wide uppercase"
            >Sin stock</span
        >
    </p>
</template>
