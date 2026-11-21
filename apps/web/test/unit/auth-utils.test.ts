import { describe, expect, it } from 'vitest';
import { formErrorMessages } from '../../app/utils/api-error';
import { safeRedirect } from '../../app/utils/safe-redirect';
import { readCookie, xsrfHeaders } from '../../app/utils/xsrf';

describe('xsrfHeaders', () => {
    const cookies = 'nextkeys-session=abc; XSRF-TOKEN=eyJpdiI6%3D%3D; other=1';

    it('copies the decoded XSRF-TOKEN cookie on requests that change state', () => {
        expect(xsrfHeaders('POST', cookies)).toEqual({
            'X-XSRF-TOKEN': 'eyJpdiI6==',
        });
        expect(xsrfHeaders('delete', cookies)).toEqual({
            'X-XSRF-TOKEN': 'eyJpdiI6==',
        });
    });

    it('adds nothing to reads or without the cookie', () => {
        expect(xsrfHeaders(undefined, cookies)).toEqual({});
        expect(xsrfHeaders('GET', cookies)).toEqual({});
        expect(xsrfHeaders('POST', 'nextkeys-session=abc')).toEqual({});
    });

    it('reads a cookie by its exact name', () => {
        expect(readCookie('MY-XSRF-TOKEN=a; XSRF-TOKEN=b', 'XSRF-TOKEN')).toBe(
            'b',
        );
        expect(readCookie('', 'XSRF-TOKEN')).toBeUndefined();
    });
});

describe('safeRedirect', () => {
    it('keeps an internal path', () => {
        expect(safeRedirect('/cuenta')).toBe('/cuenta');
        expect(safeRedirect('/starfall?x=1')).toBe('/starfall?x=1');
    });

    it.each([
        ['another origin', 'https://evil.test/cuenta'],
        ['a protocol-relative URL', '//evil.test'],
        ['a backslash URL', '/\\evil.test'],
        ['no value', undefined],
        ['a repeated parameter', ['/cuenta', '/otra']],
    ])('falls back to the account page for %s', (_case, value) => {
        expect(safeRedirect(value)).toBe('/cuenta');
    });
});

describe('formErrorMessages', () => {
    it('lists the validation messages', () => {
        expect(
            formErrorMessages({
                statusCode: 422,
                data: {
                    message: 'Estas credenciales no son válidas.',
                    errors: { email: ['Estas credenciales no son válidas.'] },
                },
            }),
        ).toEqual(['Estas credenciales no son válidas.']);
    });

    it('explains a rate limit in Spanish', () => {
        expect(
            formErrorMessages({
                statusCode: 429,
                data: { message: 'Too Many Attempts.' },
            }),
        ).toEqual([
            'Demasiados intentos. Espera un minuto y vuelve a intentarlo.',
        ]);
    });

    it('falls back to a generic message', () => {
        expect(formErrorMessages(new Error('network'))).toEqual([
            'No pudimos completar la solicitud. Vuelve a intentarlo.',
        ]);
        expect(
            formErrorMessages({
                statusCode: 500,
                data: { message: 'Server Error' },
            }),
        ).toEqual(['No pudimos completar la solicitud. Vuelve a intentarlo.']);
    });
});
