import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it } from 'vitest';
import { ProductPageSchema } from '../../app/api/schemas';
import ProductCard from '../../app/components/ProductCard.vue';
import { fixture } from '../fixtures';

const products = fixture('products.index', ProductPageSchema).data;
const digital = products.find((p) => p.activation_service !== null)!;
const physical = products.find((p) => p.type.slug === 'physical')!;

describe('product card', () => {
    afterEach(() => useCart().clear());

    it('shows type, region and activation, and the price with Agregar at the foot', async () => {
        const card = await mountSuspended(ProductCard, {
            props: { product: digital },
        });

        expect(card.text()).toContain(digital.type.name);
        expect(card.text()).toContain(
            `Se activa en ${digital.activation_service!.name}`,
        );
        expect(card.find('data').text()).toBe(formatClp(digital.price));
        expect(card.text()).not.toMatch(/PC \d\d/);

        await card
            .find(
                `button[aria-label="Agregar ${digital.title.name} al carrito"]`,
            )
            .trigger('click');
        expect(useCart().lines.value).toEqual([
            { slug: digital.slug, quantity: 1 },
        ]);
    });

    it('leaves out the activation line of a physical game', async () => {
        const card = await mountSuspended(ProductCard, {
            props: { product: { ...physical, activation_service: null } },
        });

        expect(card.text()).not.toContain('Se activa en');
    });
});
