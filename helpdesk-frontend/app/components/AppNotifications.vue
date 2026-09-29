<script setup lang="ts">
const { toasts, confirmState, dismissToast, settleConfirm } = useNotify()

const toastIcon = (type: string) => ({
  success: 'M5 13l4 4L19 7',
  error: 'M6 18L18 6M6 6l12 12',
  warning: 'M12 9v4m0 4h.01M10.3 3.9 1.8 18.2A2 2 0 003.5 21h17a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z',
  info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
}[type] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z')

const toastClasses = (type: string) => ({
  'border-emerald-200 bg-emerald-50 text-emerald-700': type === 'success',
  'border-rose-200 bg-rose-50 text-rose-700': type === 'error',
  'border-amber-200 bg-amber-50 text-amber-700': type === 'warning',
  'border-indigo-200 bg-indigo-50 text-indigo-700': type === 'info'
})

const toastIconWrapClasses = (type: string) => ({
  'bg-emerald-100 text-emerald-600': type === 'success',
  'bg-rose-100 text-rose-600': type === 'error',
  'bg-amber-100 text-amber-600': type === 'warning',
  'bg-indigo-100 text-indigo-600': type === 'info'
})
</script>

<template>
  <Teleport to="body">
    <!-- Toast Stack -->
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:items-end sm:right-4 sm:left-auto">
      <TransitionGroup
        name="toast"
        tag="div"
        class="flex w-full max-w-sm flex-col gap-2"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border p-3.5 shadow-lg backdrop-blur-sm"
          :class="toastClasses(toast.type)"
          role="status"
        >
          <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="toastIconWrapClasses(toast.type)">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="toastIcon(toast.type)" />
            </svg>
          </span>
          <p class="min-w-0 flex-1 text-sm font-medium leading-5">{{ toast.message }}</p>
          <button
            @click="dismissToast(toast.id)"
            aria-label="Tutup notifikasi"
            class="shrink-0 rounded-md p-0.5 text-current/60 hover:text-current"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>

    <!-- Confirm Dialog -->
    <Transition name="confirm-fade">
      <div
        v-if="confirmState.visible"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
        @keydown.esc="settleConfirm(false)"
      >
        <section role="alertdialog" aria-modal="true" aria-labelledby="confirm-dialog-title" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
          <div class="flex items-start gap-3">
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
              :class="confirmState.variant === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-indigo-100 text-indigo-600'"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.2A2 2 0 003.5 21h17a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z" />
              </svg>
            </span>
            <div class="min-w-0 flex-1 pt-1">
              <h2 id="confirm-dialog-title" class="text-sm font-bold text-slate-800">{{ confirmState.title }}</h2>
              <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ confirmState.message }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-2">
            <button
              @click="settleConfirm(false)"
              class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
              {{ confirmState.cancelText }}
            </button>
            <button
              @click="settleConfirm(true)"
              autofocus
              class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
              :class="confirmState.variant === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
            >
              {{ confirmState.confirmText }}
            </button>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(16px);
}
.toast-leave-active {
  position: absolute;
}
.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.15s ease;
}
.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}
</style>
