import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, it } from 'vitest';

const appDir = join(import.meta.dirname, '../../app');

function vueFiles(dir: string): string[] {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) return vueFiles(path);
        return entry.name.endsWith('.vue') ? [path] : [];
    });
}

// Hex colors (not `href="#..."` fragments), color functions and Tailwind
// arbitrary color values such as `bg-[#123456]`.
const rawColor =
    /(?<![-\w]="?)#(?:[0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{3})\b|\b(?:rgba?|hsla?|oklch|oklab)\(|-\[#/gi;

describe('design tokens', () => {
    it('keeps raw colors out of the components', () => {
        const offenders = vueFiles(appDir).flatMap((file) =>
            [...readFileSync(file, 'utf8').matchAll(rawColor)].map(
                (match) => `${relative(appDir, file)}: ${match[0]}`,
            ),
        );

        expect(
            offenders,
            'Use the color tokens from app/assets/css/main.css (@theme), e.g. `bg-panel` or `text-sticker`; add a token there if the palette lacks one.',
        ).toEqual([]);
    });
});
