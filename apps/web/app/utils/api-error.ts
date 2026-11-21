export interface ApiError {
    statusCode: number | undefined;
    messages: string[];
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

/**
 * Reads a failed API call (an ofetch FetchError, or the NuxtError that
 * useAsyncData wraps it in): its status and the messages of the body.
 */
export function toApiError(error: unknown): ApiError {
    if (!isRecord(error)) {
        return { statusCode: undefined, messages: [] };
    }

    const statusCode =
        typeof error.statusCode === 'number' ? error.statusCode : undefined;
    const data = error.data;

    if (!isRecord(data)) {
        return { statusCode, messages: [] };
    }

    if (isRecord(data.errors)) {
        const messages = Object.values(data.errors)
            .flat()
            .filter((message) => typeof message === 'string');

        return { statusCode, messages: [...new Set(messages)] };
    }

    return {
        statusCode,
        messages: typeof data.message === 'string' ? [data.message] : [],
    };
}

const genericFormError =
    'No pudimos completar la solicitud. Vuelve a intentarlo.';

/**
 * The messages a form shows for a failed API call: the validation messages
 * of a 422, a Spanish rate limit notice for a 429, or a generic message.
 */
export function formErrorMessages(error: unknown): string[] {
    const { statusCode, messages } = toApiError(error);

    if (statusCode === 429) {
        return ['Demasiados intentos. Espera un minuto y vuelve a intentarlo.'];
    }

    if (statusCode === 422 && messages.length > 0) {
        return messages;
    }

    return [genericFormError];
}
