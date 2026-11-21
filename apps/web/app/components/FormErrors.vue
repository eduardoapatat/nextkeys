<script setup lang="ts">
defineProps<{ messages: string[] }>();
</script>

<template>
    <ul
        v-if="messages.length > 0"
        role="alert"
        class="flex flex-col gap-1 rounded-card border border-danger/50 bg-danger-soft px-4 py-3 text-fg"
    >
        <li v-for="message in messages" :key="message">{{ message }}</li>
    </ul>
</template>
