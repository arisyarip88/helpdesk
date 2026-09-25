<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

definePageMeta({
  layout: 'user',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const storageBase = config.public.storageBase || apiBase.replace(/\/api\/?$/, '') + '/storage'

const { token, user, hasRole } = useAuth()
const route = useRoute()
const router = useRouter()

// Identitas Reaktif User
const department_id = computed(() => user.value?.department_id)
const userId = computed(() => user.value?.id)
const userRoleId = computed(() => Number(user.value?.role_id))

const getAuthHeaders = () => ({
  'Accept': 'application/json',
  'Authorization': `Bearer ${token.value}`
})

// --- 1. STATE FILTER & PAGINASI ---
const currentPage = ref(Number(route.query.page) || 1)
const perPage = ref(Number(route.query.per_page) || 4)
const searchQuery = ref(String(route.query.search || ''))
const selectedStatusFilter = ref(String(route.query.status_id || ''))

// Sinkronisasi State jika Query URL berubah
watch(
  () => route.query,
  (newQuery) => {
    currentPage.value = Number(newQuery.page) || 1
    perPage.value = Number(newQuery.per_page) || 10
    searchQuery.value = String(newQuery.search || '')
    selectedStatusFilter.value = String(newQuery.status_id || '')
  }
)

// Helper Pengecekan Akses Edit
const isEditAllowed = (ticket) => {
  if (userRoleId.value === 4) {
    return Number(ticket.status_id) === 1
  }
  return true
}

// Sync State ke URL Query Params
const updateQueryParams = () => {
  router.replace({
    query: {
      ...route.query,
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      status_id: selectedStatusFilter.value || undefined
    }
  })
}


// Fetch Statistik Tiket langsung dari API
const { data: statsResponse, refresh: refreshStats } = await useAsyncData(
  'admin-tickets-stats',
  () => $fetch(`${apiBase}/tickets/stats`, { // Ubah endpoint sesuai endpoint backend kamu
    headers: getAuthHeaders(),
    params: {
      user_id: userId.value,
      department_id: department_id.value,
      role_id: userRoleId.value
    }
  }),
  {
    watch: [userId, department_id],
    getCachedData: () => undefined
  }
)

// Computed untuk membaca hasil data statistik dari API
const summaryStats = computed(() => {
  const stats = statsResponse.value?.data || statsResponse.value || {}

  return {
    total: stats.total || 0,
    open: stats.open || stats.status_1 || 0,
    inProgress: stats.in_progress || stats.status_2 || 0,
    resolve: stats.resolve || stats.status_3 || 0,
    complete: stats.complete || stats.status_4 || 0,
    rejected: stats.rejected || stats.status_5 || 0
  }
})


// --- 2. FETCH DATA UTAMA (Nuxt 4 Standard) ---
const { data: responseData, pending, error, refresh } = await useAsyncData(
  'admin-tickets-list',
  () => $fetch(`${apiBase}/tickets`, {
    headers: getAuthHeaders(),
    params: {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value,
      status_id: selectedStatusFilter.value,
      department_id: department_id.value,
      role_id: userRoleId.value,
      user_id: userId.value
    }
  }),
  {
    watch: [currentPage, perPage, searchQuery, selectedStatusFilter],
    getCachedData: () => undefined
  }
)

watch([currentPage, perPage, selectedStatusFilter], () => {
  updateQueryParams()
})

watch(selectedStatusFilter, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
  }
})

// Search Debounce
let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    updateQueryParams()
  }, 400)
}

// Computed Data List
const tickets = computed(() => responseData.value?.data?.data || responseData.value?.data || [])
const departmentsList = computed(() => responseData.value?.departments || [])
const assigneesList = computed(() => responseData.value?.assignees || [])
const statusesList = computed(() => responseData.value?.statuses || [])

const pagination = computed(() => {
  const meta = responseData.value?.data || responseData.value?.meta || responseData.value || {}
  return {
    currentPage: meta.current_page || 1,
    lastPage: meta.last_page || 1,
    total: meta.total || 0,
    from: meta.from || 0,
    to: meta.to || 0
  }
})

// --- 3. STATE & OPERASI MODAL TIKET (CRUD PERBAIKAN) ---
const isModalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const formError = ref('')
const fileInputRef = ref(null)
const imagePreviewUrl = ref('')

const form = ref({
  id: null,
  department_id: '',
  judul: '',
  deskripsi: '',
  prioritas: 'medium',
  status_id: 1,
  assignee_id: '',
  lampiran: null,
  existing_lampiran: null
})

