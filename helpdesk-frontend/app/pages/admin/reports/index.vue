<script setup>
definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

// Configuration & Composable
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()

// State Filter, Search, Pagination & Per Page Limit
const currentPage = ref(1)
const perPage = ref(10)
const exporting = ref(false)
const filters = ref({
  month: '',
  start_date: '',
  end_date: '',
  department_id: '',
  status: ''
})
const startDatePicker = ref(null)
const endDatePicker = ref(null)

const monthOptions = computed(() => Array.from({ length: 24 }, (_, index) => {
  const date = new Date()
  date.setDate(1)
  date.setMonth(date.getMonth() - index)

  return {
    value: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
    label: new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date)
  }
}))

const openDatePicker = (picker) => {
  picker?.showPicker?.()
}

const selectMonth = () => {
  if (filters.value.month) {
    filters.value.start_date = ''
    filters.value.end_date = ''
  }
}

const selectDateRange = () => {
  if (filters.value.start_date || filters.value.end_date) {
    filters.value.month = ''
  }
}

// Reset ke halaman 1 ketika perPage atau filter berubah
watch([perPage, filters], () => {
  currentPage.value = 1
}, { deep: true })

// Fetch Data Departemen
const { data: departments } = await useAsyncData(
  'report-departments',
  () => $fetch(`${apiBase}/reports/departments`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token.value}`
    }
  })
)

// Fetch Data Laporan Tiket
const { data: apiResponse, pending, refresh, error } = await useAsyncData(
  'report-tickets',
  () => $fetch(`${apiBase}/reports/tickets`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token.value}`
    },
    query: {
      page: currentPage.value,
      per_page: perPage.value,
      month: filters.value.month,
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
      department_id: filters.value.department_id,
      status: filters.value.status
    }
  }),
  {
    watch: [
      currentPage,
      perPage,
      () => filters.value.month,
      () => filters.value.start_date,
      () => filters.value.end_date,
      () => filters.value.department_id,
      () => filters.value.status
    ],
    getCachedData: () => undefined
  }
)

