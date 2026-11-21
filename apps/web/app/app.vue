<script setup lang="ts">
useHead({
    htmlAttrs: { lang: 'es' },
    titleTemplate: (title) => (title ? `${title} · NextKeys` : 'NextKeys'),
});
</script>

<template>
    <div>
        <NuxtRouteAnnouncer />
        <AccountNav />
        <NuxtPage />
    </div>
</template>

<style>
/* Prototype only: just enough to read it. The visual design is pending. */
body {
    font-family: system-ui, sans-serif;
    line-height: 1.5;
    max-width: 64rem;
    margin: 0 auto;
    padding: 1rem;
}

img {
    max-width: 16rem;
    height: auto;
}
</style>
