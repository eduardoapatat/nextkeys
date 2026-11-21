import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    CatalogFiltersSchema,
    ProductPageSchema,
    ValidationErrorSchema,
} from '../../app/api/schemas';
import IndexPage from '../../app/pages/index.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi } from './api';

const productPage = fixture('products.index', ProductPageSchema);

describe('catalog listing', () => {
    beforeEach(() => {
        serveApi(
            '/catalog/filters',
            fixture('catalog.filters', CatalogFiltersSchema),
        );
    });

    afterEach(resetApi);

    it('lists the products with their price in CLP', async () => {
        serveApi('/products', productPage);

        const page = await mountSuspended(IndexPage, { route: '/' });

        expect(page.findAll('article')).toHaveLength(productPage.data.length);
        expect(page.text()).toContain('Shield Antivirus');
        expect(page.text()).toContain('$14.990');
        // Without cover_url the storefront prints a cover from the product.
        expect(
            page
                .find(
                    '[data-cover="generated"][aria-label="Carátula de Shield Antivirus"]',
                )
                .exists(),
        ).toBe(true);
        expect(page.find('img[alt="Carátula de Starfall"]').exists()).toBe(
            true,
        );
    });

    it('sends the filters, sort and page of the URL to the API', async () => {
        const queries = serveApi('/products', productPage);

        const page = await mountSuspended(IndexPage, {
            route: '/?platform=ps4&platform=ps5&region=latam&sort=-price&page=2',
        });

        expect(queries.at(-1)).toEqual({
            platform: 'ps4,ps5',
            region: 'latam',
            sort: '-price',
            page: '2',
        });
        expect(
            page.find('input[name="platform"][value="ps5"]').element,
        ).toHaveProperty('checked', true);
        // SSR renders the attribute; the select's value follows it after hydration.
        expect(
            page.find('option[value="-price"]').attributes('selected'),
        ).toBeDefined();
    });

    it('says so when no product matches', async () => {
        serveApi('/products', { ...productPage, data: [] });

        const page = await mountSuspended(IndexPage, {
            route: '/?region=europe',
        });

        expect(page.text()).toContain('No hay productos con estos filtros.');
    });

    it('shows the API messages of an invalid filter', async () => {
        serveApi(
            '/products',
            fixture('products.index.422', ValidationErrorSchema),
            422,
        );

        const page = await mountSuspended(IndexPage, {
            route: '/?platform=does-not-exist',
        });

        expect(page.find('[role="alert"]').text()).toContain(
            'La plataforma «does-not-exist» no existe.',
        );
    });

    it('links to the previous and next pages keeping the filters', async () => {
        serveApi('/products', {
            ...productPage,
            meta: { ...productPage.meta, current_page: 2, last_page: 3 },
        });

        const page = await mountSuspended(IndexPage, {
            route: '/?platform=ps5&page=2',
        });

        expect(page.find('a[rel="prev"]').attributes('href')).toBe(
            '/?platform=ps5',
        );
        expect(page.find('a[rel="next"]').attributes('href')).toBe(
            '/?platform=ps5&page=3',
        );
        expect(page.text()).toContain('Página 2 de 3');
    });
});
