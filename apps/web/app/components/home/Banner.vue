<script setup lang="ts">
import type { ProductSummary } from '~/api/schemas';

/**
 * The banner slider at the top of the home: the deal of the day (a real
 * product at its usual price, never an invented discount) and two slides on
 * how the store works. A scroll-snap track, so touch swipes for free; the
 * arrows and dots scroll it. It advances on its own every few seconds,
 * pauses on hover, on focus and with its pause button, and never moves by
 * itself under `prefers-reduced-motion`.
 */
const props = defineProps<{
    deal?: ProductSummary;
    receipt?: ProductSummary;
    fan: ProductSummary[];
}>();

const INTERVAL_MS = 7000;

// Three different titles for the fanned covers (editions share a title).
const fanned = computed(() =>
    props.fan
        .filter(
            (product, index, all) =>
                all.findIndex((p) => p.title.name === product.title.name) ===
                index,
        )
        .slice(0, 3),
);

const slides = computed(() =>
    [
        props.deal ? 'deal' : null,
        'webpay',
        fanned.value.length ? 'library' : null,
    ].filter((slide): slide is string => slide !== null),
);

const track = ref<HTMLElement | null>(null);
const active = ref(0);
const playing = ref(true);
const hovered = ref(false);
const focused = ref(false);
let reducedMotion = false;
let timer: ReturnType<typeof setInterval> | undefined;

function go(index: number) {
    const el = track.value;
    if (!el) return;
    const count = slides.value.length;
    const target = ((index % count) + count) % count;
    el.scrollTo({
        left: target * el.clientWidth,
        behavior: reducedMotion ? 'auto' : 'smooth',
    });
}

function onScroll() {
    const el = track.value;
    if (!el || !el.clientWidth) return;
    active.value = Math.round(el.scrollLeft / el.clientWidth);
}

onMounted(() => {
    reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
    if (reducedMotion) playing.value = false;
    timer = setInterval(() => {
        if (playing.value && !hovered.value && !focused.value) {
            go(active.value + 1);
        }
    }, INTERVAL_MS);
});

onBeforeUnmount(() => clearInterval(timer));

function onFocusOut(event: FocusEvent) {
    const next = event.relatedTarget as Node | null;
    focused.value = !!next && !!track.value?.parentElement?.contains(next);
}

const control =
    'pressable grid size-11 place-items-center rounded-control border border-line bg-ink-2 text-chalk hover:border-crt hover:text-crt';
</script>

