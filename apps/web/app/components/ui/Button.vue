<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router';

/**
 * The one button of the storefront. `primary` is the yellow price sticker
 * (one per view), `secondary` a melamine key, `ghost` a bare label.
 * With `to` it renders a NuxtLink that looks like a button.
 */
const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'ghost';
        size?: 'sm' | 'md' | 'lg';
        to?: RouteLocationRaw;
        type?: 'button' | 'submit';
        disabled?: boolean;
    }>(),
    { variant: 'secondary', size: 'md', to: undefined, type: 'button' },
);

const classes = computed(() => [
    'pressable inline-flex items-center justify-center gap-2 rounded-control font-display font-extrabold uppercase tracking-wide whitespace-nowrap',
    'transition-[transform,background-color,border-color,color] duration-150 ease-out',
    'disabled:cursor-not-allowed disabled:opacity-50',
    {
        sm: 'h-9 px-3 text-sm',
        md: 'h-11 px-4 text-base',
        lg: 'h-13 px-6 text-lg',
    }[props.size],
    {
        primary:
            'bg-sticker text-sticker-ink hover:bg-sticker-hover shadow-sticker',
        secondary:
            'border border-line-strong bg-panel-2 text-chalk hover:border-chalk-faint hover:bg-panel',
        ghost: 'text-chalk-muted hover:text-chalk',
    }[props.variant],
]);
</script>

<template>
    <NuxtLink v-if="to" :to="to" :class="classes"><slot /></NuxtLink>
    <button v-else :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
