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
};

export type TitleFormValues = {
    id: number;
    name: string;
    description: string;
};
