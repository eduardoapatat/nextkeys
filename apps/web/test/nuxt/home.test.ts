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

    it('shows the shelf right after the banner, without a side menu', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const main = page.find('main');

        expect(main.find('h1').text()).toContain('NextKeys');
        expect(main.find('[aria-labelledby="case-heading"]').exists()).toBe(
            false,
        );
        expect(main.find('nav').exists()).toBe(false);

        const shelf = page.find('#panel-novedades');
        await shelf
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

    it('opens with a banner slider whose first slide is the deal', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const banner = page.find('[aria-roledescription="carrusel"]');
        const slides = banner.findAll('[aria-roledescription="lámina"]');

        expect(slides).toHaveLength(3);
        expect(slides[0]!.find('#deal-heading').exists()).toBe(true);
        expect(slides[0]!.attributes('inert')).toBeUndefined();
        expect(slides[1]!.attributes('inert')).toBeDefined();
        expect(page.text()).not.toContain('Precios de hoy');

        const pause = banner.find('button[aria-label="Pausar el carrusel"]');
        await pause.trigger('click');
        expect(
            banner.find('button[aria-label="Reanudar el carrusel"]').exists(),
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

    it('links the platforms and types from the brand band', async () => {
        const page = await mountSuspended(HomePage, { route: '/' });
        const categories = page.find('[aria-labelledby="categories-heading"]');

        expect(categories.classes()).toContain('surface-brand');
        expect(
            categories.find('a[href="/catalogo?platform=ps5,ps4"]').text(),
        ).toContain('PlayStation');
        expect(
            categories.find('a[href="/catalogo?type=digital_key"]').exists(),
        ).toBe(true);
    });
});
