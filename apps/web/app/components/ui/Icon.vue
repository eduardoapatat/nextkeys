<script setup lang="ts">
/**
 * The storefront's icon set: authored on a 24px grid, one 1.75 stroke,
 * square caps. Decorative by default (aria-hidden); give the parent the label.
 */
const paths = {
    search: 'M10.5 17a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13ZM15.25 15.25 20 20',
    cart: 'M3 4h2.2l2.1 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.76L20 8H6.1M9.5 19.5h.01M17 19.5h.01',
    user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0',
    menu: 'M4 7h16M4 12h16M4 17h16',
    close: 'M6 6l12 12M18 6 6 18',
    bolt: 'M13 3 5 13.5h6L10 21l8-10.5h-6L13 3Z',
    lock: 'M6.5 11h11v9h-11zM8.5 11V8a3.5 3.5 0 0 1 7 0v3',
    globe: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM3.5 9h17M3.5 15h17M12 3c2.4 2.5 3.5 5.5 3.5 9s-1.1 6.5-3.5 9c-2.4-2.5-3.5-5.5-3.5-9S9.6 5.5 12 3Z',
    key: 'M14.5 13.5a5 5 0 1 0-4.2-2.3L3 18.5V21h3v-2h2v-2h2l1.8-1.8a5 5 0 0 0 2.7.3ZM16.5 7.5h.01',
    receipt:
        'M6 3h12v18l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5L6 21V3ZM9 8h6M9 12h6M9 16h3',
    chevronRight: 'M9 5l7 7-7 7',
    chevronLeft: 'M15 5l-7 7 7 7',
    arrowRight: 'M4 12h15M13 6l6 6-6 6',
    pause: 'M8 5v14M16 5v14',
    play: 'M7 4.5v15L19 12 7 4.5Z',
    check: 'M4.5 12.5 9.5 17.5 19.5 6.5',
    clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM12 7v5l3.5 2',
    filter: 'M4 6h16M7 12h10M10 18h4',
    library: 'M4 4h4v16H4zM10 4h4v16h-4zM16.2 5.2l3.8-1 3.9 15.5-3.8 1z',
    mail: 'M3.5 6h17v12h-17zM3.5 6.5 12 13l8.5-6.5',
    shield: 'M12 3 4.5 6v5.5c0 4.6 3.1 8.2 7.5 9.5 4.4-1.3 7.5-4.9 7.5-9.5V6L12 3ZM8.5 12l2.5 2.5 4.5-5',
} as const;

export type IconName = keyof typeof paths;

withDefaults(defineProps<{ name: IconName; size?: number }>(), { size: 20 });
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="square"
        stroke-linejoin="miter"
        aria-hidden="true"
        focusable="false"
        class="shrink-0"
    >
        <path :d="paths[name]" />
    </svg>
</template>
