<script setup lang="ts">
import { fetchCatalogFilters, fetchProducts } from '~/api/catalog';

useHead({ title: 'Claves y juegos al instante' });

// Three real orderings of the catalog: newest for the case and "Novedades",
// cheapest for the banner receipt and the example offers, by name for
// the banner covers and the example featured selection.
const { data, error } = await useAsyncData('home', async () => {
    const [newest, cheapest, byName, filters] = await Promise.all([
        fetchProducts({ sort: 'newest' }),
        fetchProducts({ sort: 'price' }),
        fetchProducts({ sort: 'name' }),
        fetchCatalogFilters(),
    ]);

    return {
        newest: newest.data,
        cheapest: cheapest.data,
        byName: byName.data,
        filters,
    };
});

if (error.value || !data.value) {
    throw createError({ statusCode: 502, fatal: true });
}

const home = data.value;
const deal = home.newest[dealIndex(new Date(), home.newest.length)];

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
    <main
        class="mx-auto flex max-w-7xl flex-col gap-16 px-4 py-6 md:gap-20 md:py-8"
    >
        <div class="flex flex-col gap-6">
            <HomeBanner
                :deal="deal"
                :receipt="home.cheapest[0]"
                :fan="home.byName"
            />
            <HomeDisplayCase :products="home.newest" />
        </div>
        <HomeCategories :filters="home.filters" />
        <HomeShelfTabs :tabs="tabs" :filters="home.filters" />
        <HomeBenefits />
    </main>
</template>
