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
    <main v-if="order" class="mx-auto max-w-3xl px-4 py-8 md:py-10">
        <NuxtLink
            to="/pedidos"
            class="inline-flex items-center gap-1 text-sm text-chalk-muted hover:text-chalk"
            ><UiIcon name="chevronLeft" :size="16" />Mis pedidos</NuxtLink
        >
        <h1 class="mt-4 text-5xl text-chalk uppercase md:text-6xl">
            Pedido
            <span class="font-mono text-4xl md:text-5xl">{{
                order.reference
            }}</span>
        </h1>
        <FormErrors :messages="errors" class="mt-6" />

        <div class="mt-6 flex flex-col gap-6">
            <template v-if="view === 'paid'">
                <div
                    class="flex flex-col gap-2 rounded-card border px-5 py-4 border-ok/50 bg-ok-deep text-chalk"
                >
                    <p
                        role="status"
                        class="flex items-center gap-2 font-display text-2xl font-bold uppercase"
                    >
                        <UiIcon
                            name="check"
                            :size="24"
                            class="text-ok"
                        /><strong>Pago aprobado.</strong>
                    </p>
                    <p class="text-chalk-muted">
                        Te enviamos las claves a tu correo. También están en
                        <NuxtLink to="/biblioteca" class="text-crt underline"
                            >Mi biblioteca</NuxtLink
                        >.
                        <template v-if="order.has_physical_items">
                            Los productos físicos se despachan por separado.
                        </template>
                    </p>
                </div>

                <ThermalReceipt title="Comprobante de pago">
                    <h2 class="sr-only">Comprobante</h2>
                    <dl class="flex flex-col py-3">
                        <div class="flex justify-between gap-4 py-1">
                            <dt>Comercio</dt>
                            <dd>NextKeys</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-1">
                            <dt>Orden de compra</dt>
                            <dd>{{ order.reference }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-1">
                            <dt>Monto</dt>
                            <dd class="tabular font-semibold">
                                {{ formatClp(order.total) }} (CLP)
                            </dd>
                        </div>
                        <template v-if="order.last_payment">
                            <div class="flex justify-between gap-4 py-1">
                                <dt>Código de autorización</dt>
                                <dd>
                                    {{ order.last_payment.authorization_code }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-1">
                                <dt>Fecha</dt>
                                <dd>
                                    {{
                                        order.last_payment.transaction_date
                                            ? formatDateTime(
                                                  order.last_payment
                                                      .transaction_date,
                                              )
                                            : '—'
                                    }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-1">
                                <dt>Tipo de pago</dt>
                                <dd>{{ order.last_payment.payment_type }}</dd>
                            </div>
                            <div
                                v-if="order.last_payment.installments_number"
                                class="flex justify-between gap-4 py-1"
                            >
                                <dt>Cuotas</dt>
                                <dd>
                                    {{ order.last_payment.installments_number }}
                                    <template
                                        v-if="
                                            order.last_payment
                                                .installments_amount
                                        "
                                    >
                                        de
                                        {{
                                            formatClp(
                                                order.last_payment
                                                    .installments_amount,
                                            )
                                        }}
                                    </template>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-1">
                                <dt>Tarjeta</dt>
                                <dd>
                                    ****
                                    {{ order.last_payment.card_last_digits }}
                                </dd>
                            </div>
                        </template>
                    </dl>
                </ThermalReceipt>
            </template>

            <div
                v-else-if="view === 'rejected'"
                class="flex flex-col gap-2 rounded-card border px-5 py-4 border-danger/50 bg-danger-deep text-chalk"
            >
                <p
                    role="alert"
                    class="font-display text-2xl font-bold uppercase"
                >
                    <strong
                        >Orden de Compra
                        {{ order.reference }} rechazada.</strong
                    >
                </p>
                <p class="text-chalk-muted">
                    Las posibles causas de este rechazo son:
                </p>
                <ul class="list-disc pl-5 text-chalk-muted">
                    <li>Error en el ingreso de los datos de tu tarjeta.</li>
                    <li>Tu tarjeta no tiene saldo o cupo suficiente.</li>
                    <li>
                        Tu tarjeta no está habilitada para compras por internet.
                    </li>
                </ul>
            </div>

            <div
                v-else-if="view === 'unpaid'"
                class="flex flex-col gap-2 rounded-card border px-5 py-4 border-crt/40 bg-crt-deep text-chalk"
            >
                <p
                    v-if="route.query.estado === 'error'"
                    role="alert"
                    class="font-semibold"
                >
                    No pudimos iniciar el pago con Webpay.
                </p>
                <p
                    v-else-if="order.last_payment"
                    role="status"
                    class="font-semibold"
                >
                    El pago no se completó ({{
                        order.last_payment.status.name
                    }}). No se hizo ningún cargo.
                </p>
                <p class="text-chalk-muted">
                    Tu pedido está pendiente: reservamos el stock hasta las
                    <time class="tabular text-chalk">{{
                        formatDateTime(order.expires_at)
                    }}</time>.
                </p>
            </div>

            <div
                v-else
                class="flex flex-col gap-2 rounded-card border px-5 py-4 border-line-strong bg-panel text-chalk"
            >
                <p role="status">
                    <strong class="font-display text-2xl uppercase"
                        >Pedido {{ order.status.name.toLowerCase() }}.</strong
                    >
                    No se hizo ningún cargo.
                </p>
                <UiButton :to="catalogLink()" size="sm" class="self-start"
                    >Volver al catálogo</UiButton
                >
            </div>

            <div
                v-if="order.status.slug === 'pending'"
                class="flex flex-wrap gap-3"
            >
                <UiButton
                    v-if="order.can_pay"
                    variant="primary"
                    size="lg"
                    data-action="retry"
                    :disabled="pending"
                    @click="retry"
                >
                    <UiIcon name="lock" />{{
                        order.last_payment
                            ? 'Reintentar el pago'
                            : 'Pagar con Webpay'
                    }}
                </UiButton>
                <UiButton
                    size="lg"
                    data-action="cancel"
                    :disabled="pending"
                    @click="cancel"
                >
                    Cancelar el pedido
                </UiButton>
            </div>

            <section
                aria-labelledby="items-heading"
                class="rounded-card border border-line bg-ink-2 p-5"
            >
                <h2 id="items-heading" class="text-2xl text-chalk uppercase">
                    Lo que compraste
                </h2>
                <ul class="mt-3 flex flex-col divide-y divide-line">
                    <li
                        v-for="item in order.items"
                        :key="item.product_slug"
                        class="flex justify-between gap-4 py-3"
                    >
                        <span class="min-w-0">
                            {{ item.quantity }} ×
                            <NuxtLink
                                :to="`/${item.product_slug}`"
                                class="font-semibold text-chalk hover:text-sticker"
                                >{{ item.title_name }}</NuxtLink
                            >
                            <span class="block text-sm text-chalk-muted">{{
                                item.type.name
                            }}</span>
                        </span>
                        <span class="tabular shrink-0 text-chalk">{{
                            formatClp(item.line_total)
                        }}</span>
                    </li>
                </ul>
                <p
                    class="flex justify-between border-t border-line-strong pt-3 font-semibold text-chalk"
                >
                    Total:
                    <span class="tabular font-display text-2xl">{{
                        formatClp(order.total)
                    }}</span>
                </p>
            </section>
        </div>
    </main>
</template>
