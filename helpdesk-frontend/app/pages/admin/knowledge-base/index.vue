<script setup>
definePageMeta({
  layout: 'admin',
  middleware: ['auth', 'role'],
  roles: ['1','2']
})

// Configuration & Composable
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()

// State Search, Pagination & Per Page Limit
const currentPage = ref(1)
const perPage = ref(10)
const searchQuery = ref('')
const debouncedSearch = ref('')

// Watcher reset halaman saat perPage berubah
watch(perPage, () => {
  currentPage.value = 1
})

// Debounce Search Input
let searchTimeout = null
const handleSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    debouncedSearch.value = searchQuery.value
    currentPage.value = 1
  }, 400)
}

// Reactive States Modal & Type
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

// Form State
const form = ref({
  id: null,
  question: '',
  keywordsInput: '',
  response_type: 'text', // 'text' atau 'options'
  answer: '',
  options: [], // [{ label: '', value: '', next_action: '' }]
  is_active: true
})

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

// Helper Opsi Jawaban
const addOption = () => {
  form.value.options.push({ label: '', value: '', next_action: '' })
}

const removeOption = (index) => {
  form.value.options.splice(index, 1)
}

// Fetch Data dengan Pagination & Search
const { data: apiResponse, pending, refresh, error } = await useAsyncData(
  'knowledge-bases',
  () => $fetch(`${apiBase}/knowledge-base`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token.value}`
    },
    query: {
      page: currentPage.value,
      per_page: perPage.value,
      search: debouncedSearch.value
    }
  }),
  {
    watch: [currentPage, perPage, debouncedSearch]
  }
)

// Computed Properties
const knowledgeList = computed(() => {
  if (!apiResponse.value) return []
  const rawData = apiResponse.value.data || apiResponse.value
  
  // Normalisasi data agar keywords dan options selalu berupa Array di UI
  return (Array.isArray(rawData) ? rawData : []).map(item => ({
    ...item,
    keywords: parseJsonSafe(item.keywords),
    options: parseJsonSafe(item.options)
  }))
})

const paginationMeta = computed(() => {
  if (!apiResponse.value) return { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 }
  return {
    current_page: apiResponse.value.current_page || 1,
    last_page: apiResponse.value.last_page || 1,
    from: apiResponse.value.from || 0,
    to: apiResponse.value.to || 0,
    total: apiResponse.value.total || 0
  }
})

// Handler Halaman
const changePage = (page) => {
  if (page >= 1 && page <= paginationMeta.value.last_page) {
    currentPage.value = page
  }
}

// Reset Form Function
const resetForm = () => {
  form.value = {
    id: null,
    question: '',
    keywordsInput: '',
    response_type: 'text',
    answer: '',
    options: [],
    is_active: true
  }
  errorMessage.value = ''
}

// Modal Actions
const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (item) => {
  errorMessage.value = ''

  const parsedOptions = parseJsonSafe(item.options)
  const parsedKeywords = parseJsonSafe(item.keywords)

  form.value = {
    id: item.id,
    question: item.question || '',
    keywordsInput: parsedKeywords.length ? parsedKeywords.join(', ') : (typeof item.keywords === 'string' ? item.keywords : ''),
    response_type: item.response_type || (parsedOptions.length > 0 ? 'options' : 'text'),
    answer: item.answer || '',
    options: parsedOptions.length > 0 ? parsedOptions : [],
    is_active: Boolean(item.is_active)
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  resetForm()
}

// Submit Handler
const handleSubmit = async () => {
  isSubmitting.value = true
  errorMessage.value = ''

  const keywordsArray = form.value.keywordsInput
    .split(',')
    .map(k => k.trim())
    .filter(k => k.length > 0)

  const payload = {
    question: form.value.question,
    keywords: keywordsArray,
    response_type: form.value.response_type,
    answer: form.value.answer,
    options: form.value.response_type === 'options' ? form.value.options : [],
    is_active: form.value.is_active
  }

  try {
    const isEdit = Boolean(form.value.id)
    const url = isEdit ? `${apiBase}/knowledge-base/${form.value.id}` : `${apiBase}/knowledge-base`
    const method = isEdit ? 'PUT' : 'POST'

    await $fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token.value}`
      },
      body: payload
    })

    closeModal()
    refresh()
  } catch (err) {
    errorMessage.value = err.data?.message || 'Gagal menyimpan data.'
  } finally {
    isSubmitting.value = false
  }
}

