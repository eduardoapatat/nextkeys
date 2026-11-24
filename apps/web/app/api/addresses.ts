import type { Address, Shipping } from './schemas';
import { del, post, put } from './auth';

/** What the address form sends: the region by its code. */
export type AddressInput = {
    recipient_name: string;
    phone: string;
    region: string;
    commune: string;
    street: string;
    street_number: string;
    apartment: string;
};

export async function fetchAddresses(): Promise<Address[]> {
    const response = await useNuxtApp().$api<{ data: Address[] }>(
        '/account/addresses',
    );

    return response.data;
}

export async function createAddress(address: AddressInput): Promise<Address> {
    const response = await post<{ data: Address }>(
        '/account/addresses',
        address,
    );

    return response.data;
}

export async function updateAddress(
    publicId: string,
    address: AddressInput,
): Promise<Address> {
    const response = await put<{ data: Address }>(
        `/account/addresses/${encodeURIComponent(publicId)}`,
        address,
    );

    return response.data;
}

export function deleteAddress(publicId: string): Promise<void> {
    return del(`/account/addresses/${encodeURIComponent(publicId)}`);
}

/** The flat shipping fee and the regions with their communes. */
export async function fetchShipping(): Promise<Shipping> {
    const response = await useNuxtApp().$api<{ data: Shipping }>('/shipping');

    return response.data;
}
