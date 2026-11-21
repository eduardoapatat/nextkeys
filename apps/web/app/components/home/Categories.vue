<script setup lang="ts">
import type { CatalogFilters } from '~/api/schemas';
import type { TileFamily } from '~/components/PlatformTile.vue';

/**
 * The full-width turquoise band: the console families as drawn tiles
 * (PlatformTile) and Steam, where PC games are activated (the cover band
 * says the same), and and the product types,
 * each a real catalog filter (the home has no side menu). The platform names under each family come from the API.
 */
const props = defineProps<{ filters: CatalogFilters }>();

function platformNames(slugs: readonly string[]): string {
    return props.filters.platforms
        .filter((platform) => slugs.includes(platform.slug))
        .map((platform) => platform.name)
        .join(' · ');
}

// Steam is a store, not a platform: its tile filters by activation service.
// PC programs (licenses) are reached through the "Licencias" link.
const tiles = computed(() => [
    ...platformFamilies
        .filter((family) => family.id !== 'pc')
        .map((family) => ({
            family: family.id as TileFamily,
            label: family.label,
            platforms: platformNames(family.platform),
            to: catalogLink({ platform: [...family.platform] }),
        })),
    {
        family: 'steam' as const,
        label: 'Steam',
        platforms: 'Juegos para escritorio',
        to: catalogLink({ activation_service: ['steam'] }),
    },
]);

const typeNotes: Record<string, string> = {
    digital_key: 'Llega a tu correo y a tu biblioteca',
    license: 'Programas con su clave de activación',
    physical: 'La caja, para tu estante',
};
</script>

<template>
    <section
        aria-labelledby="categories-heading"
        class="surface-brand py-12 md:py-16"
    >
        <div class="mx-auto max-w-7xl px-4">
            <h2
                id="categories-heading"
                class="text-4xl text-fg uppercase md:text-5xl"
            >
                Elige tu plataforma
            </h2>
            <ul class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4 md:gap-4">
                <li v-for="tile in tiles" :key="tile.family">
                    <NuxtLink
                        :to="tile.to"
                        class="pressable group block h-full rounded-card shadow-case hover:shadow-lift"
                    >
                        <PlatformTile
                            :family="tile.family"
                            :label="tile.label"
                            :platforms="tile.platforms"
                        />
                    </NuxtLink>
                </li>
            </ul>
            <ul class="mt-4 grid gap-3 sm:grid-cols-3 md:gap-4">
                <li v-for="item in productTypeLinks" :key="item.label">
                    <NuxtLink
                        :to="catalogLink({ type: [...item.type] })"
                        class="pressable group flex items-center justify-between gap-4 rounded-card border border-line-strong px-4 py-3 hover:bg-surface-2"
                    >
                        <span>
                            <span
                                class="block font-display text-xl font-extrabold text-fg uppercase"
                                >{{ item.label }}</span
                            >
                            <span class="block text-sm text-fg-muted">{{
                                typeNotes[item.type[0]]
                            }}</span>
                        </span>
                        <UiIcon name="arrowRight" class="text-fg" />
                    </NuxtLink>
                </li>
            </ul>
        </div>
    </section>
</template>