// Delete Handler
const handleDelete = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return

  try {
    await $fetch(`${apiBase}/knowledge-base/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token.value}`
      }
    })
    refresh()
  } catch (err) {
    alert('Gagal menghapus data')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Knowledge Base AI</h1>
        <p class="text-sm text-slate-500">Kelola kata kunci, jawaban teks, dan opsi pilihan respons chatbot.</p>
      </div>

      <button 
        type="button"
        @click="openCreateModal" 
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm shrink-0 self-start sm:self-auto"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Pengetahuan
      </button>
    </div>

    <!-- Toolbar Search & Per Page -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
      <div class="relative w-full sm:max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input 
          v-model="searchQuery"
          @input="handleSearchInput"
          type="text" 
          placeholder="Cari topik, kata kunci, atau jawaban..." 
          class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs outline-none focus:ring-2 focus:ring-indigo-500/50 text-slate-700 placeholder-slate-400"
        />
      </div>

      <div class="flex items-center gap-2 text-xs text-slate-500 self-end sm:self-auto shrink-0">
        <label for="per_page_select" class="font-medium">Tampilkan:</label>
        <select 
          id="per_page_select"
          v-model="perPage" 
          class="bg-white border border-slate-200 text-slate-700 font-medium py-1.5 px-3 pr-7 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/50 cursor-pointer"
        >
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 bg-rose-50 text-rose-600 rounded-2xl text-sm font-semibold border border-rose-100">
      Gagal memuat data dari server.
    </div>

    <!-- Loading State -->
    <div v-else-if="pending" class="p-12 text-center text-slate-400 text-sm">
      Memuat data Knowledge Base...
    </div>

    <!-- Data Table & Pagination -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
            <tr>
              <th class="px-5 py-3">Topik / Pertanyaan</th>
              <th class="px-5 py-3">Kata Kunci</th>
              <th class="px-5 py-3">Tipe & Jawaban AI</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in knowledgeList" :key="item.id" class="hover:bg-slate-50/50">
              <td class="px-5 py-3 font-semibold text-slate-800">{{ item.question }}</td>
              <td class="px-5 py-3">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="(kw, kIdx) in item.keywords" 
                    :key="kIdx" 
                    class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-semibold rounded-md border border-slate-200"
                  >
                    {{ kw }}
                  </span>
                </div>
              </td>
              <td class="px-5 py-3 text-xs text-slate-500 max-w-xs">
                <div class="mb-1">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                    :class="item.response_type === 'options' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'">
                    {{ item.response_type || 'text' }}
                  </span>
                </div>
                <div class="truncate font-medium text-slate-700">{{ item.answer }}</div>
                
                <!-- Preview Options di Tabel -->
                <div v-if="item.options && item.options.length" class="mt-2 flex flex-wrap gap-1">
                  <span 
                    v-for="(opt, idx) in item.options" 
                    :key="idx" 
                    class="px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-600 font-medium rounded-full text-[10px]"
                  >
                    🔘 {{ opt.label || opt.value || 'Opsi tanpa nama' }}
                  </span>
                </div>
              </td>
              <td class="px-5 py-3">
                <span 
                  class="px-2.5 py-1 text-[10px] font-bold rounded-full" 
                  :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                >
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="px-5 py-3 text-right space-x-3">
                <button 
                  type="button" 
                  @click="openEditModal(item)" 
                  class="text-xs font-semibold text-indigo-600 hover:underline cursor-pointer"
                >
                  Edit
                </button>
                <button 
                  type="button" 
                  @click="handleDelete(item.id)" 
                  class="text-xs font-semibold text-rose-600 hover:underline cursor-pointer"
                >
                  Hapus
                </button>
              </td>
            </tr>
            <tr v-if="!knowledgeList || knowledgeList.length === 0">
              <td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400">
                Tidak ada data yang ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="paginationMeta.total > 0" class="px-5 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-xs text-slate-500">
          Menampilkan <span class="font-semibold text-slate-700">{{ paginationMeta.from || 0 }}</span> - 
          <span class="font-semibold text-slate-700">{{ paginationMeta.to || 0 }}</span> dari 
          <span class="font-semibold text-slate-700">{{ paginationMeta.total }}</span> data
        </p>

        <div class="flex items-center gap-1">
          <button 
            @click="changePage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed transition"
          >
            Sebelumnya
          </button>

          <button 
            v-for="page in paginationMeta.last_page" 
            :key="page"
            @click="changePage(page)"
            class="w-8 h-8 text-xs font-bold rounded-lg transition cursor-pointer"
            :class="currentPage === page ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
          >
            {{ page }}
          </button>

          <button 
            @click="changePage(currentPage + 1)"
            :disabled="currentPage === paginationMeta.last_page"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed transition"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form (Tambah & Edit) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-slate-800">
          {{ form.id ? 'Edit Pengetahuan AI' : 'Tambah Pengetahuan AI Baru' }}
        </h3>

        <div v-if="errorMessage" class="p-3 bg-rose-50 text-rose-600 rounded-xl text-xs font-semibold border border-rose-100">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Topik / Pertanyaan</label>
            <input 
              v-model="form.question" 
              type="text" 
              required 
              placeholder="Contoh: Pengajuan Tiket Kendala" 
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" 
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Kata Kunci / Keywords (Pisahkan dengan koma)</label>
            <input 
              v-model="form.keywordsInput" 
              type="text" 
              required 
              placeholder="tiket, laporan, aduan" 
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" 
            />
          </div>

          <!-- Pilihan Tipe Respons Chatbot -->
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Tipe Respons Chatbot</label>
            <div class="grid grid-cols-2 gap-3">
              <label 
                class="flex items-center justify-center gap-2 p-2.5 border rounded-xl cursor-pointer text-xs font-semibold transition"
                :class="form.response_type === 'text' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="form.response_type" value="text" class="hidden" />
                <span>Teks Biasa</span>
              </label>

              <label 
                class="flex items-center justify-center gap-2 p-2.5 border rounded-xl cursor-pointer text-xs font-semibold transition"
                :class="form.response_type === 'options' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="form.response_type" value="options" class="hidden" />
                <span>Dengan Opsi Pilihan</span>
              </label>
            </div>
          </div>

          <!-- Input Pesan Utama / Jawaban AI -->
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">
              {{ form.response_type === 'options' ? 'Pesan Pengantar / Pertanyaan Bot' : 'Jawaban AI' }}
            </label>
            <textarea 
              v-model="form.answer" 
              rows="3" 
              required 
              :placeholder="form.response_type === 'options' ? 'Silakan pilih menu bantuan di bawah ini:' : 'Silakan klik menu tiket pada dashboard Anda...'" 
              class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
            ></textarea>
          </div>

          <!-- Dynamic Form: Opsi Pilihan (Multiple Choices) -->
          <div v-if="form.response_type === 'options'" class="space-y-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-slate-700">Opsi / Tombol Pilihan</label>
              <button 
                type="button" 
                @click="addOption" 
                class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 cursor-pointer"
              >
                + Tambah Opsi
              </button>
            </div>

            <div v-if="form.options.length === 0" class="text-center py-3 text-xs text-slate-400">
              Belum ada opsi ditambahkan. Klik "+ Tambah Opsi"
            </div>

            <div v-for="(opt, index) in form.options" :key="index" class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200">
              <input 
                v-model="opt.label" 
                type="text" 
                placeholder="Label (ex: Cek Status)" 
                class="w-1/2 px-2 py-1 border border-slate-200 rounded text-xs outline-none focus:ring-1 focus:ring-indigo-500" 
                required
              />
              <input 
                v-model="opt.value" 
                type="text" 
                placeholder="Value / Kata Kunci Respon" 
                class="w-1/2 px-2 py-1 border border-slate-200 rounded text-xs outline-none focus:ring-1 focus:ring-indigo-500" 
                required
              />
              <button 
                type="button" 
                @click="removeOption(index)" 
                class="text-rose-500 hover:text-rose-700 p-1 cursor-pointer font-bold"
                title="Hapus Opsi"
              >
                ✕
              </button>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input 
              id="is_active_checkbox" 
              v-model="form.is_active" 
              type="checkbox" 
              class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4" 
            />
            <label for="is_active_checkbox" class="text-xs font-semibold text-slate-700">
              Aktifkan pengetahuan ini pada bot
            </label>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button 
              type="button" 
              @click="closeModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="isSubmitting" 
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-2"
            >
              <span v-if="isSubmitting">Menyimpan...</span>
              <span v-else>Simpan Data</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>