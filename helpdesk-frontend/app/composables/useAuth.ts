// composables/useAuth.ts
export const useAuth = () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('token', { maxAge: 60 * 60 * 24 * 7 }) // Simpan token di cookie 7 hari
  const user = useState<any>('user', () => null)

  // Ambil Data Profil User
  const fetchUser = async () => {
    if (!token.value) return null
    try {
      const data: any = await $fetch(`${config.public.apiBase}/profile`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
      user.value = data.data
      return user.value
    } catch (err) {
      token.value = null
      user.value = null
      return null
    }
  }

  // Helper cek role
  const hasRole = (allowedRoles: number | string | (number | string)[]) => {
    if (!user.value) return false
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles]
    const userRoleId = user.value.role_id ?? user.value.role?.id
    if (userRoleId === undefined || userRoleId === null) return false
    return roles.map(String).includes(String(userRoleId))
  }

  // Helper navigasi berdasarkan role
  const navigateByRole = (roleInput?: number | string | null) => {
    const role = Number(roleInput ?? user.value?.role_id ?? user.value?.role?.id)
    if (role === 4) {
      return navigateTo('/user')
    }
    if ([1, 2, 3].includes(role)) {
      return navigateTo('/admin')
    }
    return navigateTo('/')
  }

  // Redirect langsung ke login SSO UNPAM
  const redirectToSso = async () => {
    let ssoUrl = config.public.ssoLoginUrl as string
    try {
      const res: any = await $fetch(`${config.public.apiBase}/sso/url`)
      if (res?.url) {
        ssoUrl = res.url
      }
    } catch (e) {
      // Gunakan default fallback jika backend endpoint belum siap
    }

    if (import.meta.client) {
      window.location.assign(ssoUrl)
    }
  }

  // Login dengan Access Token yang didapat dari callback backend
  const loginWithAuthToken = async (authToken: string, explicitRole?: number | string | null) => {
    token.value = authToken
    const profile = await fetchUser()
    const role = explicitRole ?? profile?.role_id ?? profile?.role?.id
    return navigateByRole(role)
  }

  // Login dengan JWT Token SSO (kirim ke backend /api/loginsso untuk diverifikasi)
  const loginWithSsoJwt = async (ssoJwt: string) => {
    const res: any = await $fetch(`${config.public.apiBase}/loginsso`, {
      method: 'POST',
      body: { token: ssoJwt }
    })
    token.value = res.access_token
    await fetchUser()
    const role = Number(user.value?.role_id ?? res.data?.role_id)
    return navigateByRole(role)
  }

  // Fungsi Login Lokal / Form
  const login = async (credentials: object) => {
    const res: any = await $fetch(`${config.public.apiBase}/loginsso`, {
      method: 'POST',
      body: credentials
    })
    token.value = res.access_token
    await fetchUser()
    const role = Number(user.value?.role_id ?? res.data?.role_id ?? res.user?.role_id)
    return navigateByRole(role)
  }

  // Fungsi Register
  const register = async (payload: object) => {
    const res: any = await $fetch(`${config.public.apiBase}/register`, {
      method: 'POST',
      body: payload
    })
    token.value = res.access_token
    await fetchUser()
    return navigateTo('/admin')
  }

  // Fungsi Logout
  const logout = async () => {
    try {
      await $fetch(`${config.public.apiBase}/logout`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` }
      })
    } finally {
      token.value = null
      user.value = null
      return navigateTo('/')
    }
  }

  return {
    token,
    user,
    fetchUser,
    login,
    register,
    logout,
    hasRole,
    redirectToSso,
    loginWithAuthToken,
    loginWithSsoJwt,
    navigateByRole
  }
}