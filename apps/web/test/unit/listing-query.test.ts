import { describe, expect, it } from 'vitest';
import {
    catalogLink,
    listingQueryFromForm,
    parseListingQuery,
    serializeListingQuery,
    withPage,
} from '../../app/utils/listing-query';

describe('listing query', () => {
    it('reads comma-separated slugs, repeated keys, sort and page', () => {
        expect(
            parseListingQuery({
                platform: 'ps4,ps5',
                region: ['latam', 'global', 'latam'],
                type: '',
                sort: 'price',
                page: '2',
            }),
        ).toEqual({
            platform: ['ps4', 'ps5'],
            type: [],
            activation_service: [],
            region: ['latam', 'global'],
            sort: 'price',
            page: '2',
        });
    });

    it('serializes to a canonical query without empty values or page 1', () => {
        const query = parseListingQuery({
            platform: ['ps5', 'ps4'],
            page: '1',
        });

        expect(serializeListingQuery(query)).toEqual({ platform: 'ps5,ps4' });
        expect(serializeListingQuery(withPage(query, 3))).toEqual({
            platform: 'ps5,ps4',
            page: '3',
        });
    });

    it('passes unknown values through for the API to reject', () => {
        expect(
            serializeListingQuery(
                parseListingQuery({ platform: 'nope', sort: 'cheapest' }),
            ),
        ).toEqual({ platform: 'nope', sort: 'cheapest' });
    });

    it('builds the query from the filter form and drops the page', () => {
        const form = new FormData();
        form.append('platform', 'ps5');
        form.append('platform', 'switch');
        form.append('sort', 'name');
        form.append('page', '4');

        expect(serializeListingQuery(listingQueryFromForm(form))).toEqual({
            platform: 'ps5,switch',
            sort: 'name',
        });
    });

    it('links to the catalog at /catalogo with canonical filters', () => {
        expect(catalogLink()).toEqual({ path: '/catalogo', query: {} });
        expect(
            catalogLink({ platform: ['ps5', 'ps4'], sort: 'newest' }),
        ).toEqual({
            path: '/catalogo',
            query: { platform: 'ps5,ps4', sort: 'newest' },
        });
    });
});
