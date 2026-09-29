<script setup>
definePageMeta({ layout: 'admin', middleware: 'auth' })

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()
const filters = ref({ month: '', year: '', department_id: '' })
const exporting = ref(false)
const loading = ref(false)
const error = ref('')
const departments = ref([])
const summary = ref({})
const rankings = ref({ departments: [], users: [], fastest_departments: [] })
const categoryFrequency = ref([])

const headers = computed(() => ({ Accept: 'application/json', Authorization: `Bearer ${token.value}` }))
const years = computed(() => Array.from({ length: 6 }, (_, index) => new Date().getFullYear() - index))
const query = computed(() => ({
  month: filters.value.month || undefined,
  year: filters.value.year || undefined,
  department_id: filters.value.department_id || undefined
}))

const loadReport = async () => {
  loading.value = true
  error.value = ''
  try {
    const [departmentResponse, summaryResponse, rankingResponse, categoryResponse] = await Promise.all([
      $fetch(`${apiBase}/analytics/departments`, { headers: headers.value, query: query.value }),
      $fetch(`${apiBase}/analytics/summary`, { headers: headers.value, query: query.value }),
      $fetch(`${apiBase}/analytics/rating-ranking`, { headers: headers.value, query: query.value }),
      $fetch(`${apiBase}/analytics/category-frequency`, { headers: headers.value, query: query.value })
    ])
    departments.value = departmentResponse.data || []
    summary.value = summaryResponse.data || {}
    rankings.value = rankingResponse.data || rankings.value
    categoryFrequency.value = categoryResponse.data || []
  } catch (err) {
    error.value = err.data?.message || 'Gagal memuat data laporan.'
  } finally {
    loading.value = false
  }
}

watch(query, loadReport, { deep: true })
await loadReport()

const resetFilter = () => { filters.value = { month: '', year: '', department_id: '' } }
const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(Number(value) || 0)
const formatHours = (value) => `${Number(value || 0).toFixed(1)} jam`
const maxCategoryTotal = computed(() => Math.max(...categoryFrequency.value.map(item => Number(item.total)), 1))

