import { mountSuspended } from '@nuxt/test-utils/runtime';
import { describe, expect, it } from 'vitest';
import type { ProductSummary } from '../../app/api/schemas';
import ProductCover from '../../app/components/ProductCover.vue';

const product = {
    slug: 'hades-ps5',
    cover_url: null,
    title: { name: 'Hades' },
    type: { slug: 'digital_key', name: 'Clave digital' },
    region: { slug: 'latam', name: 'LATAM' },
    platforms: [{ slug: 'ps5', name: 'PS5' }],
} satisfies Pick<
    ProductSummary,
    'slug' | 'cover_url' | 'title' | 'type' | 'region' | 'platforms'
>;

describe('product cover', () => {
    it('sits in the case of its platform with the official mark in the band', async () => {
        const cover = await mountSuspended(ProductCover, {
            props: { product },
        });

        expect(cover.attributes('data-tone')).toBe('ps5');
        expect(cover.find('[aria-label="Plataformas: PS5"] svg').exists()).toBe(
            true,
        );
        expect(cover.find('[data-cover="generated"]').exists()).toBe(true);
        expect(cover.attributes('data-case')).toBeUndefined();
    });

    it('frames only a physical game in its case', async () => {
        const cover = await mountSuspended(ProductCover, {
            props: {
                product: {
                    ...product,
                    type: { slug: 'physical', name: 'Físico' },
                },
            },
        });

        expect(cover.attributes('data-case')).toBe('physical');
    });

    it('frames an uploaded cover the same way', async () => {
        const cover = await mountSuspended(ProductCover, {
            props: {
                product: {
                    ...product,
                    cover_url: '/covers/hades.jpg',
                    platforms: [{ slug: 'switch', name: 'Switch' }],
                },
            },
        });

        expect(cover.attributes('data-tone')).toBe('nintendo');
        expect(cover.find('img[alt="Carátula de Hades"]').exists()).toBe(true);
    });

    it('shows an uploaded cover bare when its band is turned off', async () => {
        const cover = await mountSuspended(ProductCover, {
            props: {
                product: {
                    ...product,
                    cover_url: '/covers/hades.jpg',
                    cover_band: false,
                },
            },
        });

        expect(cover.find('[data-cover-box]').exists()).toBe(false);
        expect(cover.find('img[alt="Carátula de Hades"]').exists()).toBe(true);
    });
});
