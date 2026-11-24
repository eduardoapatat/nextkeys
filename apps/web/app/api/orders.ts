import type { Order, OrderPage, PaymentRedirect } from './schemas';
import { post } from './auth';

export function fetchOrders(page = 1): Promise<OrderPage> {
    return useNuxtApp().$api<OrderPage>('/orders', { query: { page } });
}

export async function fetchOrder(reference: string): Promise<Order> {
    const response = await useNuxtApp().$api<{ data: Order }>(
        `/orders/${encodeURIComponent(reference)}`,
    );

    return response.data;
}

/**
 * Places the order from the cart; the API checks prices and stock. Physical
 * products ship to `shippingAddress`, a saved address's `public_id`.
 */
export async function placeOrder(
    items: Array<{ slug: string; quantity: number }>,
    shippingAddress: string | null = null,
): Promise<Order> {
    const response = await post<{ data: Order }>(
        '/orders',
        shippingAddress === null
            ? { items }
            : { items, shipping_address: shippingAddress },
    );

    return response.data;
}

/** Starts a Webpay attempt: post `token` to `url` with `postToWebpay`. */
export async function startPayment(
    reference: string,
): Promise<PaymentRedirect> {
    const response = await post<{ data: PaymentRedirect }>(
        `/orders/${encodeURIComponent(reference)}/payments`,
    );

    return response.data;
}

export async function cancelOrder(reference: string): Promise<Order> {
    const response = await post<{ data: Order }>(
        `/orders/${encodeURIComponent(reference)}/cancel`,
    );

    return response.data;
}
