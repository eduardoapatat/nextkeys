import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, it } from 'vitest';

const appDir = join(import.meta.dirname, '../../app');

function filesOf(dir: string, extensions: string[]): string[] {
    return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) return filesOf(path, extensions);
        return extensions.some((ext) => entry.name.endsWith(ext)) ? [path] : [];
    });
}

const vueFiles = (dir: string) => filesOf(dir, ['.vue']);

// The color tokens defined in @theme.
const tokens = new Set(
    [
        ...readFileSync(join(appDir, 'assets/css/main.css'), 'utf8').matchAll(
            /--color-([a-z0-9-]+):/g,
        ),
    ].map((match) => match[1]),
);

// Color utilities (`bg-fg`, `text-accent/80`, `hover:border-brand`…) and the
// non-color utilities that share their prefixes.
const colorUtility =
    /(?<![\w-])(?:bg|text|border(?:-[trblxy])?|ring|outline|fill|stroke|decoration|divide|placeholder|caret)-([a-z][a-z0-9-]*)/g;
const notColors = new Set([
    ...[
        'xs',
        'sm',
        'base',
        'lg',
        'xl',
        '2xl',
        '3xl',
        '4xl',
        '5xl',
        '6xl',
        '7xl',
    ],
    ...['left', 'center', 'right', 'balance', 'pretty', 'wrap', 'nowrap'],
    ...['ellipsis', 'clip', 'start', 'end', 'justify'],
    ...['solid', 'dashed', 'dotted', 'double', 'none', 'hidden', 'collapse'],
    ...['offset', 'inset', 'auto', 'from-font', 'cover', 'contain', 'center'],
    ...['no-repeat', 'repeat', 'fixed', 'local', 'scroll', 'origin-border'],
]);
// Sides (`border-b`, `border-t-0`), offsets, widths and SVG stroke attributes.
const notColorPattern =
    /^(?:[trblxy](?:-\d+)?|offset-\d+|\d.*|width|linecap|linejoin|color)$/;

// Hex colors (not `href="#..."` fragments), color functions and Tailwind
// arbitrary color values such as `bg-[#123456]`.
const rawColor =
    /(?<![-\w]="?)#(?:[0-9a-f]{8}|[0-9a-f]{6}|[0-9a-f]{3})\b|\b(?:rgba?|hsla?|oklch|oklab)\(|-\[#/gi;

describe('design tokens', () => {
    it('only uses color utilities whose token exists in @theme', () => {
        const unknown = filesOf(appDir, ['.vue', '.ts']).flatMap((file) =>
            [...readFileSync(file, 'utf8').matchAll(colorUtility)]
                .map((match) => match[1]!)
                .filter(
                    (name) =>
                        !tokens.has(name) &&
                        !notColors.has(name) &&
                        !notColorPattern.test(name),
                )
                .map((name) => `${relative(appDir, file)}: ${name}`),
        );

        expect(
            unknown,
            'Tailwind silently drops a color class without a token: use a token from app/assets/css/main.css (@theme), e.g. `text-fg`, `bg-surface`, `bg-accent`, `border-brand`, or add the token there.',
        ).toEqual([]);
    });

    it('keeps raw colors out of the components', () => {
        const offenders = vueFiles(appDir).flatMap((file) =>
            [...readFileSync(file, 'utf8').matchAll(rawColor)].map(
                (match) => `${relative(appDir, file)}: ${match[0]}`,
            ),
        );

        expect(
            offenders,
            'Use the color tokens from app/assets/css/main.css (@theme), e.g. `bg-surface` or `text-accent`; add a token there if the palette lacks one.',
        ).toEqual([]);
    });
});
