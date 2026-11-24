const dateTime = new Intl.DateTimeFormat('es-CL', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'America/Santiago',
});

/** An ISO 8601 date from the API, in Chilean time. */
export function formatDateTime(iso: string): string {
    return dateTime.format(new Date(iso));
}

const date = new Intl.DateTimeFormat('es-CL', {
    dateStyle: 'long',
    timeZone: 'America/Santiago',
});

/** An ISO 8601 date from the API as a day, in Chilean time. */
export function formatDate(iso: string): string {
    return date.format(new Date(iso));
}
