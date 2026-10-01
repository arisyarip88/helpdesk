<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const route = useRoute()
const isSidebarOpen = ref(false) // Default tertutup pada layar mobile
const isKnowledgeMenuOpen = ref(route.path.startsWith('/admin/knowledge-base'))
const isTicketNotificationOpen = ref(false)
const ticketNotificationStats = ref({ open: 0, complete: 0, rejected: 0 })
const unreadTicketNotifications = ref({ open: 0, complete: 0, rejected: 0 })
const unreadChatNotifications = useState('admin-unread-chat-notifications', () => ({}))
const departmentStatusNotifications = ref([])
const departmentTicketWarnings = ref([])
const previousChatMessageIds = ref(null)
const isFetchingDepartmentStatuses = ref(false)
const isFetchingChatNotifications = ref(false)
const isFetchingStatusUpdateNotifications = ref(false)
const hasLoadedTicketNotificationStats = ref(false)
const ticketNotificationRef = ref(null)
const apiBase = useRuntimeConfig().public.apiBase || 'http://localhost:8000/api'
const router = useRouter()

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}
//tambahkan menu category
const isCategoryMenuOpen = ref(route.path.startsWith('/admin/categories'))


// State untuk dropdown menu profil
const isDropdownOpen = ref(false)
const dropdownRef = ref(null)

const { token, user, fetchUser, logout, hasRole } = useAuth()
const unreadTicketNotificationTotal = computed(() =>
  unreadTicketNotifications.value.open +
  unreadTicketNotifications.value.complete +
  unreadTicketNotifications.value.rejected
)
const roleThreeNotificationTotal = computed(() =>
  unreadTicketNotificationTotal.value +
  departmentStatusNotifications.value.length +
  departmentTicketWarnings.value.length +
  Object.keys(unreadChatNotifications.value).length
)
const unreadChatNotificationTotal = computed(() => Object.keys(unreadChatNotifications.value).length)
const ticketNotificationTotal = computed(() => {
  if (hasRole(['3'])) return roleThreeNotificationTotal.value
  return unreadTicketNotificationTotal.value + unreadChatNotificationTotal.value + departmentStatusNotifications.value.length
})

const fetchTicketNotificationStats = async () => {
  const roleId = Number(user.value?.role_id)
  if (![1, 2, 3].includes(roleId) || !token.value) return

  if (roleId === 3 && !isFetchingDepartmentStatuses.value) {
    isFetchingDepartmentStatuses.value = true
    try {
      const warningResponse = await $fetch(`${apiBase}/ticket-warnings`, {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token.value}`
        }
      })
      departmentTicketWarnings.value = warningResponse.data || []
    } catch (error) {
      console.error('Gagal memeriksa peringatan tiket departemen:', error)
    } finally {
      isFetchingDepartmentStatuses.value = false
    }
  }

  try {
    const response = await $fetch(`${apiBase}/tickets/stats`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token.value}`
      },
      params: {
        user_id: user.value?.id,
        department_id: user.value?.department_id,
        role_id: roleId
      }
    })
    const nextStats = response.data || response
    const statusKeys = ['open', 'complete', 'rejected']

    if (!hasLoadedTicketNotificationStats.value) {
      unreadTicketNotifications.value = Object.fromEntries(statusKeys.map(key => [
        key,
        roleId === 3 ? 0 : Number(nextStats[key]) || 0
      ]))
      hasLoadedTicketNotificationStats.value = true
    } else {
      for (const key of statusKeys) {
        const increase = (Number(nextStats[key]) || 0) - (Number(ticketNotificationStats.value[key]) || 0)
        if (increase > 0) unreadTicketNotifications.value[key] += increase
      }
    }

    ticketNotificationStats.value = nextStats
  } catch (error) {
    console.error('Gagal memuat notifikasi tiket:', error)
  }
}

const fetchStatusUpdateNotifications = async () => {
  if (![1, 2, 3].includes(Number(user.value?.role_id)) || !token.value || isFetchingStatusUpdateNotifications.value) return

  isFetchingStatusUpdateNotifications.value = true
  try {
    const response = await $fetch(`${apiBase}/ticket-status-notifications`, {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token.value
      }
    })
    departmentStatusNotifications.value = (response.data || []).map(notification => ({
      id: notification.id,
      ticketId: notification.ticket_id,
      ticketNumber: notification.ticket?.nomor_tiket || '-',
      title: notification.ticket?.judul || '-',
      previousStatusName: notification.previous_status?.name || 'Status sebelumnya',
      statusName: notification.status?.name || 'Status berubah',
      statusId: Number(notification.status_id)
    }))
  } catch (error) {
    console.error('Gagal memuat notifikasi perubahan status tiket:', error)
  } finally {
    isFetchingStatusUpdateNotifications.value = false
  }
}