const exportData = async (type) => {
  exporting.value = true
  try {
    const blob = await $fetch(`${apiBase}/reports/export/${type}`, {
      headers: { ...headers.value, Accept: type === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
      query: query.value,
      responseType: 'blob'
    })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `laporan-tiket.${type === 'pdf' ? 'pdf' : 'xlsx'}`
    link.click()
    URL.revokeObjectURL(link.href)
  } finally { exporting.value = false }
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">Analitik helpdesk</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-800">Laporan Kinerja Tiket</h1>
      </div>
      <div class="flex gap-2">
        <button type="button" :disabled="exporting" @click="exportData('excel')" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50">Export Excel</button>
        <button type="button" :disabled="exporting" @click="exportData('pdf')" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700 disabled:opacity-50">Export PDF</button>
      </div>
    </div>

    <section class="grid grid-cols-1 gap-3 rounded-xl bg-slate-100 p-4 sm:grid-cols-3">
      <label class="text-xs font-bold text-slate-600">Filter periode: bulan
        <select v-model="filters.month" class="mt-1 w-full rounded-lg border-0 bg-cyan-100 px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-cyan-500">
          <option value="">Semua bulan</option><option v-for="month in 12" :key="month" :value="month">{{ new Date(2000, month - 1).toLocaleString('id-ID', { month: 'long' }) }}</option>
        </select>
      </label>
      <label class="text-xs font-bold text-slate-600">Filter periode: tahun
        <select v-model="filters.year" class="mt-1 w-full rounded-lg border-0 bg-cyan-100 px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-cyan-500">
          <option value="">Semua tahun</option><option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </select>
      </label>
      <label class="text-xs font-bold text-slate-600">Filter unit
        <select v-model="filters.department_id" class="mt-1 w-full rounded-lg border-0 bg-cyan-100 px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-cyan-500">
          <option value="">Semua unit</option><option v-for="department in departments" :key="department.kode" :value="department.kode">{{ department.nama }}</option>
        </select>
      </label>
    </section>

    <button type="button" @click="resetFilter" class="text-xs font-semibold text-slate-500 underline hover:text-cyan-700">Reset filter</button>
    <p v-if="error" class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ error }}</p>
    <div v-if="loading" class="rounded-xl bg-white p-12 text-center text-sm text-slate-400 shadow-sm">Memuat laporan...</div>

    <template v-else>
      <section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div v-for="card in [
          { label: 'Jumlah Tiket Masuk', value: formatNumber(summary.total_tiket_masuk), tone: 'text-cyan-700' },
          { label: 'Jumlah Tiket Selesai', value: formatNumber(summary.tiket_selesai), tone: 'text-emerald-700' },
          { label: 'Jumlah Belum Selesai', value: formatNumber(summary.tiket_belum_selesai), tone: 'text-amber-700' },
          { label: 'Rata-rata Rating Pelayanan Helpdesk', value: `${Number(summary.avg_rating || 0).toFixed(1)} / 5`, tone: 'text-indigo-700' },
          { label: 'Rata-rata Penyelesaian Tiket', value: formatHours(summary.sla_avg_hours), tone: 'text-violet-700' }
        ]" :key="card.label" class="min-h-24 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
          <p class="text-xs font-semibold leading-4 text-slate-500">{{ card.label }}</p><strong :class="card.tone" class="mt-2 block text-xl">{{ card.value }}</strong>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <article v-for="panel in [
          { title: 'Peringkat Rating Unit', items: rankings.departments, value: item => `${Number(item.average_rating || 0).toFixed(1)} / 5`, name: item => item.department_name },
          { title: 'Peringkat Rating Admin Unit', items: rankings.users, value: item => `${Number(item.average_rating || 0).toFixed(1)} / 5`, name: item => item.user_name },
          { title: 'Peringkat Waktu Penyelesaian Unit', items: rankings.fastest_departments, value: item => formatHours(item.average_hours), name: item => item.department_name },
          { title: 'Peringkat Waktu Penyelesaian Admin Unit', items: rankings.users, value: item => formatHours(item.average_hours), name: item => item.user_name },
          { title: 'Peringkat Waktu Menjawab Pesan Unit', items: rankings.fastest_departments, value: item => formatHours(item.average_hours), name: item => item.department_name },
          { title: 'Peringkat Waktu Menjawab Pesan Admin Unit', items: rankings.users, value: item => formatHours(item.average_hours), name: item => item.user_name }
        ]" :key="panel.title" class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
          <header class="border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700">{{ panel.title }}</header>
          <div class="min-h-36 p-3">
            <div v-for="(item, index) in panel.items.slice(0, 5)" :key="`${panel.title}-${index}`" class="flex items-center gap-3 border-b border-slate-50 py-2 last:border-0">
              <span class="w-5 text-xs font-bold text-cyan-700">{{ index + 1 }}</span><span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700">{{ panel.name(item) }}</span><span class="text-xs font-bold text-slate-600">{{ panel.value(item) }}</span>
            </div>
            <p v-if="!panel.items.length" class="py-10 text-center text-xs text-slate-400">Belum ada data pada periode ini.</p>
          </div>
        </article>
      </section>

      <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <header class="border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700">Frekuensi Kategori</header>
        <div class="space-y-3 p-4">
          <div v-for="item in categoryFrequency" :key="item.id" class="flex items-center gap-3">
            <span class="w-36 truncate text-xs font-semibold text-slate-600">{{ item.name }}</span>
            <div class="h-5 flex-1 overflow-hidden rounded bg-cyan-50"><div class="h-full rounded bg-cyan-400" :style="{ width: `${(Number(item.total) / maxCategoryTotal) * 100}%` }"></div></div>
            <span class="w-8 text-right text-xs font-bold text-slate-700">{{ item.total }}</span>
          </div>
          <p v-if="!categoryFrequency.length" class="py-8 text-center text-xs text-slate-400">Belum ada data kategori.</p>
        </div>
      </section>
    </template>
  </div>
</template>
