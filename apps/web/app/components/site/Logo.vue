<template>
    <NuxtLink
        to="/"
        class="group flex items-center gap-2 font-display text-2xl leading-none font-extrabold tracking-tight text-chalk uppercase"
    >
        <span
            aria-hidden="true"
            class="grid size-9 -rotate-6 place-items-center rounded-control bg-sticker text-sticker-ink shadow-sticker transition-transform duration-200 ease-out group-hover:rotate-0"
        >
            <UiIcon name="key" :size="20" />
        </span>
        <span>Next<span class="text-sticker">Keys</span></span>
    </NuxtLink>
</template>
