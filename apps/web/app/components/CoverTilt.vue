<script setup lang="ts">
/**
 * Tilts a cover toward the pointer like a box picked up from the shelf, with
 * a glare that follows it. Only on fine pointers that can hover and without
 * `prefers-reduced-motion`; otherwise the cover only brightens on hover.
 * The transform is written straight onto the element (no inherited CSS
 * variables), so only this element recalculates.
 */
const props = withDefaults(defineProps<{ max?: number }>(), { max: 7 });

const card = ref<HTMLElement | null>(null);
const glare = ref<HTMLElement | null>(null);
let enabled = false;
let frame = 0;

onMounted(() => {
    enabled =
        window.matchMedia('(hover: hover) and (pointer: fine)').matches &&
        !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
});

onBeforeUnmount(() => cancelAnimationFrame(frame));

function onMove(event: PointerEvent) {
    if (!enabled || !card.value) return;
    const target = card.value;
    const x = event.clientX;
    const y = event.clientY;
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
        const rect = target.getBoundingClientRect();
        const px = (x - rect.left) / rect.width - 0.5;
        const py = (y - rect.top) / rect.height - 0.5;
        target.style.transition = 'transform 120ms var(--ease-out)';
        target.style.transform = `perspective(900px) rotateX(${(-py * props.max).toFixed(2)}deg) rotateY(${(px * props.max).toFixed(2)}deg) translateY(-4px)`;
        if (glare.value) {
            glare.value.style.transform = `translate(${(px * 60).toFixed(1)}%, ${(py * 60).toFixed(1)}%)`;
        }
    });
}

function onLeave() {
    cancelAnimationFrame(frame);
    if (!card.value) return;
    card.value.style.transition = 'transform 260ms var(--ease-out)';
    card.value.style.transform = '';
    if (glare.value) glare.value.style.transform = '';
}
</script>

<template>
    <div
        ref="card"
        class="group/tilt relative rounded-card shadow-case transition-shadow duration-200 will-change-transform hover:shadow-lift"
        @pointermove="onMove"
        @pointerleave="onLeave"
    >
        <slot />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 overflow-hidden rounded-card"
        >
            <div
                ref="glare"
                class="cover-glare absolute -inset-1/2 opacity-0 transition-opacity duration-200 group-hover/tilt:opacity-100"
            />
        </div>
    </div>
</template>
