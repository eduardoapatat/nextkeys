import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import * as v from 'valibot';

// import.meta.dirname, not import.meta.url: the nuxt environment does not use file: URLs.
const contractsDir = resolve(import.meta.dirname, '../../../contracts/api/v1');

/**
 * Reads a contract fixture written by apps/api (ApiContractTest) and parses
 * it with its schema, so tests get typed data that matches the real API.
 */
export function fixture<TSchema extends v.GenericSchema>(
    name: string,
    schema: TSchema,
): v.InferOutput<TSchema> {
    const json: unknown = JSON.parse(
        readFileSync(resolve(contractsDir, `${name}.json`), 'utf8'),
    );

    return v.parse(schema, json);
}
