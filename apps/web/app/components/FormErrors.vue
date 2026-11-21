<script setup lang="ts">
defineProps<{ messages: string[] }>();
</script>

<template>
    <ul v-if="messages.length > 0" role="alert">
        <li v-for="message in messages" :key="message">{{ message }}</li>
    </ul>
</template>
