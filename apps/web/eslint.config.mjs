// @ts-check
import prettier from 'eslint-config-prettier';
import withNuxt from './.nuxt/eslint.config.mjs';

// Formatting belongs to Prettier; eslint-config-prettier turns off the
// stylistic rules that would fight it.
export default withNuxt(prettier);