<template>
    <section
        aria-roledescription="carrusel"
        aria-label="Destacados de la tienda"
        class="relative overflow-hidden rounded-card border border-line bg-ink-2"
        @mouseenter="hovered = true"
        @mouseleave="hovered = false"
        @focusin="focused = true"
        @focusout="onFocusOut"
    >
        <div
            ref="track"
            class="flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain [scrollbar-width:none]"
            @scroll.passive="onScroll"
        >
            <div
                v-for="(slide, index) in slides"
                :key="slide"
                role="group"
                aria-roledescription="lámina"
                :aria-label="`${index + 1} de ${slides.length}`"
                :inert="index !== active"
                class="grid w-full shrink-0 snap-start items-center gap-6 px-5 pt-8 pb-20 md:min-h-96 md:grid-cols-[minmax(0,1fr)_auto] md:gap-10 md:px-10 md:pb-24"
            >
                <template v-if="slide === 'deal' && deal">
                    <div aria-labelledby="deal-heading" class="max-w-xl">
                        <h2
                            id="deal-heading"
                            class="flex items-center gap-2 font-display text-lg font-bold tracking-wide text-sticker uppercase"
                        >
                            Oferta del día
                            <span
                                class="flex items-center gap-1.5 font-mono text-sm font-normal tracking-normal text-chalk-muted normal-case"
                            >
                                <UiIcon name="clock" :size="16" />
                                <DealCountdown />
                            </span>
                        </h2>
                        <p
                            class="mt-2 text-4xl leading-none text-chalk uppercase md:text-6xl font-display font-bold"
                        >
                            <NuxtLink
                                :to="`/${deal.slug}`"
                                class="hover:text-sticker"
                                >{{ deal.title.name }}</NuxtLink
                            >
                        </p>
                        <p class="mt-3 text-chalk-muted">
                            {{ deal.type.name }} ·
                            {{ deal.platforms.map((p) => p.name).join(', ') }}.
                            Elegida para hoy, a su precio de siempre. Cambia a
                            medianoche.
                        </p>
                        <div class="mt-6 flex flex-wrap items-center gap-4">
                            <PriceSticker
                                :price="deal.price"
                                :in-stock="deal.in_stock"
                                size="lg"
                            />
                            <UiButton :to="`/${deal.slug}`" size="lg"
                                >Ver oferta</UiButton
                            >
                        </div>
                    </div>
                    <NuxtLink
                        :to="`/${deal.slug}`"
                        tabindex="-1"
                        aria-hidden="true"
                        class="w-56 justify-self-center max-md:hidden"
                    >
                        <CoverTilt :max="8">
                            <ProductCover :product="deal" size="lg" />
                        </CoverTilt>
                    </NuxtLink>
                </template>

                <template v-else-if="slide === 'webpay'">
                    <div class="max-w-xl">
                        <h2
                            class="text-4xl leading-none text-chalk uppercase md:text-6xl"
                        >
                            Paga con Webpay, en pesos chilenos
                        </h2>
                        <p class="mt-3 text-chalk-muted">
                            Débito, crédito o prepago. El precio que ves en la
                            vitrina es el que pagas.
                        </p>
                        <div class="mt-6">
                            <UiButton :to="catalogLink()" size="lg"
                                >Ver catálogo</UiButton
                            >
                        </div>
                    </div>
                    <ThermalReceipt
                        v-if="receipt"
                        title="Boleta"
                        aria-hidden="true"
                        class="w-64 rotate-2 py-5 shadow-receipt max-md:hidden"
                    >
                        <p class="mt-3 flex justify-between gap-3">
                            <span class="truncate">{{
                                receipt.title.name
                            }}</span>
                            <span class="tabular">{{
                                formatClp(receipt.price)
                            }}</span>
                        </p>
                        <p
                            class="mt-3 flex justify-between border-t border-dashed border-paper-line pt-3 font-bold"
                        >
                            <span>TOTAL CLP</span>
                            <span class="tabular">{{
                                formatClp(receipt.price)
                            }}</span>
                        </p>
                        <p class="mt-3 text-center text-xs text-paper-muted">
                            Webpay Plus · aprobado
                        </p>
                    </ThermalReceipt>
                </template>

                <template v-else-if="slide === 'library'">
                    <div class="max-w-xl">
                        <h2
                            class="text-4xl leading-none text-chalk uppercase md:text-6xl"
                        >
                            Tu clave, al instante en tu biblioteca
                        </h2>
                        <p class="mt-3 text-chalk-muted">
                            Al pagar, la clave queda guardada en Mi biblioteca y
                            te llega por correo. La revelas cuando la vayas a
                            activar.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <UiButton
                                :to="catalogLink({ type: ['digital_key'] })"
                                size="lg"
                                >Ver claves digitales</UiButton
                            >
                        </div>
                    </div>
                    <div
                        aria-hidden="true"
                        class="relative h-56 w-72 max-md:hidden"
                    >
                        <div
                            v-for="(product, i) in fanned"
                            :key="product.slug"
                            class="absolute bottom-0 w-36 shadow-case"
                            :class="
                                [
                                    'left-0 -rotate-6',
                                    'left-16 z-10 -translate-y-3',
                                    'left-32 rotate-6',
                                ][i]
                            "
                        >
                            <ProductCover :product="product" size="sm" />
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div
            v-if="slides.length > 1"
            class="absolute inset-x-5 bottom-4 flex items-center gap-3 md:inset-x-10"
        >
            <ul class="flex items-center gap-1">
                <li v-for="(slide, index) in slides" :key="slide">
                    <button
                        type="button"
                        class="group grid h-11 w-8 place-items-center"
                        :aria-label="`Ir a la lámina ${index + 1}`"
                        :aria-current="index === active ? 'true' : undefined"
                        @click="go(index)"
                    >
                        <span
                            class="h-1 w-6 rounded-full transition-colors duration-200"
                            :class="
                                index === active
                                    ? 'bg-sticker'
                                    : 'bg-line-strong group-hover:bg-chalk-faint'
                            "
                        />
                    </button>
                </li>
            </ul>
            <div class="ml-auto flex gap-2">
                <button
                    type="button"
                    :class="control"
                    :aria-label="
                        playing ? 'Pausar el carrusel' : 'Reanudar el carrusel'
                    "
                    @click="playing = !playing"
                >
                    <UiIcon :name="playing ? 'pause' : 'play'" :size="18" />
                </button>
                <button
                    type="button"
                    :class="control"
                    aria-label="Lámina anterior"
                    @click="go(active - 1)"
                >
                    <UiIcon name="chevronLeft" />
                </button>
                <button
                    type="button"
                    :class="control"
                    aria-label="Lámina siguiente"
                    @click="go(active + 1)"
                >
                    <UiIcon name="chevronRight" />
                </button>
            </div>
        </div>
    </section>
</template>
