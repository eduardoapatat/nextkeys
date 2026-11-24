import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    AddressListSchema,
    CustomerSchema,
    OrderPageSchema,
    OrderSchema,
    PaymentRedirectSchema,
    ProductDetailSchema,
    ShippingSchema,
} from '../../app/api/schemas';
import CheckoutPage from '../../app/pages/checkout.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi, serveCsrfCookie, servePost } from './api';

const customer = fixture('auth.me', CustomerSchema).data;
const product = fixture('products.show', ProductDetailSchema);
const placed = fixture('orders.store', OrderSchema);
const noOrders = { ...fixture('orders.index', OrderPageSchema), data: [] };
const payment = fixture('payments.store', PaymentRedirectSchema);
const slug = product.data.slug;
const box = {
    data: {
        ...product.data,
        slug: 'hades-physical-ps5',
        type: { slug: 'physical' as const, name: 'Físico' },
    },
};
const addresses = fixture('addresses.index', AddressListSchema);
const shipping = fixture('shipping.show', ShippingSchema);

describe('checkout', () => {
    beforeEach(() => {
        serveApi(`/products/${slug}`, product);
        useCart().add(slug, 1);
    });

    afterEach(() => {
        resetApi();
        useCart().clear();
        vi.restoreAllMocks();
    });

    it('asks for a verified email before paying', async () => {
        serveApi('/orders', noOrders);
        useCustomer().value = { ...customer, email_verified: false };

        const page = await mountSuspended(CheckoutPage, { route: '/checkout' });
        await vi.waitUntil(() => page.text().includes('Hades'));

        expect(page.text()).toContain('Verifica tu correo');
        expect(page.find('button[type="submit"]').exists()).toBe(false);
    });

    it('shows the withdrawal exclusion next to the total', async () => {
        serveApi('/orders', noOrders);
        useCustomer().value = { ...customer, email_verified: true };

        const page = await mountSuspended(CheckoutPage, { route: '/checkout' });
        await vi.waitUntil(() => page.text().includes('Hades'));

        const summary = page.find('[aria-labelledby="total-heading"]');
        expect(summary.text()).toContain(formatClp(product.data.price));
        expect(summary.text()).toContain('derecho a retracto');
    });

    it('links the pending order instead of paying again', async () => {
        serveApi('/orders', { ...noOrders, data: [placed.data] });
        useCustomer().value = { ...customer, email_verified: true };

        const page = await mountSuspended(CheckoutPage, { route: '/checkout' });
        await vi.waitUntil(() => page.text().includes('pedido pendiente'));

        expect(
            page.find(`a[href="/pedidos/${placed.data.reference}"]`).exists(),
        ).toBe(true);
        expect(page.find('button[type="submit"]').exists()).toBe(false);
    });

    it('places the order and posts the token to Webpay', async () => {
        serveApi('/orders', noOrders);
        serveCsrfCookie();
        const orders = servePost('/orders', placed, 201);
        servePost(`/orders/${placed.data.reference}/payments`, payment, 201);
        const submit = vi
            .spyOn(HTMLFormElement.prototype, 'submit')
            .mockImplementation(() => {});
        useCustomer().value = { ...customer, email_verified: true };

        const page = await mountSuspended(CheckoutPage, { route: '/checkout' });
        await vi.waitUntil(() => page.find('button[type="submit"]').exists());
        await page.find('form[aria-label="Pagar"]').trigger('submit');
        await vi.waitUntil(() => submit.mock.calls.length > 0);

        expect(orders).toEqual([{ items: [{ slug, quantity: 1 }] }]);
        const form = submit.mock.contexts[0] as HTMLFormElement;
        expect(form.method).toBe('post');
        expect(form.action).toBe(payment.data.url);
        expect(
            (form.elements.namedItem('token_ws') as HTMLInputElement).value,
        ).toBe(payment.data.token);
        expect(useCart().lines.value).toEqual([]);
    });

    describe('with physical products', () => {
        beforeEach(() => {
            useCart().clear();
            serveApi(`/products/${box.data.slug}`, box);
            useCart().add(box.data.slug, 1);
            serveApi('/orders', noOrders);
            serveApi('/shipping', shipping);
            useCustomer().value = { ...customer, email_verified: true };
        });

        it('adds the shipping fee and ships to the chosen address', async () => {
            serveApi('/account/addresses', addresses);
            serveCsrfCookie();
            const orders = servePost('/orders', placed, 201);
            servePost(
                `/orders/${placed.data.reference}/payments`,
                payment,
                201,
            );
            vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(
                () => {},
            );

            const page = await mountSuspended(CheckoutPage, {
                route: '/checkout',
            });
            await vi.waitUntil(() => page.text().includes('Despacho'));

            const summary = page.find('[aria-labelledby="total-heading"]');
            expect(summary.text()).toContain('Envío');
            expect(summary.text()).toContain(formatClp(shipping.data.cost));
            expect(summary.text()).toContain(
                formatClp(box.data.price + shipping.data.cost),
            );
            const radio = page.find('input[name="shipping_address"]');
            expect((radio.element as HTMLInputElement).checked).toBe(true);
            expect(page.text()).toContain(addresses.data[0]!.commune);

            await page.find('form[aria-label="Pagar"]').trigger('submit');
            await vi.waitUntil(() => orders.length === 1);

            expect(orders).toEqual([
                {
                    items: [{ slug: box.data.slug, quantity: 1 }],
                    shipping_address: addresses.data[0]!.public_id,
                },
            ]);
        });

        it('asks for an address before paying', async () => {
            serveApi('/account/addresses', { data: [] });

            const page = await mountSuspended(CheckoutPage, {
                route: '/checkout',
            });
            await vi.waitUntil(() => page.text().includes('Despacho'));

            expect(
                page
                    .find('a[href="/cuenta/direcciones?redirect=/checkout"]')
                    .exists(),
            ).toBe(true);
            expect(
                page.find('button[type="submit"]').attributes('disabled'),
            ).toBeDefined();
        });
    });
});
