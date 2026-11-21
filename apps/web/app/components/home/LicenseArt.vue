<script setup lang="ts">
/**
 * The licenses tile's picture: the kinds of programs the store sells as app
 * icons drawn by hand (a document, a spreadsheet, an antivirus shield and a
 * cloud subscription). Generic glyphs, no product logos. Fixed colors
 * (box-*, accent, tile-*) since the band redefines the role tokens.
 */
const apps = [
    {
        label: 'document',
        tone: 'fill-box-ps4',
        d: 'M5 2h10l5 5v15H5zM15 2v5h5',
        detail: 'M8 11h9M8 14h9M8 17h6',
    },
    {
        label: 'spreadsheet',
        tone: 'fill-box-xbox',
        d: 'M3 4h18v16H3z',
        detail: 'M3 9h18M3 14.5h18M9 4v16M15 4v16',
    },
    {
        label: 'antivirus',
        tone: 'fill-accent',
        d: 'M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5z',
        detail: 'M8.5 12.5l2.5 2.5 4.5-5',
    },
    {
        label: 'cloud',
        tone: 'fill-tile-license',
        d: 'M7 19a5 5 0 0 1-.6-10A6.5 6.5 0 0 1 19 9.5a4.8 4.8 0 0 1-.5 9.5z',
        detail: 'M12 16v-5m-2.5 2.5L12 11l2.5 2.5',
    },
] as const;
</script>

<template>
    <span class="absolute top-4 right-11 flex gap-2 md:top-5 md:right-13">
        <span
            v-for="(app, index) in apps"
            :key="app.label"
            class="grid size-9 place-items-center rounded-control bg-on-cover shadow-case md:size-10"
            :class="index % 2 ? 'translate-y-1.5' : ''"
        >
            <svg viewBox="0 0 24 24" class="size-6 md:size-7">
                <path :d="app.d" :class="app.tone" />
                <path
                    :d="app.detail"
                    fill="none"
                    class="stroke-on-cover"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>
    </span>
</template>