const handleFileChange = (e) => {
  const file = e.target.files[0]
  formError.value = ''

  if (!file) {
    form.value.lampiran = null
    return
  }

  if (!file.type.startsWith('image/') && !['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type)) {
    form.value.lampiran = null
    e.target.value = ''
    formError.value = 'Format file harus berupa gambar, PDF, atau DOC.'
    return
  }

  if (file.size > 5 * 1024 * 1024) {
    form.value.lampiran = null
    e.target.value = ''
    formError.value = 'Ukuran file maksimal 5 MB.'
    return
  }

  form.value.lampiran = file
  imagePreviewUrl.value = ''
  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = () => { imagePreviewUrl.value = String(reader.result || '') }
    reader.readAsDataURL(file)
  }
}

const isImageAttachment = (path) => /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(String(path || ''))
const attachmentUrl = (path) => /^https?:\/\//i.test(String(path || ''))
  ? path
  : `${storageBase}/${String(path || '').replace(/^\/+/, '')}`

const openCreateModal = () => {
  isEditing.value = false
  formError.value = ''
  form.value = {
    id: null,
    department_id: departmentsList.value[0]?.kode || departmentsList.value[0]?.id || '',
    judul: '',
    deskripsi: '',
    prioritas: 'medium',
    status_id: 1,
    assignee_id: '',
    lampiran: null,
    existing_lampiran: null
  }
  imagePreviewUrl.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
  isModalOpen.value = true
}

const openEditModal = (item) => {
  isEditing.value = true
  formError.value = ''
  form.value = {
    id: item.id,
    department_id: item.department_id || '',
    judul: item.judul || '',
    deskripsi: item.deskripsi || '',
    prioritas: item.prioritas || 'medium',
    status_id: item.status_id || 1,
    assignee_id: item.assignee_id || '',
    lampiran: null,
    existing_lampiran: item.lampiran || null
  }
  imagePreviewUrl.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

// SUBMIT FORM (CREATE & UPDATE)
const handleSubmit = async () => {
  submitting.value = true
  formError.value = ''

  const formData = new FormData()
  // Perbaikan: Menggunakan userId.value yang valid
  formData.append('user_id', userId.value || '')
  formData.append('department_id', form.value.department_id)
  formData.append('status_id', form.value.status_id || 1)
  formData.append('judul', form.value.judul)
  formData.append('deskripsi', form.value.deskripsi)
  formData.append('prioritas', form.value.prioritas)

  if (form.value.assignee_id) {
    formData.append('assignee_id', form.value.assignee_id)
  }

  if (form.value.lampiran) {
    formData.append('lampiran', form.value.lampiran)
  }

  // Handle Method Spoofing untuk Laravel saat Update
  if (isEditing.value) {
    formData.append('_method', 'PUT')
  }

  try {
    const url = isEditing.value ? `${apiBase}/tickets/${form.value.id}` : `${apiBase}/tickets`
    
    await $fetch(url, {
      method: 'POST', // Menggunakan POST untuk mendukung FormData File Upload di Laravel
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: formData
    })

    await refresh()
    closeModal()
  } catch (err) {
    if (err.data?.errors) {
      const firstKey = Object.keys(err.data.errors)[0]
      formError.value = err.data.errors[firstKey][0]
    } else {
      formError.value = err.data?.message || 'Gagal menyimpan data tiket.'
    }
  } finally {
    submitting.value = false
  }
}

// DELETE TIKET
const handleDelete = async (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus tiket aduan ini?')) {
    try {
      await $fetch(`${apiBase}/tickets/${id}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })
      await refresh()
    } catch (err) {
      alert(err.data?.message || 'Gagal menghapus tiket.')
    }
  }
}

// --- 4. FITUR CHAT REALTIME & NOTIFIKASI ---
const isChatModalOpen = ref(false)
const selectedTicket = ref(null)
const chatMessages = ref([])
const loadingChat = ref(false)
const sendingMessage = ref(false)
const deletingMessageId = ref(null)
const deletingAll = ref(false)
const newMessage = ref('')

const unreadCounts = ref({})
const lastMessageCounts = ref({})

let chatInterval = null
let globalPollInterval = null

const playNotificationSound = () => {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.type = 'sine'
    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime)
    gain.gain.setValueAtTime(0.1, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.5)
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.start()
    osc.stop(audioCtx.currentTime + 0.5)
  } catch (e) {
    console.error('Audio Context Error:', e)
  }
}

const openChatModal = async (ticket) => {
  selectedTicket.value = ticket
  isChatModalOpen.value = true
  
  unreadCounts.value[ticket.id] = 0

  await fetchMessages()

  chatInterval = setInterval(() => {
    fetchMessages(true)
  }, 3000)
}

const closeChatModal = () => {
  isChatModalOpen.value = false
  selectedTicket.value = null
  chatMessages.value = []
  newMessage.value = ''

  if (chatInterval) {
    clearInterval(chatInterval)
    chatInterval = null
  }
}

const fetchMessages = async (silent = false) => {
  if (!selectedTicket.value) return
  if (!silent) loadingChat.value = true
  
  try {
    const res = await $fetch(`${apiBase}/tickets/${selectedTicket.value.id}/messages`, {
      headers: getAuthHeaders()
    })
    const fetched = res.data || res || []
    
    if (chatMessages.value.length > 0 && fetched.length > chatMessages.value.length) {
      const lastMsg = fetched[fetched.length - 1]
      if (lastMsg.user_id !== userId.value) {
        playNotificationSound()
      }
    }

    chatMessages.value = fetched
    lastMessageCounts.value[selectedTicket.value.id] = fetched.length
  } catch (err) {
    console.error('Gagal mengambil pesan:', err)
  } finally {
    if (!silent) loadingChat.value = false
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedTicket.value) return
  sendingMessage.value = true
  try {
    await $fetch(`${apiBase}/tickets/${selectedTicket.value.id}/messages`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: { message: newMessage.value }
    })
    newMessage.value = ''
    await fetchMessages(true)
  } catch (err) {
    alert(err.data?.message || 'Gagal mengirim pesan.')
  } finally {
    sendingMessage.value = false
  }
}

const deleteSingleMessage = async (messageId) => {
  if (!confirm('Apakah Anda yakin ingin menghapus pesan ini?')) return
  
  deletingMessageId.value = messageId
  try {
    await $fetch(`${apiBase}/tickets/${selectedTicket.value.id}/messages/${messageId}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })
    chatMessages.value = chatMessages.value.filter(m => m.id !== messageId)
    if (lastMessageCounts.value[selectedTicket.value.id]) {
      lastMessageCounts.value[selectedTicket.value.id]--
    }
  } catch (err) {
    alert(err.data?.message || 'Gagal menghapus pesan.')
  } finally {
    deletingMessageId.value = null
  }
}

