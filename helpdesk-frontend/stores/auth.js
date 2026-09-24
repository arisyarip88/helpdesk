// stores/auth.ts
import { defineStore } from 'pinia'

export interface User {
  id: number
  name: string
  email: string
  roles: string[]
  permissions?: string[]
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7, // 7 Hari
    sameSite: 'lax'
  })

  // Getters / Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const roles = computed<string[]>(() => user.value?.roles || [])
  const permissions = computed<string[]>(() => user.value?.permissions || [])

  // Helper: Cek apakah user memiliki salah satu role yang diizinkan
  const hasRole = (requiredRoles: string | string[]): boolean => {
    if (!user.value) return false
    const roleList = Array.isArray(requiredRoles) ? requiredRoles : [requiredRoles]
    // Return true jika user punya setidaknya salah satu role dalam array
    return roleList.some(r => roles.value.includes(r))
  }

  // Helper: Cek permission (opsional)
  const hasPermission = (requiredPermission: string): boolean => {
    if (!user.value) return false
    return permissions.value.includes(requiredPermission)
  }

  // Actions
  const setUser = (userData: User) => {
    user.value = userData
  }

  const setToken = (authToken: string) => {
    token.value = authToken
  }

  // Fetch profil user dari API Laravel (Dipanggil saat login atau refresh halaman)
  const fetchUser = async () => {
    if (!token.value) return

    const config = useRuntimeConfig()
    try {
      const data = await $fetch<User>('/api/user', {
        baseURL: config.public.apiBase || 'http://localhost:8000',
        headers: {
          Authorization: `Bearer ${token.value}`,
          Accept: 'application/json'
        }
      })
      setUser(data)
    } catch (error) {
      logout()
    }
  }

  const logout = () => {
    token.value = null
    user.value = null
    navigateTo('/login')
  }

  return {
    user,
    token,
    isAuthenticated,
    roles,
    permissions,
    hasRole,
    hasPermission,
    setUser,
    setToken,
    fetchUser,
    logout
  }
})