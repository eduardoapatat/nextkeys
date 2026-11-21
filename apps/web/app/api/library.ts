import type { LibraryPage, RevealedKey } from './schemas';
import { post } from './auth';

export function fetchLibrary(page = 1): Promise<LibraryPage> {
    return useNuxtApp().$api<LibraryPage>('/library', { query: { page } });
}

/** The codes of one line: the API records the first reveal of each key. */
export async function revealKeys(
    reference: string,
    slug: string,
): Promise<RevealedKey[]> {
    const response = await post<{ data: { keys: RevealedKey[] } }>(
        `/library/${encodeURIComponent(reference)}/${encodeURIComponent(slug)}/keys`,
    );

    return response.data.keys;
}