const deleteAllMessages = async () => {
  if (!confirm('Apakah Anda yakin ingin menghapus SELURUH pesan percakapan pada tiket ini?')) return

  deletingAll.value = true
  try {
    await $fetch(`${apiBase}/tickets/${selectedTicket.value.id}/messages`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })
    chatMessages.value = []
    lastMessageCounts.value[selectedTicket.value.id] = 0
    unreadCounts.value[selectedTicket.value.id] = 0
  } catch (err) {
    alert(err.data?.message || 'Gagal menghapus semua pesan.')
  } finally {
    deletingAll.value = false
  }
}

const checkGlobalUnreadMessages = async () => {
  if (isChatModalOpen.value || !tickets.value.length) return

  for (const ticket of tickets.value) {
    try {
      const res = await $fetch(`${apiBase}/tickets/${ticket.id}/messages`, {
        headers: getAuthHeaders()
      })
      const messages = res.data || res || []
      const currentCount = messages.length
      const prevCount = lastMessageCounts.value[ticket.id]

      if (prevCount !== undefined && currentCount > prevCount) {
        const diff = currentCount - prevCount
        unreadCounts.value[ticket.id] = (unreadCounts.value[ticket.id] || 0) + diff
        playNotificationSound()
      }

      lastMessageCounts.value[ticket.id] = currentCount
    } catch (e) {
      // Silent error
    }
  }
}

onMounted(() => {
  globalPollInterval = setInterval(() => {
    checkGlobalUnreadMessages()
  }, 7000)
})

onUnmounted(() => {
  if (chatInterval) clearInterval(chatInterval)
  if (globalPollInterval) clearInterval(globalPollInterval)
})


//data statistik

//card filter
const toggleStatusFilter = (statusId) => {
  const target = String(statusId)
  if (selectedStatusFilter.value === target) {
    selectedStatusFilter.value = '' // Reset filter jika card yang sama diklik ulang
  } else {
    selectedStatusFilter.value = target
  }
}
</script>

<template>
  <div class="space-y-6 bg-slate-50/60 p-4 sm:p-6 rounded-3xl">
    <!-- Banner Header Modern Minimalis -->
