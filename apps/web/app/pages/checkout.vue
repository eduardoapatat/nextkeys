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
    <main v-if="customer">
        <h1>Pagar</h1>

        <FormErrors :messages="errors" />
        <p v-if="notice" role="status">{{ notice }}</p>

        <p v-if="lines.length === 0">
            Tu carrito está vacío.
            <NuxtLink :to="catalogLink()">Ver el catálogo</NuxtLink>
        </p>
        <template v-else>
            <h2>Resumen</h2>
            <ul>
                <li v-for="line in lines" :key="line.slug">
                    {{ line.quantity }} ×
                    {{ line.product?.title.name ?? line.slug }}
                    <template v-if="line.product">
                        ({{ variantLabel(line.product) }}) ·
                        {{ formatClp(line.product.price * line.quantity) }}
                    </template>
                    <strong v-if="!line.available"> No disponible</strong>
                </li>
            </ul>

            <section aria-labelledby="total-heading">
                <h2 id="total-heading">Total</h2>
                <p>
                    <strong>{{ formatClp(total) }}</strong>
                </p>
                <WithdrawalNotice :has-physical-items="hasPhysicalItems" />
            </section>

            <p v-if="!ready">
                Hay productos no disponibles.
                <NuxtLink to="/carrito">Revisa tu carrito</NuxtLink>.
            </p>
            <section
                v-else-if="!customer.email_verified"
                aria-labelledby="verify-heading"
            >
                <h2 id="verify-heading">Verifica tu correo</h2>
                <p>
                    Para pagar necesitas verificar tu correo: ahí te enviamos
                    las claves. Revisa el enlace que mandamos a
                    {{ customer.email }}.
                </p>
                <button type="button" @click="resend">
                    Reenviar el correo
                </button>
            </section>
            <p v-else-if="pendingOrder">
                Tienes un pedido pendiente:
                <NuxtLink :to="`/pedidos/${pendingOrder.reference}`">
                    {{ pendingOrder.reference }}
                </NuxtLink>
                . Págalo o cancélalo antes de hacer otro.
            </p>
            <form v-else aria-label="Pagar" @submit.prevent="pay">
                <button type="submit" :disabled="pending">
                    Pagar con Webpay
                </button>
            </form>
        </template>
    </main>
</template>
