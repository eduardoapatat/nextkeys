<script setup lang="ts">
import type { NuxtError } from '#app';

const props = defineProps<{ error: NuxtError }>();

const heading = computed(() => {
    if (props.error.statusCode !== 404) {
        return 'Algo salió mal';
    }

    return props.error.statusMessage === productNotFound
        ? productNotFound
        : 'Página no encontrada';
});

useHead({ title: heading });
</script>

<template>
    <main>
        <h1>{{ heading }}</h1>
        <p v-if="error.statusCode === 404">
            No existe o ya no está disponible.
        </p>
        <p v-else>
            No pudimos cargar esta página. Intenta de nuevo en unos minutos.
        </p>
        <a href="/" @click.prevent="clearError({ redirect: '/' })">
            Volver al catálogo
        </a>
    </main>
</template>