<div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
  <div class="space-y-1">
    <div class="flex items-center gap-2">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">
        Selamat Datang, {{ user?.name || 'User' }}! 
      </h1>
      <span class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-indigo-50 text-indigo-600 rounded-full border border-indigo-100">
        {{ user?.role_name || 'Client' }}
      </span>
    </div>
    <p class="text-xs sm:text-sm text-slate-500">
      Pantau dan kelola tiket pengaduan layanan akademik & teknis UNPAM Anda di sini.
    </p>
  </div>

  <!-- Tombol Aksi Cepat Buat Tiket -->
  <button 
    @click="openCreateModal" 
    class="shrink-0 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 flex items-center gap-2 shadow-sm hover:shadow-indigo-200 active:scale-95"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
    </svg>
    <span>Buat Tiket Baru</span>
  </button>
</div>
    
    <!-- Summary Cards (Stat Highlights Grid) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      
      <!-- Total Tiket -->
      <div 
        @click="selectedStatusFilter = ''"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between relative overflow-hidden shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '' 
          ? 'bg-gradient-to-br from-indigo-600 to-indigo-700 border-indigo-600 text-white ring-4 ring-indigo-500/15' 
          : 'bg-white border-slate-200/80 hover:border-indigo-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '' ? 'text-indigo-100' : 'text-slate-500'">Total</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '' ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2 2 2 0 010 4 2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2H5z" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.total }}
        </span>
      </div>

      <!-- Open (Status ID: 1) -->
      <div 
        @click="toggleStatusFilter(1)"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '1' 
          ? 'bg-amber-500 border-amber-500 text-white ring-4 ring-amber-500/15' 
          : 'bg-white border-slate-200/80 hover:border-amber-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '1' ? 'text-amber-100' : 'text-amber-600'">Open</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '1' ? 'bg-white/20 text-white' : 'bg-amber-50 text-amber-600 group-hover:bg-amber-100'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '1' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.open }}
        </span>
      </div>

      <!-- In Progress (Status ID: 2) -->
      <div 
        @click="toggleStatusFilter(2)"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '2' 
          ? 'bg-blue-600 border-blue-600 text-white ring-4 ring-blue-500/15' 
          : 'bg-white border-slate-200/80 hover:border-blue-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '2' ? 'text-blue-100' : 'text-blue-600'">Progress</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '2' ? 'bg-white/20 text-white' : 'bg-blue-50 text-blue-600 group-hover:bg-blue-100'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '2' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.inProgress }}
        </span>
      </div>

      <!-- Resolve (Status ID: 3) -->
      <div 
        @click="toggleStatusFilter(3)"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '3' 
          ? 'bg-emerald-600 border-emerald-600 text-white ring-4 ring-emerald-500/15' 
          : 'bg-white border-slate-200/80 hover:border-emerald-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '3' ? 'text-emerald-100' : 'text-emerald-600'">Resolve</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '3' ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '3' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.resolve }}
        </span>
      </div>

      <!-- Complete (Status ID: 4) -->
      <div 
        @click="toggleStatusFilter(4)"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '4' 
          ? 'bg-slate-700 border-slate-700 text-white ring-4 ring-slate-500/15' 
          : 'bg-white border-slate-200/80 hover:border-slate-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '4' ? 'text-slate-200' : 'text-slate-600'">Complete</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '4' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '4' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.complete }}
        </span>
      </div>

      <!-- Rejected (Status ID: 5) -->
      <div 
        @click="toggleStatusFilter(5)"
        class="group p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer select-none flex flex-col justify-between shadow-sm hover:shadow-md"
        :class="selectedStatusFilter === '5' 
          ? 'bg-rose-600 border-rose-600 text-white ring-4 ring-rose-500/15' 
          : 'bg-white border-slate-200/80 hover:border-rose-300 text-slate-800'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tracking-wide uppercase" :class="selectedStatusFilter === '5' ? 'text-rose-100' : 'text-rose-600'">Rejected</span>
          <div class="p-2 rounded-xl transition-colors" :class="selectedStatusFilter === '5' ? 'bg-white/20 text-white' : 'bg-rose-50 text-rose-600 group-hover:bg-rose-100'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-2xl font-black tracking-tight" :class="selectedStatusFilter === '5' ? 'text-white' : 'text-slate-900'">
          {{ summaryStats.rejected }}
        </span>
      </div>

    </div>

    <!-- Main Container -->
    <div class="space-y-4">
      
      <!-- Filter & Search Bar -->
      <div class="bg-white p-3.5 sm:p-4 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row gap-3 justify-between items-center">
        <div class="relative w-full md:w-96">
          <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input 
            v-model="searchQuery" 
            @input="handleSearch"
            type="text" 
            placeholder="Cari nomor tiket atau judul aduan..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition"
          />
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500 self-end md:self-auto font-medium">
          <span>Tampilkan:</span>
          <select v-model="perPage" class="border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold text-slate-700">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>

      <!-- State Loading -->
      <div v-if="pending" class="bg-white p-12 rounded-2xl border border-slate-200/80 text-center text-slate-400 shadow-sm">
        <div class="flex items-center justify-center gap-3">
          <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm font-medium text-slate-600">Memuat data tiket...</span>
        </div>
      </div>

      <!-- State Error -->
      <div v-else-if="error" class="bg-rose-50/50 border border-rose-200 p-8 rounded-2xl text-center text-rose-600 font-medium text-sm shadow-sm">
        Gagal memuat data tiket aduan. Silakan coba lagi.
      </div>

      <!-- List Card Full Width -->
      <div v-else-if="tickets.length > 0" class="space-y-3">
        <div 
          v-for="item in tickets" 
          :key="item.id" 
          class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200/80 hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative overflow-hidden group"
        >
          <!-- Aksen Warna Status di Sisi Kiri Card -->
          <div 
            class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors"
            :class="{
              'bg-amber-400': item.status_id === 1,
              'bg-blue-500': item.status_id === 2,
              'bg-emerald-500': item.status_id === 3,
              'bg-slate-400': item.status_id === 4,
              'bg-rose-500': item.status_id === 5
            }"
          ></div>

          <!-- Bagian Utama Tiket -->
          <div class="space-y-2 flex-1 pl-2">
            
            <!-- Badges Bar -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-mono text-[11px] font-bold px-2.5 py-0.5 bg-slate-100 text-slate-700 rounded-md border border-slate-200/60 tracking-wide">
                #{{ item.nomor_tiket }}
              </span>

              <!-- Badge Status -->
              <span 
                class="px-2.5 py-0.5 text-[11px] font-semibold rounded-full flex items-center gap-1"
                :class="{
                  'bg-amber-50 text-amber-700 border border-amber-200/60': item.status_id === 1,
                  'bg-blue-50 text-blue-700 border border-blue-200/60': item.status_id === 2,
                  'bg-emerald-50 text-emerald-700 border border-emerald-200/60': item.status_id === 3,
                  'bg-slate-100 text-slate-700 border border-slate-200/60': item.status_id === 4,
                  'bg-rose-50 text-rose-700 border border-rose-200/60': item.status_id === 5
                }"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="{
                  'bg-amber-500': item.status_id === 1,
                  'bg-blue-500': item.status_id === 2,
                  'bg-emerald-500': item.status_id === 3,
                  'bg-slate-500': item.status_id === 4,
                  'bg-rose-500': item.status_id === 5
                }"></span>
                {{ item.status?.name || '-' }}
              </span>

              <!-- Badge Prioritas -->
              <span 
                class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase tracking-wider border"
                :class="{
                  'bg-slate-50 text-slate-500 border-slate-200': item.prioritas === 'low',
                  'bg-blue-50 text-blue-600 border-blue-200': item.prioritas === 'medium',
                  'bg-amber-50 text-amber-700 border-amber-200': item.prioritas === 'high',
                  'bg-rose-50 text-rose-600 border-rose-200 animate-pulse': item.prioritas === 'urgent'
                }"
              >
                {{ item.prioritas }}
              </span>
            </div>

            <!-- Judul Aduan -->
            <h3 class="font-bold text-slate-800 text-base leading-snug group-hover:text-indigo-600 transition-colors">
              {{ item.judul }}
            </h3>

            <!-- Indikator Status Balasan (Comment Preview) -->
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200/60 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span class="truncate max-w-md italic">{{ item.comment ? item.comment : 'Belum ada balasan' }}</span>
            </div>

            <!-- Metadata Info -->
            <div class="flex flex-wrap items-center gap-y-1.5 gap-x-5 text-xs text-slate-500 pt-1">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Pelapor: <strong class="text-slate-700 font-medium">{{ item.user?.name || '-' }}</strong></span>
              </div>

              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>Tujuan: <strong class="text-slate-700 font-medium">{{ item.department?.nama || '-' }}</strong></span>
              </div>

              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                <template v-if="item.lampiran">
                  <img
                    v-if="isImageAttachment(item.lampiran)"
                    :src="attachmentUrl(item.lampiran)"
                    alt="Preview lampiran"
                    class="h-10 w-10 rounded-lg border border-slate-200 object-cover"
                  />
                  <a
                    :href="attachmentUrl(item.lampiran)"
                    target="_blank"
                    class="text-indigo-600 hover:text-indigo-800 hover:underline font-semibold transition"
                  >
                    Lihat Lampiran
                  </a>
                </template>
                <span v-else class="text-slate-400">Tanpa lampiran</span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0 pl-2 lg:pl-0">
            
            <!-- Chat -->
            <button 
              @click="openChatModal(item)" 
              class="relative px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200/60 hover:border-emerald-600 transition-all flex items-center gap-1.5 shadow-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span>Chat</span>

              <span 
                v-if="unreadCounts[item.id] > 0"
                class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow animate-bounce"
              >
                {{ unreadCounts[item.id] > 9 ? '9+' : unreadCounts[item.id] }}
              </span>
            </button>
            
            <!-- Edit -->
            <button 
              v-if="item.status?.id === 1"
              @click="openEditModal(item)" 
              class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-indigo-600 hover:text-white transition-all flex items-center gap-1.5 border border-slate-200/80 hover:border-indigo-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              <span>Edit</span>
            </button>

            <!-- Hapus -->
            <button 
              v-if="item.status?.id === 1"
              @click="handleDelete(item.id)" 
              class="px-3 py-2 rounded-xl text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200/60 hover:border-rose-600 transition-all flex items-center gap-1"
              title="Hapus Tiket"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- State Kosong -->
      <div v-else class="bg-white p-12 rounded-2xl border border-slate-200/80 text-center text-slate-400 shadow-sm">
        Data tiket aduan tidak ditemukan.
      </div>

      <!-- Pagination -->
      <div v-if="!pending && tickets.length > 0" class="bg-white p-4 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500 shadow-sm">
        <div>
          Menampilkan <span class="font-semibold text-slate-800">{{ pagination.from }}</span> - <span class="font-semibold text-slate-800">{{ pagination.to }}</span> dari <span class="font-semibold text-slate-800">{{ pagination.total }}</span> total data
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50 disabled:opacity-40 transition"
          >
            Sebelumnya
          </button>
          <span class="px-3 py-1.5 text-slate-700 font-semibold bg-slate-50 rounded-lg border border-slate-200/60">
            Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }}
          </span>
          <button 
            @click="currentPage++" 
            :disabled="currentPage >= pagination.lastPage"
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50 disabled:opacity-40 transition"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form (Create / Edit Tiket) -->
   <!-- Modal Form (Create / Edit Tiket - Wide Landscape Layout) -->
