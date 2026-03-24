/**
 * Rollup configuration for building the plugin assets
 *
 * USAGE: npx rollup --config rollup.config.mjs
 */

import css from "rollup-plugin-import-css";
import terser from '@rollup/plugin-terser';
import copy from 'rollup-plugin-copy';

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
  plugins: [
    css(),
    copy({
      targets: [
        {src: 'node_modules/annotate-pdf', dest: 'resources'}
      ]
    })
  ],
  treeshake: false
};

