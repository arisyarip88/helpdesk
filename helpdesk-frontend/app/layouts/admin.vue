<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const route = useRoute()
const isSidebarOpen = ref(false) // Default tertutup pada layar mobile

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

// State untuk dropdown menu profil
const isDropdownOpen = ref(false)
const dropdownRef = ref(null)

const { user, fetchUser, logout, hasRole } = useAuth()

// Menutup dropdown jika klik dilakukan di luar area menu
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

// Otomatis menutup sidebar di mobile saat pengguna berpindah halaman
watch(() => route.path, () => {
  if (process.client && window.innerWidth < 768) {
    isSidebarOpen.value = false
  }
})

onMounted(() => {
  fetchUser()
  document.addEventListener('click', handleClickOutside)
  
  // Buka sidebar secara default khusus tampilan Desktop
  if (window.innerWidth >= 768) {
    isSidebarOpen.value = true
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col font-sans">
    
    <!-- Header Navbar -->
    <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 fixed top-0 left-0 right-0 z-40">
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
    </header>

    <!-- Overlay Latar Belakang Gelap (Mobile Backdrop) -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-black/50 z-20 md:hidden transition-opacity"
      aria-hidden="true"
    ></div>

    <!-- Content & Sidebar Wrapper -->
    <div class="flex pt-16 min-h-screen">
      
      <!-- Sidebar Navigation -->
      <aside 
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
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Department</span>
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

          <!-- 5. Knowledge Base -->
          <NuxtLink v-if="hasRole(['1','2'])"
            to="/admin/knowledge-base" 
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-gray-800 hover:text-white transition-colors"
            active-class="bg-blue-600 text-white hover:bg-blue-600"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span :class="{'md:hidden': !isSidebarOpen}" class="whitespace-nowrap font-medium text-sm">Knowledge Base</span>
          </NuxtLink>

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
      <main class="flex-1 p-4 sm:p-6 overflow-y-auto w-full">
        <slot />
      </main>

    </div>
  </div>
</template>