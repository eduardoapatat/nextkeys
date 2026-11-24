import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    AddressListSchema,
    AddressSchema,
    CustomerSchema,
    ShippingSchema,
    ValidationErrorSchema,
} from '../../app/api/schemas';
import AddressesPage from '../../app/pages/cuenta/direcciones.vue';
import { fixture } from '../fixtures';
import { resetApi, serveApi, serveCsrfCookie, serveRequest } from './api';

const customer = fixture('auth.me', CustomerSchema).data;
const addresses = fixture('addresses.index', AddressListSchema);
const created = fixture('addresses.store', AddressSchema);
const invalid = fixture('addresses.store.422', ValidationErrorSchema);
const shipping = fixture('shipping.show', ShippingSchema);
const saved = addresses.data[0]!;

async function fillForm(page: Awaited<ReturnType<typeof mountSuspended>>) {
    await page.find('#recipient_name').setValue('Ana Pérez');
    await page.find('#phone').setValue('+56 9 1234 5678');
    await page.find('#region').setValue('RM');
    await page.find('#commune').setValue('Ñuñoa');
    await page.find('#street').setValue('Av. Irarrázaval');
    await page.find('#street_number').setValue('1234');
    await page.find('#apartment').setValue('Depto 501');
}

describe('shipping addresses', () => {
    beforeEach(() => {
        useCustomer().value = customer;
        serveCsrfCookie();
        serveApi('/shipping', shipping);
    });

    afterEach(resetApi);

    it('lists the saved addresses', async () => {
        serveRequest('GET', '/account/addresses', addresses);

        const page = await mountSuspended(AddressesPage, {
            route: '/cuenta/direcciones',
        });

        const list = page.find('[aria-label="Direcciones guardadas"]');
        expect(list.text()).toContain(saved.recipient_name);
        expect(list.text()).toContain(`${saved.commune}, ${saved.region.name}`);
        expect(list.text()).toContain(saved.phone);
    });

    it('lists only the communes of the chosen region and clears a commune from another', async () => {
        serveRequest('GET', '/account/addresses', { data: [] });

        const page = await mountSuspended(AddressesPage, {
            route: '/cuenta/direcciones',
        });
        await page.find('#region').setValue('RM');
        await page.find('#commune').setValue('Ñuñoa');
        await page.find('#region').setValue('VS');

        const communes = page.findAll('#commune option').map((o) => o.text());
        expect(communes).toContain('Viña del Mar');
        expect(communes).not.toContain('Ñuñoa');
        expect((page.find('#commune').element as HTMLSelectElement).value).toBe(
            '',
        );
    });

    it('saves a new address and goes back to the checkout', async () => {
        serveRequest('GET', '/account/addresses', { data: [] });
        const requests = serveRequest(
            'POST',
            '/account/addresses',
            created,
            201,
        );

        const page = await mountSuspended(AddressesPage, {
            route: '/cuenta/direcciones?redirect=/checkout',
        });
        await fillForm(page);
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => useRoute().path === '/checkout');

        expect(requests).toEqual([
            {
                recipient_name: 'Ana Pérez',
                phone: '+56 9 1234 5678',
                region: 'RM',
                commune: 'Ñuñoa',
                street: 'Av. Irarrázaval',
                street_number: '1234',
                apartment: 'Depto 501',
            },
        ]);
    });

    it('shows what the API rejected', async () => {
        serveRequest('GET', '/account/addresses', { data: [] });
        serveRequest('POST', '/account/addresses', invalid, 422);

        const page = await mountSuspended(AddressesPage, {
            route: '/cuenta/direcciones',
        });
        await fillForm(page);
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => page.find('[role="alert"]').exists());

        expect(page.find('[role="alert"]').text()).toContain(
            Object.values(invalid.errors)[0]![0],
        );
    });

    it('edits an address and deletes one after confirming', async () => {
        serveRequest('GET', '/account/addresses', addresses);
        const updates = serveRequest(
            'PUT',
            `/account/addresses/${saved.public_id}`,
            { data: { ...saved, street: 'Los Leones' } },
        );
        const deletes = serveRequest(
            'DELETE',
            `/account/addresses/${saved.public_id}`,
            undefined,
            204,
        );

        const page = await mountSuspended(AddressesPage, {
            route: '/cuenta/direcciones',
        });
        const editButton = page
            .findAll('button')
            .find((b) => b.text() === 'Editar')!;
        await editButton.trigger('click');
        expect((page.find('#commune').element as HTMLSelectElement).value).toBe(
            saved.commune,
        );
        await page.find('#street').setValue('Los Leones');
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => updates.length === 1);
        expect(updates[0]).toMatchObject({
            street: 'Los Leones',
            region: saved.region.code,
        });

        const deleteButton = () =>
            page
                .findAll('button')
                .find((b) => /^(Borrar|Confirmar borrado)$/.test(b.text()))!;
        await vi.waitUntil(() => deleteButton()?.text() === 'Borrar');
        await deleteButton().trigger('click');
        expect(deletes).toHaveLength(0);
        await deleteButton().trigger('click');
        await vi.waitUntil(() => deletes.length === 1);
    });
});
