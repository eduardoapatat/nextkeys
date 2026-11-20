import { afterEach, describe, expect, it, vi } from 'vitest';

describe('nuxt.config', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('points the storefront to the local catalog API by default', async () => {
        vi.stubGlobal('defineNuxtConfig', (config: unknown) => config);

        const { default: config } = await import('../../nuxt.config');

        expect(config.runtimeConfig?.public?.apiBase).toBe(
            'http://localhost:8000/api/v1',
        );
    });
});
