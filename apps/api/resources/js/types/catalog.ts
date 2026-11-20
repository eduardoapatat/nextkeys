export type ProductTypeValue = 'digital_key' | 'license' | 'physical';

export type SelectOption<T extends string | number = number> = {
    value: T;
    label: string;
};

/**
 * The choices of the product form, sent by ProductController::formOptions().
 */
export type ProductFormOptions = {
    types: SelectOption<ProductTypeValue>[];
    titles: SelectOption[];
    activationServices: SelectOption[];
    regions: SelectOption[];
    platforms: SelectOption[];
};

export type ProductFormValues = {
    id: number;
    type: ProductTypeValue;
    title_id: number;
    activation_service_id: number | null;
    region_id: number | null;
    platform_ids: number[];
    price: number;
    stock: number | null;
    is_published: boolean;
    slug: string;
    cover_url: string | null;
};

export type TitleFormValues = {
    id: number;
    name: string;
    description: string;
};

/** Flashed by ProductKeyController::store; rejections are capped at 100. */
export type KeyImportResult = {
    imported: number;
    rejectedCount: number;
    rejections: { line: number; reason: string }[];
};

export type KeyProduct = {
    id: number;
    slug: string;
    type: string;
    statusCounts: { label: string; total: number }[];
};
