<script setup lang="ts">
import type { IconName } from '~/components/ui/Icon.vue';

// Each line is true of this store (docs/domain/orders.md and payments.md).
const benefits: { icon: IconName; title: string; text: string }[] = [
    {
        icon: 'bolt',
        title: 'Entrega inmediata',
        text: 'Las claves llegan a tu correo y quedan en Mi biblioteca.',
    },
    {
        icon: 'lock',
        title: 'Pago con Webpay',
        text: 'En pesos chilenos, con débito o crédito.',
    },
    {
        icon: 'globe',
        title: 'Región y activación claras',
        text: 'Cada producto dice dónde funciona y dónde se activa.',
    },
    {
        icon: 'shield',
        title: 'Garantía legal',
        text: 'Las claves entregadas no tienen retracto, pero sí garantía.',
    },
];
</script>

<template>
    <section aria-labelledby="benefits-heading">
        <h2 id="benefits-heading" class="sr-only">Por qué comprar aquí</h2>
        <ul class="grid gap-3 sm:grid-cols-2 md:gap-4 lg:grid-cols-4">
            <li
                v-for="benefit in benefits"
                :key="benefit.title"
                class="flex flex-col gap-4 rounded-card border border-line bg-bg-subtle p-5"
            >
                <span
                    class="grid size-12 place-items-center rounded-card bg-brand text-on-cover"
                >
                    <UiIcon :name="benefit.icon" :size="24" />
                </span>
                <p>
                    <strong
                        class="block font-display text-xl font-extrabold tracking-wide text-fg uppercase"
                        >{{ benefit.title }}</strong
                    >
                    <span class="mt-1 block text-fg-muted">{{
                        benefit.text
                    }}</span>
                </p>
            </li>
        </ul>
    </section>
</template>
