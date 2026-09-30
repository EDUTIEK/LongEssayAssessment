/**
 * Rollup configuration for building the plugin assets
 *
 * DEVELOPMENT
 *    npm ci --ignore-scripts
 *    npx rollup --config rollup.config.mjs
 * Then commit the created resources
 *
 * PRODUCTION
 * run composer du in the ilias main directory
 * This will copy the resources to the public components directory
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
        {src: 'node_modules/pdf-viewer', dest: 'resources'}
      ]
    })
  ],
  treeshake: false
};

