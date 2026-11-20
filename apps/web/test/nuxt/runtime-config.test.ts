import { describe, expect, it } from 'vitest';

describe('runtime config', () => {
    it('points the storefront to the local catalog API by default', () => {
        expect(useRuntimeConfig().public.apiBase).toBe(
            'http://localhost:8000/api/v1',
        );
    });
});
