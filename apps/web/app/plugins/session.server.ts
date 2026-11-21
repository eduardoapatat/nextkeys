import { fetchCustomer } from '~/api/auth';

/**
 * Loads the customer of the request's session before rendering, so pages
 * and route middleware know it during SSR. An API failure renders the page
 * as signed out instead of failing it.
 */
export default defineNuxtPlugin(async () => {
    const customer = useCustomer();

    try {
        customer.value = await fetchCustomer();
    } catch (error) {
        console.error('Could not load the customer session', error);
        customer.value = null;
    }
});
