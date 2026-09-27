<script setup>
import { ref, onMounted, watch } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'
import ChartDataLabels from 'chartjs-plugin-datalabels'

definePageMeta({
  layout: 'admin',
  middleware: 'auth',
})


// State Summary Metric
const summaryData = ref({
  total_user: 0,
  total_departemen: 0,
  total_tiket_masuk: 0,
  tiket_selesai: 0,
  sla_avg_hours: 0
})
const loadingSummary = ref(true)
const selectedMonth = ref('')
const selectedYear = ref('')
const monthOptions = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' }, { value: 4, label: 'April' },
  { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' }, { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' }, { value: 12, label: 'Desember' }
]
const yearOptions = Array.from({ length: 7 }, (_, index) => new Date().getFullYear() - index)
const periodParams = () => ({
  ...(selectedMonth.value ? { month: selectedMonth.value } : {}),
  ...(selectedYear.value ? { year: selectedYear.value } : {})
})
const resetPeriod = () => {
  selectedMonth.value = ''
  selectedYear.value = ''
}

// Fetch Data Summary
const fetchSummary = async () => {
  loadingSummary.value = true
  try {
    const res = await authFetch('/analytics/summary', { params: periodParams() })
    if (res?.data) {
      summaryData.value = res.data
    }
  } catch (e) {
    console.error('Gagal mengambil ringkasan data:', e)
  } finally {
    loadingSummary.value = false
  }
}

// Fungsi Muat Ulang Keseluruhan Dashboard
const handleReload = () => {
  fetchSummary()
  fetchDepartments()
  fetchDeptTickets()
  fetchStatusStats()
  fetchResolutionTime()
  fetchPriorityAndRecent()
  fetchRatingRanking()
}


// Registrasi komponen dan plugin secara eksplisit
ChartJS.register(Title, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale, ChartDataLabels)

const config = useRuntimeConfig()
const apiBaseUrl = config.public.apiBase || 'http://localhost:8000/api'
const router = useRouter()

const { token } = useAuth()

const departments = ref([{ kode: 'all', nama: 'Semua Departemen' }])
const selectedStatusDept = ref('all')
const selectedResolutionDept = ref('all')
const departmentFilterCodes = ref([])
const resolutionDepartmentCodes = ref([])
const statusFilterIds = ref([])

const openTicketManagement = (query = {}) => router.push({
  path: '/admin/tickets',
  query
})

const loadingStatus = ref(true)
const loadingDeptTickets = ref(true)
const loadingResolution = ref(true)
const loadingRatingRanking = ref(true)
const ratingRanking = ref({ departments: [], users: [] })

const fetchRatingRanking = async () => {
  loadingRatingRanking.value = true
  try {
    const res = await authFetch('/analytics/rating-ranking', { params: periodParams() })
    if (res?.data) ratingRanking.value = res.data
  } catch (e) {
    console.error('Gagal mengambil peringkat rating:', e)
  } finally {
    loadingRatingRanking.value = false
  }
}

// Palette warna variatif untuk tiap batang
const colorPalette = [
  '#F97316','#6366F1', '#3B82F6', '#10B981', '#F59E0B', 
  '#EC4899', '#8B5CF6', '#14B8A6'
]

// Helper fetch dengan token Authorization
const authFetch = (url, options = {}) => {
  return $fetch(`${apiBaseUrl}${url}`, {
    ...options,
    query: {
      ...(options.query || {}),
      _refresh: Date.now()
    },
    headers: {
      ...options?.headers,
      Authorization: `Bearer ${token.value}`
    }
  })
}

// Opsi Chart Bar (dengan angka di atas batang)
const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    datalabels: {
      anchor: 'end',
      align: 'top',
      color: '#64748b',
      font: { weight: 'bold', size: 11 },
      formatter: (val) => (val > 0 ? val : '')
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grace: '15%'
    }
  }
}

const createClickableBarOptions = (handleIndex) => ({
  ...barOptions,
  onClick: (_event, elements) => {
    const index = elements[0]?.index
    if (index !== undefined) handleIndex(index)
  },
  onHover: (event, elements) => {
    if (event.native?.target) {
      event.native.target.style.cursor = elements.length ? 'pointer' : 'default'
    }
  }
})

