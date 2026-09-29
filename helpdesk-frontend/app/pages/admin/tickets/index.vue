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
const notify = useNotify()
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

const fetchWarningMode = async () => {
  try {
    const endpoint = userRoleId.value === 3
      ? `${apiBase}/ticket-warnings`
      : `${apiBase}/ticket-handling-settings`
    const response = await $fetch(endpoint, {
      headers: getAuthHeaders()
    })
    warningMode.value = response.alert_mode || response.data?.alert_mode || 'automatic'
    warningMaxHours.value = Number(response.max_hours || response.data?.max_hours) || 24
  } catch {
    warningMode.value = 'automatic'
    warningMaxHours.value = 24
  }
}

watch(userRoleId, (roleId) => {
  if ([1, 2, 3].includes(roleId)) fetchWarningMode()
}, { immediate: true })

// --- 1. STATE FILTER & PAGINASI ---
const currentPage = ref(Number(route.query.page) || 1)
const perPage = ref(Number(route.query.per_page) || 10)
const searchQuery = ref(String(route.query.search || ''))
const selectedStatusFilter = ref(String(route.query.status_id || ''))
const selectedDepartmentFilter = ref(String(route.query.department_id || ''))
const selectedPriorityFilter = ref(String(route.query.prioritas || ''))
const overdueOnly = ref(String(route.query.overdue || '') === '1')
const canFilterDepartment = computed(() => [1, 2].includes(userRoleId.value))
const showPriorityStatus = computed(() => [1, 2, 3].includes(userRoleId.value))
const canDeleteChat = computed(() => ![3, 4].includes(userRoleId.value))
const canDeleteTicket = computed(() => userRoleId.value !== 3)
const sendingWarningTicketId = ref(null)
const warningMode = ref('automatic')
const warningMaxHours = ref(24)

const hasExceededWarningDeadline = (ticket) => {
  if (!ticket?.created_at || [4, 5].includes(Number(ticket.status_id))) return false

  const createdAt = new Date(ticket.created_at).getTime()
  return Number.isFinite(createdAt) && Date.now() - createdAt >= warningMaxHours.value * 60 * 60 * 1000
}

// Sinkronisasi State jika Query URL berubah
watch(
  () => route.query,
  (newQuery) => {
    currentPage.value = Number(newQuery.page) || 1
    perPage.value = Number(newQuery.per_page) || 10
    searchQuery.value = String(newQuery.search || '')
    selectedStatusFilter.value = String(newQuery.status_id || '')
    selectedDepartmentFilter.value = String(newQuery.department_id || '')
    selectedPriorityFilter.value = String(newQuery.prioritas || '')
    overdueOnly.value = String(newQuery.overdue || '') === '1'
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
      status_id: selectedStatusFilter.value || undefined,
      department_id: canFilterDepartment.value ? selectedDepartmentFilter.value || undefined : undefined,
      prioritas: showPriorityStatus.value ? selectedPriorityFilter.value || undefined : undefined,
      overdue: overdueOnly.value ? 1 : undefined
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
      department_id: canFilterDepartment.value ? selectedDepartmentFilter.value : undefined,
      prioritas: showPriorityStatus.value ? selectedPriorityFilter.value : undefined,
      overdue: overdueOnly.value ? 1 : undefined,
      role_id: userRoleId.value,
      user_id: userId.value
    }
  }),
  {
    watch: [currentPage, perPage, searchQuery, selectedStatusFilter, selectedDepartmentFilter, selectedPriorityFilter, overdueOnly],
    getCachedData: () => undefined
  }
)

watch([currentPage, perPage, selectedStatusFilter, selectedDepartmentFilter, selectedPriorityFilter, overdueOnly], () => {
  updateQueryParams()
})

