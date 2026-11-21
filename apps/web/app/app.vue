<script setup lang="ts">
useHead({
    htmlAttrs: { lang: 'es' },
    titleTemplate: (title) => (title ? `${title} · NextKeys` : 'NextKeys'),
    link: [
        {
            rel: 'preload',
            href: '/fonts/barlow-400-latin.woff2',
            as: 'font',
            type: 'font/woff2',
            crossorigin: '',
        },
        {
            rel: 'preload',
            href: '/fonts/barlow-condensed-700-latin.woff2',
            as: 'font',
            type: 'font/woff2',
            crossorigin: '',
        },
    ],
});
</script>

<template>
    <NuxtRouteAnnouncer />
    <NuxtLayout>
        <NuxtPage />
    </NuxtLayout>
</template>
