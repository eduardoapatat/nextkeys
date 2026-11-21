<script setup lang="ts">
import type { CatalogFilters, ProductSummary } from '~/api/schemas';
import type { TileKind } from '~/components/CategoryTile.vue';

/**
 * The full-width turquoise band, the home's only shortcut to the filters
 * (it has no side menu): hand-drawn tiles (CategoryTile) for the console
 * families, Steam and the product types, each a real catalog filter. Steam
 * is where PC games are activated, as the cover band says. The platform
 * names under each family come from the API.
 */
const props = defineProps<{
    filters: CatalogFilters;
    // Up to three physical games whose covers fan out on their tile.
    physical: ProductSummary[];
}>();

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
            kind: family.id as TileKind,
            label: family.label,
            note: platformNames(family.platform),
            to: catalogLink({ platform: [...family.platform] }),
        })),
    {
        kind: 'steam' as const,
        label: 'Steam',
        note: 'Juegos para escritorio',
        to: catalogLink({ activation_service: ['steam'] }),
    },
]);

const typeNotes: Record<(typeof productTypeLinks)[number]['type'][0], string> =
    {
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
                <li v-for="tile in tiles" :key="tile.kind">
                    <NuxtLink
                        :to="tile.to"
                        class="pressable group block h-full rounded-card shadow-case hover:shadow-lift"
                    >
                        <CategoryTile
                            :kind="tile.kind"
                            :label="tile.label"
                            :note="tile.note"
                        />
                    </NuxtLink>
                </li>
            </ul>
            <ul class="mt-3 grid gap-3 sm:grid-cols-3 md:mt-4 md:gap-4">
                <li v-for="item in productTypeLinks" :key="item.label">
                    <NuxtLink
                        :to="catalogLink({ type: [...item.type] })"
                        class="pressable group block h-full rounded-card shadow-case hover:shadow-lift"
                    >
                        <CategoryTile
                            :kind="item.type[0]"
                            :label="item.label"
                            :note="typeNotes[item.type[0]]"
                            size="md"
                            :art="
                                item.type[0] === 'license' ? 'above' : 'beside'
                            "
                        >
                            <template v-if="item.type[0] === 'license'" #art>
                                <HomeLicenseArt />
                            </template>
                            <template
                                v-else-if="
                                    item.type[0] === 'physical' &&
                                    physical.length
                                "
                                #art
                            >
                                <span
                                    v-for="(product, i) in physical"
                                    :key="product.slug"
                                    class="absolute bottom-0 w-[44%] max-w-24 translate-y-[18%] shadow-case"
                                    :class="
                                        [
                                            'right-[50%] -rotate-8',
                                            'right-[26%] z-10 translate-y-[4%]',
                                            'right-[2%] rotate-8',
                                        ][i]
                                    "
                                >
                                    <ProductCover
                                        :product="product"
                                        size="sm"
                                    />
                                </span>
                            </template>
                        </CategoryTile>
                    </NuxtLink>
                </li>
            </ul>
        </div>
    </section>
</template>
