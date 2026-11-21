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
    <NuxtLayout>
        <main
            class="mx-auto flex max-w-xl flex-col items-start gap-5 px-4 py-20 md:py-28"
        >
            <p
                aria-hidden="true"
                class="-rotate-2 rounded-control bg-board px-4 py-2 font-marker text-2xl text-board-red shadow-sticker"
            >
                {{
                    error.statusCode === 404
                        ? 'Este puesto está vacío'
                        : 'Fuera de servicio'
                }}
            </p>
            <h1 class="text-5xl text-chalk uppercase md:text-6xl">
                {{ heading }}
            </h1>
            <p v-if="error.statusCode === 404" class="text-chalk-muted">
                No existe o ya no está disponible.
            </p>
            <p v-else class="text-chalk-muted">
                No pudimos cargar esta página. Intenta de nuevo en unos minutos.
            </p>
            <a
                :href="catalogPath"
                class="pressable inline-flex h-11 items-center rounded-control bg-sticker px-4 font-display font-extrabold tracking-wide text-sticker-ink uppercase hover:bg-sticker-hover"
                @click.prevent="clearError({ redirect: catalogPath })"
            >
                Volver al catálogo
            </a>
        </main>
    </NuxtLayout>
</template>
