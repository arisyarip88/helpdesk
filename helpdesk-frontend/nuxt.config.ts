// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  css: ['~/assets/css/main.css'],

  vite: {
    plugins: [
      tailwindcss(),
    ],
  },

  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api' // Sesuai dengan URL Laravel kamu
    }
  },

  modules: [
    '@pinia/nuxt',
    '@nuxtjs/color-mode'
  ],

  // Memastikan auto-import untuk folder stores berjalan lancar di Nuxt 4
  imports: {
    dirs: ['stores']
  },

  // @ts-ignore - Untuk menghilangkan error pembacaan tipe TS pada colorMode
  colorMode: {
    classSuffix: '', // Agar menghasilkan class 'dark' bukan 'dark-mode'
    preference: 'system', // Opsi awal: 'system', 'light', atau 'dark'
    fallback: 'light'
  }
})