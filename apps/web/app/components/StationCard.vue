<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * A product as a station of the cibercafé: the cover on its numbered spot,
 * the price sticker on the box, and three fixed slots below it (platforms,
 * region, activation) that always mean the same thing in the same place.
 * The whole card is one link to the product.
 */
const props = withDefaults(
    defineProps<{
        product: ProductSummary;
        station?: number;
        headingLevel?: 'h2' | 'h3';
        eager?: boolean;
    }>(),
    { station: undefined, headingLevel: 'h3', eager: false },
);

const platforms = computed(() =>
    props.product.platforms.map((platform) => platform.name).join(', '),
);
</script>

<template>
    <article class="group relative flex flex-col gap-3">
        <div class="relative">
            <CoverTilt>
                <ProductCover :product="product" :eager="eager" />
            </CoverTilt>
            <PriceSticker
                :price="product.price"
                :in-stock="product.in_stock"
                class="absolute -right-1.5 -bottom-2"
            />
        </div>

        <p
            v-if="station !== undefined"
            aria-hidden="true"
            class="tabular -mb-2 pt-1 font-mono text-xs text-chalk-faint"
        >
            PC {{ String(station).padStart(2, '0') }}
        </p>
        <component
            :is="headingLevel"
            class="pr-4 font-sans text-base leading-snug font-semibold tracking-normal normal-case"
        >
            <NuxtLink
                :to="`/${product.slug}`"
                class="text-chalk after:absolute after:inset-0 after:rounded-card hover:text-sticker focus-visible:outline-none after:focus-visible:outline-2 after:focus-visible:outline-offset-4 after:focus-visible:outline-crt"
            >
                {{ product.title.name }}
            </NuxtLink>
        </component>

        <dl
            class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-sm text-chalk-muted"
        >
            <dt class="sr-only">Plataformas</dt>
            <dd class="col-span-2 truncate text-chalk">{{ platforms }}</dd>
            <dt class="sr-only">Tipo</dt>
            <dd>{{ product.type.name }}</dd>
            <dt class="sr-only">Región</dt>
            <dd class="truncate text-right">
                {{ product.region?.name ?? 'Sin región' }}
            </dd>
            <dt class="sr-only">Activación</dt>
            <dd class="col-span-2 truncate text-chalk-faint">
                {{
                    product.activation_service
                        ? `Se activa en ${product.activation_service.name}`
                        : 'Sin activación'
                }}
            </dd>
        </dl>
    </article>
</template>
