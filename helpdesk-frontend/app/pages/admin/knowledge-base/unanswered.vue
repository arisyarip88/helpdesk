<script setup>
definePageMeta({
  layout: 'admin',
  middleware: ['auth', 'role'],
  roles: ['1', '2']
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8000/api'
const { token } = useAuth()

const currentPage = ref(1)
const searchQuery = ref('')
const debouncedSearch = ref('')
const selectedQuestion = ref(null)
const keywordsInput = ref('')
const answerInput = ref('')
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const formError = ref('')

let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    debouncedSearch.value = searchQuery.value.trim()
    currentPage.value = 1
  }, 350)
}

const getAuthHeaders = () => ({
  Accept: 'application/json',
  Authorization: `Bearer ${token.value}`
})

const { data: response, pending, error, refresh } = await useAsyncData(
  'unanswered-chat-questions',
  () => $fetch(`${apiBase}/knowledge-base/unanswered`, {
    headers: getAuthHeaders(),
    query: { page: currentPage.value, search: debouncedSearch.value }
  }),
  { watch: [currentPage, debouncedSearch], getCachedData: () => undefined }
)

const questions = computed(() => response.value?.data || [])
const pagination = computed(() => ({
  currentPage: response.value?.current_page || 1,
  lastPage: response.value?.last_page || 1,
  from: response.value?.from || 0,
  to: response.value?.to || 0,
  total: response.value?.total || 0
}))

const generateKeywords = (question) => {
  const stopWords = new Set([
    'apa', 'apakah', 'bagaimana', 'dimana', 'dari', 'dengan', 'dan', 'di', 'ke', 'saya',
    'kami', 'kita', 'ini', 'itu', 'untuk', 'yang', 'ada', 'bisa', 'cara', 'mohon',
    'tolong', 'dong', 'ya', 'kah', 'the', 'untuk'
  ])

  return [...new Set(
    String(question || '')
      .toLocaleLowerCase('id-ID')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .match(/[a-z0-9]+/g)
      ?.filter(word => word.length > 2 && !stopWords.has(word)) || []
  )].slice(0, 8)
}

const openKnowledgeModal = (item) => {
  selectedQuestion.value = item
  keywordsInput.value = generateKeywords(item.question).join(', ')
  answerInput.value = ''
  formError.value = ''
  isModalOpen.value = true
}

const closeKnowledgeModal = () => {
  isModalOpen.value = false
  selectedQuestion.value = null
  keywordsInput.value = ''
  answerInput.value = ''
  formError.value = ''
}

const createKnowledge = async () => {
  const keywords = [...new Set(keywordsInput.value.split(',').map(keyword => keyword.trim()).filter(Boolean))]
  if (!keywords.length || !answerInput.value.trim()) {
    formError.value = 'Isi minimal satu keyword dan jawaban chatbot.'
    return
  }

  isSubmitting.value = true
  formError.value = ''
  try {
    await $fetch(`${apiBase}/knowledge-base/unanswered/${selectedQuestion.value.id}/knowledge`, {
      method: 'POST',
      headers: getAuthHeaders(),
      body: { keywords, answer: answerInput.value.trim() }
    })
    closeKnowledgeModal()
    await refresh()
  } catch (err) {
    formError.value = err.data?.message || 'Gagal membuat knowledge dari pertanyaan ini.'
  } finally {
    isSubmitting.value = false
  }
}

const deleteQuestion = async (item) => {
  if (!confirm('Hapus pertanyaan belum terjawab ini?')) return
  try {
    await $fetch(`${apiBase}/knowledge-base/unanswered/${item.id}`, {
      method: 'DELETE',
      headers: getAuthHeaders()
    })
    await refresh()
  } catch (err) {
    alert(err.data?.message || 'Gagal menghapus pertanyaan.')
  }
}
</script>

