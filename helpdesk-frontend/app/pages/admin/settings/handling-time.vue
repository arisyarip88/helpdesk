<script setup>
import { ref, onMounted } from 'vue'

definePageMeta({
  layout: 'admin',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token, hasRole } = useAuth()
const maxHours = ref(24)
const alertMode = ref('automatic')
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const getAuthHeaders = () => ({
  Accept: 'application/json',
  Authorization: `Bearer ${token.value}`
})

onMounted(async () => {
  if (!hasRole(['1', '2'])) {
    loading.value = false
    return
  }

  try {
    const response = await $fetch(`${apiBase}/ticket-handling-settings`, {
      headers: getAuthHeaders()
    })
    maxHours.value = Number(response.data?.max_hours) || 24
    alertMode.value = response.data?.alert_mode || 'automatic'
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal memuat batas waktu penanganan.'
  } finally {
    loading.value = false
  }
})

const saveSettings = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await $fetch(`${apiBase}/ticket-handling-settings`, {
      method: 'PUT',
      headers: getAuthHeaders(),
      body: { max_hours: maxHours.value, alert_mode: alertMode.value }
    })
    maxHours.value = Number(response.data?.max_hours) || maxHours.value
    successMessage.value = response.message || 'Pengaturan berhasil disimpan.'
  } catch (error) {
    errorMessage.value = error.data?.errors?.max_hours?.[0] || error.data?.message || 'Gagal menyimpan pengaturan.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-3xl space-y-6">
    <header>
      <p class="text-xs font-semibold uppercase text-indigo-600">Pengaturan Tiket</p>
      <h1 class="mt-1 text-2xl font-bold text-slate-900">Batas Waktu Penanganan</h1>
    </header>

    <div v-if="!hasRole(['1', '2'])" class="border-y border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
      Anda tidak memiliki akses untuk mengubah pengaturan ini.
    </div>
    <div v-else class="border-y border-slate-200 bg-white px-5 py-6">
      <form class="max-w-xl space-y-5" @submit.prevent="saveSettings">
        <div>
          <label for="max-hours" class="block text-sm font-semibold text-slate-800">Maksimal waktu penanganan (jam)</label>
          <p class="mt-1 text-sm text-slate-500">Tiket yang belum selesai setelah batas ini akan ditandai untuk role 3.</p>
          <div class="mt-3 flex items-center gap-3">
            <input
              id="max-hours"
              v-model.number="maxHours"
              type="number"
              min="1"
              max="720"
              required
              :disabled="loading || saving"
              class="w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-100"
            />
            <span class="text-sm text-slate-500">jam (maksimal 720 jam)</span>
          </div>
        </div>

        <fieldset>
          <legend class="block text-sm font-semibold text-slate-800">Mode peringatan</legend>
          <div role="radiogroup" aria-label="Mode peringatan" class="mt-2 inline-flex rounded-lg border border-slate-300 p-1">
            <button
              type="button"
              role="radio"
              :aria-checked="alertMode === 'automatic'"
              @click="alertMode = 'automatic'"
              class="rounded-md px-3 py-2 text-sm font-medium transition"
              :class="alertMode === 'automatic' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            >
              Otomatis
            </button>
            <button
              type="button"
              role="radio"
              :aria-checked="alertMode === 'manual'"
              @click="alertMode = 'manual'"
              class="rounded-md px-3 py-2 text-sm font-medium transition"
              :class="alertMode === 'manual' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
            >
              Manual
            </button>
          </div>
          <p class="mt-2 text-sm text-slate-500">
            {{ alertMode === 'automatic' ? 'Role 3 melihat peringatan saat tiket melewati batas waktu.' : 'Admin mengirim peringatan dari daftar tiket, satuan atau bulk.' }}
          </p>
        </fieldset>

        <p v-if="errorMessage" role="alert" class="text-sm text-rose-700">{{ errorMessage }}</p>
        <p v-if="successMessage" role="status" class="text-sm text-emerald-700">{{ successMessage }}</p>

        <button
          type="submit"
          :disabled="loading || saving || !hasRole(['1', '2'])"
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ loading ? 'Memuat...' : saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
        </button>
      </form>
    </div>
  </section>
</template>