<div 
  v-if="isModalOpen" 
  class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 transition-all"
>
  <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full flex flex-col overflow-hidden border border-slate-100 transition-all transform duration-300">
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
      <div class="flex items-center gap-3">
        <div class="p-2.5 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100/60">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </div>
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-slate-800">
            {{ isEditing ? 'Edit Pengaduan Tiket' : 'Buat Tiket Pengaduan Baru' }}
          </h3>
          <p class="text-xs text-slate-500 font-medium">Isi formulir di bawah ini untuk menyampaikan kendala Anda</p>
        </div>
      </div>

      <button 
        @click="closeModal" 
        class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-200/60 transition"
        title="Tutup Modal"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Alert Form Error -->
    <div v-if="formError" class="mx-6 mt-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-2xl font-medium flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ formError }}</span>
    </div>

    <!-- Modal Body / Form Input Layout Grid 2 Kolom Sejajar -->
    <form @submit.prevent="handleSubmit" class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Kolom Kiri: Informasi Utama Aduan -->
        <div class="space-y-4">
          <!-- Judul Pengaduan -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Judul Kendala / Aduan <span class="text-rose-500">*</span>
            </label>
            <input 
              v-model="form.judul" 
              type="text" 
              required
              placeholder="Contoh: Kendala Pembayaran UKT" 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition"
            />
          </div>

          <!-- Grid Sub-Row untuk Tujuan & Prioritas -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Tujuan Departemen -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                Unit Tujuan <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <select 
                  v-model="form.department_id" 
                  required 
                  class="w-full appearance-none px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition cursor-pointer"
                >
                  <option value="" disabled selected>Pilih Unit</option>
                  <option
                    v-for="dept in departmentsList"
                    :key="dept.kode"
                    :value="dept.kode"
                    :title="dept.deskripsi || 'Tidak ada deskripsi departemen'"
                  >
                    {{ dept.nama }}
                  </option>
                </select>
                <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Tingkat Prioritas -->
            <div>
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                Prioritas <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <select 
                  v-model="form.prioritas" 
                  required 
                  class="w-full appearance-none px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition cursor-pointer font-medium"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
                <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Tiket (Hanya saat mode Edit) -->
          <div v-if="isEditing">
            <!-- <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Status Tiket <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <select 
                v-model="form.status_id" 
                required 
                class="w-full appearance-none px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition cursor-pointer font-semibold"
              >
                <option value="" disabled>Pilih Status</option>
                <option v-for="status in statusesList" :key="status.id" :value="status.id">
                  {{ status.name || '-' }}
                </option>
              </select>
              <div class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div> -->
          </div>
        </div>

        <!-- Kolom Kanan: Detail Deskripsi & Lampiran File -->
        <div class="space-y-4 flex flex-col justify-between">
          <!-- Deskripsi Detail Aduan -->
          <div class="flex-1">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Rincian Deskripsi <span class="text-rose-500">*</span>
            </label>
            <textarea 
              v-model="form.deskripsi" 
              rows="3" 
              required
              placeholder="Jelaskan detail masalah atau kronologi kendala..." 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition leading-relaxed resize-none h-[88px]"
            ></textarea>
          </div>

          <!-- Upload Lampiran Dropzone Compact -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Lampiran Bukti (Opsional)
            </label>
            
            <div class="relative border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-slate-50/60 hover:bg-indigo-50/30 rounded-2xl p-3 text-center transition group cursor-pointer">
              <input 
                ref="fileInputRef"
                type="file" 
                @change="handleFileChange"
                accept="image/*,.pdf,.doc,.docx"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
              />
              
              <div class="flex items-center justify-center gap-3">
                <div class="p-2 rounded-xl bg-white shadow-xs group-hover:scale-105 transition-transform">
                  <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                </div>
                <div class="text-left">
                  <div class="text-xs font-semibold text-slate-700">
                    <span class="text-indigo-600 hover:underline">Unggah berkas</span> atau tarik file
                  </div>
                  <p class="text-[10px] text-slate-400">PNG, JPG, PDF, DOC (Maks. 5MB)</p>
                </div>
              </div>
              <p v-if="form.lampiran" class="mt-2 truncate text-[11px] font-medium text-indigo-600">
                {{ form.lampiran.name }}
              </p>
            </div>
            <img
              v-if="imagePreviewUrl || (!form.lampiran && form.existing_lampiran && isImageAttachment(form.existing_lampiran))"
              :src="imagePreviewUrl || attachmentUrl(form.existing_lampiran)"
              alt="Preview lampiran"
              class="mt-2 h-28 w-full rounded-xl border border-slate-200 object-contain bg-slate-50 p-1"
            />
          </div>
        </div>

      </div>
    </form>

    <!-- Modal Footer Actions -->
    <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-end gap-3">
      <button 
        type="button" 
        @click="closeModal" 
        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-200/60 transition"
      >
        Batal
      </button>
      
      <button 
        type="submit" 
        @click="handleSubmit"
        :disabled="submitting"
        class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white shadow-sm hover:shadow-indigo-200 disabled:opacity-50 transition flex items-center gap-2"
      >
        <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>{{ submitting ? 'Memproses...' : (isEditing ? 'Simpan Perubahan' : 'Kirim Tiket Aduan') }}</span>
      </button>
    </div>

  </div>
