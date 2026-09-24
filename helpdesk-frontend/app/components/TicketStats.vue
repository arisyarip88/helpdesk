<script setup>
import { computed } from 'vue'

const props = defineProps({
  selectedStatusFilter: {
    type: [String, Number],
    default: ''
  },
  apiBase: {
    type: String,
    required: true
  },
  token: {
    type: String,
    required: true
  },
  departmentId: [String, Number],
  userId: [String, Number],
  userRoleId: [String, Number]
})

const emit = defineEmits(['filter'])

// Fetch statistik independen tanpa membawa param status_id atau search
const { data: statsData } = await useAsyncData(
  'global-ticket-stats',
  () => $fetch(`${props.apiBase}/tickets`, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${props.token}`
    },
    params: {
      per_page: 1,
      department_id: props.departmentId,
      role_id: props.userRoleId,
      user_id: props.userId
    }
  })
)

const stats = computed(() => {
  const backendStats = statsData.value?.stats || statsData.value?.statistics || {}
  return {
    jumlah: backendStats.total || backendStats.jumlah || 0,
    open: backendStats.open || backendStats.new || 0,
    in_progress_resolve: backendStats.in_progress_resolve || backendStats.in_progress || 0,
    completed: backendStats.completed || 0,
    rejected: backendStats.rejected || 0
  }
})

const handleStatusFilter = (statusId) => {
  emit('filter', statusId)
}
</script>

<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
    <!-- Total Tiket -->
    <button 
      type="button"
      @click="handleStatusFilter('')"
      :class="[
        'bg-white text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group shadow-sm',
        selectedStatusFilter === '' 
          ? 'ring-2 ring-slate-800 border-slate-800' 
          : 'border-slate-200 hover:border-slate-300 hover:shadow-md'
      ]"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 tracking-wide">Jumlah Tiket</span>
        <div class="p-2 rounded-xl bg-slate-100 text-slate-700 group-hover:bg-slate-200 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
        </div>
      </div>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.jumlah }}</span>
        <span class="text-[11px] font-medium text-slate-400">Semua</span>
      </div>
    </button>

    <!-- Open -->
    <button 
      type="button"
      @click="handleStatusFilter(1)"
      :class="[
        'bg-white text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group shadow-sm',
        selectedStatusFilter === '1' 
          ? 'ring-2 ring-amber-500 border-amber-500' 
          : 'border-slate-200 hover:border-amber-300 hover:shadow-md'
      ]"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-amber-600 tracking-wide">Open</span>
        <div class="p-2 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
      </div>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.open }}</span>
        <span class="text-[11px] font-medium text-amber-600">Baru</span>
      </div>
    </button>

    <!-- In Progress -->
    <button 
      type="button"
      @click="handleStatusFilter(2)"
      :class="[
        'bg-white text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group shadow-sm',
        (selectedStatusFilter === '2' || selectedStatusFilter === '3')
          ? 'ring-2 ring-blue-500 border-blue-500' 
          : 'border-slate-200 hover:border-blue-300 hover:shadow-md'
      ]"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-blue-600 tracking-wide">In Progress / Resolve</span>
        <div class="p-2 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
      </div>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.in_progress_resolve }}</span>
        <span class="text-[11px] font-medium text-blue-600">Proses</span>
      </div>
    </button>

    <!-- Completed -->
    <button 
      type="button"
      @click="handleStatusFilter(4)"
      :class="[
        'bg-white text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group shadow-sm',
        selectedStatusFilter === '4' 
          ? 'ring-2 ring-emerald-500 border-emerald-500' 
          : 'border-slate-200 hover:border-emerald-300 hover:shadow-md'
      ]"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-emerald-600 tracking-wide">Completed</span>
        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        </div>
      </div>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.completed }}</span>
        <span class="text-[11px] font-medium text-emerald-600">Selesai</span>
      </div>
    </button>

    <!-- Rejected -->
    <button 
      type="button"
      @click="handleStatusFilter(5)"
      :class="[
        'col-span-2 sm:col-span-1 bg-white text-left p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group shadow-sm',
        selectedStatusFilter === '5' 
          ? 'ring-2 ring-rose-500 border-rose-500' 
          : 'border-slate-200 hover:border-rose-300 hover:shadow-md'
      ]"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-rose-600 tracking-wide">Rejected</span>
        <div class="p-2 rounded-xl bg-rose-50 text-rose-600 group-hover:bg-rose-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </div>
      </div>
      <div class="mt-3 flex items-baseline justify-between">
        <span class="text-2xl font-black text-slate-800 tracking-tight">{{ stats.rejected }}</span>
        <span class="text-[11px] font-medium text-rose-600">Ditolak</span>
      </div>
    </button>
  </div>
</template>