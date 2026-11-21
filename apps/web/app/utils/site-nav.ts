/**
 * The store's merchandising menu: platform families and product types,
 * each a real catalog filter. The slugs are the catalog's platform and type
 * slugs (`/api/v1/catalog/filters`); a slug the API does not know answers
 * 422 in the catalog, so renames show up at once.
 */
export const platformFamilies = [
    { label: 'PlayStation', platform: ['ps5', 'ps4'] },
    { label: 'Xbox', platform: ['xbox-series', 'xbox-one'] },
    { label: 'Nintendo', platform: ['switch-2', 'switch'] },
    { label: 'PC y Mac', platform: ['windows', 'macos'] },
] as const;

export const productTypeLinks = [
    { label: 'Claves digitales', type: ['digital_key'] },
    { label: 'Licencias', type: ['license'] },
    { label: 'Juegos físicos', type: ['physical'] },
] as const;