const departmentBarOptions = createClickableBarOptions((index) => {
  const departmentId = departmentFilterCodes.value[index]
  if (departmentId) openTicketManagement({ department_id: departmentId })
})

const resolutionBarOptions = createClickableBarOptions((index) => {
  const departmentId = resolutionDepartmentCodes.value[index]
  if (departmentId) openTicketManagement({ department_id: departmentId, status_id: 4 })
})

// Opsi Chart Doughnut (dengan angka di dalam lingkaran)
const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
    datalabels: {
      color: '#ffffff',
      font: { weight: 'bold', size: 12 },
      formatter: (val) => (val > 0 ? val : '')
    }
  }
}

const clickableDoughnutOptions = {
  ...doughnutOptions,
  onClick: (_event, elements) => {
    const statusId = statusFilterIds.value[elements[0]?.index]
    if (statusId) {
      openTicketManagement({
        status_id: statusId,
        department_id: selectedStatusDept.value !== 'all' ? selectedStatusDept.value : undefined
      })
    }
  },
  onHover: (event, elements) => {
    if (event.native?.target) {
      event.native.target.style.cursor = elements.length ? 'pointer' : 'default'
    }
  }
}

// Master Data Departemen
const fetchDepartments = async () => {
  try {
    const res = await authFetch('/analytics/departments')
    if (res?.data) {
      departments.value = [{ kode: 'all', nama: 'Semua Departemen' }, ...res.data]
    }
  } catch (e) {
    console.error('Gagal mengambil daftar departemen:', e)
  }
}

// State Prioritas & Tiket Terbaru
const priorityData = ref({ low: 0, medium: 0, high: 0, urgent: 0 })
const recentTickets = ref([])
const loadingPriorityRecent = ref(true)

// Fetch Data Prioritas & Tiket Terbaru
const fetchPriorityAndRecent = async () => {
  loadingPriorityRecent.value = true
  try {
    const res = await authFetch('/analytics/priority_recent', { params: periodParams() })
    if (res?.data) {
      priorityData.value = res.data.priority
      recentTickets.value = res.data.recent_tickets
    }
  } catch (e) {
    console.error('Gagal mengambil data prioritas & tiket terbaru:', e)
  } finally {
    loadingPriorityRecent.value = false
  }
}

// 1. Chart Tiket Per Departemen (Warna Berbeda-beda)
const deptChartData = ref(null)
const fetchDeptTickets = async () => {
  loadingDeptTickets.value = true
  try {
    const res = await authFetch('/analytics/department-tickets', { params: periodParams() })
    if (res?.data) {
      deptChartData.value = {
        labels: res.data.map(i => i.department_name),
        datasets: [{
          label: 'Total Tiket',
          data: res.data.map(i => Number(i.total)),
          backgroundColor: res.data.map((_, idx) => colorPalette[idx % colorPalette.length]),
          borderRadius: 6
        }]
      }
      departmentFilterCodes.value = res.data.map(item => item.department_code)
    }
  } catch (e) {
    console.error('Gagal mengambil data tiket departemen:', e)
  } finally {
    loadingDeptTickets.value = false
  }
}

/// Mapping warna tetap berdasarkan nama status
const statusColorMap = {
  'Resolve': '#3B82F6',         // Biru
  'In Progress': '#F59E0B', // Kuning/Amber
  'Close': '#10B981',     // Hijau
  'New': '#EF4444',       // Merah
  'Reject': '#64748B'       // Abu-abu
}

