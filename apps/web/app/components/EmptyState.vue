<script setup lang="ts">
/** An empty list with one way forward. */
defineProps<{ message: string }>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-4 rounded-card border border-dashed border-line-strong px-6 py-16 text-center"
    >
        <p class="font-display text-2xl text-chalk uppercase">{{ message }}</p>
        <slot />
    </div>
</template>
