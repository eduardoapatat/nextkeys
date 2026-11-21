<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * The whiteboard behind the counter: today's prices in marker (real
 * products, cheapest first) and the deal of the day with its countdown.
 * The deal is a pick at its real price, never an invented discount.
 */
defineProps<{ prices: ProductSummary[]; deal?: ProductSummary }>();
</script>

<template>
    <aside
        aria-label="Pizarra"
        class="flex flex-col gap-6 rounded-card border-4 border-panel-2 bg-board p-5 text-board-ink shadow-case"
    >
        <section aria-labelledby="prices-heading">
            <h2
                id="prices-heading"
                class="font-marker text-3xl font-normal tracking-normal normal-case"
            >
                Precios de hoy
            </h2>
            <ul class="mt-3 flex flex-col">
                <li
                    v-for="product in prices"
                    :key="product.slug"
                    class="border-b border-board-line last:border-0"
                >
                    <NuxtLink
                        :to="`/${product.slug}`"
                        class="flex items-baseline gap-2 py-2 hover:text-board-red"
                    >
                        <span class="min-w-0">
                            <span class="block truncate font-semibold">{{
                                product.title.name
                            }}</span>
                            <span
                                class="block truncate text-xs text-board-muted"
                                >{{
                                    product.platforms
                                        .map((p) => p.name)
                                        .join(', ')
                                }}</span
                            >
                        </span>
                        <span
                            aria-hidden="true"
                            class="mb-1 flex-1 self-end border-b-2 border-dotted border-board-line"
                        />
                        <data
                            :value="product.price"
                            class="tabular font-display text-xl font-bold"
                            >{{ formatClp(product.price) }}</data
                        >
                    </NuxtLink>
                </li>
            </ul>
        </section>

        <section
            v-if="deal"
            aria-labelledby="deal-heading"
            class="rounded-control border-2 border-dashed border-board-red p-4"
        >
            <h2
                id="deal-heading"
                class="font-marker text-2xl font-normal tracking-normal text-board-red normal-case"
            >
                Oferta del día
            </h2>
            <p
                class="mt-1 font-display text-3xl leading-none font-bold uppercase"
            >
                <NuxtLink :to="`/${deal.slug}`" class="hover:text-board-red">{{
                    deal.title.name
                }}</NuxtLink>
            </p>
            <p class="mt-1 text-sm text-board-muted">
                {{ deal.type.name }} ·
                {{ deal.platforms.map((p) => p.name).join(', ') }}
            </p>
            <div class="mt-3 flex items-end justify-between gap-3">
                <data
                    :value="deal.price"
                    class="tabular font-display text-4xl leading-none font-bold"
                    >{{ formatClp(deal.price) }}</data
                >
                <p class="flex items-center gap-1.5 font-mono text-sm">
                    <UiIcon name="clock" :size="16" />
                    <DealCountdown />
                </p>
            </div>
            <p class="mt-3 text-xs text-board-muted">
                Elegida para hoy, a su precio de siempre. Cambia a medianoche.
            </p>
        </section>
    </aside>
</template>
