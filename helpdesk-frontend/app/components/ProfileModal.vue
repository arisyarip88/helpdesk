<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'updated'])

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'

const { token, user, fetchUser } = useAuth()

const getAuthHeaders = () => ({
  'Accept': 'application/json',
  'Authorization': `Bearer ${token.value}`
})

const submitting = ref(false)
const alertMessage = ref({ type: '', text: '' })
const masterRoles = ref([])
const masterDepartments = ref([])

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

// Fetch Master Data Tanpa Top-Level Await
const fetchMasterData = async () => {
  try {
    const res = await $fetch(`${apiBase}/users`, {
      headers: getAuthHeaders()
    })
    masterRoles.value = res?.roles || []
    masterDepartments.value = res?.departments || []
  } catch (err) {
    console.error('Gagal memuat master data:', err)
  }
}

// Populate Data User
const populateForm = () => {
  if (user.value) {
    form.value.name = user.value.name || ''
    form.value.username = user.value.username || ''
    form.value.email = user.value.email || ''
    form.value.tlp = user.value.tlp || ''
    form.value.role_id = user.value.role_id || user.value.role?.id || ''
    form.value.department_id = user.value.department_id || user.value.department?.kode || ''
    form.value.current_password = ''
    form.value.password = ''
    form.value.password_confirmation = ''
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    alertMessage.value = { type: '', text: '' }
    populateForm()
  }
})

onMounted(() => {
  fetchMasterData()
})

const closeModal = () => {
  emit('update:modelValue', false)
}

const handleSubmit = async () => {
  submitting.value = true
  alertMessage.value = { type: '', text: '' }

  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    alertMessage.value = { 
      type: 'error', 
      text: 'Konfirmasi password baru tidak cocok.' 
    }
    submitting.value = false
    return
  }

  const payload = {
    name: form.value.name,
    username: form.value.username,
    email: form.value.email,
    tlp: form.value.tlp,
    role_id: form.value.role_id,
    department_id: form.value.department_id
  }

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

    form.value.current_password = ''
    form.value.password = ''
    form.value.password_confirmation = ''

    if (fetchUser) await fetchUser()
    emit('updated')

    setTimeout(() => {
      closeModal()
    }, 1200)
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
  <div v-if="modelValue" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
      
      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-lg font-bold text-slate-800">Pengaturan Profil</h3>
          <p class="text-xs text-slate-500">Kelola informasi pribadi, relasi akun, dan kata sandi Anda.</p>
        </div>
        <button @click="closeModal" type="button" class="text-slate-400 hover:text-slate-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Alert Notifikasi -->
      <div 
        v-if="alertMessage.text" 
        :class="[
          'p-3.5 rounded-xl text-xs font-medium border flex items-center gap-2.5 transition',
          alertMessage.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-600'
        ]"
      >
        <svg v-if="alertMessage.type === 'success'" class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg v-else class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ alertMessage.text }}</span>
      </div>

      <!-- Form Profil -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Informasi Pengguna -->
          <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
              <h4 class="text-sm font-bold text-slate-800">Informasi Pengguna</h4>
              <p class="text-xs text-slate-400">Detail identitas, role, dan departemen.</p>
            </div>

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
                  class="w-full px-3.5 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed"
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Role <span class="text-slate-400">(Terkunci)</span>
                </label>
                <select 
                  v-model="form.role_id" 
                  disabled
                  class="w-full px-3 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed"
                >
                  <option v-if="masterRoles.length === 0" :value="form.role_id">
                    {{ user?.role?.name || 'Pilih Role' }}
                  </option>
                  <option 
                    v-for="role in masterRoles" 
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
                  class="w-full px-3 py-2 border border-slate-200 bg-slate-100 text-slate-500 rounded-xl text-sm cursor-not-allowed"
                >
                  <option v-if="masterDepartments.length === 0" :value="form.department_id">
                    {{ user?.department?.nama || 'Pilih Departemen' }}
                  </option>
                  <option 
                    v-for="dept in masterDepartments" 
                    :key="dept.kode" 
                    :value="dept.kode"
                  >
                    {{ dept.nama }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Keamanan Password -->
          <div class="space-y-4">
            <div class="border-b border-slate-100 pb-2">
              <h4 class="text-sm font-bold text-slate-800">Ubah Password</h4>
              <p class="text-xs text-slate-400">Kosongkan jika tidak ingin merubah password.</p>
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

        <!-- Action Footer -->
        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
          <button 
            type="button" 
            @click="closeModal" 
            class="px-4 py-2.5 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100 transition"
          >
            Batal
          </button>
          <button 
            type="submit" 
            :disabled="submitting"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl flex items-center gap-2 transition text-xs font-medium shadow-sm shadow-indigo-200 disabled:opacity-50"
          >
            <svg v-if="submitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ submitting ? 'Menyimpan...' : 'Simpan Profil' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>