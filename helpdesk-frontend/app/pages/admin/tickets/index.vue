<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

definePageMeta({
  layout: 'admin',
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
const perPage = ref(Number(route.query.per_page) || 10)
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

// Fetch Statistik Tiket langsung dari API
const { data: statsResponse, refresh: refreshStats } = await useAsyncData(
  'admin-tickets-stats',
  () => $fetch(`${apiBase}/tickets/stats`, {
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

// Card Filter
const toggleStatusFilter = (statusId) => {
  const target = String(statusId)
  if (selectedStatusFilter.value === target) {
    selectedStatusFilter.value = ''
  } else {
    selectedStatusFilter.value = target
  }
}

// --- 3. STATE & OPERASI BULK ACTION ---
const selectedIds = ref([])
const isBulkModalOpen = ref(false)
const bulkActionType = ref('') // 'delete', 'change_status', 'change_priority'
const bulkActionValue = ref('')
const submittingBulk = ref(false)

const isSelectAll = computed({
  get: () => tickets.value.length > 0 && selectedIds.value.length === tickets.value.length,
  set: (val) => {
    selectedIds.value = val ? tickets.value.map(t => t.id) : []
  }
})

// Reset seleksi ketika daftar tiket atau halaman berubah
watch(tickets, () => {
  selectedIds.value = []
})

const openBulkModal = (action) => {
  bulkActionType.value = action
  bulkActionValue.value = ''
  isBulkModalOpen.value = true
}

const executeBulkAction = async () => {
  if (selectedIds.value.length === 0) return

  submittingBulk.value = true
  try {
    await $fetch(`${apiBase}/tickets/bulk-action`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: {
        ids: selectedIds.value,
        action: bulkActionType.value,
        value: bulkActionValue.value
      }
    })

    selectedIds.value = []
    isBulkModalOpen.value = false
    await refresh()
    if (refreshStats) await refreshStats()
  } catch (err) {
    alert(err.data?.message || 'Gagal memproses bulk action.')
  } finally {
    submittingBulk.value = false
  }
}

// --- 4. STATE & OPERASI MODAL TIKET (CRUD) ---
const isModalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const formError = ref('')
const fileInputRef = ref(null)

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
  if (file) {
    form.value.lampiran = file
  }
}

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

  if (isEditing.value) {
    formData.append('_method', 'PUT')
  }

  try {
    const url = isEditing.value ? `${apiBase}/tickets/${form.value.id}` : `${apiBase}/tickets`
    
    await $fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: formData
    })

    await Promise.all([refresh(), refreshStats()])
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
      await Promise.all([refresh(), refreshStats()])
    } catch (err) {
      alert(err.data?.message || 'Gagal menghapus tiket.')
    }
  }
}

// --- 5. FITUR CHAT REALTIME & NOTIFIKASI ---
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
</script>

