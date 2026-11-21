<script setup lang="ts">
const customer = useCustomer();
const cart = useCart();
const menu = ref<{ open: () => void } | null>(null);

// The API has no name search yet (phase 6): the box says so instead of
// pretending to search.
const searchNote = ref(false);
</script>

<template>
    <header class="border-b border-line bg-ink md:border-b-0">
        <div
            class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-4 md:flex-nowrap md:py-5"
        >
            <button
                type="button"
                class="pressable -ml-2 grid size-10 place-items-center rounded-control text-chalk-muted hover:text-chalk md:hidden"
                aria-label="Abrir menú"
                @click="menu?.open()"
            >
                <UiIcon name="menu" :size="22" />
            </button>

            <SiteLogo />

            <form
                role="search"
                class="order-last w-full md:order-none md:mx-auto md:max-w-md md:flex-1"
                @submit.prevent="searchNote = true"
            >
                <label class="sr-only" for="site-search">Buscar</label>
                <div
                    class="flex h-11 items-center rounded-control border border-line bg-panel focus-within:border-crt"
                >
                    <input
                        id="site-search"
                        type="search"
                        name="q"
                        placeholder="Busca un juego o un programa"
                        autocomplete="off"
                        class="h-full min-w-0 flex-1 bg-transparent px-3 text-chalk placeholder:text-chalk-faint focus:outline-none"
                        @input="searchNote = false"
                    />
                    <button
                        type="submit"
                        class="pressable grid h-full w-11 place-items-center text-chalk-muted hover:text-chalk"
                        aria-label="Buscar"
                    >
                        <UiIcon name="search" />
                    </button>
                </div>
                <p
                    v-if="searchNote"
                    role="status"
                    class="mt-2 text-sm text-chalk-muted"
                >
                    La búsqueda por nombre llega pronto. Mientras,
                    <NuxtLink
                        :to="catalogLink()"
                        class="text-crt underline hover:text-chalk"
                        >filtra el catálogo</NuxtLink
                    >.
                </p>
            </form>

            <nav
                aria-label="Tu cuenta"
                class="ml-auto flex items-center gap-1 md:ml-0"
            >
                <NuxtLink
                    v-if="customer"
                    to="/biblioteca"
                    class="hidden h-10 items-center gap-2 rounded-control px-3 text-chalk-muted hover:text-chalk lg:flex"
                    active-class="text-chalk"
                >
                    <UiIcon name="library" />Mi biblioteca</NuxtLink
                >
                <NuxtLink
                    v-if="customer"
                    to="/cuenta"
                    class="hidden h-10 max-w-48 items-center gap-2 truncate rounded-control px-3 text-chalk-muted hover:text-chalk sm:flex"
                    active-class="text-chalk"
                    ><UiIcon name="user" />{{ customer.name }}</NuxtLink
                >
                <NuxtLink
                    v-else
                    to="/cuenta/ingresar"
                    class="hidden h-10 items-center gap-2 rounded-control px-3 text-chalk-muted hover:text-chalk sm:flex"
                    active-class="text-chalk"
                    ><UiIcon name="user" />Ingresar</NuxtLink
                >
                <NuxtLink
                    to="/carrito"
                    class="pressable relative flex h-10 items-center gap-2 rounded-control border border-line-strong bg-panel px-3 font-display font-bold tracking-wide text-chalk uppercase hover:border-chalk-faint"
                    :aria-label="`Carrito, ${cart.count.value} ${cart.count.value === 1 ? 'producto' : 'productos'}`"
                >
                    <UiIcon name="cart" />
                    <span class="hidden sm:inline">Carrito</span>
                    <span
                        class="tabular grid h-6 min-w-6 place-items-center rounded-control px-1.5 text-sm"
                        :class="
                            cart.count.value > 0
                                ? 'bg-sticker text-sticker-ink'
                                : 'bg-panel-2 text-chalk-muted'
                        "
                        >{{ cart.count.value }}</span
                    >
                </NuxtLink>
            </nav>
        </div>

        <SiteNav class="hidden md:block" />
        <SiteMobileMenu ref="menu" />
    </header>
</template>
