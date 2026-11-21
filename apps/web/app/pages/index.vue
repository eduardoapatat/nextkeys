<script setup lang="ts">
import { fetchCatalogFilters, fetchProducts } from '~/api/catalog';

useHead({ title: 'Claves y juegos al instante' });

// Games (keys and boxes) in three real orderings: newest for the deal and
// "Novedades", cheapest for the banner receipt and the example offers, by
// name for the banner covers and the example featured selection. Programs
// (licenses) have their own section.
const GAMES = 'digital_key,physical';

const { data, error } = await useAsyncData('home', async () => {
    const [newest, cheapest, byName, programs, filters] = await Promise.all([
        fetchProducts({ type: GAMES, sort: 'newest' }),
        fetchProducts({ type: GAMES, sort: 'price' }),
        fetchProducts({ type: GAMES, sort: 'name' }),
        fetchProducts({ type: 'license', sort: 'name' }),
        fetchCatalogFilters(),
    ]);

    return {
        newest: newest.data,
        cheapest: cheapest.data,
        byName: byName.data,
        programs: programs.data,
        filters,
    };
});

if (error.value || !data.value) {
    throw createError({ statusCode: 502, fatal: true });
}

const home = data.value;
const deal = home.newest[dealIndex(new Date(), home.newest.length)];

// Three physical games, one per title, for the covers on their tile.
const physical = [...home.newest, ...home.byName]
    .filter((product) => product.type.slug === 'physical')
    .filter(
        (product, index, all) =>
            all.findIndex((p) => p.title.name === product.title.name) === index,
    )
    .slice(0, 3);

const tabs = [
    {
        id: 'novedades',
        label: 'Novedades',
        products: home.newest.slice(0, 8),
        example: false,
    },
    {
        id: 'ofertas',
        label: 'En oferta',
        products: home.cheapest.slice(0, 8),
        example: true,
    },
    {
        id: 'destacados',
        label: 'Destacados',
        products: home.byName.slice(0, 8),
        example: true,
    },
];
</script>

<template>
    <main class="flex flex-col gap-16 py-6 md:gap-20 md:py-8">
        <h1 class="sr-only">NextKeys: claves y juegos al instante</h1>
        <div
            class="mx-auto flex w-full max-w-7xl flex-col gap-16 px-4 md:gap-20"
        >
            <HomeBanner
                :deal="deal"
                :receipt="home.cheapest[0]"
                :fan="home.byName"
            />
            <HomeShelfTabs :tabs="tabs" />
        </div>
        <HomeCategories :filters="home.filters" :physical="physical" />
        <div
            class="mx-auto flex w-full max-w-7xl flex-col gap-16 px-4 md:gap-20"
        >
            <HomePrograms
                v-if="home.programs.length"
                :products="home.programs.slice(0, 8)"
            />
            <HomeBenefits />
            <HomeFaq />
        </div>
    </main>
</template>
