<script setup lang="ts">
/**
 * A thermal receipt printed at the counter: cream paper with torn edges,
 * monospace print. Cart totals, checkout and payment results use it.
 */
withDefaults(defineProps<{ title?: string }>(), { title: undefined });
</script>

<template>
    <div class="receipt px-5 font-mono text-sm sm:px-6">
        <p
            v-if="title"
            class="border-b border-dashed border-paper-line pb-3 text-center text-xs tracking-[0.2em] text-paper-muted uppercase"
        >
            NextKeys · {{ title }}
        </p>
        <slot />
    </div>
</template>
