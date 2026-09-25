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
  // Konfigurasi devServer agar bisa diakses dari luar/NAT
  devServer: {
    host: '0.0.0.0',
    port: 3000
  },

  runtimeConfig: {
    public: {
      apiBase:process.env.NUXT_PUBLIC_API_BASE || 'https://apihelpdesk.s-net.my.id'
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