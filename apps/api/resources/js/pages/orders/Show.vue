<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import OrderController from '@/actions/App/Http/Controllers/Orders/OrderController';
import ConfirmActionButton from '@/components/ConfirmActionButton.vue';
import Heading from '@/components/Heading.vue';
import ShipOrderDialog from '@/components/ShipOrderDialog.vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type OrderLine = {
    id: number;
    title_name: string;
    type: { value: string; label: string };
    quantity: number;
    unit_price: number;
    line_total: number;
    keys: {
        id: number;
        code_hint: string;
        status: string;
        revealed_at: string | null;
    }[];
};

type PaymentAttempt = {
    number: number;
    buy_order: string;
    status: {
        value: string;
        label: string;
        variant: NonNullable<BadgeVariants['variant']>;
    };
    amount: number;
    authorization_code: string | null;
    payment_type: string | null;
    card_last_digits: string | null;
    created_at: string | null;
    finished_at: string | null;
    refunded_amount: number | null;
    refunded_at: string | null;
};

type Shipment = {
    status: {
        value: string;
        label: string;
        variant: NonNullable<BadgeVariants['variant']>;
    };
    recipient_name: string;
    phone: string;
    address_line: string;
    carrier: string | null;
    tracking_number: string | null;
    tracking_url: string | null;
    shipped_at: string | null;
    delivered_at: string | null;
};

type OrderDetail = {
    reference: string;
    status: {
        value: string;
        label: string;
        variant: NonNullable<BadgeVariants['variant']>;
    };
    total: number;
    shipping_cost: number;
    customer: { name: string; email: string };
    created_at: string | null;
    expires_at: string;
    paid_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    paid_email: {
        sent_at: string | null;
        resends: number;
        max_resends: number;
    };
    items: OrderLine[];
    payments: PaymentAttempt[];
    shipment: Shipment | null;
};

const props = defineProps<{
    order: OrderDetail;
    canCancel: boolean;
    canResendPaidEmail: boolean;
    canMarkPaid: boolean;
    canPayWithWebpay: boolean;
    canShip: boolean;
    canMarkDelivered: boolean;
    carriers: { value: string; label: string }[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Pedidos', href: OrderController.index() },
        {
            title: props.order.reference,
            href: OrderController.show(props.order.reference),
        },
    ],
});

