<script setup>
import { ref, computed } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'

// Ambil Token dari Cookie/Auth State
const { token } = useAuth()

// Helper function untuk Header
const getAuthHeaders = () => ({
  'Accept': 'application/json',
  'Authorization': `Bearer ${token.value}`
})

// State Query
const currentPage = ref(1)
const perPage = ref(10)
const searchQuery = ref('')

// Fetch Data dari Laravel
const { data: departmentsResponse, pending, error, refresh } = await useAsyncData(
  'departments',
  () => $fetch(`${apiBase}/departments`, {
    headers: getAuthHeaders(),
    params: {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    }
  }),
  {
    watch: [currentPage, perPage]
  }
)

// Debouncing Search
let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    refresh()
  }, 400)
}

// Computed Data & Pagination
const departments = computed(() => departmentsResponse.value?.data || [])
const pagination = computed(() => {
  const meta = departmentsResponse.value?.meta || departmentsResponse.value || {}
  return {
    currentPage: meta.current_page || 1,
    lastPage: meta.last_page || 1,
    total: meta.total || 0,
    from: meta.from || 0,
    to: meta.to || 0
  }
})

// State Modal & Form
const isModalOpen = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const formError = ref('')

const form = ref({
  kode: '',
  nama: '',
  ketua: '',
  deskripsi: ''
})

// Modal Handlers
const openCreateModal = () => {
  isEditing.value = false
  formError.value = ''
  form.value = { kode: '', nama: '', ketua: '', deskripsi: '' }
  isModalOpen.value = true
}

const openEditModal = (item) => {
  isEditing.value = true
  formError.value = ''
  form.value = {
    kode: item.kode || '',
    nama: item.nama || '',
    ketua: item.ketua || '',
    deskripsi: item.deskripsi || ''
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

// CRUD: Submit (Create & Update)
const handleSubmit = async () => {
  submitting.value = true
  formError.value = ''

  const payload = {
    kode: form.value.kode,
    nama: form.value.nama,
    ketua: form.value.ketua,
    deskripsi: form.value.deskripsi
  }

  try {
    if (isEditing.value) {
      // UPDATE DATA (Menggunakan kode sebagai URL parameter)
      await $fetch(`${apiBase}/departments/${form.value.kode}`, {
        method: 'PUT',
        headers: getAuthHeaders(),
        body: payload
      })
    } else {
      // CREATE DATA
      await $fetch(`${apiBase}/departments`, {
        method: 'POST',
        headers: getAuthHeaders(),
        body: payload
      })
    }

    await refresh()
    closeModal()
  } catch (err) {
    if (err.data?.errors) {
      const firstKey = Object.keys(err.data.errors)[0]
      formError.value = err.data.errors[firstKey][0]
    } else {
      formError.value = err.data?.message || 'Gagal menyimpan data departemen.'
    }
  } finally {
    submitting.value = false
  }
}

// CRUD: Delete (Menggunakan kode)
const handleDelete = async (kode) => {
  if (confirm(`Apakah Anda yakin ingin menghapus departemen ${kode}?`)) {
    try {
      await $fetch(`${apiBase}/departments/${kode}`, {
        method: 'DELETE',
        headers: getAuthHeaders()
      })
      await refresh()
    } catch (err) {
      alert(err.data?.message || 'Gagal menghapus departemen.')
    }
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen Departemen</h1>
        <p class="text-sm text-slate-500">Kelola daftar divisi dan ketua departemen perusahaan.</p>
      </div>
      <button 
        @click="openCreateModal"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl flex items-center gap-2 transition text-sm font-medium shadow-sm shadow-indigo-200"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Departemen
      </button>
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
          placeholder="Cari kode, nama departemen, atau ketua..." 
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

    <!-- Tabel Data -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
            <tr>
              <th class="px-6 py-4">No</th>
              <th class="px-6 py-4">Kode</th>
              <th class="px-6 py-4">Nama Departemen</th>
              <th class="px-6 py-4">Ketua Departemen</th>
              <th class="px-6 py-4">Deskripsi</th>
              <th class="px-6 py-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Loading State -->
            <tr v-if="pending">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                <div class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Memuat data departemen...</span>
                </div>
              </td>
            </tr>

            <!-- Error State -->
            <tr v-else-if="error">
              <td colspan="6" class="px-6 py-12 text-center text-rose-500">
                Gagal memuat data dari server API. (Periksa status login/token Anda)
              </td>
            </tr>

            <!-- Data State (key menggunakan dept.kode) -->
            <tr v-else v-for="(dept, index) in departments" :key="dept.kode" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-4 font-mono text-xs text-slate-400">
                {{ (pagination.currentPage - 1) * perPage + index + 1 }}
              </td>
              <td class="px-6 py-4 font-mono text-xs text-indigo-600 font-semibold">
                {{ dept.kode || '-' }}
              </td>
              <td class="px-6 py-4 font-medium text-slate-800">
                {{ dept.nama }}
              </td>
              <td class="px-6 py-4 text-slate-600">
                {{ dept.ketua || '-' }}
              </td>
              <td class="px-6 py-4 text-slate-500 max-w-xs truncate">
                {{ dept.deskripsi || '-' }}
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button 
                  @click="openEditModal(dept)" 
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition"
                >
                  Edit
                </button>
                <button 
                  @click="handleDelete(dept.kode)" 
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 transition"
                >
                  Hapus
                </button>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="!pending && departments.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                Departemen tidak ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="!pending && departments.length > 0" class="p-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
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

    <!-- Modal Form (Tambah / Edit) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-lg font-bold text-slate-800">
            {{ isEditing ? 'Edit Departemen' : 'Tambah Departemen' }}
          </h3>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Alert Error Form -->
        <div v-if="formError" class="p-3 bg-rose-50 border border-rose-200 text-rose-600 text-xs rounded-xl">
          {{ formError }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">
              Kode Departemen <span class="text-rose-500">*</span>
            </label>
            <input 
              v-model="form.kode" 
              type="text" 
              required
              :disabled="isEditing"
              placeholder="Contoh: DPT-001" 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Nama Departemen <span class="text-rose-500">*</span></label>
            <input 
              v-model="form.nama" 
              type="text" 
              required
              placeholder="Contoh: IT Support" 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Ketua Departemen</label>
            <input 
              v-model="form.ketua" 
              type="text" 
              placeholder="Contoh: Budi Santoso" 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi</label>
            <textarea 
              v-model="form.deskripsi" 
              rows="3" 
              placeholder="Penjelasan tugas departemen..." 
              class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
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