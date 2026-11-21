import type { ProductSummary } from '~/api/schemas';
import { formatClp } from './money';

/**
 * Everything that tells two variants apart. The activation service is always
 * included: two variants can differ only in it (Steam vs Microsoft Store).
 */
export function variantLabel(product: ProductSummary): string {
    return `${variantFacts(product)} · ${formatClp(product.price)}`;
}

/** The variant's facts without its price, for lines that show the price apart. */
export function variantFacts(product: ProductSummary): string {
    return [
        product.type.name,
        product.activation_service?.name,
        product.platforms.map((platform) => platform.name).join(', '),
        product.region?.name,
    ]
        .filter((part) => part !== undefined && part !== '')
        .join(' · ');
}
