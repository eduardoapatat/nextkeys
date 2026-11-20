const clp = new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
});

/** Prices come from the API as integer CLP. */
export function formatClp(amount: number): string {
    return clp.format(amount);
}
