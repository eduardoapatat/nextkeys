<script setup lang="ts">
useHead({
    htmlAttrs: { lang: 'es' },
    titleTemplate: (title) => (title ? `${title} · NextKeys` : 'NextKeys'),
    link: [
        {
            rel: 'preload',
            href: '/fonts/rubik-latin.woff2',
            as: 'font',
            type: 'font/woff2',
            crossorigin: '',
        },
        {
            rel: 'preload',
            href: '/fonts/sofia-sans-condensed-latin.woff2',
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
