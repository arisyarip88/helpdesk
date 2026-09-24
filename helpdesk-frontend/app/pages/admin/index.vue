<script setup>
import { ref, onMounted } from 'vue'
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

// Fetch Data Summary
const fetchSummary = async () => {
  loadingSummary.value = true
  try {
    const res = await authFetch('/analytics/summary')
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
}


// Registrasi komponen dan plugin secara eksplisit
ChartJS.register(Title, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale, ChartDataLabels)

const config = useRuntimeConfig()
const apiBaseUrl = config.public.apiBase || 'http://localhost:8000/api'

const { token } = useAuth()

const departments = ref([{ kode: 'all', nama: 'Semua Departemen' }])
const selectedStatusDept = ref('all')
const selectedResolutionDept = ref('all')

const loadingStatus = ref(true)
const loadingDeptTickets = ref(true)
const loadingResolution = ref(true)

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
    const res = await authFetch('/analytics/priority_recent')
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
    const res = await authFetch('/analytics/department-tickets')
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
      params: { department_id: selectedStatusDept.value }
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
      params: { department_id: selectedResolutionDept.value }
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
  fetchPriorityAndRecent()
})

</script>

<template>
  <ClientOnly>
    <div class="p-6 space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen">

    <!-- Header Dashboard Utama & Tombol Action -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard Analitik Tiket Helpdesk</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Ringkasan data pengguna, departemen, dan statistik tiket aduan.
          </p>
        </div>

        <div class="flex items-center gap-2 no-print">
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

      <!-- Grid 4 Card Ringkasan Metric -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total User -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold tracking-wider text-slate-400 uppercase">Total User</span>
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
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
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
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
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
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 rounded-2xl shadow-sm flex items-center justify-between">
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
      <div class="bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 p-5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
          <Bar v-else-if="deptChartData" :data="deptChartData" :options="barOptions" />
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
            <Doughnut v-else-if="statusChartData" :data="statusChartData" :options="doughnutOptions" />
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
            <Bar v-else-if="resolutionChartData" :data="resolutionChartData" :options="barOptions" />
            <div v-else class="text-xs text-slate-400">Data tidak ditemukan</div>
          </div>
        </div>

      </div>

      <!-- Section Baru: Grid Prioritas & Tiket Terbaru -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 print:grid-cols-12">

        <!-- 1. Berdasarkan Prioritas (4 Kolom) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 rounded-2xl shadow-sm">
          <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">
            Berdasarkan Prioritas
          </h3>

          <div class="space-y-3">
            <!-- LOW -->
            <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
              <span class="text-xs font-bold text-slate-500 tracking-wider">LOW</span>
              <span class="text-sm font-bold text-slate-900 dark:text-white">
                {{ loadingPriorityRecent ? '...' : priorityData.low }}
              </span>
            </div>

            <!-- MEDIUM -->
            <div class="flex items-center justify-between p-3.5 bg-blue-50/50 dark:bg-blue-950/20 rounded-xl">
              <span class="text-xs font-bold text-blue-600 dark:text-blue-400 tracking-wider">MEDIUM</span>
              <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                {{ loadingPriorityRecent ? '...' : priorityData.medium }}
              </span>
            </div>

            <!-- HIGH -->
            <div class="flex items-center justify-between p-3.5 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl">
              <span class="text-xs font-bold text-amber-600 dark:text-amber-400 tracking-wider">HIGH</span>
              <span class="text-sm font-bold text-amber-600 dark:text-amber-400">
                {{ loadingPriorityRecent ? '...' : priorityData.high }}
              </span>
            </div>

            <!-- URGENT -->
            <div class="flex items-center justify-between p-3.5 bg-rose-50/50 dark:bg-rose-950/20 rounded-xl">
              <span class="text-xs font-bold text-rose-600 dark:text-rose-400 tracking-wider">URGENT</span>
              <span class="text-sm font-bold text-rose-600 dark:text-rose-400">
                {{ loadingPriorityRecent ? '...' : priorityData.urgent }}
              </span>
            </div>
          </div>
        </div>

        <!-- 2. Tiket Terbaru (8 Kolom) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col justify-between">
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
                    <tr v-for="(t, idx) in recentTickets" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
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