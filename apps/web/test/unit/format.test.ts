import { describe, expect, it } from 'vitest';
import { ProductDetailSchema } from '../../app/api/schemas';
import { toApiError } from '../../app/utils/api-error';
import { formatClp } from '../../app/utils/money';
import { variantFacts, variantLabel } from '../../app/utils/variant-label';
import { fixture } from '../fixtures';

describe('formatClp', () => {
    it('formats integer CLP with es-CL', () => {
        expect(formatClp(19990)).toBe('$19.990');
        expect(formatClp(1250000)).toBe('$1.250.000');
    });
});

describe('variantLabel', () => {
    const variants = fixture('products.show', ProductDetailSchema).data
        .variants;

    it('includes type, activation service, platforms, region and price', () => {
        const steam = variants.find(
            (variant) => variant.slug === 'hades-steam-macos-windows-global',
        );

        expect(steam && variantLabel(steam)).toBe(
            'Clave digital · Steam · Windows, macOS · Global · $19.990',
        );
        // Lines that print the price in their own column leave it out.
        expect(steam && variantFacts(steam)).toBe(
            'Clave digital · Steam · Windows, macOS · Global',
        );
    });

    it('skips the parts a variant does not have', () => {
        const physical = variants.find(
            (variant) => variant.activation_service === null,
        );

        expect(physical && variantLabel(physical)).toBe(
            'Físico · PS5 · North America · $34.990',
        );
    });
});

describe('toApiError', () => {
    it('flattens the validation messages of a 422', () => {
        expect(
            toApiError({
                statusCode: 422,
                data: {
                    message: 'La plataforma «a» no existe. (and 1 more error)',
                    errors: {
                        'platform.0': ['La plataforma «a» no existe.'],
                        sort: ['El orden no es válido.'],
                    },
                },
            }),
        ).toEqual({
            statusCode: 422,
            messages: [
                'La plataforma «a» no existe.',
                'El orden no es válido.',
            ],
        });
    });

    it('keeps the status of other errors', () => {
        expect(toApiError({ statusCode: 404, data: { message: 'x' } })).toEqual(
            { statusCode: 404, messages: ['x'] },
        );
        expect(toApiError(new Error('network'))).toEqual({
            statusCode: undefined,
            messages: [],
        });
    });
});
