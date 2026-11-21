<script setup lang="ts">
import { cancelOrder, fetchOrder, startPayment } from '~/api/orders';

definePageMeta({ middleware: 'auth' });

const route = useRoute();
const reference = computed(() => String(route.params.reference));
useHead({ title: () => `Pedido ${reference.value}` });

const { data: order, error } = await useAsyncData(
    () => `order:${reference.value}`,
    () => fetchOrder(reference.value),
);

if (error.value) {
    throw createError(
        toApiError(error.value).statusCode === 404
            ? {
                  statusCode: 404,
                  statusMessage: 'Pedido no encontrado',
                  fatal: true,
              }
            : { statusCode: 502, fatal: true },
    );
}

/**
 * What the page shows. `?estado` from the Webpay return only adds wording:
 * the order from the API is the truth.
 */
const view = computed(() => {
    const current = order.value;

    if (!current) {
        return null;
    }

    if (['paid', 'completed'].includes(current.status.slug)) {
        return 'paid';
    }

    if (current.status.slug !== 'pending') {
        return 'closed';
    }

    return current.last_payment?.status.slug === 'rejected'
        ? 'rejected'
        : 'unpaid';
});

const errors = ref<string[]>([]);
const pending = ref(false);

async function retry() {
    pending.value = true;
    errors.value = [];

    try {
        const redirect = await startPayment(reference.value);
        postToWebpay(redirect.url, redirect.token);
    } catch (failure) {
        errors.value = formErrorMessages(failure);
        pending.value = false;
    }
}

async function cancel() {
    pending.value = true;
    errors.value = [];

    try {
        order.value = await cancelOrder(reference.value);
    } catch (failure) {
        errors.value = formErrorMessages(failure);
    } finally {
        pending.value = false;
    }
}
</script>

<template>
    <main v-if="order">
        <h1>Pedido {{ order.reference }}</h1>
        <FormErrors :messages="errors" />

        <template v-if="view === 'paid'">
            <p role="status"><strong>Pago aprobado.</strong></p>
            <p>
                Te enviamos las claves a tu correo. También están en
                <NuxtLink to="/biblioteca">Mi biblioteca</NuxtLink>.
                <template v-if="order.has_physical_items">
                    Los productos físicos se despachan por separado.
                </template>
            </p>

            <h2>Comprobante</h2>
            <dl>
                <dt>Comercio</dt>
                <dd>NextKeys</dd>
                <dt>Orden de compra</dt>
                <dd>{{ order.reference }}</dd>
                <dt>Monto</dt>
                <dd>{{ formatClp(order.total) }} (CLP)</dd>
                <template v-if="order.last_payment">
                    <dt>Código de autorización</dt>
                    <dd>{{ order.last_payment.authorization_code }}</dd>
                    <dt>Fecha</dt>
                    <dd>
                        {{
                            order.last_payment.transaction_date
                                ? formatDateTime(
                                      order.last_payment.transaction_date,
                                  )
                                : '—'
                        }}
                    </dd>
                    <dt>Tipo de pago</dt>
                    <dd>{{ order.last_payment.payment_type }}</dd>
                    <template v-if="order.last_payment.installments_number">
                        <dt>Cuotas</dt>
                        <dd>
                            {{ order.last_payment.installments_number }}
                            <template
                                v-if="order.last_payment.installments_amount"
                            >
                                de
                                {{
                                    formatClp(
                                        order.last_payment.installments_amount,
                                    )
                                }}
                            </template>
                        </dd>
                    </template>
                    <dt>Tarjeta</dt>
                    <dd>**** {{ order.last_payment.card_last_digits }}</dd>
                </template>
            </dl>
        </template>

        <template v-else-if="view === 'rejected'">
            <p role="alert">
                <strong
                    >Orden de Compra {{ order.reference }} rechazada.</strong
                >
            </p>
            <p>Las posibles causas de este rechazo son:</p>
            <ul>
                <li>Error en el ingreso de los datos de tu tarjeta.</li>
                <li>Tu tarjeta no tiene saldo o cupo suficiente.</li>
                <li>
                    Tu tarjeta no está habilitada para compras por internet.
                </li>
            </ul>
        </template>

        <template v-else-if="view === 'unpaid'">
            <p v-if="route.query.estado === 'error'" role="alert">
                No pudimos iniciar el pago con Webpay.
            </p>
            <p v-else-if="order.last_payment" role="status">
                El pago no se completó ({{ order.last_payment.status.name }}).
                No se hizo ningún cargo.
            </p>
            <p>
                Tu pedido está pendiente: reservamos el stock hasta las
                {{ formatDateTime(order.expires_at) }}.
            </p>
        </template>

        <template v-else>
            <p role="status">
                <strong>Pedido {{ order.status.name.toLowerCase() }}.</strong>
                No se hizo ningún cargo.
            </p>
            <p><NuxtLink :to="catalogLink()">Volver al catálogo</NuxtLink></p>
        </template>

        <p v-if="order.status.slug === 'pending'">
            <button
                v-if="order.can_pay"
                type="button"
                data-action="retry"
                :disabled="pending"
                @click="retry"
            >
                {{
                    order.last_payment
                        ? 'Reintentar el pago'
                        : 'Pagar con Webpay'
                }}
            </button>
            <button
                type="button"
                data-action="cancel"
                :disabled="pending"
                @click="cancel"
            >
                Cancelar el pedido
            </button>
        </p>

        <h2>Lo que compraste</h2>
        <ul>
            <li v-for="item in order.items" :key="item.product_slug">
                {{ item.quantity }} ×
                <NuxtLink :to="`/${item.product_slug}`">{{
                    item.title_name
                }}</NuxtLink>
                ({{ item.type.name }}) · {{ formatClp(item.line_total) }}
            </li>
        </ul>
        <p>Total: {{ formatClp(order.total) }}</p>

        <p><NuxtLink to="/pedidos">Mis pedidos</NuxtLink></p>
    </main>
</template>