// 2. Chart Status Tiket (Warna Terikat Nama Status)
const statusChartData = ref(null)
const fetchStatusStats = async () => {
  loadingStatus.value = true
  try {
    const res = await authFetch('/analytics/ticket-status', {
      params: { department_id: selectedStatusDept.value, ...periodParams() }
    })
    if (res?.data) {
      statusChartData.value = {
        labels: res.data.map(i => i.status_name),
        datasets: [{
          data: res.data.map(i => Number(i.total)),
          // Memetakan warna sesuai nama status, dengan fallback abu-abu jika nama tidak cocok
          backgroundColor: res.data.map(i => statusColorMap[i.status_name] || '#64748B')
        }]
      }
      statusFilterIds.value = res.data.map(item => Number(item.status_id))
    }
  } catch (e) {
    console.error('Gagal mengambil data status:', e)
  } finally {
    loadingStatus.value = false
  }
}
// Chart Rata-Rata Waktu Penyelesaian (Warna Unik per Departemen)
const resolutionChartData = ref(null)
const fetchResolutionTime = async () => {
  loadingResolution.value = true
  try {
    const res = await authFetch('/analytics/resolution-time', {
      params: { department_id: selectedResolutionDept.value, ...periodParams() }
    })
    if (res?.data) {
      resolutionChartData.value = {
        labels: res.data.map(i => i.department_name),
        datasets: [{
          label: 'Rata-Rata (Jam)',
          data: res.data.map(i => Number(i.avg_hours)),
          // Memakai palet warna yang sama sesuai urutan departemen
          backgroundColor: res.data.map((_, idx) => colorPalette[idx % colorPalette.length]),
          borderRadius: 6
        }]
      }
      resolutionDepartmentCodes.value = res.data.map(item => item.department_code)
    }
  } catch (e) {
    console.error('Gagal mengambil data penyelesaian:', e)
  } finally {
    loadingResolution.value = false
  }
}
onMounted(() => {
  fetchSummary()
  fetchDepartments()
  fetchDeptTickets()
  fetchStatusStats()
  fetchResolutionTime()
  fetchRatingRanking()
  fetchPriorityAndRecent()
})

watch([selectedMonth, selectedYear], () => {
  fetchSummary()
  fetchDeptTickets()
  fetchStatusStats()
  fetchResolutionTime()
  fetchPriorityAndRecent()
  fetchRatingRanking()
})

const printDashboard = () => {
  const periodMonth = selectedMonth.value
    ? monthOptions[Number(selectedMonth.value) - 1].label
    : ''
  const periodYear = selectedYear.value || ''
  const periodLabel = [periodMonth, periodYear].filter(Boolean).join(' ') || 'Semua Periode'
  const originalTitle = document.title

  document.title = `Laporan ${periodLabel}`
  document.body.classList.add('dashboard-print-mode')
  window.addEventListener('afterprint', () => {
    document.body.classList.remove('dashboard-print-mode')
    document.title = originalTitle
  }, { once: true })
  window.print()
}

</script>

