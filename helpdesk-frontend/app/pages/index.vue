<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'

const config = useRuntimeConfig()


// --- Modul Color Mode Nuxt ---
const colorMode = useColorMode()
const toggleDarkMode = () => {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}

// --- State Modal Login ---
const { login } = useAuth()
const isLoginModalOpen = ref(false)
const loginForm = ref({ username: '', password: '', remember: false })
const isLoading = ref(false)
const errorMessage = ref('')

// --- State & Logika Floating Chatbot AI ---
const isChatbotOpen = ref(false)
const chatInput = ref('')
const isBotTyping = ref(false)
const chatMessagesRef = ref(null)

// Ref untuk deteksi klik di luar area chatbot
const chatbotContainerRef = ref(null)
const chatbotButtonRef = ref(null)

const messages = ref([
  {
    sender: 'bot',
    text: 'Halo! Saya UNPAM Support AI. Ada yang bisa saya bantu terkait layanan Helpdesk hari ini?',
    options: []
  }
])

const quickPrompts = [
  'Cara buat tiket baru?',
  'Lupa password akun',
  'Jam operasional layanan'
]

// Deteksi klik sembarang di luar area Chatbot
const handleClickOutside = (event) => {
  if (
    isChatbotOpen.value &&
    chatbotContainerRef.value &&
    !chatbotContainerRef.value.contains(event.target) &&
    chatbotButtonRef.value &&
    !chatbotButtonRef.value.contains(event.target)
  ) {
    isChatbotOpen.value = false
  }
}

// Cek LocalStorage & pasang event listener saat dimuat
onMounted(() => {
  if (import.meta.client) {
    const savedUsername = localStorage.getItem('remember_username')
    if (savedUsername) {
      loginForm.value.username = savedUsername
      loginForm.value.remember = true
    }
    window.addEventListener('click', handleClickOutside)
  }
})

onUnmounted(() => {
  if (import.meta.client) {
    window.removeEventListener('click', handleClickOutside)
  }
})

const openLoginModal = () => {
  errorMessage.value = ''
  isLoginModalOpen.value = true
}
const closeLoginModal = () => { isLoginModalOpen.value = false }

const handleLogin = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    if (loginForm.value.remember) {
      localStorage.setItem('remember_username', loginForm.value.username)
    } else {
      localStorage.removeItem('remember_username')
    }

    await login({
      username: loginForm.value.username,
      password: loginForm.value.password,
      remember: loginForm.value.remember
    })
    closeLoginModal()
  } catch (err) {
    errorMessage.value = err.data?.message || 'Username atau kata sandi salah.'
  } finally {
    isLoading.value = false
  }
}

const toggleChatbot = () => {
  isChatbotOpen.value = !isChatbotOpen.value
  if (isChatbotOpen.value) scrollToBottom()
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatMessagesRef.value) {
    chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight
  }
}

// Helper Parse JSON Safe
const parseJsonSafe = (data) => {
  if (!data) return []
  if (Array.isArray(data)) return data
  if (typeof data === 'string') {
    try {
      const parsed = JSON.parse(data)
      return Array.isArray(parsed) ? parsed : []
    } catch (e) {
      return []
    }
  }
  return []
}

// Mengirim Pertanyaan ke Backend Laravel
const sendMessage = async (textToSend = null, customValue = null) => {
  const query = textToSend || chatInput.value.trim()
  if (!query) return

  // Push pesan user ke chat
  messages.value.push({ sender: 'user', text: query })
  if (!textToSend) chatInput.value = ''

  scrollToBottom()
  isBotTyping.value = true

  try {
    const res = await $fetch(`${config.public.apiBase}/chatbot/ask`, {
      method: 'POST',
      body: { 
        message: customValue || query 
      }
    })

    const botOptions = parseJsonSafe(res.options)

    messages.value.push({ 
      sender: 'bot', 
      text: res.answer || res.message || 'Maaf, saya tidak dapat memahami pertanyaan tersebut.',
      options: botOptions
    })
  } catch (error) {
    messages.value.push({ 
      sender: 'bot', 
      text: 'Gagal terhubung ke server support. Silakan coba beberapa saat lagi.',
      options: []
    })
  } finally {
    isBotTyping.value = false
    scrollToBottom()
  }
}

