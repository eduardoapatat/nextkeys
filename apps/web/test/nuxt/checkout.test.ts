import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    CustomerSchema,
    OrderPageSchema,
    OrderSchema,
    PaymentRedirectSchema,
    ProductDetailSchema,
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
        await vi.waitUntil(() => page.text().includes('Starfall'));

        expect(page.text()).toContain('Verifica tu correo');
        expect(page.find('button[type="submit"]').exists()).toBe(false);
    });

    it('shows the withdrawal exclusion next to the total', async () => {
        serveApi('/orders', noOrders);
        useCustomer().value = { ...customer, email_verified: true };

        const page = await mountSuspended(CheckoutPage, { route: '/checkout' });
        await vi.waitUntil(() => page.text().includes('Starfall'));

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
});
