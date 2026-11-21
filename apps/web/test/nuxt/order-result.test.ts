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