const fetchChatNotifications = async () => {
  if (![1, 2, 3].includes(Number(user.value?.role_id)) || !token.value || isFetchingChatNotifications.value) return

  isFetchingChatNotifications.value = true
  try {
    const response = await $fetch(`${apiBase}/tickets/chat-notifications`, {
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token.value,
      }
    })
    const messages = response.data || []
    const currentMessageIds = new Map(messages.map(message => [
      Number(message.ticket_id),
      Number(message.id)
    ]))

    if (previousChatMessageIds.value) {
      for (const message of messages) {
        const ticketId = Number(message.ticket_id)
        const previousMessageId = previousChatMessageIds.value.get(ticketId)
        if (previousMessageId === Number(message.id)) continue

        if (Number(message.user_id) === Number(user.value?.id)) {
          delete unreadChatNotifications.value[ticketId]
        } else {
          unreadChatNotifications.value[ticketId] = message
        }
      }
    } else {
      for (const message of messages) {
        if (Number(message.user_id) !== Number(user.value?.id)) {
          unreadChatNotifications.value[Number(message.ticket_id)] = message
        }
      }
    }

    previousChatMessageIds.value = currentMessageIds
  } catch (error) {
    console.error('Gagal memuat notifikasi chat:', error)
  } finally {
    isFetchingChatNotifications.value = false
  }
}

const openTicketsByStatus = (statusKey, statusId, search = '') => {
  if (statusKey) unreadTicketNotifications.value[statusKey] = 0
  isTicketNotificationOpen.value = false
  router.push({ path: '/admin/tickets', query: { status_id: statusId, search: search || undefined } })
}

const openChatNotifications = () => {
  isTicketNotificationOpen.value = false
  router.push({
    path: '/admin/tickets',
    query: { new_messages: '1' }
  })
}

const openChatNotification = (notification) => {
  isTicketNotificationOpen.value = false
  router.push({
    path: '/admin/tickets',
    query: { new_messages: '1', search: notification.ticket?.nomor_tiket }
  })
}

const openDepartmentStatusNotification = async (notification) => {
  try {
    await $fetch(`${apiBase}/ticket-status-notifications/${notification.id}/read`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: 'Bearer ' + token.value
      }
    })
  } catch (error) {
    console.error('Gagal menandai notifikasi tiket sudah dibaca:', error)
    return
  }

  departmentStatusNotifications.value = departmentStatusNotifications.value.filter(item => item.id !== notification.id)
  isTicketNotificationOpen.value = false
  router.push({
    path: '/admin/tickets',
    query: { status_id: notification.statusId, search: notification.ticketNumber }
  })
}

