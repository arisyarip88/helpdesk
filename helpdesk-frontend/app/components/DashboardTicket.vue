<script setup>
import { ref, onMounted, nextTick } from 'vue'
import {
  Chart,
  DoughnutController,
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'

Chart.register(
  DoughnutController,
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
)

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()

const loading = ref(true)
const avgOverall = ref(0)

const statusOverallCanvas = ref(null)
const deptTicketCanvas = ref(null)
const statusDeptCanvas = ref(null)
const avgTimeCanvas = ref(null)

const fetchAndRenderCharts = async () => {
  try {
    const res = await $fetch(`${apiBase}/analytics`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token.value}`
      }
    })

    avgOverall.value = res.avg_time_overall_hours || 0
    loading.value = false

    await nextTick()

    // 1. Chart Sebaran Status Tiket Overall (Doughnut)
    if (statusOverallCanvas.value) {
      new Chart(statusOverallCanvas.value, {
        type: 'doughnut',
        data: {
          labels: res.status_overall.map((item) => item.status),
          datasets: [{
            data: res.status_overall.map((item) => item.total),
            backgroundColor: ['#6366F1', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#EC4899']
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

    // 2. Chart Sebaran Tiket per Departemen (Bar)
    if (deptTicketCanvas.value) {
      new Chart(deptTicketCanvas.value, {
        type: 'bar',
        data: {
          labels: res.ticket_per_department.map((item) => item.department),
          datasets: [{
            label: 'Jumlah Tiket',
            data: res.ticket_per_department.map((item) => item.total),
            backgroundColor: '#3B82F6',
            borderRadius: 6
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

    // 3. Chart Sebaran Status per Departemen (Stacked Bar)
    if (statusDeptCanvas.value) {
      const departments = [...new Set(res.status_per_department.map((d) => d.department))]
      const statuses = [...new Set(res.status_per_department.map((s) => s.status))]
      
      const colors = ['#6366F1', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#EC4899']
      const datasets = statuses.map((status, idx) => ({
        label: String(status),
        backgroundColor: colors[idx % colors.length],
        data: departments.map(dept => {
          const found = res.status_per_department.find((item) => item.department === dept && item.status === status)
          return found ? found.total : 0
        })
      }))

      new Chart(statusDeptCanvas.value, {
        type: 'bar',
        data: { labels: departments, datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { x: { stacked: true }, y: { stacked: true } }
        }
      })
    }

    // 4. Chart Rata-rata Waktu Penyelesaian per Departemen
    if (avgTimeCanvas.value) {
      new Chart(avgTimeCanvas.value, {
        type: 'bar',
        data: {
          labels: res.avg_time_per_department.map((item) => item.department),
          datasets: [{
            label: 'Waktu Penyelesaian (Jam)',
            data: res.avg_time_per_department.map((item) => item.avg_hours),
            backgroundColor: '#10B981',
            borderRadius: 6
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      })
    }

  } catch (error) {
    console.error('Gagal memuat visualisasi analitik:', error)
  }
}

onMounted(() => {
  fetchAndRenderCharts()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Indicator SLA Waktu Penyelesaian Keseluruhan -->
    <div class="bg-indigo-50/60 border border-indigo-100 p-4 rounded-2xl flex items-center justify-between">
      <div>
        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Performa Penyelesaian Tiket (SLA)</h3>
        <p class="text-xs text-slate-500 mt-0.5">Rata-rata durasi penyelesaian semua tiket secara keseluruhan.</p>
      </div>
      <div class="text-right">
        <span class="text-2xl font-extrabold text-indigo-900">{{ avgOverall }}</span>
        <span class="text-xs font-semibold text-indigo-600 ml-1">Jam</span>
      </div>
    </div>

    <!-- Grid Visualisasi Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Sebaran Status Tiket (Semua)</h3>
        <div class="relative h-64 w-full">
          <canvas ref="statusOverallCanvas"></canvas>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Sebaran Tiket per Departemen</h3>
        <div class="relative h-64 w-full">
          <canvas ref="deptTicketCanvas"></canvas>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Sebaran Status per Departemen</h3>
        <div class="relative h-64 w-full">
          <canvas ref="statusDeptCanvas"></canvas>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Rata-rata Waktu Penyelesaian per Departemen (Jam)</h3>
        <div class="relative h-64 w-full">
          <canvas ref="avgTimeCanvas"></canvas>
        </div>
      </div>
    </div>
  </div>
</template>