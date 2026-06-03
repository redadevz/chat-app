import { defineConfig, splitVendorChunkPlugin } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";
import tailwindcss from "tailwindcss";

// In Docker, Vite must bind to 0.0.0.0 and tell the browser to reach the
// HMR websocket at localhost (the mapped port). Outside Docker this block is
// inert, so host-based `npm run craftable-pro:dev` is unchanged.
const inDocker = process.env.DOCKER === "true";

export default defineConfig({
  ...(inDocker && {
    server: {
      host: "0.0.0.0",
      port: 5173,
      hmr: { host: "localhost" },
    },
  }),
  plugins: [
    splitVendorChunkPlugin(),
    laravel({
      input: [
        "resources/js/craftable-pro/index.ts",
        "resources/css/craftable-pro.css",
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  css: {
    postcss: {
      plugins: [
        tailwindcss({
          config: "./craftable-pro.tailwind.config.js",
        }),
      ],
    },
  },
  resolve: {
    alias: {
      "@": resolve(__dirname, "./resources/js"),
      "craftable-pro": resolve(
        __dirname,
        "./vendor/brackets/craftable-pro/resources/js"
      ),
      ziggy: resolve(__dirname, "./vendor/tightenco/ziggy"),
    },
  },
});