<template>
  <ClientOnly>
    <div id="dashboard-report" class="p-6 space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen">

    <!-- Header Dashboard Utama & Tombol Action -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard Analitik Tiket Helpdesk</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Ringkasan data pengguna, departemen, dan statistik tiket aduan.
          </p>
          <p class="mt-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
            Periode laporan: {{ selectedMonth ? monthOptions[Number(selectedMonth) - 1].label : 'Semua bulan' }} · {{ selectedYear || 'Semua tahun' }}
          </p>
        </div>

        <div class="flex items-center gap-2 dashboard-no-print">
          <button
            @click="printDashboard"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z" />
            </svg>
            Cetak PDF A4
          </button>
          <!-- Tombol Muat Ulang -->
          <button 
            @click="handleReload"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl text-xs font-semibold shadow-sm transition-all cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Muat Ulang
          </button>

        </div>
      </div>

      <div class="dashboard-no-print flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900">
        <div>
          <p class="text-xs font-bold uppercase text-slate-700 dark:text-slate-200">Filter Periode</p>
          <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Statistik tiket memakai tanggal dibuat; User Terdaftar memakai tanggal pendaftaran akun.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
          <div>
            <label for="dashboard-month" class="mb-1 block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Bulan</label>
            <select id="dashboard-month" v-model="selectedMonth" class="w-full min-w-40 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
              <option value="">Semua bulan</option>
              <option v-for="month in monthOptions" :key="month.value" :value="month.value">{{ month.label }}</option>
            </select>
          </div>
          <div>
            <label for="dashboard-year" class="mb-1 block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Tahun</label>
            <select id="dashboard-year" v-model="selectedYear" class="w-full min-w-32 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
              <option value="">Semua tahun</option>
              <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <button v-if="selectedMonth || selectedYear" type="button" @click="resetPeriod" class="rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50">Reset filter</button>
        </div>
      </div>

      <!-- Grid 4 Card Ringkasan Metric -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total User -->
        <div role="link" tabindex="0" @click="router.push('/admin/users')" @keydown.enter="router.push('/admin/users')" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between cursor-pointer transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <div>
            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">User Terdaftar</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
              {{ loadingSummary ? '...' : summaryData.total_user }}
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>

        <!-- Card 2: Total Departemen -->
        <div role="link" tabindex="0" @click="router.push('/admin/departments')" @keydown.enter="router.push('/admin/departments')" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between cursor-pointer transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500">
          <div>
            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Total Departemen</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
              {{ loadingSummary ? '...' : summaryData.total_departemen }}
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4M9 11h4m-4 0V7m0 0h4" />
            </svg>
          </div>
        </div>

        <!-- Card 3: Total Tiket Masuk -->
        <div role="link" tabindex="0" @click="openTicketManagement()" @keydown.enter="openTicketManagement()" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between cursor-pointer transition hover:border-violet-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-violet-500">
          <div>
            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Total Tiket Masuk</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
              {{ loadingSummary ? '...' : summaryData.total_tiket_masuk }}
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
          </div>
        </div>

        <!-- Card 4: Tiket Selesai -->
        <div role="link" tabindex="0" @click="openTicketManagement({ status_id: 4 })" @keydown.enter="openTicketManagement({ status_id: 4 })" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between cursor-pointer transition hover:border-emerald-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <div>
            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Tiket Selesai</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
              {{ loadingSummary ? '...' : summaryData.tiket_selesai }}
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

      </div>

      <!-- Banner Performa Penyelesaian Tiket (SLA) -->
      <div role="link" tabindex="0" @click="openTicketManagement({ status_id: 4 })" @keydown.enter="openTicketManagement({ status_id: 4 })" class="bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 cursor-pointer transition hover:border-indigo-300 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <div>
          <h4 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 tracking-wider uppercase">
            Performa Penyelesaian Tiket (SLA)
          </h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Rata-rata durasi penyelesaian semua tiket secara keseluruhan.
          </p>
        </div>
        <div class="text-2xl font-bold text-slate-900 dark:text-white flex items-baseline gap-1">
          <span>{{ loadingSummary ? '...' : summaryData.sla_avg_hours }}</span>
          <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Jam</span>
        </div>
      </div>

      <!-- Single Chart: Jumlah Tiket per Departemen -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
        <div class="mb-4">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Jumlah Tiket per Departemen</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Total akumulasi tiket yang diterima oleh tiap departemen</p>
        </div>

        <div class="h-64 relative flex items-center justify-center">
          <div v-if="loadingDeptTickets" class="text-xs text-slate-400 animate-pulse">Memuat grafik...</div>
          <Bar v-else-if="deptChartData" :data="deptChartData" :options="departmentBarOptions" />
          <div v-else class="text-xs text-slate-400">Data tidak ditemukan</div>
        </div>
      </div>

      <!-- Grid 2 Kolom -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Chart Status Tiket -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="flex items-center justify-between gap-4 mb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">Status Tiket</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Distribusi status tiket saat ini</p>
            </div>
            <select 
              v-model="selectedStatusDept" 
              @change="fetchStatusStats"
              class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-200 outline-none cursor-pointer"
            >
              <option v-for="d in departments" :key="d.kode" :value="d.kode">{{ d.nama }}</option>
            </select>
          </div>

          <div class="h-64 relative flex items-center justify-center">
            <div v-if="loadingStatus" class="text-xs text-slate-400 animate-pulse">Memuat grafik...</div>
            <Doughnut v-else-if="statusChartData" :data="statusChartData" :options="clickableDoughnutOptions" />
            <div v-else class="text-xs text-slate-400">Data tidak ditemukan</div>
          </div>
        </div>

        <!-- Chart Waktu Penyelesaian -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <div class="flex items-center justify-between gap-4 mb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">Waktu Penyelesaian Rata-Rata</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Durasi penanganan tiket selesai (dalam jam)</p>
            </div>
            <select 
              v-model="selectedResolutionDept" 
              @change="fetchResolutionTime"
              class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-200 outline-none cursor-pointer"
            >
              <option v-for="d in departments" :key="d.kode" :value="d.kode">{{ d.nama }}</option>
            </select>
          </div>

          <div class="h-64 relative flex items-center justify-center">
            <div v-if="loadingResolution" class="text-xs text-slate-400 animate-pulse">Memuat grafik...</div>
            <Bar v-else-if="resolutionChartData" :data="resolutionChartData" :options="resolutionBarOptions" />
            <div v-else class="text-xs text-slate-400">Data tidak ditemukan</div>
          </div>
        </div>

      </div>

      <!-- Peringkat Rating Pelayanan -->
      <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Peringkat Rating Departemen</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rata-rata rating layanan pada tiket Close</p>
          </header>
          <div v-if="loadingRatingRanking" class="p-8 text-center text-xs text-slate-400">Memuat peringkat...</div>
          <ol v-else-if="ratingRanking.departments.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="(item, index) in ratingRanking.departments" :key="item.department_code" class="flex items-center justify-between gap-4 px-5 py-3">
              <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ index + 1 }}</span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.department_name }}</p>
                  <p class="text-[11px] text-slate-500">{{ item.rating_count }} tiket dinilai</p>
                </div>
              </div>
              <div class="shrink-0 text-right" :aria-label="`Rating rata-rata ${Number(item.average_rating).toFixed(1)} dari 5`">
                <p class="text-xs text-amber-500" aria-hidden="true">★★★★★</p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ Number(item.average_rating).toFixed(1) }}<span class="text-xs font-medium text-slate-400"> / 5</span></p>
              </div>
            </li>
          </ol>
          <p v-else class="p-8 text-center text-xs text-slate-400">Belum ada rating pada tiket Close.</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Departemen dengan Rata-rata Penyelesaian Tercepat</h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Durasi rata-rata tiket Close, diurutkan dari paling cepat</p>
          </header>
          <div v-if="loadingRatingRanking" class="p-8 text-center text-xs text-slate-400">Memuat peringkat...</div>
          <ol v-else-if="ratingRanking.fastest_departments?.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="(item, index) in ratingRanking.fastest_departments" :key="item.department_code" class="flex items-center justify-between gap-4 px-5 py-3">
              <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ index + 1 }}</span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.department_name }}</p>
                  <p class="truncate text-[11px] text-slate-500">{{ item.completed_ticket_count }} tiket selesai</p>
                </div>
              </div>
              <div class="shrink-0 text-right" :aria-label="`Rata-rata ${Number(item.average_hours).toFixed(1)} jam`">
                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ Number(item.average_hours).toFixed(1) }} jam</p>
                <p class="text-[11px] text-slate-400">rata-rata</p>
              </div>
            </li>
          </ol>
          <p v-else class="p-8 text-center text-xs text-slate-400">Belum ada tiket Close pada periode ini.</p>
        </div>
      </section>

      <!-- Section Baru: Grid Prioritas & Tiket Terbaru -->
      <div class="ticket-priority-recent-grid grid grid-cols-1 lg:grid-cols-12 gap-6 print:grid-cols-12">

        <!-- 1. Berdasarkan Prioritas (4 Kolom) -->
        <div class="ticket-priority-card lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">
            Berdasarkan Prioritas
          </h3>

          <div class="space-y-3">
            <!-- LOW -->
            <div role="link" tabindex="0" @click="openTicketManagement({ prioritas: 'low' })" @keydown.enter="openTicketManagement({ prioritas: 'low' })" class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl cursor-pointer transition hover:ring-2 hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400">
              <span class="text-xs font-bold text-slate-500 tracking-wider">LOW</span>
              <span class="text-sm font-bold text-slate-900 dark:text-white">
                {{ loadingPriorityRecent ? '...' : priorityData.low }}
              </span>
            </div>

            <!-- MEDIUM -->
            <div role="link" tabindex="0" @click="openTicketManagement({ prioritas: 'medium' })" @keydown.enter="openTicketManagement({ prioritas: 'medium' })" class="flex items-center justify-between p-3.5 bg-blue-50/50 dark:bg-blue-950/20 rounded-xl cursor-pointer transition hover:ring-2 hover:ring-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-400">
              <span class="text-xs font-bold text-blue-600 dark:text-blue-400 tracking-wider">MEDIUM</span>
              <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                {{ loadingPriorityRecent ? '...' : priorityData.medium }}
              </span>
            </div>

            <!-- HIGH -->
            <div role="link" tabindex="0" @click="openTicketManagement({ prioritas: 'high' })" @keydown.enter="openTicketManagement({ prioritas: 'high' })" class="flex items-center justify-between p-3.5 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl cursor-pointer transition hover:ring-2 hover:ring-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400">
              <span class="text-xs font-bold text-amber-600 dark:text-amber-400 tracking-wider">HIGH</span>
              <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                {{ loadingPriorityRecent ? '...' : priorityData.high }}
              </span>
            </div>

            <!-- URGENT -->
            <div role="link" tabindex="0" @click="openTicketManagement({ prioritas: 'urgent' })" @keydown.enter="openTicketManagement({ prioritas: 'urgent' })" class="flex items-center justify-between p-3.5 bg-rose-50/50 dark:bg-rose-950/20 rounded-xl cursor-pointer transition hover:ring-2 hover:ring-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-400">
              <span class="text-xs font-bold text-rose-600 dark:text-rose-400 tracking-wider">URGENT</span>
              <span class="text-sm font-bold text-rose-600 dark:text-rose-400">
                {{ loadingPriorityRecent ? '...' : priorityData.urgent }}
              </span>
            </div>
          </div>
        </div>

        <!-- 2. Tiket Terbaru (8 Kolom) -->
        <div class="dashboard-no-print lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
          <div>
            <!-- Card Header -->
            <div class="p-6 pb-4 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                Tiket Terbaru
              </h3>
              <NuxtLink 
                to="/admin/tickets" 
                class="no-print text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 transition-colors flex items-center gap-1"
              >
                Lihat Semua &rarr;
              </NuxtLink>
            </div>

            <!-- Tabel Tiket -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 border-b border-slate-100 dark:border-slate-800">
                  <tr>
                    <th class="py-3 px-6 font-semibold">No. Tiket</th>
                    <th class="py-3 px-6 font-semibold">Judul</th>
                    <th class="py-3 px-6 font-semibold">Departemen</th>
                    <th class="py-3 px-6 font-semibold">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <template v-if="loadingPriorityRecent">
                    <tr>
                      <td colspan="4" class="py-12 text-center text-slate-400 animate-pulse">
                        Memuat data tiket...
                      </td>
                    </tr>
                  </template>

                  <template v-else-if="recentTickets && recentTickets.length > 0">
                    <tr v-for="(t, idx) in recentTickets" :key="idx" role="link" tabindex="0" @click="openTicketManagement({ search: t.no_tiket })" @keydown.enter="openTicketManagement({ search: t.no_tiket })" class="hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors cursor-pointer focus:outline-none focus:bg-indigo-50/70">
                      <td class="py-3.5 px-6 font-semibold text-indigo-600 dark:text-indigo-400">{{ t.no_tiket }}</td>
                      <td class="py-3.5 px-6 font-medium text-slate-800 dark:text-slate-200 truncate max-w-[200px]">{{ t.judul }}</td>
                      <td class="py-3.5 px-6 text-slate-600 dark:text-slate-400">{{ t.departemen || '-' }}</td>
                      <td class="py-3.5 px-6">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                          {{ t.status }}
                        </span>
                      </td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr>
                      <td colspan="4" class="py-16 text-center text-slate-400">
                        Belum ada tiket aduan.
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </ClientOnly>
</template>

<style>
@page {
  size: A4 landscape;
  margin: 14mm 16mm;
}

@media print {
  body.dashboard-print-mode .admin-print-hide {
    display: none !important;
  }

  body.dashboard-print-mode .admin-layout-shell,
  body.dashboard-print-mode .admin-layout-content,
  body.dashboard-print-mode .admin-print-main {
    display: block !important;
    width: 100% !important;
    min-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
  }

  .dashboard-no-print {
    display: none !important;
  }

  body.dashboard-print-mode #dashboard-report {
    position: static !important;
    box-sizing: border-box !important;
    width: 100% !important;
    min-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #fff !important;
    color: #0f172a !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }

  body.dashboard-print-mode .ticket-priority-recent-grid {
    display: block !important;
  }

  body.dashboard-print-mode .ticket-priority-card {
    width: 100% !important;
    margin: 0 !important;
  }

  #dashboard-report > * {
    break-inside: avoid-page;
  }

  #dashboard-report canvas {
    max-height: 55mm !important;
  }
}
</style>