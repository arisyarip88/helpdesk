<script setup>
definePageMeta({
  layout: 'admin',
  middleware: ['auth', 'role'],
  roles: ['1', '2']
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()

const complaints = ref([])
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
const search = ref('')
const debouncedSearch = ref('')
const statusFilter = ref('')
const page = ref(1)
const perPage = ref(10)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const modalOpen = ref(false)
const detailsOpen = ref(false)
const selectedComplaint = ref(null)
const attachmentInput = ref(null)
const attachment = ref(null)
const removeAttachment = ref(false)
const form = ref(emptyForm())
let searchTimer = null

const statusOptions = [
  { value: 'new', label: 'Baru' },
  { value: 'in_progress', label: 'Diproses' },
  { value: 'resolved', label: 'Selesai' },
  { value: 'rejected', label: 'Ditolak' }
]

function emptyForm() {
  return {
    id: null,
    name: '',
    phone: '',
    email: '',
    category_id: '',
    description: '',
    status: 'new'
  }
}

const authHeaders = () => ({
  Accept: 'application/json',
  Authorization: `Bearer ${token.value}`
})

const loadComplaints = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await $fetch(`${apiBase}/admin/public-complaints`, {
      headers: authHeaders(),
      query: {
        page: page.value,
        per_page: perPage.value,
        search: debouncedSearch.value.trim() || undefined,
        status: statusFilter.value || undefined
      }
    })
    complaints.value = result.data || []
    pagination.value = {
      current_page: result.current_page || 1,
      last_page: result.last_page || 1,
      total: result.total || 0
    }
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal memuat daftar aduan umum.'
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    categories.value = await $fetch(`${apiBase}/public/categories`)
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal memuat kategori aduan.'
  }
}

const openCreate = () => {
  selectedComplaint.value = null
  form.value = emptyForm()
  attachment.value = null
  removeAttachment.value = false
  if (attachmentInput.value) attachmentInput.value.value = ''
  modalOpen.value = true
}

const openEdit = (complaint) => {
  selectedComplaint.value = complaint
  form.value = {
    id: complaint.id,
    name: complaint.name || '',
    phone: complaint.phone || '',
    email: complaint.email || '',
    category_id: complaint.category_id || '',
    description: complaint.description || '',
    status: complaint.status || 'new'
  }
  attachment.value = null
  removeAttachment.value = false
  if (attachmentInput.value) attachmentInput.value.value = ''
  modalOpen.value = true
}

const openDetails = async (complaint) => {
  errorMessage.value = ''
  try {
    const result = await $fetch(`${apiBase}/admin/public-complaints/${complaint.id}`, {
      headers: authHeaders()
    })
    selectedComplaint.value = result.data
    detailsOpen.value = true
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal memuat detail aduan.'
  }
}

const closeModal = () => {
  modalOpen.value = false
  selectedComplaint.value = null
  attachment.value = null
}

const setAttachment = (event) => {
  attachment.value = event.target.files?.[0] || null
  if (attachment.value) removeAttachment.value = false
}

const saveComplaint = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  const body = new FormData()
  for (const key of ['name', 'phone', 'email', 'category_id', 'description']) {
    body.append(key, form.value[key])
  }
  if (form.value.id) body.append('status', form.value.status)
  if (attachment.value) body.append('attachment', attachment.value)
  if (removeAttachment.value) body.append('remove_attachment', '1')
  if (form.value.id) body.append('_method', 'PUT')

  try {
    await $fetch(
      form.value.id
        ? `${apiBase}/admin/public-complaints/${form.value.id}`
        : `${apiBase}/admin/public-complaints`,
      {
        method: 'POST',
        headers: authHeaders(),
        body
      }
    )
    successMessage.value = form.value.id ? 'Aduan berhasil diperbarui.' : 'Aduan berhasil ditambahkan.'
    closeModal()
    await loadComplaints()
  } catch (error) {
    const validationErrors = error.data?.errors
    errorMessage.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : error.data?.message || 'Gagal menyimpan aduan.'
  } finally {
    saving.value = false
  }
}

