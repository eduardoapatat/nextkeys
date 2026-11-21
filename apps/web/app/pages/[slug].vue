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

const deliveryNote = computed(() =>
    product.value?.type.slug === 'physical'
        ? 'Juego físico, en su caja original.'
        : 'Entrega inmediata: la clave llega a tu correo y queda en Mi biblioteca.',
);
</script>

<template>
    <main v-if="product" class="mx-auto max-w-7xl px-4 py-6 md:py-10">
        <NuxtLink
            :to="catalogLink()"
            class="inline-flex items-center gap-1 text-sm text-fg-muted hover:text-fg"
            ><UiIcon name="chevronLeft" :size="16" />Volver al
            catálogo</NuxtLink
        >

        <article
            class="mt-6 grid gap-10 md:grid-cols-[minmax(0,22rem)_1fr] lg:gap-16"
        >
            <div class="relative mx-auto w-4/5 max-w-sm md:w-full">
                <CoverTilt :max="10">
                    <ProductCover :product="product" size="lg" eager />
                </CoverTilt>
                <PriceSticker
                    :price="product.price"
                    :in-stock="product.in_stock"
                    size="lg"
                    class="absolute -right-4 -bottom-4"
                />
            </div>

            <div class="flex flex-col gap-6">
                <h1 class="text-5xl text-fg uppercase md:text-7xl">
                    {{ product.title.name }}
                </h1>
                <ProductFacts :product="product" />
                <p class="flex items-start gap-2 text-sm text-fg-muted">
                    <UiIcon
                        :name="
                            product.type.slug === 'physical'
                                ? 'receipt'
                                : 'bolt'
                        "
                        :size="18"
                        class="mt-0.5 text-accent"
                    />{{ deliveryNote }}
                </p>
                <AddToCart :key="product.slug" :product="product" />
                <p class="max-w-prose leading-relaxed text-fg-muted">
                    {{ product.title.description }}
                </p>
            </div>
        </article>

        <nav
            aria-labelledby="variants-heading"
            class="mt-16 border-t border-line pt-8"
        >
            <h2 id="variants-heading" class="text-3xl text-fg uppercase">
                Variantes
            </h2>
            <p class="mt-1 text-sm text-fg-muted">
                Cada variante dice su tipo, dónde se activa, sus plataformas y
                su región.
            </p>
            <ul class="mt-5 grid gap-2 md:grid-cols-2">
                <li
                    v-for="variant in product.variants"
                    :key="variant.slug"
                    class="flex items-center gap-3"
                >
                    <NuxtLink
                        :to="`/${variant.slug}`"
                        :aria-current="
                            variant.slug === product.slug ? 'page' : undefined
                        "
                        class="pressable tabular flex min-h-12 flex-1 items-center rounded-control border px-4 py-2 text-sm"
                        :class="
                            variant.slug === product.slug
                                ? 'border-brand bg-brand-soft text-fg'
                                : 'border-line text-fg-muted hover:border-line-strong hover:text-fg'
                        "
                        >{{ variantLabel(variant) }}</NuxtLink
                    >
                    <span
                        v-if="!variant.in_stock"
                        class="shrink-0 text-xs text-fg-faint"
                        >(sin stock)</span
                    >
                </li>
            </ul>
        </nav>
    </main>
</template>
