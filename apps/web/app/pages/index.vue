<script setup lang="ts">
import { fetchCatalogFilters, fetchProducts } from '~/api/catalog';
import type { CatalogFilters, Reference } from '~/api/schemas';

useHead({ title: 'Catálogo' });

const route = useRoute();
const listing = computed(() => parseListingQuery(route.query));
const apiQuery = computed(() => serializeListingQuery(listing.value));

const { data: filters, error: filtersError } = await useAsyncData(
    'catalog-filters',
    () => fetchCatalogFilters(),
);

if (filtersError.value) {
    throw createError({ statusCode: 502, fatal: true });
}

// The key changes with every filter, sort and page, so a new URL always
// fetches again instead of showing the previous result.
const {
    data: page,
    error,
    status,
} = await useAsyncData(
    () => `products:${new URLSearchParams(apiQuery.value)}`,
    () => fetchProducts(apiQuery.value),
);

const apiError = computed(() =>
    error.value ? toApiError(error.value) : undefined,
);

if (apiError.value && apiError.value.statusCode !== 422) {
    throw createError({
        statusCode: apiError.value.statusCode ?? 502,
        fatal: true,
    });
}

const facetLabels: Record<ListingFacet, string> = {
    platform: 'Plataforma',
    type: 'Tipo',
    activation_service: 'Servicio de activación',
    region: 'Región',
};

function facetOptions(
    facet: ListingFacet,
    options: CatalogFilters,
): Reference[] {
    const byFacet: Record<ListingFacet, Reference[]> = {
        platform: options.platforms,
        type: options.types,
        activation_service: options.activation_services,
        region: options.regions,
    };

    return byFacet[facet];
}

const sortOptions = [
    { value: 'newest', label: 'Más recientes' },
    { value: 'price', label: 'Precio: menor a mayor' },
    { value: '-price', label: 'Precio: mayor a menor' },
    { value: 'name', label: 'Nombre' },
];

function applyFilters(event: Event) {
    if (event.currentTarget instanceof HTMLFormElement) {
        const query = listingQueryFromForm(new FormData(event.currentTarget));

        return navigateTo({ path: '/', query: serializeListingQuery(query) });
    }
}

function pageLink(number: number) {
    return {
        path: '/',
        query: serializeListingQuery(withPage(listing.value, number)),
    };
}
</script>

<template>
    <main>
        <h1>NextKeys</h1>

        <form method="get" action="/" @submit.prevent="applyFilters">
            <template v-if="filters">
                <fieldset v-for="facet in listingFacets" :key="facet">
                    <legend>{{ facetLabels[facet] }}</legend>
                    <label
                        v-for="option in facetOptions(facet, filters)"
                        :key="option.slug"
                    >
                        <input
                            type="checkbox"
                            :name="facet"
                            :value="option.slug"
                            :checked="listing[facet].includes(option.slug)"
                        />
                        {{ option.name }}
                    </label>
                </fieldset>
            </template>
            <label>
                Ordenar por
                <select name="sort">
                    <option
                        v-for="option in sortOptions"
                        :key="option.value"
                        :value="option.value"
                        :selected="(listing.sort ?? 'newest') === option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </label>
            <button type="submit">Aplicar</button>
            <NuxtLink to="/">Quitar filtros</NuxtLink>
        </form>

        <section aria-labelledby="results-heading">
            <h2 id="results-heading">Productos</h2>

            <div v-if="apiError" role="alert">
                <p>No pudimos aplicar estos filtros:</p>
                <ul>
                    <li v-for="message in apiError.messages" :key="message">
                        {{ message }}
                    </li>
                </ul>
                <NuxtLink to="/">Quitar filtros</NuxtLink>
            </div>
            <p v-else-if="status === 'pending'">Cargando…</p>
            <template v-else-if="page">
                <p v-if="page.data.length === 0">
                    No hay productos con estos filtros.
                </p>
                <template v-else>
                    <p>{{ page.meta.total }} productos</p>
                    <ul>
                        <li v-for="product in page.data" :key="product.slug">
                            <article>
                                <h3>
                                    <NuxtLink :to="`/${product.slug}`">
                                        {{ product.title.name }}
                                    </NuxtLink>
                                </h3>
                                <ProductCover :product="product" />
                                <ProductFacts :product="product" />
                            </article>
                        </li>
                    </ul>
                </template>
                <nav v-if="page.meta.last_page > 1" aria-label="Paginación">
                    <NuxtLink
                        v-if="page.meta.current_page > 1"
                        :to="pageLink(page.meta.current_page - 1)"
                        rel="prev"
                    >
                        Anterior
                    </NuxtLink>
                    Página {{ page.meta.current_page }} de
                    {{ page.meta.last_page }}
                    <NuxtLink
                        v-if="page.meta.current_page < page.meta.last_page"
                        :to="pageLink(page.meta.current_page + 1)"
                        rel="next"
                    >
                        Siguiente
                    </NuxtLink>
                </nav>
            </template>
        </section>
    </main>
</template>
