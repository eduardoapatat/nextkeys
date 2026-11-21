import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it } from 'vitest';
import SiteFooter from '../../app/components/site/Footer.vue';
import SiteHeader from '../../app/components/site/Header.vue';

describe('store layout', () => {
    afterEach(() => {
        useCart().clear();
    });

    it('shows the number of units in the cart', async () => {
        useCart().add('starfall', 2);

        const header = await mountSuspended(SiteHeader);
        const cart = header.find('a[href="/carrito"]');

        expect(cart.attributes('aria-label')).toBe('Carrito, 2 productos');
        expect(cart.text()).toContain('2');
    });

    it('says that name search is not available yet instead of searching', async () => {
        const header = await mountSuspended(SiteHeader);

        await header.find('form[role="search"]').trigger('submit');

        const note = header.find('form[role="search"] [role="status"]');
        expect(note.text()).toContain('La búsqueda por nombre llega pronto');
        expect(note.find('a').attributes('href')).toBe('/catalogo');
    });

    it('links the platform families to the filtered catalog', async () => {
        const header = await mountSuspended(SiteHeader);
        const nav = header.find('nav[aria-label="Principal"]');

        expect(nav.find('a[href="/catalogo?platform=ps5,ps4"]').text()).toBe(
            'PlayStation',
        );
        expect(nav.find('a[href="/catalogo?type=physical"]').text()).toBe(
            'Juegos físicos',
        );
    });

    it('keeps the newsletter as a labeled example', async () => {
        const footer = await mountSuspended(SiteFooter);

        await footer
            .find('form[aria-label="Suscribirse al boletín"]')
            .trigger('submit');

        expect(footer.find('[role="status"]').text()).toBe(
            'Es una demo: no guardamos tu correo.',
        );
        expect(footer.text()).toContain('los pagos son ficticios');
    });
});
