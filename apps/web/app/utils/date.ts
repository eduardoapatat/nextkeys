const dateTime = new Intl.DateTimeFormat('es-CL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'America/Santiago',
});

/** An ISO 8601 date from the API, in Chilean time. */
export function formatDateTime(iso: string): string {
    return dateTime.format(new Date(iso));
}
