<script setup lang="ts">
const customer = useCustomer();

// Static example: there is no newsletter service behind this form.
const subscribed = ref(false);

const columns = computed(() => [
    {
        title: 'Tienda',
        links: [
            { label: 'Catálogo', to: catalogLink() },
            ...productTypeLinks.map((item) => ({
                label: item.label,
                to: catalogLink({ type: [...item.type] }),
            })),
        ],
    },
    {
        title: 'Tu cuenta',
        links: customer.value
            ? [
                  { label: 'Mi cuenta', to: '/cuenta' },
                  { label: 'Mis pedidos', to: '/pedidos' },
                  { label: 'Mi biblioteca', to: '/biblioteca' },
                  { label: 'Carrito', to: '/carrito' },
              ]
            : [
                  { label: 'Ingresar', to: '/cuenta/ingresar' },
                  { label: 'Crear cuenta', to: '/cuenta/registro' },
                  { label: 'Carrito', to: '/carrito' },
              ],
    },
    {
        title: 'Ayuda',
        links: [
            { label: 'Cómo activar una clave', to: '/#activar' },
            { label: 'Preguntas frecuentes', to: '/#preguntas' },
            { label: 'Contacto', href: '#' },
        ],
    },
    {
        title: 'Legal',
        links: [
            { label: 'Derecho a retracto', href: '#' },
            { label: 'Términos y condiciones', href: '#' },
            { label: 'Privacidad', href: '#' },
        ],
    },
]);
</script>

<template>
    <footer class="surface-brand mt-24 bg-brand-deep">
        <section aria-labelledby="newsletter-heading" class="bg-bg">
            <div
                class="mx-auto grid max-w-7xl gap-6 px-4 py-10 md:grid-cols-[1fr_minmax(0,28rem)] md:items-center"
            >
                <div>
                    <h2
                        id="newsletter-heading"
                        class="text-3xl text-fg uppercase"
                    >
                        Lo nuevo de la vitrina, cada viernes
                    </h2>
                    <p class="mt-2 max-w-prose text-fg-muted">
                        Lanzamientos, reposiciones y la oferta del día en un
                        solo correo.
                    </p>
                </div>
                <form
                    aria-label="Suscribirse al boletín"
                    class="flex flex-col gap-2"
                    @submit.prevent="subscribed = true"
                >
                    <div class="flex gap-2">
                        <label class="sr-only" for="newsletter-email"
                            >Correo electrónico</label
                        >
                        <input
                            id="newsletter-email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="tu@correo.cl"
                            class="h-11 min-w-0 flex-1 rounded-control border border-line bg-surface px-3 text-fg placeholder:text-fg-faint focus:border-brand focus:outline-none"
                        />
                        <UiButton type="submit" variant="primary"
                            >Suscribirme</UiButton
                        >
                    </div>
                    <p
                        v-if="subscribed"
                        role="status"
                        class="text-sm text-fg-muted"
                    >
                        Es una demo: no guardamos tu correo.
                    </p>
                </form>
            </div>
        </section>

        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_repeat(4,1fr)]"
        >
            <div class="flex flex-col gap-4">
                <SiteLogo />
                <p class="max-w-xs text-sm text-fg-muted">
                    Claves digitales, licencias y juegos físicos, en pesos
                    chilenos y con pago Webpay.
                </p>
            </div>
            <nav
                v-for="column in columns"
                :key="column.title"
                :aria-label="column.title"
            >
                <h2
                    class="font-display text-sm font-extrabold tracking-wider text-fg-faint uppercase"
                >
                    {{ column.title }}
                </h2>
                <ul class="mt-3 flex flex-col gap-2">
                    <li v-for="item in column.links" :key="item.label">
                        <NuxtLink
                            v-if="'to' in item"
                            :to="item.to"
                            class="text-fg-muted hover:text-fg"
                            >{{ item.label }}</NuxtLink
                        >
                        <a
                            v-else
                            :href="item.href"
                            class="text-fg-muted hover:text-fg"
                            >{{ item.label }}</a
                        >
                    </li>
                </ul>
            </nav>
        </div>

        <div class="border-t border-line">
            <p
                class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-6 text-sm text-fg-faint sm:flex-row sm:justify-between"
            >
                <span
                    >NextKeys es un proyecto de portafolio: los productos, los
                    datos y los pagos son ficticios.</span
                >
                <span>Pagos con Webpay Plus en ambiente de integración.</span>
            </p>
        </div>
    </footer>
</template>
