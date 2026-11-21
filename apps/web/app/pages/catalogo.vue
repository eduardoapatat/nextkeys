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

const form = ref<HTMLFormElement | null>(null);

// With JS a change applies at once; without it, "Aplicar" submits the GET form.
function autoApply() {
    form.value?.requestSubmit();
}

const activeFilters = computed(() =>
    listingFacets.reduce(
        (count, facet) => count + listing.value[facet].length,
        0,
    ),
);

function applyFilters(event: Event) {
    if (event.currentTarget instanceof HTMLFormElement) {
        const query = listingQueryFromForm(new FormData(event.currentTarget));

        return navigateTo({
            path: catalogPath,
            query: serializeListingQuery(query),
        });
    }
}

function pageLink(number: number) {
    return {
        path: catalogPath,
        query: serializeListingQuery(withPage(listing.value, number)),
    };
}
</script>

<template>
    <main class="mx-auto max-w-7xl px-4 py-8 md:py-10">
        <div
            class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5"
        >
            <div>
                <h1 class="text-5xl text-chalk uppercase md:text-6xl">
                    Catálogo
                </h1>
                <p
                    v-if="page && !apiError"
                    class="tabular mt-1 text-chalk-muted"
                >
                    {{ page.meta.total }}
                    {{ page.meta.total === 1 ? 'producto' : 'productos' }}
                </p>
            </div>
            <label class="flex items-center gap-3 text-sm text-chalk-muted">
                Ordenar por
                <select
                    name="sort"
                    form="catalog-filters"
                    class="h-10 rounded-control border border-line bg-panel px-3 text-chalk focus:border-crt focus:outline-none"
                    @change="autoApply"
                >
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
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <details
                class="catalog-filters group rounded-card border border-line bg-ink-2 lg:border-0 lg:bg-transparent"
            >
                <summary
                    class="flex h-12 cursor-pointer list-none items-center justify-between px-4 font-display font-bold tracking-wide text-chalk uppercase lg:hidden"
                >
                    <span class="flex items-center gap-2">
                        <UiIcon name="filter" />Filtros
                        <span
                            v-if="activeFilters > 0"
                            class="tabular grid h-6 min-w-6 place-items-center rounded-control bg-sticker px-1.5 text-sm text-sticker-ink"
                            >{{ activeFilters }}</span
                        >
                    </span>
                    <UiIcon
                        name="chevronRight"
                        class="transition-transform duration-200 group-open:rotate-90"
                    />
                </summary>
                <form
                    id="catalog-filters"
                    ref="form"
                    method="get"
                    :action="catalogPath"
                    aria-label="Filtros del catálogo"
                    class="flex flex-col gap-6 px-4 pb-5 lg:px-0"
                    @change="autoApply"
                    @submit.prevent="applyFilters"
                >
                    <template v-if="filters">
                        <fieldset
                            v-for="facet in listingFacets"
                            :key="facet"
                            class="flex flex-col gap-1"
                        >
                            <legend
                                class="mb-2 font-display text-sm font-bold tracking-wider text-chalk-faint uppercase"
                            >
                                {{ facetLabels[facet] }}
                            </legend>
                            <label
                                v-for="option in facetOptions(facet, filters)"
                                :key="option.slug"
                                class="flex min-h-9 cursor-pointer items-center gap-3 rounded-control px-2 text-chalk-muted hover:bg-panel hover:text-chalk has-checked:text-chalk"
                            >
                                <input
                                    type="checkbox"
                                    :name="facet"
                                    :value="option.slug"
                                    :checked="
                                        listing[facet].includes(option.slug)
                                    "
                                    class="size-4"
                                />
                                {{ option.name }}
                            </label>
                        </fieldset>
                    </template>
                    <div class="flex items-center gap-3">
                        <UiButton type="submit" size="sm">Aplicar</UiButton>
                        <UiButton
                            v-if="activeFilters > 0"
                            :to="catalogLink()"
                            variant="ghost"
                            size="sm"
                            >Quitar filtros</UiButton
                        >
                    </div>
                </form>
            </details>

            <section aria-labelledby="results-heading">
                <h2 id="results-heading" class="sr-only">Productos</h2>

                <div
                    v-if="apiError"
                    role="alert"
                    class="rounded-card border border-danger/50 bg-danger-deep p-5 text-chalk"
                >
                    <p class="font-semibold">
                        No pudimos aplicar estos filtros:
                    </p>
                    <ul class="mt-2 list-disc pl-5 text-chalk-muted">
                        <li v-for="message in apiError.messages" :key="message">
                            {{ message }}
                        </li>
                    </ul>
                    <UiButton :to="catalogLink()" size="sm" class="mt-4"
                        >Quitar filtros</UiButton
                    >
                </div>
                <p
                    v-else-if="status === 'pending'"
                    class="py-16 text-center text-chalk-muted"
                >
                    Cargando…
                </p>
                <template v-else-if="page">
                    <div
                        v-if="page.data.length === 0"
                        class="rounded-card border border-dashed border-line-strong px-6 py-16 text-center"
                    >
                        <p class="font-display text-2xl text-chalk uppercase">
                            No hay productos con estos filtros.
                        </p>
                        <UiButton :to="catalogLink()" size="sm" class="mt-4"
                            >Ver todo el catálogo</UiButton
                        >
                    </div>
                    <ul
                        v-else
                        class="grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-3 xl:grid-cols-4"
                    >
                        <li
                            v-for="(product, index) in page.data"
                            :key="product.slug"
                            class="animate-rise"
                            :style="{
                                animationDelay: `${Math.min(index, 8) * 40}ms`,
                            }"
                        >
                            <StationCard
                                :product="product"
                                :station="(page.meta.from ?? 1) + index"
                                :eager="index < 4"
                            />
                        </li>
                    </ul>
                    <nav
                        v-if="page.meta.last_page > 1"
                        aria-label="Paginación"
                        class="mt-12 flex items-center justify-center gap-4 text-chalk-muted"
                    >
                        <UiButton
                            v-if="page.meta.current_page > 1"
                            :to="pageLink(page.meta.current_page - 1)"
                            rel="prev"
                            size="sm"
                        >
                            <UiIcon name="chevronLeft" :size="16" />Anterior
                        </UiButton>
                        <span class="tabular"
                            >Página {{ page.meta.current_page }} de
                            {{ page.meta.last_page }}</span
                        >
                        <UiButton
                            v-if="page.meta.current_page < page.meta.last_page"
                            :to="pageLink(page.meta.current_page + 1)"
                            rel="next"
                            size="sm"
                        >
                            Siguiente<UiIcon name="chevronRight" :size="16" />
                        </UiButton>
                    </nav>
                </template>
            </section>
        </div>
    </main>
</template>
