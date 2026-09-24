<script setup>
import { ref, computed, onMounted, watch } from 'vue'

definePageMeta({
  layout: 'user',
  middleware: 'auth'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'

// Ambil Token & Data Auth dari Composables
const { token, user, fetchUser } = useAuth()

// Helper Header Authentication
const getAuthHeaders = () => ({
  'Accept': 'application/json',
  'Authorization': `Bearer ${token.value}`
})

// State Alert & Submitting
const submitting = ref(false)
const alertMessage = ref({ type: '', text: '' })

// Form State
const form = ref({
  name: '',
  username: '',
  email: '',
  tlp: '',
  role_id: '',
  department_id: '',
  current_password: '',
  password: '',
  password_confirmation: ''
})

// Fetch Data Master Roles & Departments dari Endpoint
const { data: masterData } = await useAsyncData('profile-master-data', () =>
  $fetch(`${apiBase}/users`, {
    headers: getAuthHeaders()
  })
)

const rolesList = computed(() => masterData.value?.roles || [])
const departmentsList = computed(() => masterData.value?.departments || [])

// Isi Form dari State User Global
const populateForm = () => {
  if (user.value) {
    form.value.name = user.value.name || ''
    form.value.username = user.value.username || ''
    form.value.email = user.value.email || ''
    form.value.tlp = user.value.tlp || ''
    form.value.role_id = user.value.role_id || user.value.role?.id || ''
    form.value.department_id = user.value.department_id || user.value.department?.kode || ''
  }
}

onMounted(async () => {
  if (!user.value) {
    await fetchUser()
  }
  populateForm()
})

watch(user, () => {
  populateForm()
}, { deep: true })

// Handler Submit Form Update Profile
const handleSubmit = async () => {
  submitting.value = true
  alertMessage.value = { type: '', text: '' }

  // Validasi Konfirmasi Password jika Diisi
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    alertMessage.value = { 
      type: 'error', 
      text: 'Konfirmasi password baru tidak cocok.' 
    }
    submitting.value = false
    return
  }

  // Structure Payload (Hanya mengirimkan field yang diizinkan diubah)
 // Structure Payload
const payload = {
  name: form.value.name,
  username: form.value.username, // Tetap sertakan username
  email: form.value.email,
  tlp: form.value.tlp,
  role_id: form.value.role_id,   // Tetap sertakan role_id
  department_id: form.value.department_id
}

  // Masukkan Password Jika Ada Perubahan
  if (form.value.password) {
    payload.current_password = form.value.current_password
    payload.password = form.value.password
    payload.password_confirmation = form.value.password_confirmation
  }

  try {
    await $fetch(`${apiBase}/profile`, {
      method: 'PUT',
      headers: getAuthHeaders(),
      body: payload
    })

    alertMessage.value = { 
      type: 'success', 
      text: 'Profil berhasil diperbarui!' 
    }

    // Reset Input Password
    form.value.current_password = ''
    form.value.password = ''
    form.value.password_confirmation = ''

    // Refresh Global User State
    await fetchUser()
  } catch (err) {
    if (err.data?.errors) {
      const firstKey = Object.keys(err.data.errors)[0]
      alertMessage.value = { 
        type: 'error', 
        text: err.data.errors[firstKey][0] 
      }
    } else {
      alertMessage.value = { 
        type: 'error', 
        text: err.data?.message || 'Gagal memperbarui data profil.' 
      }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Pengaturan Profil</h1>
      <p class="text-sm text-slate-500">Kelola informasi pribadi, relasi akun, dan kata sandi Anda.</p>
    </div>

    <!-- Alert Notification -->
    <div 
      v-if="alertMessage.text" 
      :class="[
        'p-4 rounded-2xl text-xs font-medium border flex items-center gap-2.5 transition',
        alertMessage.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-600'
      ]"
    >
      <svg v-if="alertMessage.type === 'success'" class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <svg v-else class="w-5 h-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ alertMessage.text }}</span>
    </div>

    <!-- Card Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <!-- Loading State -->
      <div v-if="!user" class="p-12 text-center text-slate-400">
        <div class="flex items-center justify-center gap-2">
          <svg class="animate-spin h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>Memuat data profil...</span>
        </div>
      </div>

      <!-- Main Form -->
      <form v-else @submit.prevent="handleSubmit" class="p-6 space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Column 1: Informasi Diri & Relasi -->
          <div class="space-y-4">
            <div class="border-b border-slate-100 pb-3">
              <h2 class="text-base font-bold text-slate-800">Informasi Pengguna</h2>
              <p class="text-xs text-slate-400">Detail identitas, hak akses role, dan instansi departemen.</p>
            </div>

            <!-- Nama Lengkap & Username (2 Grid Row) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">
                  Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required
                  placeholder="Nama Pengguna" 
                  class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Username <span class="text-slate-400">(Terkunci)</span>
                </label>
                <input 
                  v-model="form.username" 
                  type="text" 
                  disabled
                  placeholder="Username" 
                  class="w-full px-3.5 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">
                Email <span class="text-rose-500">*</span>
              </label>
              <input 
                v-model="form.email" 
                type="email" 
                required
                placeholder="email@domain.com" 
                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">No. Telepon (Tlp)</label>
              <input 
                v-model="form.tlp" 
                type="text" 
                placeholder="081234567890" 
                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <!-- Role & Departemen Select Options -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Role <span class="text-slate-400">(Terkunci)</span>
                </label>
                <select 
                  v-model="form.role_id" 
                  disabled
                  class="w-full px-3 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed focus:outline-none"
                >
                  <option v-if="rolesList.length === 0" :value="form.role_id">
                    {{ user?.role?.name || 'Pilih Role' }}
                  </option>
                  <option 
                    v-for="role in rolesList" 
                    :key="role.id" 
                    :value="role.id"
                  >
                    {{ role.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Departemen <span class="text-slate-400">(Terkunci)</span>
                </label>
                <select 
                  v-model="form.department_id" 
                  disabled
                  class="w-full px-3 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed focus:outline-none"
                >
                  <option v-if="departmentsList.length === 0" :value="form.department_id">
                    {{ user?.department?.nama || 'Pilih Departemen' }}
                  </option>
                  <option 
                    v-for="dept in departmentsList" 
                    :key="dept.kode" 
                    :value="dept.kode"
                  >
                    {{ dept.nama }}
                  </option>
                </select>
              </div>
            </div>

          </div>

          <!-- Column 2: Keamanan / Password -->
          <div class="space-y-4">
            <div class="border-b border-slate-100 pb-3">
              <h2 class="text-base font-bold text-slate-800">Ubah Password</h2>
              <p class="text-xs text-slate-400">Kosongkan bagian password jika tidak ingin mengubahnya.</p>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Password Saat Ini</label>
              <input 
                v-model="form.current_password" 
                type="password" 
                placeholder="••••••••" 
                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Password Baru</label>
              <input 
                v-model="form.password" 
                type="password" 
                placeholder="••••••••" 
                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 mb-1">Konfirmasi Password Baru</label>
              <input 
                v-model="form.password_confirmation" 
                type="password" 
                placeholder="••••••••" 
                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

        </div>

        <!-- Submit Button Footer -->
        <div class="flex justify-end pt-4 border-t border-slate-100">
          <button 
            type="submit" 
            :disabled="submitting"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl flex items-center gap-2 transition text-sm font-medium shadow-sm shadow-indigo-200 disabled:opacity-50"
          >
            <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan Perubahan...' : 'Simpan Profil' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>