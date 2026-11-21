<script setup lang="ts">
import { resendVerification } from '~/api/auth';
import { fetchOrders, placeOrder, startPayment } from '~/api/orders';

definePageMeta({ middleware: 'auth' });
useHead({ title: 'Pagar' });

const customer = useCustomer();
const { cart, lines, total, ready } = await useCartProducts();

const { data: orders } = await useAsyncData('checkout:orders', () =>
    fetchOrders(),
);
const pendingOrder = computed(
    () =>
        orders.value?.data.find((order) => order.status.slug === 'pending') ??
        null,
);
const hasPhysicalItems = computed(() =>
    lines.value.some((line) => line.product?.type.slug === 'physical'),
);

const errors = ref<string[]>([]);
const notice = ref('');
const pending = ref(false);

async function resend() {
    errors.value = [];

    try {
        await resendVerification();
        notice.value = 'Te enviamos un nuevo enlace de verificación.';
    } catch (error) {
        errors.value = formErrorMessages(error);
    }
}

async function pay() {
    pending.value = true;
    errors.value = [];
    let reference: string | null = null;

    try {
        const order = await placeOrder(
            cart.lines.value.map(({ slug, quantity }) => ({ slug, quantity })),
        );
        reference = order.reference;
        cart.clear();

        const redirect = await startPayment(order.reference);
        postToWebpay(redirect.url, redirect.token);
    } catch (error) {
        pending.value = false;

        if (reference) {
            // The order exists: its page lets the customer retry or cancel.
            await navigateTo(`/pedidos/${reference}?estado=error`);
            return;
        }

        errors.value = formErrorMessages(error);
    }
}
</script>

<template>
    <main v-if="customer" class="mx-auto max-w-3xl px-4 py-8 md:py-10">
        <h1 class="text-5xl text-chalk uppercase md:text-6xl">Pagar</h1>

        <div class="mt-6 flex flex-col gap-3">
            <FormErrors :messages="errors" />
            <p
                v-if="notice"
                role="status"
                class="rounded-card border border-ok/50 bg-ok-deep px-4 py-3 text-chalk"
            >
                {{ notice }}
            </p>
        </div>

        <EmptyState
            v-if="lines.length === 0"
            message="Tu carrito está vacío."
            class="mt-6"
        >
            <UiButton :to="catalogLink()" variant="primary"
                >Ver el catálogo</UiButton
            >
        </EmptyState>
        <ThermalReceipt v-else title="Resumen" class="mt-6">
            <h2 class="sr-only">Resumen</h2>
            <ul class="flex flex-col gap-3 py-4">
                <li
                    v-for="line in lines"
                    :key="line.slug"
                    class="flex justify-between gap-4"
                >
                    <span class="min-w-0">
                        {{ line.quantity }} ×
                        {{ line.product?.title.name ?? line.slug }}
                        <span
                            v-if="line.product"
                            class="block text-xs text-paper-muted"
                            >{{ variantFacts(line.product) }}</span
                        >
                        <strong v-if="!line.available" class="text-board-red">
                            No disponible</strong
                        >
                    </span>
                    <span
                        v-if="line.product"
                        class="tabular flex shrink-0 flex-col items-end"
                        ><span>{{
                            formatClp(line.product.price * line.quantity)
                        }}</span
                        ><span
                            v-if="line.quantity > 1"
                            class="text-xs text-paper-muted"
                            >{{ line.quantity }} ×
                            {{ formatClp(line.product.price) }}</span
                        ></span
                    >
                </li>
            </ul>

            <section
                aria-labelledby="total-heading"
                class="flex flex-col gap-3 border-t border-dashed border-paper-line pt-4"
            >
                <p class="flex items-baseline justify-between">
                    <span
                        id="total-heading"
                        role="heading"
                        aria-level="2"
                        class="font-semibold"
                        >Total</span
                    >
                    <strong class="tabular text-base">{{
                        formatClp(total)
                    }}</strong>
                </p>
                <WithdrawalNotice
                    :has-physical-items="hasPhysicalItems"
                    class="rounded-control border border-paper-line p-3 text-paper-ink"
                />
            </section>

            <div class="mt-5 font-sans">
                <p v-if="!ready" class="text-paper-ink">
                    Hay productos no disponibles.
                    <NuxtLink to="/carrito" class="underline"
                        >Revisa tu carrito</NuxtLink
                    >.
                </p>
                <section
                    v-else-if="!customer.email_verified"
                    aria-labelledby="verify-heading"
                    class="flex flex-col items-start gap-3"
                >
                    <h2
                        id="verify-heading"
                        class="text-2xl text-paper-ink uppercase"
                    >
                        Verifica tu correo
                    </h2>
                    <p class="text-paper-ink">
                        Para pagar necesitas verificar tu correo: ahí te
                        enviamos las claves. Revisa el enlace que mandamos a
                        {{ customer.email }}.
                    </p>
                    <UiButton variant="primary" @click="resend"
                        >Reenviar el correo</UiButton
                    >
                </section>
                <p v-else-if="pendingOrder" class="text-paper-ink">
                    Tienes un pedido pendiente:
                    <NuxtLink
                        :to="`/pedidos/${pendingOrder.reference}`"
                        class="font-mono font-semibold underline"
                    >
                        {{ pendingOrder.reference }}
                    </NuxtLink>
                    . Págalo o cancélalo antes de hacer otro.
                </p>
                <form v-else aria-label="Pagar" @submit.prevent="pay">
                    <UiButton
                        type="submit"
                        variant="primary"
                        size="lg"
                        class="w-full"
                        :disabled="pending"
                    >
                        <UiIcon name="lock" />{{
                            pending
                                ? 'Conectando con Webpay…'
                                : 'Pagar con Webpay'
                        }}
                    </UiButton>
                </form>
            </div>
        </ThermalReceipt>
    </main>
</template>