const features = [
  { icon: 'M13 10V3L4 14h7v7l9-11h-7z', title: 'Penanganan Kilat', desc: 'Sistem alokasi otomatis meneruskan tiket ke petugas yang tepat.' },
  { icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', title: 'Diskusi Real-time', desc: 'Percakapan langsung dan notifikasi instan untuk memantau progres.' },
  { icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', title: 'Aman & Terstruktur', desc: 'Setiap aduan tersimpan secara rapi dan riwayat penanganan transparan.' },
  { icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', title: 'Laporan Analitik', desc: 'Pantau performa layanan dan kepuasan pengguna melalui dasbor analitik.' }
]

const openUnpam = () => window.open('https://unpam.ac.id', '_blank')

const isHtml = (content) => {
  if (!content) return false
  const htmlRegex = /<[a-z][\s\S]*>/i
  return htmlRegex.test(content)
}

// Otomatis membuka chatbot 1 detik setelah halaman dimuat
onMounted(() => {
  setTimeout(() => {
    toggleChatbot()
  }, 2000)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-300 relative">

    <!-- Header Navbar -->
    <header class="border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">

        <NuxtLink to="/" class="flex items-center gap-3 group">
          <div class="transition transform hover:scale-105"> 
            <img 
              src="/unpam.png" 
              alt="Logo Helpdesk" 
              class="w-14 h-14 object-contain rounded-xl" 
            />
          </div>

          <div>
            <span class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">HELPDESK UNIVERSITAS PAMULANG</span>
            <span class="text-[10px] block font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Support Portal</span>
          </div>
        </NuxtLink>

        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600 dark:text-slate-300">
          <a href="#fitur" class="hover:text-indigo-600 dark:hover:text-white transition">Fitur Utama</a>
          <button
            @click="openLoginModal"
            class="hover:text-indigo-600 dark:hover:text-white transition cursor-pointer">Admin Panel</button>
        </nav>

        <div class="flex items-center gap-3">
          <button @click="toggleDarkMode" aria-label="Toggle Theme" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
            <svg v-if="colorMode.value === 'light'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <button @click="openLoginModal" class="text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-white px-4 py-2.5 transition cursor-pointer">Masuk</button>
          <button @click="openUnpam" class="text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2 cursor-pointer">
            <span>WEB RESMI UNPAM</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="pt-20 pb-24 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto text-center">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-8">
        <span class="flex h-2 w-2 rounded-full bg-indigo-600 dark:bg-emerald-400 animate-pulse"></span>
        Sistem Layanan Aduan & Support
      </div>

      <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white max-w-4xl mx-auto leading-tight">
        Layanan Helpdesk Terpadu, Cepat & <span class="text-indigo-600 dark:text-indigo-400">Transparan</span>
      </h1>

      <p class="mt-6 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
        Laporkan kendala Anda, lacak progres perbaikan secara berkala, dan berkomunikasi secara langsung dengan tim teknis kami.
      </p>

      <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
        <button @click="openLoginModal" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-md shadow-indigo-600/20 transition text-sm cursor-pointer">
          Buat Tiket Baru
        </button>
      </div>
    </section>

    <!-- Fitur Section -->
    <section id="fitur" class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="(feat, idx) in features" :key="idx" class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feat.icon" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">{{ feat.title }}</h3>
          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ feat.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Modal Login Popup -->
    <Teleport to="body">
      <div 
        v-if="isLoginModalOpen" 
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" 
        @click.self="closeLoginModal"
      >
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl p-6 sm:p-8 shadow-2xl relative">
          <button @click="closeLoginModal" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white p-2 cursor-pointer">✕</button>
          <h3 class="text-xl font-bold text-slate-900 dark:text-white text-center mb-6">Masuk Akun</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 text-center mt-1 mb-6">
  Gunakan username dan password akun satu.unpam
</p>

          <div v-if="errorMessage" class="mb-4 p-3 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-medium border border-rose-200 dark:border-rose-900">
            {{ errorMessage }}
          </div>

          <form @submit.prevent="handleLogin" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold mb-1 text-slate-700 dark:text-slate-300">Username</label>
              <input v-model="loginForm.username" type="text" required placeholder="Masukkan username" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1 text-slate-700 dark:text-slate-300">Kata Sandi</label>
              <input v-model="loginForm.password" type="password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>

            <div class="flex items-center justify-between pt-1 pb-2">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input 
                  v-model="loginForm.remember" 
                  type="checkbox" 
                  class="w-4 h-4 text-indigo-600 rounded border-slate-300 dark:border-slate-700 focus:ring-indigo-500 accent-indigo-600 cursor-pointer" 
                />
                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Ingat Saya</span>
              </label>

              <a href="#" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                Lupa Kata Sandi?
              </a>
            </div>

            <button type="submit" :disabled="isLoading" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition disabled:opacity-75 flex items-center justify-center gap-2 shadow-md cursor-pointer">
              <template v-if="isLoading">
                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memproses Akun...</span>
              </template>
              <template v-else>
                <span>Masuk Sekarang</span>
              </template>
            </button>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Window Chatbot AI -->
    <Teleport to="body">
      <div 
        v-if="isChatbotOpen" 
        ref="chatbotContainerRef"
        class="fixed bottom-20 left-2 right-2 z-50 w-auto sm:bottom-24 sm:left-auto sm:right-6 sm:w-[440px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl flex flex-col h-[min(460px,calc(100vh-6rem))] sm:h-[520px] overflow-hidden"
      >
        <!-- Header Modal Chat -->
        <div class="p-4 bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 text-white flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="relative w-9 h-9 rounded-full bg-white/20 flex items-center justify-center border border-white/30 shrink-0">
              <svg class="w-5 h-5 text-white fill-current" viewBox="0 0 24 24"><path d="M12 2a1 1 0 0 1 1 1v1.05A7.002 7.002 0 0 1 19 11v5a3 3 0 0 1-3 3h-1v2a1 1 0 0 1-1.6.8l-2.667-2H9a3 3 0 0 1-3-3v-5a7.002 7.002 0 0 1 6-6.95V3a1 1 0 0 1 1-1z"/></svg>
            </div>
            <div>
              <h4 class="text-sm font-bold">UNPAM Support AI</h4>
              <p class="text-[10px] text-indigo-100">Online • Knowledge Base Active</p>
            </div>
          </div>
          <button @click="toggleChatbot" class="text-white/80 hover:text-white p-1 cursor-pointer">✕</button>
        </div>

        <!-- Body Chat Messages -->
        <div ref="chatMessagesRef" class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/50 dark:bg-slate-950/50 text-xs">
          <div v-for="(msg, index) in messages" :key="index" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'" class="flex gap-2.5">
            <div v-if="msg.sender === 'bot'" class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white shrink-0 mt-0.5">
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2a1 1 0 0 1 1 1v1.05A7.002 7.002 0 0 1 19 11v5a3 3 0 0 1-3 3h-1v2a1 1 0 0 1-1.6.8l-2.667-2H9a3 3 0 0 1-3-3v-5a7.002 7.002 0 0 1 6-6.95V3a1 1 0 0 1 1-1z"/></svg>
            </div>

            <div class="space-y-2 max-w-[80%]">
              <div :class="msg.sender === 'user' ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none px-3.5 py-2.5' : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700/80 rounded-2xl rounded-tl-none px-3.5 py-2.5 shadow-sm'">
                <div v-if="isHtml(msg.text)" v-html="msg.text" class="leading-relaxed"></div>
                <p v-else class="whitespace-pre-line leading-relaxed">{{ msg.text }}</p>
              </div>

              <!-- Rendering Opsi Interaktif -->
              <div v-if="msg.sender === 'bot' && msg.options && msg.options.length > 0" class="flex flex-col gap-1.5 pt-1">
                <button 
                  v-for="(opt, oIdx) in msg.options" 
                  :key="oIdx"
                  @click="sendMessage(opt.label || opt.value, opt.value)"
                  class="w-full text-left px-3 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 font-medium rounded-xl text-xs transition cursor-pointer flex items-center justify-between"
                >
                  <span>{{ opt.label || opt.value }}</span>
                  <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Indikator Animasi Bot Ketik -->
          <div v-if="isBotTyping" class="flex gap-2.5 justify-start items-end">
            <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white shrink-0">
              <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2a1 1 0 0 1 1 1v1.05A7.002 7.002 0 0 1 19 11v5a3 3 0 0 1-3 3h-1v2a1 1 0 0 1-1.6.8l-2.667-2H9a3 3 0 0 1-3-3v-5a7.002 7.002 0 0 1 6-6.95V3a1 1 0 0 1 1-1z"/></svg>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-2xl rounded-tl-none px-4 py-3 flex items-center gap-1.5 shadow-sm">
              <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
              <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse [animation-delay:0.2s]"></span>
              <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 ml-1">AI sedang berpikir...</span>
            </div>
          </div>
        </div>

        <!-- Quick Prompts Bar -->
        <div class="px-3 py-2 bg-slate-100/70 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex gap-1.5 overflow-x-auto">
          <button v-for="(prompt, pIdx) in quickPrompts" :key="pIdx" @click="sendMessage(prompt)" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] text-indigo-600 dark:text-indigo-400 shrink-0 hover:bg-indigo-50 dark:hover:bg-slate-700 transition cursor-pointer">
            {{ prompt }}
          </button>
        </div>

        <!-- Input Chat -->
        <form @submit.prevent="sendMessage()" class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
          <input v-model="chatInput" type="text" placeholder="Tanyakan sesuatu..." class="flex-1 bg-slate-100 dark:bg-slate-800 border-0 rounded-xl px-3.5 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none" />
          <button type="submit" :disabled="!chatInput.trim() || isBotTyping" class="p-2 rounded-xl bg-indigo-600 text-white disabled:opacity-40 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
          </button>
        </form>
      </div>
    </Teleport>

    <!-- Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3">

      <!-- Button Robot AI -->
      <button 
        ref="chatbotButtonRef"
        @click="toggleChatbot" 
        aria-label="Tanya AI Assistant" 
        class="relative bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white p-3.5 rounded-full shadow-lg hover:shadow-indigo-500/40 transition-all duration-300 transform hover:scale-105 flex items-center justify-center group cursor-pointer"
      >
        <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-cyan-400 border-2 border-slate-900"></span>
        </span>
        <svg class="w-7 h-7 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v3M12 2a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z" fill="currentColor"/>
          <rect x="4" y="5" width="16" height="12" rx="4" stroke="currentColor" stroke-width="1.8" />
          <circle cx="8.5" cy="10" r="1.5" fill="currentColor" />
          <circle cx="15.5" cy="10" r="1.5" fill="currentColor" />
          <path d="M9 14h6" stroke-width="1.8" />
          <path d="M2 10h2M20 10h2" stroke-width="2" />
        </svg>
        <span class="max-w-0 overflow-hidden whitespace-nowrap group-hover:max-w-xs group-hover:ml-2 transition-all duration-300 text-xs font-semibold">Tanya SasMi AI</span>
      </button>

      <!-- Button WhatsApp -->
      <a href="https://wa.me/6281293812467?text=Halo%20Admin%20Helpdesk%20UNPAM,%20saya%20butuh%20bantuan." target="_blank" rel="noopener noreferrer" aria-label="Hubungi via WhatsApp" class="bg-emerald-500 hover:bg-emerald-600 text-white p-3.5 rounded-full shadow-lg hover:shadow-emerald-500/30 transition-all duration-300 transform hover:scale-105 flex items-center justify-center group cursor-pointer">
        <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24">
          <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.892 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
        </svg>
        <span class="max-w-0 overflow-hidden whitespace-nowrap group-hover:max-w-xs group-hover:ml-2 transition-all duration-300 text-xs font-semibold">Hubungi kami</span>
      </a>

    </div>

  </div>
</template>