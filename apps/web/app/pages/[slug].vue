<script setup lang="ts">
import { fetchProduct } from '~/api/catalog';

const route = useRoute();
const slug = computed(() => String(route.params.slug));

const { data: product, error } = await useAsyncData(
    () => `product:${slug.value}`,
    () => fetchProduct(slug.value),
);

if (error.value) {
    const { statusCode } = toApiError(error.value);

    // A real 404 status during SSR, not a 200 with an error page.
    throw createError(
        statusCode === 404
            ? { statusCode: 404, statusMessage: productNotFound, fatal: true }
            : { statusCode: 502, fatal: true },
    );
}

useHead({ title: () => product.value?.title.name });
</script>

<template>
    <main v-if="product">
        <p><NuxtLink :to="catalogLink()">Volver al catálogo</NuxtLink></p>
        <article>
            <h1>{{ product.title.name }}</h1>
            <ProductCover :product="product" />
            <ProductFacts :product="product" />
            <AddToCart :key="product.slug" :product="product" />
            <p>{{ product.title.description }}</p>
        </article>
        <nav aria-labelledby="variants-heading">
            <h2 id="variants-heading">Variantes</h2>
            <ul>
                <li v-for="variant in product.variants" :key="variant.slug">
                    <NuxtLink
                        :to="`/${variant.slug}`"
                        :aria-current="
                            variant.slug === product.slug ? 'page' : undefined
                        "
                    >
                        {{ variantLabel(variant) }}
                    </NuxtLink>
                    <template v-if="!variant.in_stock"> (sin stock)</template>
                </li>
            </ul>
        </nav>
    </main>
</template>