const clp = new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
});
const dateTime = new Intl.DateTimeFormat('es-CL', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

function formatDateTime(value: string | null): string {
    return value === null ? '—' : dateTime.format(new Date(value));
}

/** Dates of the order, only the ones that apply to its status. */
const timeline = [
    { label: 'Creado', value: props.order.created_at },
    props.order.status.value === 'pending' ||
    props.order.status.value === 'expired'
        ? { label: 'Reserva hasta', value: props.order.expires_at }
        : null,
    { label: 'Pagado', value: props.order.paid_at },
    { label: 'Completado', value: props.order.completed_at },
    { label: 'Cancelado', value: props.order.cancelled_at },
].filter(
    (entry): entry is { label: string; value: string } =>
        entry !== null && entry.value !== null,
);
</script>

<template>
    <Head :title="`Pedido ${order.reference}`" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <Heading
                    :title="`Pedido ${order.reference}`"
                    :description="`${order.customer.name} · ${order.customer.email}`"
                />
                <Badge :variant="order.status.variant">
                    {{ order.status.label }}
                </Badge>
            </div>

            <div class="flex gap-2">
                <ConfirmActionButton
                    v-if="canPayWithWebpay"
                    :action="OrderController.payWithWebpay(order.reference)"
                    :title="`¿Pagar ${order.reference} con Webpay?`"
                    description="Solo para pruebas locales: abre Webpay en el ambiente de integración, con las tarjetas de prueba de Transbank."
                    confirm-label="Ir a Webpay"
                >
                    <Button variant="outline">Pagar con Webpay (prueba)</Button>
                </ConfirmActionButton>
                <ConfirmActionButton
                    v-if="canMarkPaid"
                    :action="OrderController.markPaid(order.reference)"
                    :title="`¿Marcar ${order.reference} como pagado?`"
                    description="Solo para pruebas locales: entrega las claves sin cobrar."
                    confirm-label="Marcar como pagado"
                >
                    <Button variant="outline">Marcar como pagado</Button>
                </ConfirmActionButton>
                <ConfirmActionButton
                    v-if="canCancel"
                    :action="OrderController.cancel(order.reference)"
                    :title="`¿Cancelar el pedido ${order.reference}?`"
                    description="Las claves y el stock reservados vuelven a estar disponibles."
                    confirm-label="Cancelar pedido"
                    confirm-variant="destructive"
                >
                    <Button variant="destructive">Cancelar pedido</Button>
                </ConfirmActionButton>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div v-for="entry in timeline" :key="entry.label">
                <dt class="text-sm text-muted-foreground">{{ entry.label }}</dt>
                <dd class="text-sm font-medium">
                    {{ formatDateTime(entry.value) }}
                </dd>
            </div>
        </dl>

        <Card>
            <CardHeader>
                <CardTitle>Productos</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Producto</TableHead>
                            <TableHead class="text-right">Cantidad</TableHead>
                            <TableHead class="text-right">Precio</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="line in order.items" :key="line.id">
                            <TableCell class="align-top">
                                <div class="font-medium">
                                    {{ line.title_name }}
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ line.type.label }}
                                </div>
                                <ul
                                    v-if="line.keys.length > 0"
                                    class="mt-2 space-y-1"
                                    aria-label="Claves"
                                >
                                    <li
                                        v-for="key in line.keys"
                                        :key="key.id"
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <code class="font-mono">{{
                                            key.code_hint
                                        }}</code>
                                        <Badge variant="outline">{{
                                            key.status
                                        }}</Badge>
                                        <span
                                            v-if="key.revealed_at"
                                            class="text-muted-foreground"
                                        >
                                            Revelada el
                                            {{
                                                formatDateTime(key.revealed_at)
                                            }}
                                        </span>
                                    </li>
                                </ul>
                            </TableCell>
                            <TableCell class="text-right align-top">
                                {{ line.quantity }}
                            </TableCell>
                            <TableCell class="text-right align-top">
                                {{ clp.format(line.unit_price) }}
                            </TableCell>
                            <TableCell class="text-right align-top">
                                {{ clp.format(line.line_total) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    <TableFooter>
                        <TableRow v-if="order.shipment !== null">
                            <TableCell colspan="3">Envío</TableCell>
                            <TableCell class="text-right">
                                {{ clp.format(order.shipping_cost) }}
                            </TableCell>
                        </TableRow>
                        <TableRow>
                            <TableCell colspan="3">Total</TableCell>
                            <TableCell class="text-right font-medium">
                                {{ clp.format(order.total) }}
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </CardContent>
        </Card>

        <Card v-if="order.shipment !== null">
            <CardHeader class="flex flex-row items-center gap-3">
                <CardTitle>Envío</CardTitle>
                <Badge :variant="order.shipment.status.variant">
                    {{ order.shipment.status.label }}
                </Badge>
            </CardHeader>
            <CardContent
                class="flex flex-wrap items-start justify-between gap-4"
            >
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-sm text-muted-foreground">Recibe</dt>
                        <dd class="text-sm font-medium">
                            {{ order.shipment.recipient_name }} ·
                            {{ order.shipment.phone }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Dirección</dt>
                        <dd class="text-sm font-medium">
                            {{ order.shipment.address_line }}
                        </dd>
                    </div>
                    <div v-if="order.shipment.carrier !== null">
                        <dt class="text-sm text-muted-foreground">
                            Seguimiento
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ order.shipment.carrier }} ·
                            <a
                                v-if="order.shipment.tracking_url !== null"
                                :href="order.shipment.tracking_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-mono underline underline-offset-4"
                                >{{ order.shipment.tracking_number }}</a
                            >
                        </dd>
                    </div>
                    <div v-if="order.shipment.shipped_at !== null">
                        <dt class="text-sm text-muted-foreground">
                            Despachado
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ formatDateTime(order.shipment.shipped_at) }}
                        </dd>
                    </div>
                    <div v-if="order.shipment.delivered_at !== null">
                        <dt class="text-sm text-muted-foreground">Entregado</dt>
                        <dd class="text-sm font-medium">
                            {{ formatDateTime(order.shipment.delivered_at) }}
                        </dd>
                    </div>
                </dl>
                <ShipOrderDialog
                    v-if="canShip"
                    :reference="order.reference"
                    :carriers="carriers"
                />
                <ConfirmActionButton
                    v-if="canMarkDelivered"
                    :action="OrderController.markDelivered(order.reference)"
                    :title="`¿Marcar ${order.reference} como entregado?`"
                    description="El pedido queda completado y el cliente tiene 10 días de derecho a retracto desde hoy."
                    confirm-label="Marcar como entregado"
                >
                    <Button>Marcar como entregado</Button>
                </ConfirmActionButton>
            </CardContent>
        </Card>

        <Card v-if="order.paid_at !== null">
            <CardHeader>
                <CardTitle>Email de confirmación</CardTitle>
            </CardHeader>
            <CardContent
                class="flex flex-wrap items-center justify-between gap-4"
            >
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm text-muted-foreground">
                            Destinatario
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ order.customer.email }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Enviado</dt>
                        <dd class="text-sm font-medium">
                            {{
                                order.paid_email.sent_at === null
                                    ? 'Pendiente'
                                    : formatDateTime(order.paid_email.sent_at)
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Reenvíos</dt>
                        <dd class="text-sm font-medium">
                            {{ order.paid_email.resends }}/{{
                                order.paid_email.max_resends
                            }}
                        </dd>
                    </div>
                </dl>
                <ConfirmActionButton
                    v-if="canResendPaidEmail"
                    :action="OrderController.resendPaidEmail(order.reference)"
                    :title="`¿Reenviar el email de ${order.reference}?`"
                    :description="`Se envía de nuevo, con las claves, a ${order.customer.email}.`"
                    confirm-label="Reenviar email"
                >
                    <Button variant="outline">Reenviar email</Button>
                </ConfirmActionButton>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Pagos</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="order.payments.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Sin intentos de pago.
                </p>
                <Table v-else>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Orden de compra</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Tipo de pago</TableHead>
                            <TableHead>Tarjeta</TableHead>
                            <TableHead>Autorización</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead class="text-right">Monto</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="payment in order.payments"
                            :key="payment.number"
                        >
                            <TableCell class="font-mono">
                                {{ payment.buy_order }}
                            </TableCell>
                            <TableCell>
                                <Badge :variant="payment.status.variant">
                                    {{ payment.status.label }}
                                </Badge>
                                <div
                                    v-if="payment.refunded_at !== null"
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    {{
                                        clp.format(payment.refunded_amount ?? 0)
                                    }}
                                    devueltos el
                                    {{ formatDateTime(payment.refunded_at) }}
                                </div>
                            </TableCell>
                            <TableCell>{{
                                payment.payment_type ?? '—'
                            }}</TableCell>
                            <TableCell class="font-mono">
                                {{
                                    payment.card_last_digits === null
                                        ? '—'
                                        : `•••• ${payment.card_last_digits}`
                                }}
                            </TableCell>
                            <TableCell class="font-mono">
                                {{ payment.authorization_code ?? '—' }}
                            </TableCell>
                            <TableCell>
                                {{
                                    formatDateTime(
                                        payment.finished_at ??
                                            payment.created_at,
                                    )
                                }}
                            </TableCell>
                            <TableCell class="text-right">
                                {{ clp.format(payment.amount) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
