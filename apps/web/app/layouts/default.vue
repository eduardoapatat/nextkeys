<template>
    <div class="flex min-h-dvh flex-col">
        <a
            href="#contenido"
            class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-sticker focus:px-3 focus:py-2 focus:text-sticker-ink"
            >Saltar al contenido</a
        >
        <SiteHeader />
        <div id="contenido" class="flex-1">
            <slot />
        </div>
        <SiteFooter />
    </div>
</template>
