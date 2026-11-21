<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * The tabbed shelf right under the banner, full width: the platform and
 * type shortcuts live in the turquoise band below (HomeCategories). "Novedades" is the API's
 * newest sort; the other two tabs are real products in another order,
 * marked as an example selection until the API has offers and featured
 * products.
 */
const props = defineProps<{
    tabs: {
        id: string;
        label: string;
        products: ProductSummary[];
        example: boolean;
    }[];
}>();

const active = ref(props.tabs[0]?.id);
const tabRefs = ref<HTMLButtonElement[]>([]);

// Arrow keys move between tabs (WAI-ARIA tabs pattern, automatic activation).
function onKeydown(event: KeyboardEvent, index: number) {
    const last = props.tabs.length - 1;
    const next =
        event.key === 'ArrowRight'
            ? index === last
                ? 0
                : index + 1
            : event.key === 'ArrowLeft'
              ? index === 0
                  ? last
                  : index - 1
              : event.key === 'Home'
                ? 0
                : event.key === 'End'
                  ? last
                  : undefined;
    if (next === undefined) return;
    event.preventDefault();
    active.value = props.tabs[next]!.id;
    tabRefs.value[next]?.focus();
}
</script>

<template>
    <section aria-labelledby="shelf-heading">
        <div
            class="flex flex-wrap items-end justify-between gap-4 border-b border-line"
        >
            <h2 id="shelf-heading" class="sr-only">Estante</h2>
            <div role="tablist" aria-label="Estante" class="flex gap-1">
                <button
                    v-for="(tab, index) in tabs"
                    :id="`tab-${tab.id}`"
                    :key="tab.id"
                    ref="tabRefs"
                    type="button"
                    role="tab"
                    :aria-selected="active === tab.id"
                    :aria-controls="`panel-${tab.id}`"
                    :tabindex="active === tab.id ? 0 : -1"
                    class="relative h-12 px-4 font-display text-lg font-extrabold tracking-wide uppercase transition-colors duration-150"
                    :class="
                        active === tab.id
                            ? 'text-fg'
                            : 'text-fg-faint hover:text-fg-muted'
                    "
                    @click="active = tab.id"
                    @keydown="onKeydown($event, index)"
                >
                    {{ tab.label }}
                    <span
                        aria-hidden="true"
                        class="absolute inset-x-3 -bottom-px h-0.5 bg-accent"
                        :class="active === tab.id ? 'opacity-100' : 'opacity-0'"
                    />
                </button>
            </div>
            <UiButton :to="catalogLink()" variant="ghost" size="sm" class="mb-1"
                >Ver todo<UiIcon name="arrowRight" :size="16"
            /></UiButton>
        </div>

        <div
            v-for="tab in tabs"
            :id="`panel-${tab.id}`"
            :key="tab.id"
            role="tabpanel"
            :aria-labelledby="`tab-${tab.id}`"
            :hidden="active !== tab.id"
            class="pt-6"
        >
            <p
                v-if="tab.example"
                class="mb-5 inline-flex items-center gap-2 rounded-control border border-line-strong px-2 py-1 text-xs text-fg-muted"
            >
                Selección de ejemplo: productos reales, sin descuentos
                inventados.
            </p>
            <ul
                class="grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-3 lg:grid-cols-4"
            >
                <li v-for="product in tab.products" :key="product.slug">
                    <ProductCard :product="product" />
                </li>
            </ul>
        </div>
    </section>
</template>
