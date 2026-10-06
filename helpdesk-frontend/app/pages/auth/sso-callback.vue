<script setup>
definePageMeta({
  layout: false
})

const route = useRoute()
const router = useRouter()
const { loginWithAuthToken, loginWithSsoJwt } = useAuth()

const isLoading = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  const token = route.query.token ? String(route.query.token) : ''
  const role = route.query.role ? String(route.query.role) : ''
  const error = route.query.error ? String(route.query.error) : ''

  if (error) {
    isLoading.value = false
    if (error === 'sso_unauthorized') {
      errorMessage.value = 'Autentikasi SSO gagal. Token tidak valid atau sesi telah kedaluwarsa.'
    } else if (error === 'sso_server_error') {
      errorMessage.value = 'Layanan SSO UNPAM sedang tidak dapat dijangkau. Silakan coba sesaat lagi.'
    } else if (error === 'sso_missing_token') {
      errorMessage.value = 'Parameter token callback SSO tidak ditemukan.'
    } else {
      errorMessage.value = 'Terjadi kesalahan saat masuk via SSO.'
    }
    return
  }

  if (!token) {
    isLoading.value = false
    errorMessage.value = 'Tidak ada token autentikasi yang diterima.'
    return
  }

  try {
    // Jika token adalah JWT (biasanya diawali "ey..."), kirim ke backend /api/loginsso untuk diverifikasi
    if (token.startsWith('ey') || token.split('.').length === 3) {
      await loginWithSsoJwt(token)
    } else {
      // Jika token adalah Sanctum token dari redirect backend
      await loginWithAuthToken(token, role)
    }
  } catch (err) {
    isLoading.value = false
    errorMessage.value = err.data?.message || err.message || 'Gagal memproses sesi login SSO.'
  }
})

const backToHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-xl text-center">
      <!-- Logo UNPAM -->
      <div class="flex justify-center mb-6">
        <img src="/unpam.png" alt="Logo UNPAM" class="w-16 h-16 object-contain" />
      </div>

      <!-- State Loading -->
      <div v-if="isLoading" class="py-6 space-y-4">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-indigo-600 border-t-transparent"></div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Menghubungkan Akun SSO...</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Sedang memverifikasi identitas Anda dengan sistem SSO UNPAM dan menyinkronkan data profil.
        </p>
      </div>

      <!-- State Error -->
      <div v-else-if="errorMessage" class="py-4 space-y-4">
        <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 mx-auto flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Gagal Masuk SSO</h2>
        <p class="text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 p-3 rounded-xl border border-rose-200 dark:border-rose-900 leading-relaxed">
          {{ errorMessage }}
        </p>
        <button
          @click="backToHome"
          class="w-full mt-4 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition cursor-pointer"
        >
          Kembali ke Halaman Utama
        </button>
      </div>
    </div>
  </div>
</template>
