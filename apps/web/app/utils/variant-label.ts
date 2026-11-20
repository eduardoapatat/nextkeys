import type { ProductSummary } from '~/api/schemas';
import { formatClp } from './money';

/**
 * Everything that tells two variants apart. The activation service is always
 * included: two variants can differ only in it (Steam vs Microsoft Store).
 */
export function variantLabel(product: ProductSummary): string {
    return [
        product.type.name,
        product.activation_service?.name,
        product.platforms.map((platform) => platform.name).join(', '),
        product.region?.name,
        formatClp(product.price),
    ]
        .filter((part) => part !== undefined && part !== '')
        .join(' · ');
}
