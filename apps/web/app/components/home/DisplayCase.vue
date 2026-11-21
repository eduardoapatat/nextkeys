<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * The lit display case: the newest product at billing scale and the next
 * four on the glass shelf, each with its price sticker.
 */
const props = defineProps<{ products: ProductSummary[] }>();

const lead = computed(() => props.products[0]);
const shelf = computed(() => props.products.slice(1, 5));
</script>

<template>
    <section
        aria-labelledby="case-heading"
        class="display-case relative overflow-hidden rounded-card px-5 pt-8 pb-6 md:px-8"
    >
        <div
            aria-hidden="true"
            class="display-case-tube absolute inset-x-[12%] top-0 h-0.5 rounded-full"
        />
        <h1
            id="case-heading"
            class="max-w-[24ch] text-4xl text-chalk uppercase md:text-5xl"
        >
            Claves y juegos al instante, en pesos chilenos
        </h1>

        <div
            v-if="lead"
            class="mt-8 grid items-end gap-6 sm:grid-cols-[minmax(0,15rem)_1fr] md:gap-10"
        >
            <div class="relative w-3/5 animate-rise sm:w-full">
                <CoverTilt :max="9">
                    <ProductCover :product="lead" size="lg" eager />
                </CoverTilt>
                <PriceSticker
                    :price="lead.price"
                    :in-stock="lead.in_stock"
                    size="lg"
                    class="absolute -right-4 -bottom-4"
                />
            </div>
            <div class="flex flex-col gap-4 pb-2">
                <h2 class="text-4xl text-chalk uppercase md:text-5xl">
                    <NuxtLink :to="`/${lead.slug}`" class="hover:text-sticker">
                        {{ lead.title.name }}
                    </NuxtLink>
                </h2>
                <dl
                    class="grid max-w-md grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm"
                >
                    <dt class="text-chalk-faint">Plataformas</dt>
                    <dd class="text-chalk">
                        {{ lead.platforms.map((p) => p.name).join(', ') }}
                    </dd>
                    <dt class="text-chalk-faint">Tipo</dt>
                    <dd class="text-chalk">{{ lead.type.name }}</dd>
                    <template v-if="lead.region">
                        <dt class="text-chalk-faint">Región</dt>
                        <dd class="text-chalk">{{ lead.region.name }}</dd>
                    </template>
                    <template v-if="lead.activation_service">
                        <dt class="text-chalk-faint">Se activa en</dt>
                        <dd class="text-chalk">
                            {{ lead.activation_service.name }}
                        </dd>
                    </template>
                </dl>
                <div class="flex flex-wrap items-start gap-3">
                    <QuickAdd
                        :product="lead"
                        variant="primary"
                        size="lg"
                        data-action="lead-add"
                    />
                    <UiButton :to="`/${lead.slug}`" variant="ghost" size="lg"
                        >Ver ficha</UiButton
                    >
                </div>
            </div>
        </div>

        <template v-if="shelf.length">
            <div class="relative mt-10 max-w-2xl">
                <ul class="relative z-10 grid grid-cols-4 gap-3 px-1 md:gap-6">
                    <li
                        v-for="(product, index) in shelf"
                        :key="product.slug"
                        class="animate-rise"
                        :style="{
                            animationDelay: `${80 + staggerDelay(index)}ms`,
                        }"
                    >
                        <NuxtLink
                            :to="`/${product.slug}`"
                            class="relative block rounded-card"
                            :aria-label="`${product.title.name}, ${formatClp(product.price)}`"
                        >
                            <CoverTilt>
                                <ProductCover :product="product" size="sm" />
                            </CoverTilt>
                            <PriceSticker
                                :price="product.price"
                                :in-stock="product.in_stock"
                                class="absolute -right-1 -bottom-2 scale-90 max-sm:hidden"
                            />
                        </NuxtLink>
                    </li>
                </ul>
                <div
                    aria-hidden="true"
                    class="glass-shelf absolute -inset-x-2 -bottom-3 h-3 rounded-sm"
                />
            </div>
            <ul class="mt-6 grid max-w-2xl grid-cols-4 gap-3 px-1 md:gap-6">
                <li v-for="product in shelf" :key="product.slug">
                    <QuickAdd
                        :product="product"
                        compact
                        class="[&_button]:w-full"
                    />
                </li>
            </ul>
        </template>
    </section>
</template>