const deleteComplaint = async (complaint) => {
  if (!window.confirm(`Hapus aduan dari ${complaint.name}?`)) return

  deletingId.value = complaint.id
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await $fetch(`${apiBase}/admin/public-complaints/${complaint.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    successMessage.value = 'Aduan berhasil dihapus.'
    if (complaints.value.length === 1 && page.value > 1) {
      page.value--
    } else {
      await loadComplaints()
    }
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal menghapus aduan.'
  } finally {
    deletingId.value = null
  }
}

const downloadAttachment = async (complaint) => {
  try {
    const file = await $fetch(`${apiBase}/admin/public-complaints/${complaint.id}/attachment`, {
      headers: authHeaders(),
      responseType: 'blob'
    })
    const url = URL.createObjectURL(file)
    const link = document.createElement('a')
    link.href = url
    link.download = complaint.attachment_name || 'lampiran'
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error.data?.message || 'Gagal mengunduh lampiran.'
  }
}

const statusLabel = (status) => statusOptions.find(option => option.value === status)?.label || status
const statusClass = (status) => ({
  new: 'bg-blue-100 text-blue-700',
  in_progress: 'bg-amber-100 text-amber-700',
  resolved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-rose-100 text-rose-700'
}[status] || 'bg-slate-100 text-slate-600')

watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    debouncedSearch.value = value
  }, 300)
})

watch([debouncedSearch, statusFilter, perPage], () => {
  if (page.value !== 1) {
    page.value = 1
    return
  }
  loadComplaints()
})
watch(page, loadComplaints)

onMounted(() => {
  loadCategories()
  loadComplaints()
})

onUnmounted(() => clearTimeout(searchTimer))
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Aduan Umum</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola aduan yang dikirim melalui formulir publik.</p>
      </div>
      <button @click="openCreate" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
        + Tambah Aduan
      </button>
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ successMessage }}</div>

    <div class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row">
      <input
        v-model="search"
        type="search"
        placeholder="Cari nama, telepon, email, kategori, atau aduan..."
        class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500"
      />
      <select v-model="statusFilter" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        <option value="">Semua status</option>
        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
      <select v-model.number="perPage" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        <option :value="10">10 per halaman</option>
        <option :value="25">25 per halaman</option>
        <option :value="50">50 per halaman</option>
      </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
      <div v-if="loading" class="p-12 text-center text-sm text-slate-500">Memuat aduan...</div>
      <div v-else-if="!complaints.length" class="p-12 text-center text-sm text-slate-500">Belum ada aduan yang cocok.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-225 text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">Pengadu</th>
              <th class="px-4 py-3">Kategori</th>
              <th class="px-4 py-3">Deskripsi</th>
              <th class="px-4 py-3">Lampiran</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Tanggal</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in complaints" :key="item.id" class="align-top hover:bg-slate-50/70">
              <td class="px-4 py-3">
                <p class="font-semibold text-slate-800">{{ item.name }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ item.phone }}</p>
                <p class="text-xs text-slate-500">{{ item.email }}</p>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.category?.name || '-' }}</td>
              <td class="max-w-sm px-4 py-3 text-slate-600">
                <p class="line-clamp-3 whitespace-pre-line">{{ item.description }}</p>
              </td>
              <td class="px-4 py-3">
                <button v-if="item.attachment_path" @click="downloadAttachment(item)" class="text-xs font-semibold text-indigo-600 hover:underline">
                  {{ item.attachment_name || 'Unduh file' }}
                </button>
                <span v-else class="text-xs text-slate-400">-</span>
              </td>
              <td class="px-4 py-3">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ new Date(item.created_at).toLocaleString('id-ID') }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-right">
                <button @click="openDetails(item)" class="mr-3 text-xs font-semibold text-slate-600 hover:underline">Detail</button>
                <button @click="openEdit(item)" class="mr-3 text-xs font-semibold text-indigo-600 hover:underline">Ubah</button>
                <button :disabled="deletingId === item.id" @click="deleteComplaint(item)" class="text-xs font-semibold text-rose-600 hover:underline disabled:opacity-50">
                  {{ deletingId === item.id ? 'Menghapus...' : 'Hapus' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <span>{{ pagination.total }} aduan · Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="page <= 1 || loading" @click="page--" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40">Sebelumnya</button>
          <button :disabled="page >= pagination.last_page || loading" @click="page++" class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40">Berikutnya</button>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="detailsOpen && selectedComplaint" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4" @click.self="detailsOpen = false">
        <section class="my-8 w-full max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-slate-900">Detail Aduan</h2>
              <p class="mt-1 text-xs text-slate-500">Dikirim {{ new Date(selectedComplaint.created_at).toLocaleString('id-ID') }}</p>
            </div>
            <button type="button" @click="detailsOpen = false" aria-label="Tutup" class="text-xl text-slate-400 hover:text-slate-700">×</button>
          </div>
          <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="text-xs font-semibold text-slate-500">Nama</dt><dd class="mt-1 text-slate-800">{{ selectedComplaint.name }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">Telepon</dt><dd class="mt-1 text-slate-800">{{ selectedComplaint.phone }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">Email</dt><dd class="mt-1 text-slate-800">{{ selectedComplaint.email }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">Kategori</dt><dd class="mt-1 text-slate-800">{{ selectedComplaint.category?.name || '-' }}</dd></div>
            <div><dt class="text-xs font-semibold text-slate-500">Status</dt><dd class="mt-1 text-slate-800">{{ statusLabel(selectedComplaint.status) }}</dd></div>
            <div>
              <dt class="text-xs font-semibold text-slate-500">Lampiran</dt>
              <dd class="mt-1">
                <button v-if="selectedComplaint.attachment_path" @click="downloadAttachment(selectedComplaint)" class="text-indigo-600 hover:underline">{{ selectedComplaint.attachment_name || 'Unduh file' }}</button>
                <span v-else class="text-slate-500">-</span>
              </dd>
            </div>
          </dl>
          <div>
            <h3 class="text-xs font-semibold text-slate-500">Deskripsi Aduan</h3>
            <p class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">{{ selectedComplaint.description }}</p>
          </div>
          <div class="flex justify-end">
            <button @click="detailsOpen = false; openEdit(selectedComplaint)" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Ubah Aduan</button>
          </div>
        </section>
      </div>

      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4" @click.self="closeModal">
        <form @submit.prevent="saveComplaint" class="my-8 w-full max-w-2xl space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-slate-900">{{ form.id ? 'Ubah Aduan' : 'Tambah Aduan' }}</h2>
              <p class="mt-1 text-xs text-slate-500">Lengkapi identitas dan informasi aduan.</p>
            </div>
            <button type="button" @click="closeModal" aria-label="Tutup" class="text-xl text-slate-400 hover:text-slate-700">×</button>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-1 text-xs font-semibold text-slate-600">Nama
              <input v-model="form.name" required maxlength="150" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal" />
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Telepon
              <input v-model="form.phone" required maxlength="30" type="tel" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal" />
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Email
              <input v-model="form.email" required maxlength="150" type="email" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal" />
            </label>
            <label class="space-y-1 text-xs font-semibold text-slate-600">Kategori
              <select v-model="form.category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal">
                <option value="" disabled>Pilih kategori</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </label>
          </div>

          <label class="block space-y-1 text-xs font-semibold text-slate-600">Deskripsi
            <textarea v-model="form.description" required minlength="10" maxlength="5000" rows="5" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal"></textarea>
          </label>

          <label v-if="form.id" class="block space-y-1 text-xs font-semibold text-slate-600">Status
            <select v-model="form.status" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-normal">
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>

          <div class="space-y-2 text-xs text-slate-600">
            <p class="font-semibold">Lampiran (opsional, maks. 5 MB)</p>
            <button
              v-if="form.id && selectedComplaint?.attachment_path && !removeAttachment"
              type="button"
              @click="removeAttachment = true"
              class="block text-left text-indigo-600 hover:underline"
            >Lampiran saat ini: {{ selectedComplaint.attachment_name }} — hapus</button>
            <p v-else-if="removeAttachment" class="text-rose-600">Lampiran saat ini akan dihapus.</p>
            <input ref="attachmentInput" type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" @change="setAttachment" class="block w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeModal" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600">Batal</button>
            <button :disabled="saving" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
              {{ saving ? 'Menyimpan...' : 'Simpan Aduan' }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>
  </div>
</template>
