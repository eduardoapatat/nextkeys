import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { CatalogFiltersSchema, ProductPageSchema } from '../../app/api/schemas';
import HomePage from '../../app/pages/index.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi } from './api';

const productPage = fixture('products.index', ProductPageSchema);
const newest = productPage.data[0]!;

describe('home', () => {
    let queries: Array<Record<string, string>>;

    beforeEach(() => {
        queries = serveApi('/products', productPage);
        serveApi(
            '/catalog/filters',
            fixture('catalog.filters', CatalogFiltersSchema),
        );
    });

    afterEach(() => {
        resetApi();
        useCart().clear();
    });

    it('asks the API for three real orderings of the catalog', async () => {
        await mountSuspended(HomePage, { route: '/' });

        expect(queries.map((query) => query.sort).sort()).toEqual([
            'name',
            'newest',
            'price',
        ]);
    });

    it('puts the newest product in the case with a working add to cart', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const caseSection = page.find('[aria-labelledby="case-heading"]');

        expect(caseSection.find('h2').text()).toBe(newest.title.name);
        await caseSection
            .find(
                `button[aria-label="Agregar ${newest.title.name} al carrito"]`,
            )
            .trigger('click');
        expect(useCart().lines.value).toEqual([
            { slug: newest.slug, quantity: 1 },
        ]);
    });

    it('shows a real deal of the day without an invented discount', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const deal = page.find('[aria-labelledby="deal-heading"]');

        expect(deal.text()).toContain('a su precio de siempre');
        expect(
            productPage.data.some((product) =>
                deal.text().includes(formatClp(product.price)),
            ),
        ).toBe(true);
    });

    it('marks the tabs without API data as an example selection', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });

        expect(page.find('#panel-novedades').text()).not.toContain(
            'Selección de ejemplo',
        );
        expect(page.find('#panel-ofertas').text()).toContain(
            'Selección de ejemplo',
        );
        expect(page.find('#panel-destacados').text()).toContain(
            'Selección de ejemplo',
        );

        await page.find('#tab-ofertas').trigger('click');
        expect(page.find('#tab-ofertas').attributes('aria-selected')).toBe(
            'true',
        );
        expect(
            page.find('#panel-novedades').attributes('hidden'),
        ).toBeDefined();
    });

    it('links the category blocks to the filtered catalog', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const categories = page.find('[aria-labelledby="categories-heading"]');

        expect(
            categories.find('a[href="/catalogo?platform=ps5,ps4"]').text(),
        ).toContain('PlayStation');
        expect(
            categories.find('a[href="/catalogo?type=digital_key"]').exists(),
        ).toBe(true);
    });
});