<template>
  <div class="space-y-6 relative">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Tiket Aduan id : {{ user?.department_id }}</h1>
        <p class="text-sm text-slate-500">Kelola dan pantau seluruh laporan aduan dari pengguna. {{ user?.id}}</p>
      </div>
      <button v-if="hasRole(['4','1'])"
        @click="openCreateModal"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl flex items-center gap-2 transition text-sm font-medium shadow-sm shadow-indigo-200"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat Tiket Baru
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
      <!-- Total Tiket -->
      <div 
        @click="selectedStatusFilter = ''"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-indigo-300 select-none"
        :class="selectedStatusFilter === '' ? 'bg-indigo-50/50 border-indigo-500 ring-2 ring-indigo-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-slate-500 mb-1">
          <span class="text-[11px] font-medium truncate">Total Tiket</span>
          <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2 2 2 0 010 4 2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2H5z" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.total }}</span>
      </div>

      <!-- Open (Status ID: 1) -->
      <div 
        @click="toggleStatusFilter(1)"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-amber-300 select-none"
        :class="selectedStatusFilter === '1' ? 'bg-amber-50/50 border-amber-500 ring-2 ring-amber-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-amber-600 mb-1">
          <span class="text-[11px] font-medium text-slate-500 truncate">Open</span>
          <div class="p-1.5 bg-amber-50 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.open }}</span>
      </div>

      <!-- In Progress (Status ID: 2) -->
      <div 
        @click="toggleStatusFilter(2)"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-blue-300 select-none"
        :class="selectedStatusFilter === '2' ? 'bg-blue-50/50 border-blue-500 ring-2 ring-blue-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-blue-600 mb-1">
          <span class="text-[11px] font-medium text-slate-500 truncate">In Progress</span>
          <div class="p-1.5 bg-blue-50 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.inProgress }}</span>
      </div>

      <!-- Resolve (Status ID: 3) -->
      <div 
        @click="toggleStatusFilter(3)"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-emerald-300 select-none"
        :class="selectedStatusFilter === '3' ? 'bg-emerald-50/50 border-emerald-500 ring-2 ring-emerald-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-emerald-600 mb-1">
          <span class="text-[11px] font-medium text-slate-500 truncate">Resolve</span>
          <div class="p-1.5 bg-emerald-50 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.resolve }}</span>
      </div>

      <!-- Complete (Status ID: 4) -->
      <div 
        @click="toggleStatusFilter(4)"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-slate-300 select-none"
        :class="selectedStatusFilter === '4' ? 'bg-slate-100 border-slate-500 ring-2 ring-slate-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-slate-600 mb-1">
          <span class="text-[11px] font-medium text-slate-500 truncate">Complete</span>
          <div class="p-1.5 bg-slate-100 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.complete }}</span>
      </div>

      <!-- Rejected (Status ID: 5) -->
      <div 
        @click="toggleStatusFilter(5)"
        class="p-2.5 sm:p-3 rounded-xl border shadow-sm flex flex-col justify-between cursor-pointer transition-all hover:border-rose-300 select-none"
        :class="selectedStatusFilter === '5' ? 'bg-rose-50/50 border-rose-500 ring-2 ring-rose-500/20' : 'bg-white border-slate-100'"
      >
        <div class="flex items-center justify-between text-rose-600 mb-1">
          <span class="text-[11px] font-medium text-slate-500 truncate">Rejected</span>
          <div class="p-1.5 bg-rose-50 rounded-lg shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <span class="text-lg sm:text-xl font-bold text-slate-800">{{ summaryStats.rejected }}</span>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row gap-4 justify-between items-center">
      <div class="relative w-full md:w-96">
        <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input 
          v-model="searchQuery" 
          @input="handleSearch"
          type="text" 
          placeholder="Cari nomor tiket atau judul aduan..." 
          class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition"
        />
      </div>

      <div class="flex items-center gap-2 text-xs text-slate-500 self-end md:self-auto">
        <span>Tampilkan:</span>
        <select v-model="perPage" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
    </div>

    <!-- Tabel Data Tiket -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
            <tr>
              <th class="py-4 px-4 w-10 text-center">
                <input 
                  type="checkbox" 
                  v-model="isSelectAll"
                  class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                />
              </th>
              <th class="px-6 py-4">No. Tiket</th>
              <th class="px-6 py-4">Pelapor & Judul</th>
              <th class="px-6 py-4">Departemen/Unit Kerja Tujuan</th>
              <th class="px-6 py-4">Prioritas</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Lampiran</th>
              <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Loading -->
            <tr v-if="pending">
              <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                <div class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Memuat data tiket...</span>
                </div>
              </td>
            </tr>

            <!-- Error -->
            <tr v-else-if="error">
              <td colspan="8" class="px-6 py-12 text-center text-rose-500">
                Gagal memuat data tiket aduan.
              </td>
            </tr>

            <!-- Data List -->
            <tr 
              v-else 
              v-for="item in tickets" 
              :key="item.id" 
              :class="{'bg-indigo-50/30': selectedIds.includes(item.id)}"
              class="hover:bg-slate-50/50 transition"
            >
              <td class="py-4 px-4 text-center">
                <input 
                  type="checkbox" 
                  :value="item.id" 
                  v-model="selectedIds"
                  class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                />
              </td>
              <td class="px-6 py-4 font-mono text-xs font-bold text-indigo-600">
                {{ item.nomor_tiket }}
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-slate-800">{{ item.judul }}</div>
                <div class="text-xs text-slate-400">Oleh: {{ item.user?.name || '-' }}</div>
              </td>
              <td class="px-6 py-4 text-slate-600">
                {{ item.department?.nama || '-' }}
              </td>
              <td class="px-6 py-4">
                <span 
                  class="px-2.5 py-1 text-xs font-semibold rounded-full uppercase"
                  :class="{
                    'bg-slate-100 text-slate-600': item.prioritas === 'low',
                    'bg-blue-50 text-blue-600': item.prioritas === 'medium',
                    'bg-amber-50 text-amber-600': item.prioritas === 'high',
                    'bg-rose-50 text-rose-600': item.prioritas === 'urgent'
                  }"
                >
                  {{ item.prioritas }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span 
                  class="px-2.5 py-1 text-xs font-semibold rounded-full capitalize"
                  :class="{
                    'bg-amber-100 text-amber-700': item.status_id === 1,
                    'bg-blue-100 text-blue-700': item.status_id === 2,
                    'bg-emerald-100 text-emerald-700': item.status_id === 3,
                    'bg-slate-100 text-slate-600': item.status_id === 4,
                    'bg-rose-100 text-rose-700': item.status_id === 5
                  }"
                >
                  {{ item.status?.name || '-' }}
                </span>
              </td>
              <td class="px-6 py-4 text-xs">
                <a 
                  v-if="item.lampiran" 
                  :href="`${storageBase}/${item.lampiran}`" 
                  target="_blank" 
                  class="text-indigo-600 hover:underline flex items-center gap-1 font-medium"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  Lihat File
                </a>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button 
                  @click="openChatModal(item)" 
                  class="relative px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition inline-flex items-center gap-1"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                  <span>Chat</span>

                  <span 
                    v-if="unreadCounts[item.id] > 0"
                    class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-sm animate-bounce"
                  >
                    {{ unreadCounts[item.id] > 9 ? '9+' : unreadCounts[item.id] }}
                  </span>
                </button>
                
                <button 
                  @click="openEditModal(item)" 
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition"
                >
                  Edit
                </button>
                <button 
                  @click="handleDelete(item.id)" 
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition"
                >
                  Hapus
                </button>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-if="!pending && tickets.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                Data tiket aduan tidak ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="!pending && tickets.length > 0" class="p-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
        <div>
          Menampilkan <span class="font-semibold text-slate-700">{{ pagination.from }}</span> - <span class="font-semibold text-slate-700">{{ pagination.to }}</span> dari <span class="font-semibold text-slate-700">{{ pagination.total }}</span> total data
        </div>
        <div class="flex items-center gap-1">
          <button 
            @click="currentPage--" 
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
          >
            Sblm
          </button>
          <span class="px-3 py-1.5 text-slate-700 font-medium">Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }}</span>
          <button 
            @click="currentPage++" 
            :disabled="currentPage >= pagination.lastPage"
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 transition"
          >
            Slanj
          </button>
        </div>
      </div>
    </div>

    <!-- Floating Bulk Action Bar -->
    <Transition name="slide-up">
      <div 
        v-if="selectedIds.length > 0"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-4 border border-slate-700"
      >
        <div class="text-xs font-semibold">
          <span class="bg-indigo-600 text-white px-2 py-0.5 rounded-md font-bold mr-1">{{ selectedIds.length }}</span> 
          tiket dipilih
        </div>

        <div class="h-4 w-px bg-slate-700"></div>

        <div class="flex items-center gap-2">
          <button 
            @click="openBulkModal('change_status')" 
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-medium transition-colors"
          >
            Ubah Status
          </button>
          <button 
            @click="openBulkModal('change_priority')" 
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-xl text-xs font-medium transition-colors"
          >
            Ubah Prioritas
          </button>
          <button 
            @click="openBulkModal('delete')" 
            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-medium transition-colors"
          >
            Hapus
          </button>
        </div>

        <button @click="selectedIds = []" class="text-slate-400 hover:text-white text-xs ml-2">
          &times; Batal
        </button>
      </div>
    </Transition>

    <!-- Modal Konfirmasi Bulk Action -->
    <div v-if="isBulkModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full p-6 shadow-xl">
        <h3 class="text-base font-bold text-slate-900 mb-2">
          <span v-if="bulkActionType === 'delete'">Hapus Tiket Terpilih</span>
          <span v-else-if="bulkActionType === 'change_status'">Ubah Status Tiket</span>
          <span v-else-if="bulkActionType === 'change_priority'">Ubah Prioritas Tiket</span>
        </h3>
        
        <p class="text-xs text-slate-500 mb-4">
          Tindakan ini akan diterapkan pada <strong class="text-indigo-600">{{ selectedIds.length }} tiket</strong> yang dipilih.
        </p>

        <!-- Dropdown jika Ubah Status -->
        <div v-if="bulkActionType === 'change_status'" class="mb-4">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru</label>
          <select v-model="bulkActionValue" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white">
            <option value="" disabled>-- Pilih Status --</option>
            <option 
              v-for="st in statusesList" 
              :key="st.id" 
              :value="st.id"
            >
              {{ st.name || '-' }}
            </option>
          </select>
        </div>

        <!-- Dropdown jika Ubah Prioritas -->
        <div v-if="bulkActionType === 'change_priority'" class="mb-4">
          <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Prioritas Baru</label>
          <select v-model="bulkActionValue" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white">
            <option value="" disabled>-- Pilih Prioritas --</option>
            <option value="low">LOW</option>
            <option value="medium">MEDIUM</option>
            <option value="high">HIGH</option>
            <option value="urgent">URGENT</option>
          </select>
        </div>

        <div class="flex items-center justify-end gap-2 mt-6">
          <button 
            @click="isBulkModalOpen = false" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold"
          >
            Batal
          </button>
          <button 
            @click="executeBulkAction" 
            :disabled="submittingBulk || (bulkActionType !== 'delete' && !bulkActionValue)"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold"
          >
            {{ submittingBulk ? 'Memproses...' : 'Terapkan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form (Create / Edit Tiket) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-lg font-bold text-slate-800">
            {{ isEditing ? 'Edit Tiket Aduan' : 'Buat Tiket Aduan Baru' }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="formError" class="p-3 bg-rose-50 border border-rose-200 text-rose-600 text-xs rounded-xl">
          {{ formError }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Judul Aduan <span class="text-rose-500">*</span></label>
            <input 
              v-model="form.judul" 
              type="text" 
              required
              placeholder="Contoh: PC mati di ruang server" 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Departemen/Unit Kerja Tujuan :<span class="text-rose-500">*</span></label>
              <select 
                v-model="form.department_id" 
                required 
                class="w-full max-w-full truncate px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              >
                <option 
                  v-for="dept in departmentsList" 
                  :key="dept.kode" 
                  :value="dept.kode"
                  :title="dept.deskripsi || 'Tidak ada deskripsi departemen'"
                  class="truncate max-w-full"
                >
                  {{ dept.nama }} ({{ dept.kode }})<span v-if="dept.deskripsi"> - {{ dept.deskripsi }}</span>
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Prioritas Aduan <span class="text-rose-500">*</span></label>
              <select 
                v-model="form.prioritas" 
                required 
                class="w-full max-w-full truncate px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>

          <div v-if="isEditing" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">
                Status Tiket <span class="text-rose-500">*</span>
              </label>
              <select 
                v-model="form.status_id" 
                required 
                class="w-full max-w-full truncate px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              >
                <option value="" disabled>-- Pilih Status --</option>
                <option 
                  v-for="status in statusesList" 
                  :key="status.id" 
                  :value="status.id"
                >
                  {{ status.name || '-' }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi Detail Aduan<span class="text-rose-500">*</span></label>
            <textarea 
              v-model="form.deskripsi" 
              rows="3" 
              required
              placeholder="Jelaskan detail permasalahan..." 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Lampiran File/Foto (Opsional)</label>
            <input 
              ref="fileInputRef"
              type="file" 
              @change="handleFileChange"
              accept="image/*,.pdf,.doc,.docx"
              class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 border border-slate-200 rounded-xl cursor-pointer focus:outline-none"
            />
            <p v-if="form.existing_lampiran" class="text-[11px] text-slate-400 mt-1">
              File saat ini: <a :href="`${storageBase}/${form.existing_lampiran}`" target="_blank" class="text-indigo-600 underline">Lihat Lampiran</a>
            </p>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeModal" 
              class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="submitting"
              class="px-4 py-2 rounded-xl text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 transition"
            >
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Chat Tiket -->
    <div v-if="isChatModalOpen" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full flex flex-col h-[600px] max-h-[90vh]">
        
        <!-- Header Chat -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-2xl">
          <div>
            <h3 class="text-base font-bold text-slate-800">
              Diskusi Tiket: #{{ selectedTicket?.nomor_tiket }}
            </h3>
            <p class="text-xs text-slate-500 truncate max-w-xs">{{ selectedTicket?.judul }}</p>
          </div>
          
          <div class="flex items-center gap-2">
            <button 
              v-if="chatMessages.length > 0"
              @click="deleteAllMessages"
              :disabled="deletingAll"
              title="Hapus Semua Percakapan"
              class="px-2.5 py-1 text-[11px] font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition flex items-center gap-1 disabled:opacity-50"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <span>{{ deletingAll ? 'Menghapus...' : 'Hapus Semua' }}</span>
            </button>

            <button @click="closeChatModal" class="text-slate-400 hover:text-slate-600 p-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body Chat -->
        <div class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/30">
          <div v-if="loadingChat" class="text-center text-xs text-slate-400 py-8">
            Memuat percakapan...
          </div>
          <div v-else-if="chatMessages.length === 0" class="text-center text-xs text-slate-400 py-8">
            Belum ada diskusi untuk tiket ini.
          </div>
          <template v-else>
            <div 
              v-for="msg in chatMessages" 
              :key="msg.id" 
              class="flex flex-col group"
              :class="msg.user_id === selectedTicket?.user_id ? 'items-end' : 'items-start'"
            >
              <div class="text-[10px] text-slate-400 mb-0.5 px-1 flex items-center gap-1">
                <span>{{ msg.user?.name || 'Pengguna' }} • {{ msg.created_at_formatted || 'Baru saja' }}</span>
              </div>

              <div 
                class="flex items-center gap-1.5 max-w-[85%]"
                :class="msg.user_id === selectedTicket?.user_id ? 'flex-row-reverse' : 'flex-row'"
              >
                <div 
                  class="rounded-2xl px-4 py-2.5 text-xs shadow-sm break-words flex-1"
                  :class="
                    msg.user_id === selectedTicket?.user_id 
                      ? 'bg-indigo-600 text-white rounded-br-none' 
                      : 'bg-white border border-slate-200 text-slate-700 rounded-bl-none'
                  "
                >
                  {{ msg.message }}
                </div>

                <button 
                  @click="deleteSingleMessage(msg.id)"
                  :disabled="deletingMessageId === msg.id"
                  title="Hapus pesan ini"
                  class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-slate-400 hover:text-rose-500 hover:bg-slate-100 rounded-lg disabled:opacity-30"
                >
                  <svg v-if="deletingMessageId !== msg.id" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  <svg v-else class="animate-spin w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Form Input Chat -->
        <form @submit.prevent="sendMessage" class="p-3 border-t border-slate-100 bg-white rounded-b-2xl flex gap-2">
          <input 
            v-model="newMessage" 
            type="text" 
            placeholder="Ketik pesan..." 
            class="flex-1 px-3.5 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <button 
            type="submit" 
            :disabled="sendingMessage || !newMessage.trim()"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-xl disabled:opacity-50 transition flex items-center gap-1"
          >
            <span>Kirim</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>

      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.25s ease-out;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 20px);
}
</style>