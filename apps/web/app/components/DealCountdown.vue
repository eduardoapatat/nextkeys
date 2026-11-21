<script setup lang="ts">
/**
 * Time left until midnight in Santiago. It only ticks in the browser: the
 * server renders a stable placeholder, so hydration never disagrees.
 */
const seconds = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    const tick = () => {
        seconds.value = secondsToSantiagoMidnight(new Date());
    };
    tick();
    timer = setInterval(tick, 1000);
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <span class="tabular">
        <span class="sr-only">Termina en </span>
        <time v-if="seconds !== null" :datetime="`PT${seconds}S`">{{
            formatCountdown(seconds)
        }}</time>
        <span v-else aria-hidden="true">--:--:--</span>
    </span>
</template>