watch([selectedStatusFilter, selectedDepartmentFilter, selectedPriorityFilter], () => {
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
const overdueTicketCount = computed(() => Number(responseData.value?.overdue_count) || 0)
const departmentsList = computed(() => responseData.value?.departments || [])
const categoriesList = computed(() => responseData.value?.categories || [])
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

const formatDateTime = (value) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

const priorityBadgeClass = (prioritas) => ({
  'bg-slate-100 text-slate-600': prioritas === 'low',
  'bg-blue-50 text-blue-600': prioritas === 'medium',
  'bg-amber-50 text-amber-600': prioritas === 'high',
  'bg-rose-50 text-rose-600': prioritas === 'urgent'
})

const statusBadgeClass = (statusId) => ({
  'bg-amber-100 text-amber-700': Number(statusId) === 1,
  'bg-blue-100 text-blue-700': Number(statusId) === 2,
  'bg-emerald-100 text-emerald-700': Number(statusId) === 3,
  'bg-slate-100 text-slate-600': Number(statusId) === 4,
  'bg-rose-100 text-rose-700': Number(statusId) === 5
})

const formatResolutionDuration = (createdAt, completedAt) => {
  if (!createdAt || !completedAt) return 'Belum selesai'

  const durationMinutes = Math.floor((new Date(completedAt) - new Date(createdAt)) / 60000)
  if (!Number.isFinite(durationMinutes) || durationMinutes < 0) return '-'

  const days = Math.floor(durationMinutes / 1440)
  const hours = Math.floor((durationMinutes % 1440) / 60)
  const minutes = durationMinutes % 60
  const parts = []

  if (days) parts.push(`${days} hari`)
  if (hours) parts.push(`${hours} jam`)
  if (minutes || parts.length === 0) parts.push(`${minutes} menit`)
  return parts.join(' ')
}

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
const selectedOverdueCount = computed(() => tickets.value.filter(ticket =>
  selectedIds.value.includes(ticket.id) && hasExceededWarningDeadline(ticket)
).length)

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
    const response = await $fetch(`${apiBase}/tickets/bulk-action`, {
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
    if (bulkActionType.value === 'send_warning') {
      notify.success(response.message || 'Peringatan bulk berhasil dikirim.')
    }
  } catch (err) {
    notify.error(err.data?.message || 'Gagal memproses bulk action.')
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
const imagePreviewUrl = ref('')

const form = ref({
  id: null,
  category_id: '',
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
    imagePreviewUrl.value = ''
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = () => { imagePreviewUrl.value = String(reader.result || '') }
      reader.readAsDataURL(file)
    }
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
    category_id: categoriesList.value[0]?.id || '',
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
    category_id: item.category_id || item.category?.id || '',
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
  formData.append('user_id', userId.value || '')
  formData.append('category_id', form.value.category_id)
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
  if (!canDeleteTicket.value) return
  if (!(await notify.confirm({ title: 'Hapus Tiket', message: 'Apakah Anda yakin ingin menghapus tiket aduan ini?', confirmText: 'Ya, Hapus', variant: 'danger' }))) return

  try {
    await $fetch(`${apiBase}/tickets/${id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })
    await Promise.all([refresh(), refreshStats()])
    notify.success('Tiket berhasil dihapus.')
  } catch (err) {
    notify.error(err.data?.message || 'Gagal menghapus tiket.')
  }
}

const sendTicketWarning = async (ticket) => {
  if (![1, 2].includes(userRoleId.value) || warningMode.value !== 'manual' || [4, 5].includes(Number(ticket.status_id))) return
  if (!(await notify.confirm({ title: 'Kirim Peringatan', message: `Kirim peringatan penanganan untuk tiket ${ticket.nomor_tiket}?` }))) return

  sendingWarningTicketId.value = ticket.id
  try {
    const response = await $fetch(`${apiBase}/tickets/${ticket.id}/warnings`, {
      method: 'POST',
      headers: getAuthHeaders()
    })
    notify.success(response.message || 'Peringatan berhasil dikirim.')
  } catch (err) {
    notify.error(err.data?.message || 'Gagal mengirim peringatan tiket.')
  } finally {
    sendingWarningTicketId.value = null
  }
}

// --- 5. FITUR CHAT REALTIME & NOTIFIKASI ---
const isDetailChatActive = ref(false)
const selectedTicket = ref(null)
const chatMessages = ref([])
const loadingChat = ref(false)
const sendingMessage = ref(false)
const deletingMessageId = ref(null)
const deletingAll = ref(false)
const newMessage = ref('')

const isDetailModalOpen = ref(false)
const detailTicket = ref(null)
const detailStatus = ref('')
const detailCategoryId = ref('')
const savingTicketDetails = ref(false)
const detailSaveError = ref('')
const detailCategorySearch = ref('')
const isDetailCategoryDropdownOpen = ref(false)
const canEditTicketCategory = computed(() => [1, 2].includes(userRoleId.value))
const hasTicketDetailChanges = computed(() => {
  if (!detailTicket.value) return false

  const categoryChanged = canEditTicketCategory.value &&
    Number(detailCategoryId.value) !== Number(detailTicket.value.category_id)
  const statusChanged = Number(detailStatus.value) !== Number(detailTicket.value.status_id)

  return categoryChanged || statusChanged
})
const selectedDetailCategory = computed(() =>
  categoriesList.value.find(category => Number(category.id) === Number(detailCategoryId.value)) || detailTicket.value?.category
)
const filteredDetailCategories = computed(() => {
  const search = detailCategorySearch.value.trim().toLocaleLowerCase()
  if (!search) return categoriesList.value

  return categoriesList.value.filter(category =>
    `${category.name} ${category.department?.nama || ''}`.toLocaleLowerCase().includes(search)
  )
})

const selectDetailCategory = (category) => {
  detailCategoryId.value = String(category.id)
  detailCategorySearch.value = ''
  isDetailCategoryDropdownOpen.value = false
}

const openDetailModal = (ticket) => {
  detailTicket.value = ticket
  detailStatus.value = String(ticket.status_id || '')
  detailCategoryId.value = String(ticket.category_id || ticket.category?.id || '')
  detailSaveError.value = ''
  detailCategorySearch.value = ''
  isDetailCategoryDropdownOpen.value = false
  isDetailModalOpen.value = true
  openTicketChat(ticket)
}

const closeDetailModal = () => {
  isDetailModalOpen.value = false
  detailTicket.value = null
  detailSaveError.value = ''
  detailCategorySearch.value = ''
  isDetailCategoryDropdownOpen.value = false
  closeTicketChat()
}

const saveTicketDetails = async () => {
  if (!detailTicket.value || !hasTicketDetailChanges.value) return

  const payload = {}
  if (Number(detailStatus.value) !== Number(detailTicket.value.status_id)) {
    payload.status_id = Number(detailStatus.value)
  }
  if (canEditTicketCategory.value && Number(detailCategoryId.value) !== Number(detailTicket.value.category_id)) {
    payload.category_id = Number(detailCategoryId.value)
  }
  if (userRoleId.value === 4 && detailTicket.value.category_id) {
    payload.category_id = Number(detailTicket.value.category_id)
  }

  savingTicketDetails.value = true
  detailSaveError.value = ''
  try {
    const response = await $fetch(`${apiBase}/tickets/${detailTicket.value.id}`, {
      method: 'PUT',
      headers: getAuthHeaders(),
      body: payload
    })
    if (response.data) {
      detailTicket.value = response.data
      detailStatus.value = String(response.data.status_id)
      detailCategoryId.value = String(response.data.category_id || '')
    }
    await Promise.all([refresh(), refreshStats()])
    const updatedTicket = tickets.value.find(ticket => ticket.id === detailTicket.value.id)
    if (updatedTicket) {
      detailTicket.value = updatedTicket
      detailStatus.value = String(updatedTicket.status_id)
      detailCategoryId.value = String(updatedTicket.category_id || '')
    }
  } catch (err) {
    detailSaveError.value = err.data?.message || 'Gagal menyimpan perubahan tiket.'
  } finally {
    savingTicketDetails.value = false
  }
}

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

const openTicketChat = async (ticket) => {
  if (chatInterval) clearInterval(chatInterval)
  selectedTicket.value = ticket
  isDetailChatActive.value = true
  chatMessages.value = []
  
  unreadCounts.value[ticket.id] = 0

  await fetchMessages()

  chatInterval = setInterval(() => {
    fetchMessages(true)
  }, 3000)
}

const closeTicketChat = () => {
  isDetailChatActive.value = false
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
    notify.error(err.data?.message || 'Gagal mengirim pesan.')
  } finally {
    sendingMessage.value = false
  }
}

const deleteSingleMessage = async (messageId) => {
  if (!canDeleteChat.value || !selectedTicket.value) return
  if (!(await notify.confirm({ title: 'Hapus Pesan', message: 'Apakah Anda yakin ingin menghapus pesan ini?', confirmText: 'Ya, Hapus', variant: 'danger' }))) return

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
    notify.error(err.data?.message || 'Gagal menghapus pesan.')
  } finally {
    deletingMessageId.value = null
  }
}

const deleteAllMessages = async () => {
  if (!canDeleteChat.value || !selectedTicket.value) return
  if (!(await notify.confirm({ title: 'Hapus Semua Pesan', message: 'Apakah Anda yakin ingin menghapus SELURUH pesan percakapan pada tiket ini?', confirmText: 'Ya, Hapus Semua', variant: 'danger' }))) return

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
    notify.error(err.data?.message || 'Gagal menghapus semua pesan.')
  } finally {
    deletingAll.value = false
  }
}

const checkGlobalUnreadMessages = async () => {
  if (isDetailChatActive.value || !tickets.value.length) return

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
        <h1 class="text-2xl font-bold text-slate-800">Tiket Aduan</h1>
        <p class="text-sm text-slate-500">Kelola dan pantau seluruh laporan aduan dari pengguna.</p>
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

      <div class="flex w-full md:w-auto flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2">
        <select
          v-if="canFilterDepartment"
          v-model="selectedDepartmentFilter"
          aria-label="Filter departemen"
          class="min-w-0 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">Semua Departemen</option>
          <option v-for="department in departmentsList" :key="department.kode || department.id" :value="department.kode || department.id">
            {{ department.nama }}
          </option>
        </select>
        <select
          v-if="showPriorityStatus"
          v-model="selectedPriorityFilter"
          aria-label="Filter prioritas"
          class="min-w-0 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">Semua Prioritas</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
        <button
          v-if="[1, 2, 3].includes(userRoleId)"
          type="button"
          :aria-pressed="overdueOnly"
          @click="overdueOnly = !overdueOnly; currentPage = 1"
          class="inline-flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
          :class="overdueOnly ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-rose-300 hover:text-rose-700'"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.2A2 2 0 003.5 21h17a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z" />
          </svg>
          {{ overdueOnly ? 'Tampilkan Semua' : 'Terlambat SLA' }}
          <span
            aria-label="Jumlah tiket melewati SLA"
            class="inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-bold"
            :class="overdueOnly ? 'bg-white text-rose-700' : 'bg-rose-100 text-rose-700'"
          >
            {{ overdueTicketCount > 99 ? '99+' : overdueTicketCount }}
          </span>
        </button>
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span>Tampilkan:</span>
          <select v-model="perPage" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
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
              <th class="px-6 py-4">ID Tiket</th>
              <th class="px-6 py-4">Judul & Pelapor</th>
              <th class="px-6 py-4">Waktu & Durasi Penyelesaian</th>
              <th v-if="showPriorityStatus" class="px-6 py-4">Prioritas</th>
              <th v-if="showPriorityStatus" class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Loading -->
            <tr v-if="pending">
              <td :colspan="showPriorityStatus ? 7 : 5" class="px-6 py-12 text-center text-slate-400">
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
              <td :colspan="showPriorityStatus ? 7 : 5" class="px-6 py-12 text-center text-rose-500">
                Gagal memuat data tiket aduan.
              </td>
            </tr>

            <!-- Data List -->
            <tr 
              v-else 
              v-for="item in tickets" 
              :key="item.id" 
              @click="openDetailModal(item)"
              @keydown.enter="openDetailModal(item)"
              tabindex="0"
              :class="{'bg-indigo-50/30': selectedIds.includes(item.id)}"
              class="hover:bg-indigo-50 transition-colors cursor-pointer focus:outline-none focus:bg-indigo-50/70"
            >
              <td class="py-4 px-4 text-center">
                <input 
                  type="checkbox" 
                  :value="item.id" 
                  v-model="selectedIds"
                  @click.stop
                  class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col items-start gap-1.5">
                  <span class="font-mono text-xs font-bold text-indigo-600">{{ item.nomor_tiket }}</span>
                  <span
                    v-if="[1, 2, 3].includes(userRoleId) && hasExceededWarningDeadline(item)"
                    class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700"
                    :title="`Tiket melewati batas penanganan ${warningMaxHours} jam`"
                  >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.2A2 2 0 003.5 21h17a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z" />
                    </svg>
                    Melewati batas
                  </span>
                  <button
                    v-if="warningMode === 'manual' && [1, 2].includes(userRoleId) && hasExceededWarningDeadline(item)"
                    type="button"
                    :disabled="sendingWarningTicketId === item.id"
                    :title="`Kirim peringatan untuk ${item.nomor_tiket}`"
                    @click.stop="sendTicketWarning(item)"
                    class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                  >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.2A2 2 0 003.5 21h17a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z" />
                    </svg>
                    {{ sendingWarningTicketId === item.id ? 'Mengirim...' : 'Peringatkan' }}
                  </button>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-slate-800">{{ item.judul }}</div>
                <div class="mt-1 text-xs text-slate-500">Pelapor: {{ item.user?.name || '-' }}</div>
                <div class="mt-1 text-xs text-slate-500">Unit tujuan: {{ item.category?.department?.nama || item.department?.nama || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                <div>Dibuat: {{ formatDateTime(item.created_at) }}</div>
                <div class="mt-1">Selesai: {{ formatDateTime(item.terselesaikan_pada) }}</div>
                <div class="mt-1 font-semibold text-slate-700">Durasi: {{ formatResolutionDuration(item.created_at, item.terselesaikan_pada) }}</div>
                <div v-if="Number(item.status_id) === 4" class="mt-1 flex items-center gap-1.5" :aria-label="item.rating ? `Rating ${item.rating} dari 5` : 'Belum dinilai'">
                  <span>Rating:</span>
                  <template v-if="item.rating">
                    <span aria-hidden="true" class="text-amber-500">★★★★★</span>
                    <span class="font-semibold text-amber-700">{{ item.rating }}/5</span>
                  </template>
                  <span v-else class="text-slate-400">Belum dinilai</span>
                </div>
              </td>
              <td v-if="showPriorityStatus" class="px-6 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full uppercase" :class="{
                  'bg-slate-100 text-slate-600': item.prioritas === 'low',
                  'bg-blue-50 text-blue-600': item.prioritas === 'medium',
                  'bg-amber-50 text-amber-600': item.prioritas === 'high',
                  'bg-rose-50 text-rose-600': item.prioritas === 'urgent'
                }">{{ item.prioritas || '-' }}</span>
              </td>
              <td v-if="showPriorityStatus" class="px-6 py-4">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full capitalize" :class="{
                  'bg-amber-100 text-amber-700': Number(item.status_id) === 1,
                  'bg-blue-100 text-blue-700': Number(item.status_id) === 2,
                  'bg-emerald-100 text-emerald-700': Number(item.status_id) === 3,
                  'bg-slate-100 text-slate-600': Number(item.status_id) === 4,
                  'bg-rose-100 text-rose-700': Number(item.status_id) === 5
                }">{{ item.status?.name || '-' }}</span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button
                    type="button"
                    :aria-label="`Buka chat tiket ${item.nomor_tiket}`"
                    :title="`Chat tiket ${item.nomor_tiket}`"
                    @click.stop="openTicketChat(item)"
                    class="relative inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Chat
                    <span v-if="unreadCounts[item.id] > 0" class="absolute -right-1.5 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white">
                      {{ unreadCounts[item.id] > 9 ? '9+' : unreadCounts[item.id] }}
                    </span>
                  </button>
                  <button
                    type="button"
                    :aria-label="`Lihat detail tiket ${item.nomor_tiket}`"
                    @click.stop="openDetailModal(item)"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6z" />
                      <circle cx="12" cy="12" r="2.5" stroke-width="2" />
                    </svg>
                    Detail Tiket
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-if="!pending && tickets.length === 0">
              <td :colspan="showPriorityStatus ? 7 : 5" class="px-6 py-12 text-center text-slate-400">
                {{ overdueOnly ? 'Tidak ada tiket yang melewati batas penanganan.' : 'Data tiket aduan tidak ditemukan.' }}
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
            v-if="warningMode === 'manual' && [1, 2].includes(userRoleId) && selectedOverdueCount > 0"
            @click="openBulkModal('send_warning')"
            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-medium transition-colors"
          >
            Kirim Peringatan
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
          <span v-else-if="bulkActionType === 'send_warning'">Kirim Peringatan Tiket</span>
        </h3>
        
        <p class="text-xs text-slate-500 mb-4">
          Tindakan ini akan diterapkan pada <strong class="text-indigo-600">{{ selectedIds.length }} tiket</strong> yang dipilih.
        </p>

        <p v-if="bulkActionType === 'send_warning'" class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
          Peringatan dikirim ke role 3 untuk tiket yang belum selesai. Tiket complete/rejected dilewati.
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
            :disabled="submittingBulk || (['change_status', 'change_priority'].includes(bulkActionType) && !bulkActionValue)"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold"
          >
            {{ submittingBulk ? 'Memproses...' : 'Terapkan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Detail Tiket -->
    <div v-if="isDetailModalOpen && detailTicket" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <section role="dialog" aria-modal="true" aria-labelledby="ticket-detail-title" class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <header class="p-5 border-b border-slate-100 flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs font-mono font-semibold text-indigo-600">{{ detailTicket.nomor_tiket }}</p>
            <h2 id="ticket-detail-title" class="mt-1 text-lg font-bold text-slate-800">Detail Tiket</h2>
          </div>
          <button @click="closeDetailModal" aria-label="Tutup detail" class="p-1 text-slate-400 hover:text-slate-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </header>

        <div class="p-5 space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <p class="text-slate-500">Pelapor <span class="block mt-1 text-sm font-medium text-slate-800">{{ detailTicket.user?.name || '-' }}</span></p>
            <p class="text-slate-500">Dibuat <span class="block mt-1 text-sm font-medium text-slate-800">{{ formatDateTime(detailTicket.created_at) }}</span></p>
            <p class="text-slate-500">Selesai <span class="block mt-1 text-sm font-medium text-slate-800">{{ formatDateTime(detailTicket.terselesaikan_pada) }}</span></p>
          </div>

          <div class="border-t border-slate-100 pt-4">
            <label v-if="canEditTicketCategory" for="ticket-detail-category" class="block text-xs font-semibold text-slate-700">Kategori Aduan</label>
            <h3 v-else class="text-xs font-semibold uppercase text-slate-500">Kategori Aduan</h3>
            <div v-if="canEditTicketCategory" class="mt-2 flex flex-col gap-2 sm:flex-row">
              <div class="relative min-w-0 flex-1">
                <button
                  id="ticket-detail-category"
                  type="button"
                  role="combobox"
                  aria-label="Pilih kategori tiket"
                  :aria-expanded="isDetailCategoryDropdownOpen"
                  aria-controls="ticket-detail-category-options"
                  :disabled="savingTicketDetails"
                  @click="isDetailCategoryDropdownOpen = !isDetailCategoryDropdownOpen"
                  class="flex w-full items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-100"
                >
                  <span class="min-w-0 truncate">
                    {{ selectedDetailCategory ? `${selectedDetailCategory.name} · ${selectedDetailCategory.department?.nama || '-'}` : 'Pilih kategori' }}
                  </span>
                  <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                  </svg>
                </button>
                <div v-if="isDetailCategoryDropdownOpen" id="ticket-detail-category-options" class="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                  <div class="border-b border-slate-100 p-2">
                    <input
                      v-model="detailCategorySearch"
                      type="search"
                      aria-label="Cari kategori atau unit"
                      placeholder="Cari kategori atau unit..."
                      class="w-full rounded-md border border-slate-200 px-3 py-2 text-xs text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                      @keydown.esc="isDetailCategoryDropdownOpen = false"
                    />
                  </div>
                  <ul role="listbox" class="max-h-52 overflow-y-auto p-1">
                    <li v-for="category in filteredDetailCategories" :key="category.id" role="option" :aria-selected="Number(detailCategoryId) === Number(category.id)">
                      <button
                        type="button"
                        class="flex w-full items-start justify-between gap-2 rounded-md px-3 py-2 text-left hover:bg-indigo-50"
                        :class="Number(detailCategoryId) === Number(category.id) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700'"
                        @click="selectDetailCategory(category)"
                      >
                        <span class="min-w-0 truncate text-xs font-semibold">{{ category.name }}</span>
                        <span class="shrink-0 text-[10px] text-slate-500">{{ category.department?.nama || '-' }}</span>
                      </button>
                    </li>
                    <li v-if="filteredDetailCategories.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
                      Kategori tidak ditemukan.
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <p v-else class="mt-1 text-sm font-medium text-slate-800">
              {{ detailTicket.category?.name || '-' }}
              <span class="text-slate-500">· {{ detailTicket.category?.department?.nama || detailTicket.department?.nama || '-' }}</span>
            </p>
          </div>

          <div class="space-y-4 border-t border-slate-100 pt-4">
            <div>
              <h3 class="text-xs font-semibold uppercase text-slate-500">Judul Aduan</h3>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ detailTicket.judul || '-' }}</p>
            </div>
            <div>
              <h3 class="text-xs font-semibold uppercase text-slate-500">Deskripsi Aduan</h3>
              <p class="mt-1 text-sm leading-6 text-slate-700 whitespace-pre-line">{{ detailTicket.deskripsi || '-' }}</p>
            </div>
            <div>
              <h3 class="text-xs font-semibold uppercase text-slate-500">Prioritas Aduan</h3>
              <p class="mt-1 text-sm text-slate-700 capitalize">{{ detailTicket.prioritas || '-' }}</p>
            </div>
            <div v-if="detailTicket.lampiran">
              <h3 class="text-xs font-semibold uppercase text-slate-500">Lampiran</h3>
              <a :href="attachmentUrl(detailTicket.lampiran)" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm font-medium text-indigo-600 hover:underline">Lihat lampiran</a>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-4">
            <label for="ticket-detail-status" class="block text-xs font-semibold text-slate-700 mb-1.5">Ubah Status</label>
            <div class="flex flex-col sm:flex-row gap-2">
              <select id="ticket-detail-status" v-model="detailStatus" class="min-w-0 flex-1 px-3 py-2 border border-slate-200 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="" disabled>Pilih status</option>
                <option v-for="status in statusesList" :key="status.id" :value="String(status.id)">
                  {{ status.name || '-' }}
                </option>
              </select>
              <button @click="saveTicketDetails" :disabled="savingTicketDetails || !hasTicketDetailChanges" class="px-4 py-2 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                {{ savingTicketDetails ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
            <p v-if="detailSaveError" role="alert" class="mt-2 text-xs text-rose-600">{{ detailSaveError }}</p>
          </div>

          <section class="border-t border-slate-100 pt-4">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-slate-800">Chat Tiket</h3>
                <p class="text-xs text-slate-500">{{ detailTicket.judul }}</p>
              </div>
              <button
                v-if="canDeleteChat && chatMessages.length > 0"
                @click="deleteAllMessages"
                :disabled="deletingAll"
                class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 disabled:opacity-50"
              >
                {{ deletingAll ? 'Menghapus...' : 'Hapus Semua' }}
              </button>
            </div>

            <div class="mt-3 h-56 overflow-y-auto space-y-3 rounded-lg bg-slate-50 p-3">
              <p v-if="loadingChat" class="py-8 text-center text-xs text-slate-400">Memuat percakapan...</p>
              <p v-else-if="chatMessages.length === 0" class="py-8 text-center text-xs text-slate-400">Belum ada diskusi untuk tiket ini.</p>
              <div
                v-for="message in chatMessages"
                v-else
                :key="message.id"
                class="flex flex-col group"
                :class="message.user_id === selectedTicket?.user_id ? 'items-end' : 'items-start'"
              >
                <p class="mb-1 px-1 text-[10px] text-slate-400">
                  {{ message.user?.name || 'Pengguna' }} · {{ message.created_at_formatted || 'Baru saja' }}
                </p>
                <div class="flex max-w-[90%] items-center gap-2" :class="message.user_id === selectedTicket?.user_id ? 'flex-row-reverse' : ''">
                  <p class="rounded-xl px-3 py-2 text-xs wrap-break-word" :class="message.user_id === selectedTicket?.user_id ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700'">
                    {{ message.message }}
                  </p>
                  <button
                    v-if="canDeleteChat"
                    @click="deleteSingleMessage(message.id)"
                    :disabled="deletingMessageId === message.id"
                    title="Hapus pesan"
                    class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-rose-500 disabled:opacity-30"
                  >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <form @submit.prevent="sendMessage" class="mt-3 flex gap-2">
              <input
                v-model="newMessage"
                type="text"
                placeholder="Ketik pesan..."
                class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
              <button
                type="submit"
                :disabled="sendingMessage || !newMessage.trim()"
                class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ sendingMessage ? 'Mengirim...' : 'Kirim' }}
              </button>
            </form>
          </section>
        </div>

        <footer class="p-5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <!-- <button @click="openEditModal(detailTicket); closeDetailModal()" class="px-3 py-2 rounded-lg text-sm font-medium bg-indigo-50 text-indigo-600 hover:bg-indigo-100">Edit</button> -->
            <button v-if="canDeleteTicket" @click="handleDelete(detailTicket.id); closeDetailModal()" class="px-3 py-2 rounded-lg text-sm font-medium bg-rose-50 text-rose-600 hover:bg-rose-100">Hapus</button>
            <button @click="closeDetailModal" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">Tutup</button>
          </div>
        </footer>
      </section>
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
              <label class="block text-xs font-medium text-slate-700 mb-1">Kategori Aduan:<span class="text-rose-500">*</span></label>
              <select 
                v-model="form.category_id" 
                required 
                class="w-full max-w-full truncate px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              >
                <option 
                  v-for="category in categoriesList" 
                  :key="category.id" 
                  :value="category.id"
                  class="truncate max-w-full"
                >
                  {{ category.name }} ({{ category.department?.nama || '-' }})
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
              File saat ini: <a :href="attachmentUrl(form.existing_lampiran)" target="_blank" class="text-indigo-600 underline">Lihat Lampiran</a>
            </p>
            <img
              v-if="imagePreviewUrl || (!form.lampiran && form.existing_lampiran && isImageAttachment(form.existing_lampiran))"
              :src="imagePreviewUrl || attachmentUrl(form.existing_lampiran)"
              alt="Preview lampiran"
              class="mt-2 h-28 w-full rounded-xl border border-slate-200 object-contain bg-slate-50 p-1"
            />
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