// Computed Properties
const tickets = computed(() => {
  if (!apiResponse.value) return []
  return apiResponse.value.data || apiResponse.value
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

// Reset Filter
const resetFilter = () => {
  filters.value = {
    month: '',
    start_date: '',
    end_date: '',
    department_id: '',
    status: ''
  }
}

// Handler Export (Excel / PDF)
const exportData = (type) => {
  exporting.value = true

  $fetch(`${apiBase}/reports/export/${type}`, {
    headers: {
      Accept: type === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      Authorization: `Bearer ${token.value}`
    },
    query: filters.value,
    responseType: 'blob'
  }).then((blob) => {
    const downloadUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = `laporan-tiket.${type === 'pdf' ? 'pdf' : 'xlsx'}`
    link.click()
    URL.revokeObjectURL(downloadUrl)
  }).finally(() => {
    exporting.value = false
  })
}

const getStatusKey = (status) => {
  const value = typeof status === 'object' ? status?.name : status
  return String(value || '').toLowerCase().replaceAll(' ', '_')
}

// Badge Utility Functions
const getStatusBadge = (status) => {
  const styles = {
    open: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-amber-100 text-amber-700',
    resolved: 'bg-emerald-100 text-emerald-700',
    closed: 'bg-slate-100 text-slate-600',
    rejected: 'bg-rose-100 text-rose-700'
  }
  return styles[status] || 'bg-slate-100 text-slate-600'
}

const getPriorityBadge = (priority) => {
  const styles = {
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-indigo-100 text-indigo-700',
    high: 'bg-amber-100 text-amber-700',
    urgent: 'bg-rose-100 text-rose-700 font-bold'
  }
  return styles[priority] || 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header Page & Export Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Laporan Tiket</h1>
        <p class="text-sm text-slate-500">Filter dan unduh rekapitulasi data penanganan tiket.</p>
      </div>

      <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
        <button
          type="button"
          @click="exportData('excel')"
          :disabled="exporting"
          class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export Excel
        </button>

        <button
          type="button"
          @click="exportData('pdf')"
          :disabled="exporting"
          class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          Export PDF
        </button>
      </div>
    </div>

    <!-- Filter Form Container -->
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Bulan -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Per Bulan</label>
          <select
            v-model="filters.month"
            @change="selectMonth"
            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/50 disabled:bg-slate-50 disabled:text-slate-400"
          >
            <option value="">Pilih bulan</option>
            <option v-for="month in monthOptions" :key="month.value" :value="month.value">
              {{ month.label }}
            </option>
          </select>
        </div>

        <!-- Tanggal Mulai -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Dari Tanggal</label>
          <div class="relative">
            <input
              ref="startDatePicker"
            v-model="filters.start_date"
            type="date"
            @change="selectDateRange"
              class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-xl text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/50 disabled:bg-slate-50 disabled:text-slate-400"
            />
            <button
              type="button"
              aria-label="Pilih tanggal mulai"
              @click="openDatePicker(startDatePicker)"
              class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-indigo-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Tanggal Selesai -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Sampai Tanggal</label>
          <div class="relative">
            <input
              ref="endDatePicker"
            v-model="filters.end_date"
            type="date"
            @change="selectDateRange"
              :min="filters.start_date || undefined"
              class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-xl text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/50 disabled:bg-slate-50 disabled:text-slate-400"
            />
            <button
              type="button"
              aria-label="Pilih tanggal selesai"
              @click="openDatePicker(endDatePicker)"
              class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-indigo-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 01-2 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Departemen -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Departemen</label>
          <select
            v-model="filters.department_id"
            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/50 cursor-pointer"
          >
            <option value="">Semua Departemen</option>
            <option v-for="dept in departments" :key="dept.kode" :value="dept.kode">
              {{ dept.nama }} ({{ dept.kode }})
            </option>
          </select>
        </div>

        <!-- Status Tiket -->
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Status Tiket</label>
          <select
            v-model="filters.status"
            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/50 cursor-pointer"
          >
            <option value="">Semua Status</option>
            <option value="1">Open</option>
            <option value="2">In Progress</option>
            <option value="3">Resolved</option>
            <option value="4">Closed</option>
            <option value="5">Rejected</option>
          </select>
        </div>
      </div>

      <!-- Action Filter Buttons & Per Page Select -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-3 border-t border-slate-100">
        <div class="flex items-center gap-2 text-xs text-slate-500 w-full sm:w-auto">
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

        <div class="flex justify-end gap-2 w-full sm:w-auto">
          <button
            type="button"
            @click="resetFilter"
            class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 bg-rose-50 text-rose-600 rounded-2xl text-sm font-semibold border border-rose-100">
      Gagal memuat data laporan dari server. Pastikan backend Anda aktif.
    </div>

    <!-- Loading State -->
    <div v-else-if="pending" class="p-12 text-center text-slate-400 text-sm">
      Memuat data Laporan Tiket...
    </div>

    <!-- Data Table & Pagination -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-100">
            <tr>
              <th class="px-5 py-3">No Tiket</th>
              <th class="px-5 py-3">Pelapor</th>
              <th class="px-5 py-3">Departemen</th>
              <th class="px-5 py-3">Judul</th>
              <th class="px-5 py-3">Prioritas</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Tgl Masuk</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-slate-50/50">
              <td class="px-5 py-3 font-semibold text-indigo-600">
                {{ ticket.nomor_tiket }}
              </td>
              <td class="px-5 py-3 font-medium text-slate-800">
                {{ ticket.user?.name || '-' }}
              </td>
              <td class="px-5 py-3 text-slate-600">
                {{ ticket.department?.nama || '-' }}
              </td>
              <td class="px-5 py-3 font-medium text-slate-700 max-w-xs truncate">
                {{ ticket.judul }}
              </td>
              <td class="px-5 py-3">
                <span :class="['px-2.5 py-1 rounded-md text-[10px] uppercase font-semibold', getPriorityBadge(ticket.prioritas)]">
                  {{ ticket.prioritas }}
                </span>
              </td>
              <td class="px-5 py-3">
                <span :class="['px-2.5 py-1 text-[10px] font-bold rounded-full uppercase', getStatusBadge(getStatusKey(ticket.status))]">
                  {{ ticket.status?.name || ticket.status || '-' }}
                </span>
              </td>
              <td class="px-5 py-3 text-xs text-slate-500">
                {{ new Date(ticket.created_at).toLocaleDateString('id-ID') }}
              </td>
            </tr>

            <tr v-if="!tickets || tickets.length === 0">
              <td colspan="7" class="px-5 py-8 text-center text-xs text-slate-400">
                Tidak ada tiket yang ditemukan berdasarkan filter.
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
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white cursor-pointer disabled:cursor-not-allowed transition"
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
            class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white cursor-pointer disabled:cursor-not-allowed transition"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>
  </div>
</template>