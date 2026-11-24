<script setup lang="ts">
/**
 * A status as a small tinted label: `ok` for done, `brand` for waiting on
 * someone, `muted` for closed.
 */
withDefaults(defineProps<{ tone?: 'ok' | 'brand' | 'muted' }>(), {
    tone: 'muted',
});
</script>

<template>
    <span
        class="rounded-control px-2 py-0.5 text-sm whitespace-nowrap"
        :class="{
            'bg-ok-soft text-ok': tone === 'ok',
            'bg-brand-soft text-brand': tone === 'brand',
            'bg-surface-2 text-fg-muted': tone === 'muted',
        }"
        ><slot
    /></span>
</template>
