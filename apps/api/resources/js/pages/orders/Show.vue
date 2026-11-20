<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import OrderController from '@/actions/App/Http/Controllers/Orders/OrderController';
import ConfirmActionButton from '@/components/ConfirmActionButton.vue';
import Heading from '@/components/Heading.vue';
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
    keys: { id: number; code_hint: string; status: string }[];
};

type OrderDetail = {
    reference: string;
    status: {
        value: string;
        label: string;
        variant: NonNullable<BadgeVariants['variant']>;
    };
    total: number;
    customer: { name: string; email: string };
    created_at: string | null;
    expires_at: string;
    paid_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    items: OrderLine[];
};

const props = defineProps<{
    order: OrderDetail;
    canCancel: boolean;
    canMarkPaid: boolean;
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
    </div>
</template>
