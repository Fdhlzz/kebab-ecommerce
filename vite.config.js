import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import laravel from 'laravel-vite-plugin'
import { fileURLToPath } from 'node:url'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { VueRouterAutoImports, getPascalCaseRouteName } from 'unplugin-vue-router'
import VueRouter from 'unplugin-vue-router/vite'
import { defineConfig, loadEnv } from 'vite'
import MetaLayouts from 'vite-plugin-vue-meta-layouts'
import vuetify from 'vite-plugin-vuetify'
import svgLoader from 'vite-svg-loader'

export default defineConfig(({ mode }) => {
  // 1. Load env variables
  const env = loadEnv(mode, process.cwd(), '')

  // 2. Extract Hostname (IP) from APP_URL
  // Example: "http://10.153.156.22:8000" -> "10.153.156.22"
  const appUrl = env.APP_URL || 'http://localhost:8000'
  let host = 'localhost'
  
  try {
    const urlObj = new URL(appUrl)

    host = urlObj.hostname
  } catch (e) {
    console.warn('⚠️ Could not parse APP_URL, falling back to localhost')
  }

  // 3. Define Vite Port (Keep separate from Laravel's 8000)
  const port = 5173 

  return {
    plugins: [
      // -----------------------------
      // Vue Router Auto Routes
      // -----------------------------
      VueRouter({
        getRouteName: routeNode =>
          getPascalCaseRouteName(routeNode)
            .replace(/([a-z\d])([A-Z])/g, '$1-$2')
            .toLowerCase(),
        routesFolder: 'resources/js/pages',
      }),

      // -----------------------------
      // Vue
      // -----------------------------
      vue({
        template: {
          compilerOptions: {
            isCustomElement: tag =>
              tag === 'swiper-container' || tag === 'swiper-slide',
          },
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),

      // -----------------------------
      // Laravel Vite Plugin
      // -----------------------------
      laravel({
        input: ['resources/js/main.js'],
        refresh: true,
      }),

      vueJsx(),

      // -----------------------------
      // Vuetify
      // -----------------------------
      vuetify({
        styles: {
          configFile: 'resources/styles/variables/_vuetify.scss',
        },
      }),

      // -----------------------------
      // Meta Layouts
      // -----------------------------
      MetaLayouts({
        target: './resources/js/layouts',
        defaultLayout: 'default',
      }),

      // -----------------------------
      // Auto Components
      // -----------------------------
      Components({
        dirs: [
          'resources/js/@core/components',
          'resources/js/views/demos',
          'resources/js/components',
        ],
        dts: true,
        resolvers: [
          componentName => {
            if (componentName === 'VueApexCharts')
              return {
                name: 'default',
                from: 'vue3-apexcharts',
                as: 'VueApexCharts',
              }
          },
        ],
      }),

      // -----------------------------
      // Auto Imports
      // -----------------------------
      AutoImport({
        imports: [
          'vue',
          VueRouterAutoImports,
          '@vueuse/core',
          '@vueuse/math',
          'vue-i18n',
          'pinia',
        ],
        dirs: [
          './resources/js/@core/utils',
          './resources/js/@core/composable/',
          './resources/js/composables/',
          './resources/js/utils/',
          './resources/js/plugins/*/composables/*',
        ],
        vueTemplate: true,
        ignore: ['useCookies', 'useStorage'],
        eslintrc: {
          enabled: true,
          filepath: './.eslintrc-auto-import.json',
        },
      }),

      svgLoader(),
    ],

    define: {
      'process.env': {},
    },

    resolve: {
      alias: {
        '@core-scss': fileURLToPath(new URL('./resources/styles/@core', import.meta.url)),
        '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        '@themeConfig': fileURLToPath(new URL('./themeConfig.js', import.meta.url)),
        '@core': fileURLToPath(new URL('./resources/js/@core', import.meta.url)),
        '@layouts': fileURLToPath(new URL('./resources/js/@layouts', import.meta.url)),
        '@images': fileURLToPath(new URL('./resources/images/', import.meta.url)),
        '@styles': fileURLToPath(new URL('./resources/styles/', import.meta.url)),
        '@configured-variables': fileURLToPath(
          new URL('./resources/styles/variables/_template.scss', import.meta.url),
        ),
        '@db': fileURLToPath(
          new URL('./resources/js/plugins/fake-api/handlers/', import.meta.url),
        ),
        '@api-utils': fileURLToPath(
          new URL('./resources/js/plugins/fake-api/utils/', import.meta.url),
        ),
      },
    },

    build: {
      chunkSizeWarningLimit: 5000,
    },

    optimizeDeps: {
      exclude: ['vuetify'],
      entries: ['./resources/js/**/*.vue'],
    },

    /**
      * ✅ Network-safe dev server
      * Automatically uses the IP from APP_URL for HMR
      */
    server: {
      host: '0.0.0.0', // Listen on all interfaces
      port: port,      // Run on 5173
      hmr: {
        host: host,    // Tell phone to look for assets at 10.x.x.x
      },
      cors: true,
      watch: {
        usePolling: true,
      },
    },
  }
})
