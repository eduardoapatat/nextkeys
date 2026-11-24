import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Order } from '../../app/api/schemas';
import {
    CustomerSchema,
    OrderPageSchema,
    OrderSchema,
    PaymentRedirectSchema,
} from '../../app/api/schemas';
import OrdersPage from '../../app/pages/pedidos/index.vue';
import OrderPage from '../../app/pages/pedidos/[reference].vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi, serveCsrfCookie, servePost } from './api';

const customer = fixture('auth.me', CustomerSchema).data;
const paid = fixture('orders.show', OrderSchema).data;
const pending = fixture('orders.store', OrderSchema).data;
const page = fixture('orders.index', OrderPageSchema);
const payment = fixture('payments.store', PaymentRedirectSchema);

const rejected: Order = {
    ...pending,
    last_payment: {
        status: { slug: 'rejected', name: 'Rechazado' },
        amount: pending.total,
        authorization_code: null,
        payment_type: null,
        installments_number: null,
        installments_amount: null,
        card_last_digits: null,
        transaction_date: null,
    },
};

describe('payment result', () => {
    beforeEach(() => {
        useCustomer().value = customer;
    });

    afterEach(() => {
        resetApi();
        vi.restoreAllMocks();
    });

    it('shows the receipt of an approved payment', async () => {
        serveApi(`/orders/${paid.reference}`, { data: paid });

        const result = await mountSuspended(OrderPage, {
            route: `/pedidos/${paid.reference}?estado=authorized`,
        });

        const text = result.text();
        expect(text).toContain(`Pedido ${paid.reference}`);
        expect(text).toContain('Pago aprobado');
        expect(text).toContain(paid.last_payment?.authorization_code);
        expect(text).toContain(`**** ${paid.last_payment?.card_last_digits}`);
        expect(text).toContain(formatClp(paid.total));
        expect(text).toContain('Te enviamos las claves a tu correo');
        expect(result.find('a[href="/biblioteca"]').exists()).toBe(true);
    });

    it('follows the shipment of a paid order', async () => {
        const shipment = paid.shipment!;
        const shipped: Order = {
            ...paid,
            shipment: {
                ...shipment,
                status: { slug: 'shipped', name: 'Despachado' },
                carrier: { slug: 'starken', name: 'Starken' },
                tracking_number: 'AB-12345',
                tracking_url:
                    'https://www.starken.cl/seguimiento?codigo=AB-12345',
                shipped_at: '2026-11-24T15:00:00+00:00',
            },
        };
        serveApi(`/orders/${paid.reference}`, { data: shipped });

        const result = await mountSuspended(OrderPage, {
            route: `/pedidos/${paid.reference}`,
        });

        const section = result.find('[aria-labelledby="shipment-heading"]');
        expect(section.find('[aria-current="step"]').text()).toContain(
            'Despachado',
        );
        expect(section.text()).toContain('Starken');
        expect(
            section.find(`a[href="${shipped.shipment!.tracking_url}"]`).text(),
        ).toBe('AB-12345');
        expect(section.text()).toContain(shipment.address.recipient_name);
        expect(section.text()).toContain(
            `${shipment.address.commune}, ${shipment.address.region.name}`,
        );
        expect(section.text()).not.toContain('derecho a retracto');
        expect(result.text()).toContain('Envío');
        expect(result.text()).toContain(formatClp(paid.shipping_cost));
    });

    it('says the parcel is being packed, then when the withdrawal ends', async () => {
        const shipment = paid.shipment!;
        serveApi(`/orders/${paid.reference}`, { data: paid });

        const packing = await mountSuspended(OrderPage, {
            route: `/pedidos/${paid.reference}`,
        });
        expect(packing.find('[aria-current="step"]').text()).toContain(
            'Por despachar',
        );
        expect(packing.text()).toContain('Estamos preparando tu paquete');

        resetApi();
        const delivered: Order = {
            ...paid,
            status: { slug: 'completed', name: 'Completado' },
            shipment: {
                ...shipment,
                status: { slug: 'delivered', name: 'Entregado' },
                carrier: { slug: 'starken', name: 'Starken' },
                tracking_number: 'AB-12345',
                tracking_url: null,
                shipped_at: '2026-11-20T15:00:00+00:00',
                delivered_at: '2026-11-22T15:00:00+00:00',
                returnable_until: '2026-12-02T15:00:00+00:00',
            },
        };
        serveApi(`/orders/${paid.reference}`, { data: delivered });

        const done = await mountSuspended(OrderPage, {
            route: `/pedidos/${paid.reference}`,
        });
        expect(done.find('[aria-current="step"]').text()).toContain(
            'Entregado',
        );
        expect(done.text()).toContain(
            'Puedes ejercer tu derecho a retracto hasta el 2 de diciembre de 2026',
        );
    });

    it('shows the shipping status of a paid order in the list', async () => {
        serveApi('/orders', page);

        const list = await mountSuspended(OrdersPage, { route: '/pedidos' });
        await vi.waitUntil(() => list.text().includes(page.data[0]!.reference));

        expect(page.data[0]!.shipment?.status.name).toBe('Por despachar');
        expect(list.find('tbody').text()).toContain('Por despachar');
    });

    it('explains a rejection and retries the payment', async () => {
        serveApi(`/orders/${rejected.reference}`, { data: rejected });
        serveCsrfCookie();
        servePost(`/orders/${rejected.reference}/payments`, payment, 201);
        const submit = vi
            .spyOn(HTMLFormElement.prototype, 'submit')
            .mockImplementation(() => {});

        const result = await mountSuspended(OrderPage, {
            route: `/pedidos/${rejected.reference}?estado=rejected`,
        });

        expect(result.text()).toContain(
            `Orden de Compra ${rejected.reference} rechazada`,
        );

        await result.find('button[data-action="retry"]').trigger('click');
        await vi.waitUntil(() => submit.mock.calls.length > 0);

        const form = submit.mock.contexts[0] as HTMLFormElement;
        expect(form.action).toBe(payment.data.url);
    });

    it('cancels a pending order', async () => {
        serveApi(`/orders/${pending.reference}`, { data: pending });
        serveCsrfCookie();
        servePost(`/orders/${pending.reference}/cancel`, {
            data: {
                ...pending,
                status: { slug: 'cancelled', name: 'Cancelado' },
                can_pay: false,
            },
        });

        const result = await mountSuspended(OrderPage, {
            route: `/pedidos/${pending.reference}`,
        });
        await result.find('button[data-action="cancel"]').trigger('click');
        await vi.waitUntil(() => result.text().includes('Pedido cancelado'));

        expect(result.find('button[data-action="retry"]').exists()).toBe(false);
    });

    it('says when the payment could not be identified and lists the orders', async () => {
        serveApi('/orders', page);

        const result = await mountSuspended(OrdersPage, {
            route: '/pedidos?estado=desconocido',
        });

        expect(result.text()).toContain('No pudimos identificar el pago');
        expect(
            result
                .find(`a[href="/pedidos/${page.data[0]?.reference}"]`)
                .exists(),
        ).toBe(true);
    });
});
