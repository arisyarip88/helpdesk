import { reactive } from 'vue'

export type ToastType = 'success' | 'error' | 'info' | 'warning'

export interface Toast {
  id: number
  message: string
  type: ToastType
}

export interface ConfirmOptions {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  variant?: 'danger' | 'primary'
}

// Module-level (shared) state so every component using this composable
// renders/reads the same toast list and confirm dialog.
const toasts = reactive<Toast[]>([])
let toastId = 0

const confirmState = reactive({
  visible: false,
  title: 'Konfirmasi',
  message: '',
  confirmText: 'Ya, Lanjutkan',
  cancelText: 'Batal',
  variant: 'primary' as 'danger' | 'primary'
})

let resolveConfirm: ((value: boolean) => void) | null = null

const dismissToast = (id: number) => {
  const index = toasts.findIndex(toast => toast.id === id)
  if (index !== -1) toasts.splice(index, 1)
}

const pushToast = (message: string, type: ToastType = 'info', duration = 4000) => {
  const id = ++toastId
  toasts.push({ id, message, type })
  if (duration > 0) {
    setTimeout(() => dismissToast(id), duration)
  }
  return id
}

const confirm = (options: ConfirmOptions | string) => {
  const opts = typeof options === 'string' ? { message: options } : options

  confirmState.title = opts.title || 'Konfirmasi'
  confirmState.message = opts.message
  confirmState.confirmText = opts.confirmText || 'Ya, Lanjutkan'
  confirmState.cancelText = opts.cancelText || 'Batal'
  confirmState.variant = opts.variant || 'primary'
  confirmState.visible = true

  return new Promise<boolean>((resolve) => {
    resolveConfirm = resolve
  })
}

const settleConfirm = (result: boolean) => {
  confirmState.visible = false
  resolveConfirm?.(result)
  resolveConfirm = null
}

export const useNotify = () => ({
  toasts,
  confirmState,
  success: (message: string, duration?: number) => pushToast(message, 'success', duration),
  error: (message: string, duration?: number) => pushToast(message, 'error', duration),
  info: (message: string, duration?: number) => pushToast(message, 'info', duration),
  warning: (message: string, duration?: number) => pushToast(message, 'warning', duration),
  dismissToast,
  confirm,
  settleConfirm
})
