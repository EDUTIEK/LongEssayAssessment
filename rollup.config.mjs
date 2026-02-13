/**
 * Rollup configuration for building the plugin asset
 * USAGE: npx rollup --config rollup.config.mjs
 */

import css from "./node_modules/rollup-plugin-import-css/dist/index.js";
import terser from './node_modules/@rollup/plugin-terser/dist/es/index.js';

export default {
  external: ['ilias'],
  input: './rollup.input.mjs',
  output: [
    {
      file: './resources/js/xlas.js',
      format: 'iife',
      globals: {
        ilias: 'il'
      },
    },
    {
        file: './resources/js/xlas.min.js',
        format: 'iife',
        globals: {
          ilias: 'il'
        },
        plugins: [terser({})],
      }
  ],
  plugins: [css()],
  treeshake: false
};

