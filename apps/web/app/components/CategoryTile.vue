<script setup lang="ts">
/**
 * A home category tile, drawn by hand in SVG so all of them read as one set
 * (no store artwork): a gradient, a faint pattern of its own, a symbol at the
 * top left and a large ghost of it off the bottom right corner.
 *
 * - Platforms (PlayStation, Xbox, Nintendo, Steam): the brand's color and
 *   official symbol; patterns of the PlayStation shapes, rings, Joy-Con dots
 *   and diagonal rules.
 * - Product types: the store's own colors and drawn symbols: a key over
 *   key codes, the Windows panes (no pattern), a game case over discs.
 * - The `art` slot replaces the ghost with a picture of its own (the
 *   licenses' desktop, the physical games' covers), which grows a little
 *   from the bottom right corner on hover.
 */
export type TileKind =
    | 'playstation'
    | 'xbox'
    | 'nintendo'
    | 'steam'
    | 'digital_key'
    | 'license'
    | 'physical';

const props = withDefaults(
    defineProps<{
        kind: TileKind;
        label: string;
        note: string;
        size?: 'md' | 'lg';
        // Where the `art` slot sits: beside the text (bottom right, the text
        // narrows) or above it (top right, next to the arrow).
        art?: 'beside' | 'above';
    }>(),
    { size: 'lg', art: 'beside' },
);

interface Symbol {
    viewBox: string;
    d: string;
    // Drawn symbols are outlines; official marks are filled.
    stroke?: boolean;
}

const drawn = (d: string): Symbol => ({
    viewBox: '0 0 24 24',
    d,
    stroke: true,
});
// The symbol alone: Xbox and Switch also carry a wordmark part.
const mark = (name: PlatformMarkName): Symbol => platformMarks[name][0]!;

const look: Record<TileKind, { tone: string; symbol: Symbol }> = {
    playstation: {
        tone: 'from-tile-playstation to-box-ps4',
        symbol: mark('playstation'),
    },
    xbox: { tone: 'from-tile-xbox to-box-xbox', symbol: mark('xbox') },
    nintendo: {
        tone: 'from-tile-nintendo to-box-nintendo',
        symbol: mark('switch'),
    },
    steam: {
        tone: 'from-tile-steam to-tile-steam-deep',
        symbol: mark('steam'),
    },
    digital_key: {
        tone: 'from-tile-key to-accent',
        symbol: drawn(
            'M14.5 14a5.5 5.5 0 1 0-4.6-2.5L3 18.4V21h3v-2h2v-2h2l1.9-1.9A5.5 5.5 0 0 0 14.5 14ZM16 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z',
        ),
    },
    license: {
        tone: 'from-tile-license to-tile-license-deep',
        symbol: mark('windows'),
    },
    physical: {
        tone: 'from-tile-physical to-cover-5',
        symbol: drawn(
            'M5 2.5h14v19H5zM8 2.5v19M13.5 8a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM13.5 11.2a.8.8 0 1 1 0 1.6.8.8 0 0 1 0-1.6Z',
        ),
    },
};

const current = computed(() => look[props.kind]);
const patternId = useId();
const patternSize: Partial<Record<TileKind, number>> = {
    nintendo: 14,
    steam: 12,
    digital_key: 40,
    physical: 28,
};
</script>

<template>
    <span
        class="relative isolate flex h-full flex-col justify-between overflow-hidden rounded-card bg-linear-to-br p-4 text-on-cover md:p-5"
        :class="[
            current.tone,
            size === 'lg' ? 'min-h-40 md:min-h-48' : 'min-h-32 md:min-h-36',
        ]"
    >
        <svg
            aria-hidden="true"
            class="absolute inset-0 -z-10 size-full text-on-cover opacity-15"
        >
            <template v-if="kind === 'xbox'">
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
            <!-- The licenses' desktop picture is busy enough on its own. -->
            <template v-else-if="kind !== 'license'">
                <defs>
                    <pattern
                        :id="patternId"
                        :width="patternSize[kind] ?? 36"
                        :height="
                            patternSize[kind] === 40
                                ? 14
                                : (patternSize[kind] ?? 36)
                        "
                        patternUnits="userSpaceOnUse"
                    >
                        <path
                            v-if="kind === 'steam'"
                            d="M0 12 12 0"
                            stroke="currentColor"
                        />
                        <circle
                            v-else-if="kind === 'nintendo'"
                            cx="7"
                            cy="7"
                            r="1.6"
                            fill="currentColor"
                        />
                        <text
                            v-else-if="kind === 'digital_key'"
                            x="1"
                            y="10"
                            fill="currentColor"
                            font-family="JetBrains Mono, monospace"
                            font-size="8"
                        >
                            7KX2-
                        </text>
                        <g
                            v-else-if="kind === 'physical'"
                            fill="none"
                            stroke="currentColor"
                        >
                            <circle cx="14" cy="14" r="9" />
                            <circle cx="14" cy="14" r="2.5" />
                        </g>
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

        <span
            v-if="$slots.art"
            aria-hidden="true"
            class="absolute right-0 -z-10 transition-transform duration-300 ease-out group-hover:scale-105"
            :class="
                art === 'beside'
                    ? 'bottom-0 h-[78%] w-2/5 origin-bottom-right'
                    : 'top-0 h-1/2 w-3/5 origin-top-right'
            "
        >
            <slot name="art" />
        </span>
        <template v-else>
            <svg
                aria-hidden="true"
                :viewBox="current.symbol.viewBox"
                class="absolute -right-6 -bottom-8 -z-10 origin-bottom-right text-on-cover opacity-15 transition-transform duration-300 ease-out group-hover:scale-110"
                :class="
                    size === 'lg' ? 'size-40 md:size-48' : 'size-36 md:size-40'
                "
                v-bind="
                    current.symbol.stroke
                        ? {
                              fill: 'none',
                              stroke: 'currentColor',
                              'stroke-width': 1.5,
                          }
                        : { fill: 'currentColor' }
                "
            >
                <path :d="current.symbol.d" />
            </svg>
        </template>

        <span class="flex items-start justify-between">
            <svg
                aria-hidden="true"
                :viewBox="current.symbol.viewBox"
                class="h-9 w-auto md:h-11"
                v-bind="
                    current.symbol.stroke
                        ? {
                              fill: 'none',
                              stroke: 'currentColor',
                              'stroke-width': 1.75,
                          }
                        : { fill: 'currentColor' }
                "
            >
                <path :d="current.symbol.d" />
            </svg>
            <UiIcon name="arrowRight" />
        </span>
        <span :class="{ 'max-w-[58%]': $slots.art && art === 'beside' }">
            <span
                class="block font-display leading-none font-extrabold uppercase"
                :class="
                    size === 'lg'
                        ? 'text-3xl md:text-4xl'
                        : 'text-2xl md:text-3xl'
                "
                >{{ label }}</span
            >
            <span class="mt-1 block text-sm font-medium">{{ note }}</span>
        </span>
    </span>
</template>
