import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it } from 'vitest';
import { NotFoundSchema, ProductDetailSchema } from '../../app/api/schemas';
import ProductPage from '../../app/pages/[slug].vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi } from './api';

const product = fixture('products.show', ProductDetailSchema);

describe('product page', () => {
    afterEach(resetApi);

    it('shows the product and links every variant, marking the current one', async () => {
        serveApi(`/products/${product.data.slug}`, product);

        const page = await mountSuspended(ProductPage, {
            route: `/${product.data.slug}`,
        });

        expect(page.find('h1').text()).toBe('Hades');
        expect(page.text()).toContain(product.data.title.description);
        // A digital key says where it is delivered.
        expect(page.text()).toContain('Entrega inmediata');

        const links = page.findAll('nav[aria-labelledby="variants-heading"] a');
        expect(links.map((link) => link.attributes('href'))).toEqual(
            product.data.variants.map((variant) => `/${variant.slug}`),
        );

        const current = links.filter(
            (link) => link.attributes('aria-current') === 'page',
        );
        expect(current.map((link) => link.text())).toEqual([
            'Clave digital · Steam · Windows, macOS · Global · $19.990',
        ]);
    });

    it('turns an API 404 into a 404 error', async () => {
        serveApi(
            '/products/does-not-exist',
            fixture('products.show.404', NotFoundSchema),
            404,
        );

        await mountSuspended(ProductPage, { route: '/does-not-exist' });

        expect(useError().value).toMatchObject({
            statusCode: 404,
            statusMessage: 'Producto no encontrado',
            fatal: true,
        });
        await clearError();
    });
});
