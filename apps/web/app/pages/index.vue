<script setup lang="ts">
// Until the home page lands, the root shows the catalog.
await navigateTo(catalogLink(), { redirectCode: 302 });
</script>

<template>
    <main />
</template>
