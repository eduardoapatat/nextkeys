import type { Customer } from '~/api/schemas';

/**
 * The signed in customer, or `null`. The server loads it once per request
 * (plugins/session.server.ts) and the browser hydrates it from the payload;
 * sign in, registration and sign out update it.
 */
export function useCustomer() {
    return useState<Customer | null>('customer', () => null);
}
