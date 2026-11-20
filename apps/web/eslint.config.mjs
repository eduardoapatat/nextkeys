// @ts-check
import prettier from 'eslint-config-prettier';
import withNuxt from './.nuxt/eslint.config.mjs';

const schemasOnlyInTests =
    'Use `import type`: the valibot schemas only parse the contract fixtures in tests, and valibot is a dev dependency that must not reach the bundle.';

// Formatting belongs to Prettier; eslint-config-prettier turns off the
// stylistic rules that would fight it.
export default withNuxt(
    {
        files: ['app/**/*.{ts,vue}'],
        ignores: ['app/api/schemas.ts'],
        rules: {
            '@typescript-eslint/no-restricted-imports': [
                'error',
                {
                    paths: [
                        {
                            name: 'valibot',
                            message: schemasOnlyInTests,
                            allowTypeImports: true,
                        },
                    ],
                    patterns: [
                        {
                            group: ['**/api/schemas'],
                            message: schemasOnlyInTests,
                            allowTypeImports: true,
                        },
                    ],
                },
            ],
        },
    },
    prettier,
);
