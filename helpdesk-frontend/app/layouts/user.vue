<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const route = useRoute()
const isSidebarOpen = ref(false)

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

// State Menu Dropdown & Modal Profil
const isDropdownOpen = ref(false)
const isProfileModalOpen = ref(false)
const dropdownRef = ref(null)
const ticketNotificationRef = ref(null)
const isTicketNotificationsOpen = ref(false)
const ticketStatusNotifications = ref([])
const previousTicketStatuses = ref(null)
const isFetchingTicketStatuses = ref(false)
const apiBase = useRuntimeConfig().public.apiBase || 'http://localhost:8000/api'
const router = useRouter()

const { token, user, fetchUser, logout } = useAuth()

const fetchTicketStatusChanges = async () => {
  if (Number(user.value?.role_id) !== 4 || !token.value || isFetchingTicketStatuses.value) return

  isFetchingTicketStatuses.value = true
  try {
    const response = await $fetch(`${apiBase}/tickets`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token.value}`
      },
      params: { per_page: 100 }
    })
    const tickets = response.data?.data || []
    const currentStatuses = new Map(tickets.map(ticket => [Number(ticket.id), {
      statusId: Number(ticket.status_id),
      statusName: ticket.status?.name || 'Status berubah',
      ticketNumber: ticket.nomor_tiket,
      title: ticket.judul
    }]))

    if (previousTicketStatuses.value) {
      const changes = []
      for (const [ticketId, current] of currentStatuses) {
        const previous = previousTicketStatuses.value.get(ticketId)
        if (previous && previous.statusId !== current.statusId) {
          changes.push({
            id: `${ticketId}-${current.statusId}-${Date.now()}`,
            ticketId,
            statusId: current.statusId,
            statusName: current.statusName,
            ticketNumber: current.ticketNumber,
            title: current.title,
            changedAt: new Date().toISOString()
          })
        }
      }
      if (changes.length) {
        ticketStatusNotifications.value = [...changes.reverse(), ...ticketStatusNotifications.value].slice(0, 20)
      }
    }

    previousTicketStatuses.value = currentStatuses
  } catch (error) {
    console.error('Gagal memeriksa perubahan status tiket:', error)
  } finally {
    isFetchingTicketStatuses.value = false
  }
}

const openStatusNotification = (notification) => {
  ticketStatusNotifications.value = ticketStatusNotifications.value.filter(item => item.id !== notification.id)
  isTicketNotificationsOpen.value = false
  router.push({ path: '/user', query: { status_id: notification.statusId } })
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
  if (ticketNotificationRef.value && !ticketNotificationRef.value.contains(event.target)) {
    isTicketNotificationsOpen.value = false
  }
}

// Handler Buka Modal Profil
const openProfileModal = () => {
  isDropdownOpen.value = false
  isProfileModalOpen.value = true
}

watch(() => route.path, () => {
  isTicketNotificationsOpen.value = false
  if (process.client && window.innerWidth < 768) {
    isSidebarOpen.value = false
  }
})

watch(() => [user.value?.id, token.value], ([userId, authToken]) => {
  if (userId && authToken) fetchTicketStatusChanges()
}, { immediate: true })

let ticketStatusPollInterval = null

onMounted(() => {
  fetchUser()
  fetchTicketStatusChanges()
  ticketStatusPollInterval = setInterval(fetchTicketStatusChanges, 15000)
  document.addEventListener('click', handleClickOutside)
  
  if (window.innerWidth >= 768) {
    isSidebarOpen.value = true
  }
})

onUnmounted(() => {
  if (ticketStatusPollInterval) clearInterval(ticketStatusPollInterval)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="min-h-screen bg-slate-100 flex flex-col font-sans">
    
    <!-- Navbar Header -->
    <header class="bg-white border-b border-slate-200 h-16 fixed top-0 left-0 right-0 z-40 shadow-sm">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 h-full flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <button 
            @click="toggleSidebar" 
            type="button"
            class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 md:hidden"
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

        <!-- Notifikasi status tiket dan akun user -->
        <div class="flex h-full shrink-0 items-center gap-2">
        <div ref="ticketNotificationRef" class="relative flex h-10 shrink-0 items-center">
          <button
            type="button"
            @click="isTicketNotificationsOpen = !isTicketNotificationsOpen; isDropdownOpen = false"
            :aria-expanded="isTicketNotificationsOpen"
            aria-label="Notifikasi perubahan status tiket"
            title="Notifikasi perubahan status tiket"
            class="relative flex h-10 w-10 items-center justify-center rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4.5-5.8V4a1.5 1.5 0 0 0-3 0v1.2A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9" />
            </svg>
            <span v-if="ticketStatusNotifications.length" class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[9px] font-bold text-white">
              {{ ticketStatusNotifications.length > 99 ? '99+' : ticketStatusNotifications.length }}
            </span>
          </button>

          <Transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <div v-if="isTicketNotificationsOpen" class="absolute right-0 top-full z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
              <div class="border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-bold text-slate-800">Perubahan Status Tiket</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Klik notifikasi untuk melihat tiket.</p>
              </div>
              <div v-if="ticketStatusNotifications.length" class="max-h-80 overflow-y-auto p-2">
                <button
                  v-for="notification in ticketStatusNotifications"
                  :key="notification.id"
                  @click="openStatusNotification(notification)"
                  class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-indigo-50"
                >
                  <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="notification.statusId === 4 ? 'bg-emerald-500' : notification.statusId === 5 ? 'bg-rose-500' : 'bg-indigo-500'"></span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-xs font-semibold text-slate-800">{{ notification.ticketNumber }} · {{ notification.statusName }}</span>
                    <span class="mt-0.5 block truncate text-[11px] text-slate-500">{{ notification.title }}</span>
                  </span>
                  <span class="shrink-0 text-[10px] text-slate-400">{{ new Date(notification.changedAt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                </button>
              </div>
              <p v-else class="px-4 py-8 text-center text-xs text-slate-500">Belum ada perubahan status baru.</p>
            </div>
          </Transition>
        </div>

        <div class="relative flex h-10 shrink-0 items-center" ref="dropdownRef">
          <button 
            @click="isDropdownOpen = !isDropdownOpen; isTicketNotificationsOpen = false"
            type="button"
            class="flex h-10 items-center space-x-2 rounded-xl p-1 sm:space-x-3 hover:bg-slate-100 transition focus:outline-none"
          >
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-xs sm:text-sm shadow-sm">
              {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
            </div>
            
            <div class="text-left hidden sm:block">
              <p class="text-sm font-semibold text-slate-700 leading-none">{{ user?.name || 'Loading...' }}</p>
              <p class="text-xs text-indigo-600 font-medium mt-1 uppercase">{{ user?.role?.display_name || user?.role?.name || 'Guest' }}</p>
            </div>

            <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Submenu Dropdown -->
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
              class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50"
            >
              <div class="px-4 py-2 border-b border-slate-100">
                <p class="text-xs text-slate-400 font-medium">Masuk sebagai</p>
                <p class="text-sm font-bold text-slate-800 truncate">{{ user?.name }}</p>
                <p class="text-xs text-indigo-600 font-semibold uppercase sm:hidden mt-0.5">{{ user?.role?.display_name || user?.role?.name || 'Guest' }}</p>
                <p class="text-xs text-slate-500 truncate mt-0.5">{{ user?.email }}</p>
              </div>

              <!-- Tombol Pemicu Modal Profil -->
              <div class="py-1">
                <button 
                  type="button"
                  @click.stop="openProfileModal" 
                  class="w-full flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition text-left cursor-pointer"
                >
                  <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Profil Saya
                </button>
              </div>

              <div class="border-t border-slate-100 pt-1">
                <button 
                  @click="logout" 
                  type="button"
                  class="w-full flex items-center px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 font-medium transition text-left cursor-pointer"
                >
                  <svg class="w-4 h-4 mr-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Logout
                </button>
              </div>
            </div>
          </Transition>
        </div>
        </div>
      </div>
    </header>

    <!-- Overlay Mobile -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-20 md:hidden transition-opacity"
      aria-hidden="true"
    ></div>

    <!-- Main Content Area dengan Margin Kiri Kanan -->
    <div class="pt-20 pb-12 flex-1 w-full">
      <main class="max-w-6xl mx-auto px-4 sm:px-8">
        <slot />
      </main>
    </div>

    <!-- Panggilan Komponen Modal -->
    <ProfileModal v-model="isProfileModalOpen" />
  </div>
</template>