// Satu watcher bersama: memeriksa penanda ringan dan menaikkan `tick`
// hanya ketika ada perubahan data (tiket, pesan, atau notifikasi status).
let timer = null
let lastSignature = null
let inFlight = false
let started = false

export const useTicketChanges = () => {
  const tick = useState('ticketChangeTick', () => 0)
  const config = useRuntimeConfig()
  const { token } = useAuth()
  const apiBase = config.public.apiBase || 'http://localhost:8000/api'
  const intervalMs = 8000

  const check = async () => {
    if (inFlight || !token.value || document.hidden) return
    inFlight = true
    try {
      const res = await $fetch(`${apiBase}/tickets/changes`, {
        headers: { Accept: 'application/json', Authorization: `Bearer ${token.value}` }
      })
      if (lastSignature !== null && res.signature !== lastSignature) tick.value++
      lastSignature = res.signature
    } catch (e) {
      // diam: percobaan berikutnya mengikuti jadwal
    } finally {
      inFlight = false
    }
  }

  const onVisible = () => { if (!document.hidden) check() }

  const start = () => {
    if (started || !import.meta.client) return
    started = true
    lastSignature = null
    check()
    timer = setInterval(check, intervalMs)
    document.addEventListener('visibilitychange', onVisible)
  }

  const stop = () => {
    if (!started) return
    started = false
    clearInterval(timer)
    timer = null
    document.removeEventListener('visibilitychange', onVisible)
  }

  // Paksa pembaruan setelah aksi sendiri (kirim pesan, dsb.) agar tidak dianggap perubahan luar
  const sync = () => { lastSignature = null; check() }

  return { tick, start, stop, sync }
}