</div>

    <!-- Modal Chat Tiket -->
    <!-- Modal Chat Tiket Modern & Sleek -->
<div 
  v-if="isChatModalOpen" 
  class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 transition-all"
>
  <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full flex flex-col h-[650px] max-h-[90vh] overflow-hidden border border-slate-100 transition-all">
    
    <!-- Header Chat Room -->
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/90 backdrop-blur-sm">
      <div class="flex items-center gap-3 min-w-0">
        <!-- Avatar Indicator -->
        <div class="relative shrink-0">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-indigo-200">
            {{ selectedTicket?.nomor_tiket?.slice(-2) || 'TK' }}
          </div>
          <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></span>
        </div>

        <!-- Info Tiket -->
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="text-sm sm:text-base font-extrabold text-slate-800 truncate">
              {{ selectedTicket?.judul }}
            </h3>
            <span class="font-mono text-[10px] font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md border border-indigo-100 shrink-0">
              #{{ selectedTicket?.nomor_tiket }}
            </span>
          </div>
          <p class="text-xs text-slate-500 truncate flex items-center gap-1.5 mt-0.5">
            <span>Pelapor: <strong class="text-slate-700 font-semibold">{{ selectedTicket?.user?.name || '-' }}</strong></span>
            <span>•</span>
            <span>Tujuan: <strong class="text-slate-700 font-semibold">{{ selectedTicket?.department?.nama || '-' }}</strong></span>
          </p>
        </div>
      </div>
      
      <!-- Tombol Aksi Header -->
      <div class="flex items-center gap-2 shrink-0 ml-3">
        <button 
          v-if="chatMessages.length > 0"
          @click="deleteAllMessages"
          :disabled="deletingAll"
          title="Hapus Semua Percakapan"
          class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition-all disabled:opacity-50 flex items-center gap-1 text-xs font-semibold"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span class="hidden sm:inline">{{ deletingAll ? 'Membawa...' : 'Bersihkan Chat' }}</span>
        </button>

        <button 
          @click="closeChatModal" 
          class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-200/60 transition"
          title="Tutup Chat"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Body Area Chat (Pesan Percakapan) -->
    <div class="flex-1 p-5 overflow-y-auto space-y-4 bg-slate-50/50">
      
      <!-- Context Banner di Atas Chat -->
      <div class="flex justify-center">
        <div class="bg-white/80 border border-slate-200/80 px-3.5 py-1.5 rounded-full shadow-xs text-center">
          <p class="text-[11px] text-slate-500 font-medium">
            Diskusi resmi untuk Tiket <strong class="text-indigo-600">#{{ selectedTicket?.nomor_tiket }}</strong>
          </p>
        </div>
      </div>

      <!-- State Loading Chat -->
      <div v-if="loadingChat" class="flex flex-col items-center justify-center py-16 gap-3 text-slate-400">
        <svg class="animate-spin h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs font-semibold text-slate-500">Memuat riwayat percakapan...</span>
      </div>

      <!-- State Kosong (Belum ada pesan) -->
      <div v-else-if="chatMessages.length === 0" class="flex flex-col items-center justify-center py-16 text-center text-slate-400 space-y-2">
        <div class="p-3 bg-indigo-50 text-indigo-500 rounded-2xl">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </div>
        <p class="text-xs font-bold text-slate-600">Belum Ada Percakapan</p>
        <p class="text-[11px] text-slate-400 max-w-xs">Kirim pesan pertama Anda di bawah untuk memulai komunikasi dengan petugas Helpdesk.</p>
      </div>

      <!-- List Bubble Messages -->
      <template v-else>
        <div 
          v-for="msg in chatMessages" 
          :key="msg.id" 
          class="flex flex-col group"
          :class="msg.user_id === selectedTicket?.user_id ? 'items-end' : 'items-start'"
        >
          <!-- Metadata Pengirim & Waktu -->
          <div class="text-[10px] text-slate-400 mb-1 px-1 font-semibold flex items-center gap-1.5">
            <span>{{ msg.user?.name || 'Pengguna' }}</span>
            <span>•</span>
            <span>{{ msg.created_at_formatted || 'Baru saja' }}</span>
          </div>

          <!-- Bubble Content + Action Delete -->
          <div 
            class="flex items-center gap-2 max-w-[80%]"
            :class="msg.user_id === selectedTicket?.user_id ? 'flex-row-reverse' : 'flex-row'"
          >
            <!-- Isi Pesan -->
            <div 
              class="rounded-2xl px-4 py-2.5 text-xs sm:text-sm shadow-sm break-words leading-relaxed"
              :class="
                msg.user_id === selectedTicket?.user_id 
                  ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-tr-xs' 
                  : 'bg-white border border-slate-200/90 text-slate-800 rounded-tl-xs'
              "
            >
              {{ msg.message }}
            </div>

            <!-- Tombol Hapus Pesan Individu (Muncul saat Hover) -->
            <button 
              @click="deleteSingleMessage(msg.id)"
              :disabled="deletingMessageId === msg.id"
              title="Hapus pesan ini"
              class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg disabled:opacity-30"
            >
              <svg v-if="deletingMessageId !== msg.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <svg v-else class="animate-spin w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </div>
        </div>
      </template>

    </div>

    <!-- Form Input Chat Bar -->
    <form @submit.prevent="sendMessage" class="p-3.5 sm:p-4 border-t border-slate-100 bg-white flex items-center gap-2">
      <div class="relative flex-1">
        <input 
          v-model="newMessage" 
          type="text" 
          placeholder="Tulis balasan pesan di sini..." 
          class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white transition"
        />
      </div>

      <button 
        type="submit" 
        :disabled="sendingMessage || !newMessage.trim()"
        class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs sm:text-sm rounded-2xl disabled:opacity-40 transition flex items-center gap-2 shadow-md shadow-indigo-200 shrink-0"
      >
        <span>Kirim</span>
        <svg class="w-4 h-4 -rotate-45 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
        </svg>
      </button>
    </form>

  </div>
</div>
  </div>
</template>