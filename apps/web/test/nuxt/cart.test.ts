import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { NotFoundSchema, ProductDetailSchema } from '../../app/api/schemas';
import ProductPage from '../../app/pages/[slug].vue';
import CartPage from '../../app/pages/carrito.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi } from './api';

const product = fixture('products.show', ProductDetailSchema);
const slug = product.data.slug;

describe('cart', () => {
    afterEach(() => {
        resetApi();
        useCart().clear();
    });

    it('adds the product with the chosen quantity', async () => {
        serveApi(`/products/${slug}`, product);

        const page = await mountSuspended(ProductPage, { route: `/${slug}` });
        await page.find('select#quantity').setValue('2');
        await page
            .find('form[aria-label="Agregar al carrito"]')
            .trigger('submit');

        expect(useCart().lines.value).toEqual([{ slug, quantity: 2 }]);
        expect(page.find('[role="status"]').text()).toContain('Agregado');
    });

    it('lists the lines with their price and lets the customer edit them', async () => {
        serveApi(`/products/${slug}`, product);
        useCart().add(slug, 2);

        const page = await mountSuspended(CartPage, { route: '/carrito' });
        await vi.waitUntil(() => page.text().includes('Hades'));

        expect(page.text()).toContain(formatClp(product.data.price * 2));
        // With more than one unit the line still shows the unit price.
        expect(page.text()).toContain(`2 × ${formatClp(product.data.price)}`);

        await page.find(`select[name="quantity-${slug}"]`).setValue('3');
        expect(useCart().lines.value).toEqual([{ slug, quantity: 3 }]);

        await page.find('button[aria-label="Quitar Hades"]').trigger('click');
        expect(useCart().lines.value).toEqual([]);
    });

    it('blocks checkout while a line is no longer available', async () => {
        serveApi(`/products/${slug}`, product);
        serveApi(
            '/products/gone',
            fixture('products.show.404', NotFoundSchema),
            404,
        );
        useCart().add(slug, 1);
        useCart().add('gone', 1);

        const page = await mountSuspended(CartPage, { route: '/carrito' });
        await vi.waitUntil(() => page.text().includes('No disponible'));

        expect(page.find('a[href="/checkout"]').exists()).toBe(false);

        await page.find('button[aria-label="Quitar gone"]').trigger('click');

        await vi.waitUntil(() => page.find('a[href="/checkout"]').exists());
    });
});
