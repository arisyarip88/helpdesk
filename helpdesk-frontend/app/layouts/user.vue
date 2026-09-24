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

const { user, fetchUser, logout } = useAuth()

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

// Handler Buka Modal Profil
const openProfileModal = () => {
  isDropdownOpen.value = false
  isProfileModalOpen.value = true
}

watch(() => route.path, () => {
  if (process.client && window.innerWidth < 768) {
    isSidebarOpen.value = false
  }
})

onMounted(() => {
  fetchUser()
  document.addEventListener('click', handleClickOutside)
  
  if (window.innerWidth >= 768) {
    isSidebarOpen.value = true
  }
})

onUnmounted(() => {
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

        <!-- Area Akun User & Dropdown -->
        <div class="relative" ref="dropdownRef">
          <button 
            @click="isDropdownOpen = !isDropdownOpen"
            type="button"
            class="flex items-center space-x-2 sm:space-x-3 p-1.5 rounded-xl hover:bg-slate-100 transition focus:outline-none"
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