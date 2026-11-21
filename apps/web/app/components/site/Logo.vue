<template>
    <NuxtLink
        to="/"
        class="group flex items-center gap-2 font-display text-2xl leading-none font-extrabold tracking-tight text-fg uppercase"
    >
        <span
            aria-hidden="true"
            class="grid size-9 -rotate-6 place-items-center rounded-control bg-accent text-accent-fg shadow-tag transition-transform duration-200 ease-out group-hover:rotate-0"
        >
            <UiIcon name="key" :size="20" />
        </span>
        <span>Next<span class="text-accent">Keys</span></span>
    </NuxtLink>
</template>
