<script setup lang="ts">
/**
 * An official platform mark in one ink (currentColor), as tall as the text
 * around it. Some marks come in two parts (symbol and wordmark) so they sit
 * side by side like the horizontal lockup on a retail box.
 */
const props = defineProps<{ name: PlatformMarkName }>();

const parts = computed(() => platformMarks[props.name]);

function width(viewBox: string): number {
    const [, , w, h] = viewBox.split(' ').map(Number);
    return w! / h!;
}
</script>

<template>
    <span class="inline-flex h-full items-center gap-[0.3em]">
        <svg
            v-for="(part, index) in parts"
            :key="index"
            :viewBox="part.viewBox"
            :style="{ aspectRatio: width(part.viewBox) }"
            class="h-full w-auto overflow-hidden"
            fill="currentColor"
            aria-hidden="true"
        >
            <path :d="part.d" />
        </svg>
    </span>
</template>
