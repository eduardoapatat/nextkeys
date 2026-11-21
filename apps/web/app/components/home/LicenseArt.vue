<script setup lang="ts">
/**
 * The licenses tile's picture, drawn by hand: a desktop with a spreadsheet,
 * a slide and a document open in front, the way a program license ends up
 * being used. No product logos: the windows are generic. Fixed colors
 * (box-*, board-*) since the band redefines the role tokens.
 */
</script>

<template>
    <svg
        viewBox="0 0 220 150"
        preserveAspectRatio="xMaxYMax meet"
        class="absolute right-2 bottom-0 size-full drop-shadow-lg"
    >
        <!-- Spreadsheet, at the back. -->
        <g transform="translate(96 6)">
            <rect width="120" height="84" rx="5" class="fill-on-cover" />
            <rect width="120" height="14" rx="5" class="fill-box-xbox" />
            <rect y="9" width="120" height="5" class="fill-box-xbox" />
            <g class="stroke-board-line" stroke-width="1">
                <path
                    d="M8 30h104M8 42h104M8 54h104M8 66h104M38 22v54M68 22v54M98 22v54"
                />
            </g>
            <rect
                x="39"
                y="43"
                width="29"
                height="11"
                class="fill-box-xbox opacity-25"
            />
        </g>

        <!-- Slide, in the middle. -->
        <g transform="translate(50 30)">
            <rect width="112" height="78" rx="5" class="fill-on-cover" />
            <rect width="112" height="14" rx="5" class="fill-accent" />
            <rect y="9" width="112" height="5" class="fill-accent" />
            <rect
                x="12"
                y="24"
                width="56"
                height="8"
                rx="2"
                class="fill-board-muted"
            />
            <circle cx="86" cy="50" r="15" class="fill-accent opacity-80" />
            <path d="M86 50V35a15 15 0 0 1 15 15Z" class="fill-tile-steam" />
            <rect
                x="12"
                y="42"
                width="44"
                height="5"
                rx="2"
                class="fill-board-line"
            />
            <rect
                x="12"
                y="52"
                width="36"
                height="5"
                rx="2"
                class="fill-board-line"
            />
        </g>

        <!-- Document, in front. -->
        <g transform="translate(6 56)">
            <rect width="104" height="94" rx="5" class="fill-on-cover" />
            <rect width="104" height="14" rx="5" class="fill-box-ps4" />
            <rect y="9" width="104" height="5" class="fill-box-ps4" />
            <rect
                x="12"
                y="24"
                width="48"
                height="7"
                rx="2"
                class="fill-board-muted"
            />
            <g class="fill-board-line">
                <rect x="12" y="38" width="80" height="4" rx="2" />
                <rect x="12" y="47" width="74" height="4" rx="2" />
                <rect x="12" y="56" width="80" height="4" rx="2" />
                <rect x="12" y="65" width="58" height="4" rx="2" />
            </g>
        </g>
    </svg>
</template>