<template>
  <div class="space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Pertanyaan Belum Terjawab</h1>
        <p class="mt-1 text-sm text-slate-500">Tinjau pertanyaan chatbot dan ubah menjadi knowledge base.</p>
      </div>
      <p class="text-sm text-slate-500">{{ pagination.total }} pertanyaan</p>
    </header>

    <div class="relative w-full max-w-lg">
      <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
      </svg>
      <input v-model="searchQuery" @input="handleSearch" type="search" placeholder="Cari pertanyaan..." class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
    </div>

    <div v-if="pending" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Memuat pertanyaan...</div>
    <div v-else-if="error" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700">Gagal memuat daftar pertanyaan. Coba muat ulang halaman.</div>
    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th class="px-5 py-3">Pertanyaan</th>
              <th class="px-5 py-3">Frekuensi</th>
              <th class="px-5 py-3">Terakhir ditanyakan</th>
              <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in questions" :key="item.id" class="transition-colors hover:bg-indigo-50/60">
              <td class="max-w-2xl px-5 py-4 font-medium text-slate-800">{{ item.question }}</td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ item.occurrences }} kali</span>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ item.last_asked_at ? new Date(item.last_asked_at).toLocaleString('id-ID') : '-' }}</td>
              <td class="whitespace-nowrap px-5 py-4 text-right">
                <button @click="openKnowledgeModal(item)" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Tambah Knowledge</button>
                <button @click="deleteQuestion(item)" class="ml-2 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Hapus</button>
              </td>
            </tr>
            <tr v-if="questions.length === 0">
              <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">Belum ada pertanyaan yang perlu ditangani.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer v-if="pagination.total > 0" class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">Menampilkan {{ pagination.from }}–{{ pagination.to }} dari {{ pagination.total }}</p>
        <div class="flex items-center gap-2">
          <button @click="currentPage--" :disabled="currentPage <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-40">Sebelumnya</button>
          <span class="text-xs text-slate-600">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
          <button @click="currentPage++" :disabled="currentPage >= pagination.lastPage" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 disabled:opacity-40">Berikutnya</button>
        </div>
      </footer>
    </div>

    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4 backdrop-blur-sm">
      <section role="dialog" aria-modal="true" aria-labelledby="unanswered-modal-title" class="my-auto w-full max-w-xl rounded-xl bg-white shadow-xl">
        <header class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h2 id="unanswered-modal-title" class="text-lg font-bold text-slate-800">Tambah Knowledge</h2>
            <p class="mt-1 text-xs text-slate-500">Knowledge baru langsung aktif untuk chatbot.</p>
          </div>
          <button @click="closeKnowledgeModal" aria-label="Tutup" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" /></svg>
          </button>
        </header>

        <form @submit.prevent="createKnowledge" class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-700">Pertanyaan pengguna</label>
            <p class="rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ selectedQuestion?.question }}</p>
          </div>
          <div>
            <label for="generated-keywords" class="mb-1 block text-xs font-semibold text-slate-700">Keyword (dipisahkan koma)</label>
            <input id="generated-keywords" v-model="keywordsInput" required type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            <p class="mt-1 text-[11px] text-slate-500">Keyword dibuat otomatis dari pertanyaan dan dapat diedit.</p>
          </div>
          <div>
            <label for="knowledge-answer" class="mb-1 block text-xs font-semibold text-slate-700">Jawaban chatbot</label>
            <textarea id="knowledge-answer" v-model="answerInput" required rows="5" maxlength="5000" placeholder="Tulis jawaban yang akan diberikan chatbot..." class="w-full resize-y rounded-lg border border-slate-200 px-3 py-2.5 text-sm leading-6 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"></textarea>
          </div>
          <p v-if="formError" role="alert" class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ formError }}</p>
          <footer class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <button type="button" @click="closeKnowledgeModal" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</button>
            <button type="submit" :disabled="isSubmitting" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">{{ isSubmitting ? 'Menyimpan...' : 'Simpan Knowledge' }}</button>
          </footer>
        </form>
      </section>
    </div>
  </div>
</template>