const openDepartmentWarningNotification = async (notification) => {
  if (notification.warning_id) {
    try {
      await $fetch(`${apiBase}/ticket-warnings/${notification.warning_id}/read`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token.value}`
        }
      })
    } catch (error) {
      console.error('Gagal menandai peringatan sebagai dibaca:', error)
    }
  }

  departmentTicketWarnings.value = departmentTicketWarnings.value.filter(item => item.id !== notification.id)
  isTicketNotificationOpen.value = false
  router.push({ path: '/admin/tickets', query: { search: notification.ticket_number } })
}

let ticketNotificationInterval = null
let chatNotificationInterval = null
let statusNotificationInterval = null

// Menutup dropdown jika klik dilakukan di luar area menu
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
  if (ticketNotificationRef.value && !ticketNotificationRef.value.contains(event.target)) {
    isTicketNotificationOpen.value = false
  }
}

// Otomatis menutup sidebar di mobile saat pengguna berpindah halaman
watch(() => route.path, () => {
  isTicketNotificationOpen.value = false
  if (route.path.startsWith('/admin/knowledge-base')) {
    isKnowledgeMenuOpen.value = true
  }
  if (process.client && window.innerWidth < 768) {
    isSidebarOpen.value = false
  }
})

watch(() => [user.value?.id, token.value], ([userId, currentToken]) => {
  if (userId && currentToken) {
    previousChatMessageIds.value = null
    fetchTicketNotificationStats()
    fetchChatNotifications()
    fetchStatusUpdateNotifications()
  }
}, { immediate: true })

onMounted(() => {
  fetchUser()
  fetchTicketNotificationStats()
  fetchChatNotifications()
  fetchStatusUpdateNotifications()
  ticketNotificationInterval = setInterval(fetchTicketNotificationStats, 30000)
  chatNotificationInterval = setInterval(fetchChatNotifications, 7000)
  statusNotificationInterval = setInterval(fetchStatusUpdateNotifications, 15000)
  document.addEventListener('click', handleClickOutside)
  
  // Buka sidebar secara default khusus tampilan Desktop
  if (window.innerWidth >= 768) {
    isSidebarOpen.value = true
  }
})

onUnmounted(() => {
  if (ticketNotificationInterval) clearInterval(ticketNotificationInterval)
  if (chatNotificationInterval) clearInterval(chatNotificationInterval)
  if (statusNotificationInterval) clearInterval(statusNotificationInterval)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="admin-layout-shell min-h-screen bg-gray-100 flex flex-col font-sans">
    
    <!-- Header Navbar -->
    <header class="admin-print-hide bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 fixed top-0 left-0 right-0 z-40">
      <div class="flex items-center space-x-3">
        <!-- Tombol Hamburger / Toggle Sidebar -->
        <button 
          @click="toggleSidebar" 
          type="button"
          class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
          aria-label="Toggle Navigation"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div class="flex items-center space-x-2">
          <img src="/unpam.png" alt="Logo" class="w-7 h-7 object-contain" />
          <span class="text-base sm:text-xl font-bold text-gray-800 truncate">Helpdesk UNPAM</span> 
        </div>
      </div>

      <!-- Area Pengguna & Dropdown Submenu -->
      <div class="flex items-center gap-2">
      <div v-if="hasRole(['1','2','3'])" ref="ticketNotificationRef" class="relative">
        <button
          type="button"
          @click="isTicketNotificationOpen = !isTicketNotificationOpen; isDropdownOpen = false"
          :aria-expanded="isTicketNotificationOpen"
          aria-label="Notifikasi tiket"
          title="Notifikasi tiket"
          class="relative rounded-lg p-2 text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4.5-5.8V4a1.5 1.5 0 0 0-3 0v1.2A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9" />
          </svg>
          <span v-if="ticketNotificationTotal > 0" class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[9px] font-bold text-white">
            {{ ticketNotificationTotal > 99 ? '99+' : ticketNotificationTotal }}
          </span>
        </button>

        <Transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
          <div v-if="isTicketNotificationOpen" class="absolute right-0 z-50 mt-2 w-[min(21rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
            <div class="border-b border-gray-100 px-4 py-3">
              <p class="text-sm font-bold text-gray-800">Notifikasi Tiket</p>
              <p class="mt-0.5 text-[11px] text-gray-500">{{ hasRole(['3']) ? 'Tiket baru, pesan, perubahan status, dan peringatan SLA unit Anda.' : 'Pilih kategori untuk melihat daftar tiket.' }}</p>
            </div>
            <div v-if="unreadChatNotificationTotal > 0" class="border-b border-gray-100 p-2">
              <button
                v-for="notification in Object.values(unreadChatNotifications).slice(0, 5)"
                :key="notification.id"
                type="button"
                @click="openChatNotification(notification)"
                class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-emerald-50"
              >
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-gray-800">{{ notification.ticket?.nomor_tiket }} · Pesan chat baru</span>
                  <span class="mt-0.5 block truncate text-[11px] text-gray-600">{{ notification.user?.name || 'Pengguna' }}: {{ notification.message }}</span>
                </span>
              </button>
              <button
                type="button"
                @click="openChatNotifications"
                class="mt-1 flex w-full items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-left text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
              >
                <span>Lihat tiket dengan pesan baru</span>
                <span class="rounded-full bg-white px-2 py-0.5">{{ unreadChatNotificationTotal }}</span>
              </button>
            </div>
            <div v-if="departmentStatusNotifications.length" class="max-h-64 overflow-y-auto border-b border-gray-100 p-2">
              <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-400">Perubahan status tiket</p>
              <button
                v-for="notification in departmentStatusNotifications"
                :key="notification.id"
                @click="openDepartmentStatusNotification(notification)"
                class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-indigo-50"
              >
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="notification.statusId === 4 ? 'bg-emerald-500' : notification.statusId === 5 ? 'bg-rose-500' : 'bg-indigo-500'"></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-gray-800">{{ notification.ticketNumber }} · {{ notification.statusName }}</span>
                  <span class="mt-0.5 block truncate text-[11px] text-gray-500">{{ notification.title }}</span>
                  <span class="mt-0.5 block text-[10px] text-gray-400">{{ notification.previousStatusName }} → {{ notification.statusName }}</span>
                </span>
              </button>
            </div>
            <div v-if="hasRole(['3'])" class="max-h-80 overflow-y-auto p-2">
              <button
                v-if="unreadTicketNotifications.open > 0"
                type="button"
                @click="openTicketsByStatus('open', 1)"
                class="mb-1 flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition hover:bg-indigo-50"
              >
                <span class="flex items-center gap-2.5 text-sm font-medium text-gray-700"><span class="h-2 w-2 rounded-full bg-indigo-500"></span>Tiket Baru</span>
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700">{{ unreadTicketNotifications.open }}</span>
              </button>
              <button
                v-for="notification in departmentTicketWarnings"
                :key="notification.id"
                @click="openDepartmentWarningNotification(notification)"
                class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-amber-50"
              >
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="notification.is_overdue ? 'bg-rose-500' : 'bg-amber-500'"></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-gray-800">{{ notification.ticket_number }} · {{ notification.is_overdue ? 'Melewati batas waktu' : 'Peringatan tiket' }}</span>
                  <span class="mt-0.5 block truncate text-[11px] text-gray-500">{{ notification.title }}</span>
                  <span class="mt-0.5 block text-[10px] text-gray-500">{{ notification.message }}</span>
                </span>
              </button>
              <p v-if="roleThreeNotificationTotal === 0" class="px-3 py-5 text-center text-xs text-gray-500">Tidak ada notifikasi tiket baru.</p>
            </div>
            <div v-else class="p-2">
              <button v-if="unreadTicketNotifications.open > 0" @click="openTicketsByStatus('open', 1)" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition hover:bg-indigo-50">
                <span class="flex items-center gap-2.5 text-sm font-medium text-gray-700"><span class="h-2 w-2 rounded-full bg-indigo-500"></span>Baru Masuk</span>
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700">{{ unreadTicketNotifications.open }}</span>
              </button>
              <button v-if="unreadTicketNotifications.complete > 0" @click="openTicketsByStatus('complete', 4)" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition hover:bg-emerald-50">
                <span class="flex items-center gap-2.5 text-sm font-medium text-gray-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Close</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">{{ unreadTicketNotifications.complete }}</span>
              </button>
              <button v-if="unreadTicketNotifications.rejected > 0" @click="openTicketsByStatus('rejected', 5)" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition hover:bg-rose-50">
                <span class="flex items-center gap-2.5 text-sm font-medium text-gray-700"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Reject</span>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">{{ unreadTicketNotifications.rejected }}</span>
              </button>
              <p v-if="ticketNotificationTotal === 0" class="px-3 py-5 text-center text-xs text-gray-500">Tidak ada notifikasi baru.</p>
            </div>
          </div>
        </Transition>
      </div>

      <div class="relative" ref="dropdownRef">
        <button 
          @click="isDropdownOpen = !isDropdownOpen"
          type="button"
          class="flex items-center space-x-2 sm:space-x-3 p-1.5 rounded-lg hover:bg-gray-100 transition focus:outline-none"
        >
          <!-- Inisialisasi Avatar -->
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-xs sm:text-sm shadow">
            {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
          </div>
          
          <div class="text-left hidden sm:block">
            <p class="text-sm font-semibold text-gray-700 leading-none">{{ user?.name || 'Loading...' }}</p>
            <p class="text-xs text-indigo-600 font-medium mt-1 uppercase">{{ user?.role?.display_name || user?.role?.name || 'Guest' }}</p>
          </div>

          <!-- Panah Dropdown -->
          <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Submenu Dropdown Panel -->
        <Transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div 
            v-if="isDropdownOpen" 
            class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-200 py-2 z-50"
          >
            <!-- Info Ringkas Akun -->
            <div class="px-4 py-2 border-b border-gray-100">
              <p class="text-xs text-gray-400 font-medium">Masuk sebagai</p>
              <p class="text-sm font-bold text-gray-800 truncate">{{ user?.name }}</p>
              <p class="text-xs text-indigo-600 font-semibold uppercase sm:hidden mt-0.5">{{ user?.role?.display_name || user?.role?.name || 'Guest' }}</p>
              <p class="text-xs text-gray-500 truncate mt-0.5">{{ user?.email }}</p>
            </div>

            <!-- Submenu Items -->
            <div class="py-1">
              <NuxtLink 
                to="/admin/users/profile" 
                @click="isDropdownOpen = false"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition"
              >
                <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Profil Saya
              </NuxtLink>
            </div>

            <!-- Action Submenu: Logout -->
            <div class="border-t border-gray-100 pt-1">
              <button 
                @click="logout" 
                type="button"
                class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition"
              >
                <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Logout
              </button>
            </div>
          </div>
        </Transition>
      </div>
      </div>
    </header>

    <!-- Overlay Latar Belakang Gelap (Mobile Backdrop) -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="admin-print-hide fixed inset-0 bg-black/50 z-20 md:hidden transition-opacity"
      aria-hidden="true"
    ></div>

    <!-- Content & Sidebar Wrapper -->
    <div class="admin-layout-content flex pt-16 min-h-screen">
      
      <!-- Sidebar Navigation -->
      <aside 
        class="admin-print-hide"
        :class="[
          'bg-gray-900 text-gray-300 transition-all duration-300 ease-in-out fixed top-16 bottom-0 left-0 z-30 overflow-y-auto',
          'md:static md:z-auto',
          isSidebarOpen ? 'w-64 translate-x-0' : '-translate-x-full md:translate-x-0 md:w-16'
        ]"
      >
        <nav class="p-3 space-y-1.5">
          <!-- 1. Dashboard -->
          <NuxtLink v-if="hasRole(['1','2','3'])"
            to="/admin" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 00-1 1m-6 0h6" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Dashboard</span>
          </NuxtLink>

          <!-- 2. User Management -->
          <NuxtLink v-if="hasRole(['1','2'])"
            to="/admin/users" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">User Management</span>
          </NuxtLink>

          <!-- 3. Department -->
          <NuxtLink v-if="hasRole(['1','2'])"
            to="/admin/departments" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Unit Kerja</span>
          </NuxtLink>
          <!-- 3.5. Category -->
          <NuxtLink v-if="hasRole(['1','2','3'])"
            to="/admin/categories" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Kategori</span>
          </NuxtLink>

          <!-- 4. Ticket Management -->
          <NuxtLink 
            to="/admin/tickets" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 011 1.732 2 2 0 01-1 1.732v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 01-1-1.732 2 2 0 011-1.732V7a2 2 0 00-2-2H5z" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Ticket Management</span>
          </NuxtLink>

          <NuxtLink
            v-if="hasRole(['1','2'])"
            to="/admin/settings/handling-time"
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Batas Waktu Penanganan</span>
          </NuxtLink>

          <!-- 5. Knowledge Base -->
          <div v-if="hasRole(['1','2'])">
            <button
              type="button"
              @click="isKnowledgeMenuOpen = !isKnowledgeMenuOpen"
              :aria-expanded="isKnowledgeMenuOpen"
              class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
              :class="route.path.startsWith('/admin/knowledge-base') ? 'bg-blue-600 text-white hover:bg-blue-600' : ''"
            >
              <span class="flex items-center space-x-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Knowledge Base</span>
              </span>
              <svg v-if="isSidebarOpen" class="h-4 w-4 transition-transform" :class="isKnowledgeMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
              </svg>
            </button>
            <div v-if="isKnowledgeMenuOpen && isSidebarOpen" class="ml-5 mt-1 space-y-1 border-l border-slate-700 pl-3">
              <NuxtLink to="/admin/knowledge-base" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-300 hover:bg-gray-800 hover:text-white" exact-active-class="bg-gray-800 text-white">
                Daftar Knowledge
              </NuxtLink>
              <NuxtLink to="/admin/knowledge-base/unanswered" class="block rounded-lg px-3 py-2 text-xs font-medium text-slate-300 hover:bg-gray-800 hover:text-white" active-class="bg-gray-800 text-white">
                Belum Terjawab
              </NuxtLink>
            </div>
          </div>

          <!-- 6. Laporan -->
          <NuxtLink v-if="hasRole(['1','2','3'])"
            to="/admin/reports" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Laporan</span>
          </NuxtLink>

          <!-- 7. Profile -->
          <NuxtLink 
            v-if="!hasRole(['3'])"
            to="/admin/users/profile" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Profile</span>
          </NuxtLink>
        </nav>
      </aside>

      <!-- Main Content Area -->
      <main class="admin-print-main flex-1 p-4 sm:p-6 overflow-y-auto w-full">
        <slot />
      </main>

    </div>
  </div>
</template>