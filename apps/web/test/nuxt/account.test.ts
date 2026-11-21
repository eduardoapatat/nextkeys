import { mountSuspended } from '@nuxt/test-utils/runtime';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { ValidationErrorBody } from '../../app/api/schemas';
import { CustomerSchema, ValidationErrorSchema } from '../../app/api/schemas';
import App from '../../app/app.vue';
import LoginPage from '../../app/pages/cuenta/ingresar.vue';
import RegisterPage from '../../app/pages/cuenta/registro.vue';
import { fixture } from '../fixtures';
import { resetApi, serveCsrfCookie, servePost } from './api';

const customer = fixture('auth.me', CustomerSchema);
const invalidCredentials = fixture('auth.login.422', ValidationErrorSchema);

describe('customer account', () => {
    afterEach(resetApi);

    it('sends a visitor to sign in first, keeping the page to return to', async () => {
        await mountSuspended(App, { route: '/cuenta' });

        expect(useRoute().path).toBe('/cuenta/ingresar');
        expect(useRoute().query.redirect).toBe('/cuenta');
    });

    it('signs in and returns to the page asked for', async () => {
        serveCsrfCookie();
        const requests = servePost('/auth/login', customer);

        const page = await mountSuspended(LoginPage, {
            route: '/cuenta/ingresar?redirect=/cuenta',
        });
        await page.find('input[type="email"]').setValue('ana@example.com');
        await page.find('input[type="password"]').setValue('secreta');
        await page.find('input[type="checkbox"]').setValue(true);
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => useRoute().path === '/cuenta');

        expect(requests).toEqual([
            { email: 'ana@example.com', password: 'secreta', remember: true },
        ]);
        expect(useCustomer().value).toEqual(customer.data);
    });

    it('shows why the sign in failed', async () => {
        serveCsrfCookie();
        servePost('/auth/login', invalidCredentials, 422);

        const page = await mountSuspended(LoginPage, {
            route: '/cuenta/ingresar',
        });
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => page.find('[role="alert"]').exists());

        expect(page.find('[role="alert"]').text()).toBe(
            'Estas credenciales no son válidas.',
        );
        expect(useCustomer().value).toBeNull();
    });

    it('shows the registration errors from the API', async () => {
        serveCsrfCookie();
        const errors: ValidationErrorBody = {
            message: 'El correo electrónico ya está en uso.',
            errors: { email: ['El correo electrónico ya está en uso.'] },
        };
        servePost('/auth/register', errors, 422);

        const page = await mountSuspended(RegisterPage, {
            route: '/cuenta/registro',
        });
        await page.find('form').trigger('submit');
        await vi.waitUntil(() => page.find('[role="alert"]').exists());

        expect(page.find('[role="alert"]').text()).toBe(
            'El correo electrónico ya está en uso.',
        );
    });

    it('links the account from the header when signed in', async () => {
        const signedOut = await mountSuspended(App, {
            route: '/cuenta/ingresar',
        });
        expect(signedOut.find('header a[href="/cuenta/ingresar"]').text()).toBe(
            'Ingresar',
        );

        useCustomer().value = customer.data;
        const signedIn = await mountSuspended(App, { route: '/cuenta' });

        expect(signedIn.find('header a[href="/cuenta"]').text()).toBe(
            customer.data.name,
        );
    });
});
