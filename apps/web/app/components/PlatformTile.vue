<script setup lang="ts">
/**
 * A platform tile (the console families and Steam), drawn by hand so they read as one set (no store
 * artwork): the family's brand gradient, a faint pattern of its own (the
 * PlayStation shapes, Xbox rings, Joy-Con dots, Steam's diagonal rules), its
 * official symbol and a
 * large ghost of it off the corner.
 */
export type TileFamily = 'playstation' | 'xbox' | 'nintendo' | 'steam';

const props = defineProps<{
    family: TileFamily;
    label: string;
    platforms: string;
}>();

const look = {
    playstation: {
        tone: 'from-tile-playstation to-box-ps4',
        mark: 'playstation',
    },
    xbox: { tone: 'from-tile-xbox to-box-xbox', mark: 'xbox' },
    nintendo: { tone: 'from-tile-nintendo to-box-nintendo', mark: 'switch' },
    steam: { tone: 'from-tile-steam to-tile-steam-deep', mark: 'steam' },
} as const satisfies Record<
    TileFamily,
    { tone: string; mark: PlatformMarkName }
>;

const tone = computed(() => look[props.family].tone);
// The symbol alone: Xbox and Switch also carry a wordmark part.
const symbol = computed(() => platformMarks[look[props.family].mark][0]!);
const patternId = useId();
const patternSize = computed(
    () => ({ nintendo: 14, steam: 12 })[props.family as string] ?? 36,
);
</script>

<template>
    <span
        class="relative isolate flex h-full min-h-40 flex-col justify-between overflow-hidden rounded-card bg-linear-to-br p-4 text-on-cover md:min-h-48 md:p-5"
        :class="tone"
    >
        <svg
            aria-hidden="true"
            class="absolute inset-0 -z-10 size-full text-on-cover opacity-15"
        >
            <template v-if="family === 'xbox'">
                <circle
                    v-for="r in [60, 110, 160, 210, 260, 310]"
                    :key="r"
                    cx="100%"
                    cy="100%"
                    :r="r"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                />
            </template>
            <template v-else>
                <defs>
                    <pattern
                        :id="patternId"
                        :width="patternSize"
                        :height="patternSize"
                        patternUnits="userSpaceOnUse"
                    >
                        <path
                            v-if="family === 'steam'"
                            d="M0 12 12 0"
                            stroke="currentColor"
                            stroke-width="1"
                        />
                        <circle
                            v-else-if="family === 'nintendo'"
                            cx="7"
                            cy="7"
                            r="1.6"
                            fill="currentColor"
                        />
                        <g
                            v-else
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.2"
                        >
                            <path d="M9 3.5 13.5 11h-9z" />
                            <circle cx="27" cy="7.5" r="4" />
                            <path d="m5 23 8 8m0-8-8 8" />
                            <rect x="23" y="23" width="8" height="8" />
                        </g>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" :fill="`url(#${patternId})`" />
            </template>
        </svg>

        <svg
            aria-hidden="true"
            :viewBox="symbol.viewBox"
            class="absolute -right-6 -bottom-8 -z-10 size-40 text-on-cover opacity-15 transition-transform duration-300 ease-out group-hover:scale-105 group-hover:-rotate-3 md:size-48"
            fill="currentColor"
        >
            <path :d="symbol.d" />
        </svg>

        <span class="flex items-start justify-between">
            <svg
                aria-hidden="true"
                :viewBox="symbol.viewBox"
                class="h-9 w-auto md:h-11"
                fill="currentColor"
            >
                <path :d="symbol.d" />
            </svg>
            <UiIcon
                name="arrowRight"
                class="transition-transform duration-200 group-hover:translate-x-1"
            />
        </span>
        <span>
            <span
                class="block font-display text-3xl leading-none font-extrabold uppercase md:text-4xl"
                >{{ label }}</span
            >
            <span class="mt-1 block text-sm font-medium">{{ platforms }}</span>
        </span>
    </span>
</template>
