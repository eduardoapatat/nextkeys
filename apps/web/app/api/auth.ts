import type { Customer } from './schemas';
import { toApiError } from '~/utils/api-error';
import { readCookie } from '~/utils/xsrf';

/** Sanctum sets the `XSRF-TOKEN` cookie that every POST sends back. */
async function ensureCsrfCookie(): Promise<void> {
    if (import.meta.client && !readCookie(document.cookie, 'XSRF-TOKEN')) {
        await useNuxtApp().$api('/auth/csrf-cookie');
    }
}

export async function post<T>(
    path: string,
    body?: Record<string, unknown>,
    query?: Record<string, string>,
): Promise<T> {
    await ensureCsrfCookie();

    return useNuxtApp().$api<T>(path, { method: 'POST', body, query });
}

/** The signed in customer, or `null` without a session (401). */
export async function fetchCustomer(): Promise<Customer | null> {
    try {
        const response = await useNuxtApp().$api<{ data: Customer }>(
            '/auth/me',
        );

        return response.data;
    } catch (error) {
        if (toApiError(error).statusCode === 401) {
            return null;
        }

        throw error;
    }
}

export async function login(credentials: {
    email: string;
    password: string;
    remember: boolean;
}): Promise<Customer> {
    const response = await post<{ data: Customer }>('/auth/login', credentials);

    return response.data;
}

export async function register(registration: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}): Promise<Customer> {
    const response = await post<{ data: Customer }>(
        '/auth/register',
        registration,
    );

    return response.data;
}

export async function logout(): Promise<void> {
    await post('/auth/logout');
}

export async function resendVerification(): Promise<void> {
    await post('/auth/email/verification-notification');
}

/** The parameters of the verification link the customer got by email. */
export interface VerificationLink {
    id: string;
    hash: string;
    expires: string;
    signature: string;
}

export async function verifyEmail(link: VerificationLink): Promise<void> {
    await post(
        `/auth/email/verify/${encodeURIComponent(link.id)}/${encodeURIComponent(link.hash)}`,
        undefined,
        { expires: link.expires, signature: link.signature },
    );
}

/** Answers the same message whether the email has an account or not. */
export async function forgotPassword(email: string): Promise<string> {
    const response = await post<{ message: string }>('/auth/forgot-password', {
        email,
    });

    return response.message;
}

export async function resetPassword(reset: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}): Promise<void> {
    await post('/auth/reset-password', reset);
}
