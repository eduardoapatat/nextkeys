<script setup lang="ts">
/**
 * Frequently asked questions, as native disclosure widgets. Every answer
 * restates a rule from the domain docs (orders.md, payments.md,
 * catalog.md); change them together.
 */
const questions: { q: string; a: string; id?: string }[] = [
    {
        id: 'activar',
        q: '¿Cómo activo mi clave?',
        a: 'Cada producto dice dónde se activa (Steam, PlayStation Store, Xbox, Nintendo eShop o la cuenta del programa). Entra a esa tienda con tu cuenta, busca la opción para canjear un código y pega la clave que está en Mi biblioteca y en tu correo.',
    },
    {
        q: '¿Cuándo recibo mi clave?',
        a: 'Apenas se aprueba el pago. La clave llega a tu correo y queda guardada en Mi biblioteca, donde la revelas cuando la vayas a activar.',
    },
    {
        q: '¿Qué significa la región (LATAM, Global…)?',
        a: 'En claves y licencias, la región indica dónde se puede activar: una clave LATAM necesita una cuenta de esa región y una Global funciona en cualquiera. En juegos físicos es solo informativa: las consolas actuales no bloquean discos por región.',
    },
    {
        q: '¿Puedo devolver una clave?',
        a: 'Las claves digitales entregadas no tienen derecho a retracto, y te lo avisamos antes de pagar. Sí tienen garantía legal: si una clave no funciona, escríbenos. Los juegos físicos tienen 10 días de retracto desde que los recibes.',
    },
    {
        q: '¿Cómo pago?',
        a: 'Con Webpay Plus, en pesos chilenos: débito, crédito o prepago. El precio que ves en la tienda es el que pagas.',
    },
    {
        q: '¿Cuánto tiempo tengo para pagar?',
        a: 'Al confirmar el pedido reservamos tus claves y juegos por 15 minutos. Si no pagas en ese plazo, el pedido vence y el stock se libera.',
    },
    {
        q: '¿Cómo llegan los juegos físicos?',
        a: 'Se despachan después del pago; el pedido queda pagado hasta entonces. Si el pedido también tiene claves, esas llegan al instante.',
    },
];

// A link to one question (the footer's "/#activar") opens it.
const route = useRoute();
const open = ref<string>();
onMounted(() => {
    watch(
        () => route.hash,
        (hash) => (open.value = hash.slice(1) || undefined),
        { immediate: true },
    );
});
</script>

<template>
    <section id="preguntas" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="text-4xl text-fg uppercase md:text-5xl">
            Preguntas frecuentes
        </h2>
        <div class="mt-6 flex flex-col gap-0.5">
            <details
                v-for="item in questions"
                :id="item.id"
                :key="item.q"
                :open="item.id !== undefined && item.id === open"
                class="group"
            >
                <summary
                    class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 rounded-control bg-brand px-5 py-4 text-lg font-bold text-on-cover transition-colors duration-150 hover:bg-brand-strong md:px-6 [&::-webkit-details-marker]:hidden"
                >
                    {{ item.q }}
                    <UiIcon
                        name="chevronRight"
                        :size="20"
                        class="rotate-90 transition-transform duration-200 ease-out group-open:-rotate-90"
                    />
                </summary>
                <p class="px-5 py-5 text-fg-muted md:px-6">{{ item.a }}</p>
            </details>
        </div>
    </section>
</template>
