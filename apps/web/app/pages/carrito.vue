<script setup lang="ts">
useHead({ title: 'Carrito' });

const {
    cart,
    lines,
    total,
    pending,
    ready: canCheckout,
} = await useCartProducts();
</script>

<template>
    <main class="mx-auto max-w-7xl px-4 py-8 md:py-10">
        <h1 class="text-5xl text-chalk uppercase md:text-6xl">Carrito</h1>

        <EmptyState
            v-if="lines.length === 0"
            message="Tu carrito está vacío."
            class="mt-8"
        >
            <UiButton :to="catalogLink()" variant="primary"
                >Ver el catálogo</UiButton
            >
        </EmptyState>
        <div
            v-else
            class="mt-8 grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]"
        >
            <ul class="flex flex-col border-t border-line">
                <li
                    v-for="line in lines"
                    :key="line.slug"
                    class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-4 border-b border-line py-5 sm:grid-cols-[5.5rem_minmax(0,1fr)_auto]"
                >
                    <NuxtLink
                        v-if="line.product"
                        :to="`/${line.slug}`"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                        <ProductCover :product="line.product" size="sm" />
                    </NuxtLink>
                    <div
                        v-else
                        aria-hidden="true"
                        class="aspect-3/4 rounded-card border border-dashed border-line-strong"
                    />

                    <div class="flex min-w-0 flex-col gap-1">
                        <template v-if="line.product">
                            <NuxtLink
                                :to="`/${line.slug}`"
                                class="font-semibold text-chalk hover:text-sticker"
                            >
                                {{ line.product.title.name }}
                            </NuxtLink>
                            <p class="text-sm text-chalk-muted">
                                {{ variantLabel(line.product) }}
                            </p>
                            <p
                                v-if="!line.available"
                                class="text-sm text-danger"
                            >
                                <strong>No disponible</strong> (sin stock)
                            </p>
                        </template>
                        <template v-else>
                            <p class="font-mono text-sm text-chalk-muted">
                                {{ line.slug }}
                            </p>
                            <p class="text-sm text-danger">
                                <strong>No disponible</strong> (ya no está a la
                                venta)
                            </p>
                        </template>
                        <div class="mt-2 flex items-center gap-3">
                            <select
                                :name="`quantity-${line.slug}`"
                                :aria-label="`Cantidad de ${line.product?.title.name ?? line.slug}`"
                                :value="line.quantity"
                                class="tabular h-9 rounded-control border border-line-strong bg-panel px-2 text-chalk focus:border-crt focus:outline-none"
                                @change="
                                    cart.update(
                                        line.slug,
                                        Number(
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        ),
                                    )
                                "
                            >
                                <option
                                    v-for="n in maxQuantityPerLine"
                                    :key="n"
                                    :value="n"
                                >
                                    {{ n }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="pressable text-sm text-chalk-muted underline hover:text-danger"
                                :aria-label="`Quitar ${line.product?.title.name ?? line.slug}`"
                                @click="cart.remove(line.slug)"
                            >
                                Quitar
                            </button>
                        </div>
                    </div>

                    <p
                        class="tabular col-start-2 text-right font-display text-2xl font-bold text-chalk sm:col-start-auto"
                    >
                        {{
                            line.product
                                ? formatClp(line.product.price * line.quantity)
                                : '—'
                        }}
                    </p>
                </li>
            </ul>

            <ThermalReceipt title="Detalle" class="lg:sticky lg:top-6">
                <ul class="flex flex-col gap-1 py-3">
                    <li
                        v-for="line in lines"
                        :key="line.slug"
                        class="flex justify-between gap-3"
                    >
                        <span class="truncate"
                            >{{ line.quantity }} ×
                            {{ line.product?.title.name ?? line.slug }}</span
                        >
                        <span class="tabular shrink-0">{{
                            line.product
                                ? formatClp(line.product.price * line.quantity)
                                : '—'
                        }}</span>
                    </li>
                </ul>
                <p
                    class="flex items-baseline justify-between border-t border-dashed border-paper-line pt-3 text-base font-semibold"
                >
                    Total estimado
                    <strong class="tabular text-xl">{{
                        formatClp(total)
                    }}</strong>
                </p>
                <p class="mt-2 text-xs text-paper-muted">
                    Los precios y el stock se confirman al crear el pedido.
                </p>
                <UiButton
                    v-if="canCheckout"
                    to="/checkout"
                    variant="primary"
                    size="lg"
                    class="mt-5 w-full font-display"
                    >Ir a pagar</UiButton
                >
                <p
                    v-else-if="!pending"
                    class="mt-5 rounded-control border border-paper-line p-3 font-sans text-paper-ink"
                >
                    Quita los productos no disponibles para continuar.
                </p>
            </ThermalReceipt>
        </div>
    </main>
</template>
