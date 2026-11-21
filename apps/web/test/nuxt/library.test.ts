import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    CustomerSchema,
    LibraryKeysSchema,
    LibraryPageSchema,
} from '../../app/api/schemas';
import LibraryPage from '../../app/pages/biblioteca.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi, serveCsrfCookie, servePost } from './api';

const library = fixture('library.index', LibraryPageSchema);
const keys = fixture('library.keys', LibraryKeysSchema);
const line = library.data[0]!;

describe('my library', () => {
    afterEach(resetApi);

    it('lists the keys by hint and reveals a line on request', async () => {
        useCustomer().value = fixture('auth.me', CustomerSchema).data;
        serveApi('/library', library);
        serveCsrfCookie();
        servePost(
            `/library/${line.order_reference}/${line.product.slug}/keys`,
            keys,
        );

        const page = await mountSuspended(LibraryPage, {
            route: '/biblioteca',
        });

        expect(page.text()).toContain(line.title_name);
        expect(page.text()).toContain(line.keys[0]!.hint);
        expect(page.text()).not.toContain(keys.data.keys[0]!.code);

        await page.find('button[data-action="reveal"]').trigger('click');
        await vi.waitUntil(() => page.find('code').exists());

        expect(page.find('code').text()).toBe(keys.data.keys[0]!.code);
    });
});
