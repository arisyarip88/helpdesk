// composables/useAuth.ts
export const useAuth = () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('token', { maxAge: 60 * 60 * 24 * 7 }) // Simpan token di cookie 7 hari
  const user = useState<any>('user', () => null)
  
 

  // Ambil Data Profil User
  const fetchUser = async () => {
    if (!token.value) return
    try {
      const data: any = await $fetch(`${config.public.apiBase}/profile`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
      user.value = data.data
    } catch (err) {
      token.value = null
      user.value = null
    }
  }


// Helper cek role
const hasRole = (allowedRoles: number | string | (number | string)[]) => {
  if (!user.value) return false
  const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles]
  
  // Mengambil langsung role_id dari user (dengan fallback jika dalam bentuk object role.id)
  const userRoleId = user.value.role_id ?? user.value.role?.id

  if (userRoleId === undefined || userRoleId === null) return false

  // Konversi ke string saat pembandingan agar aman (misal: 1 dan '1' tetap dianggap sama)
  return roles.map(String).includes(String(userRoleId))
}


  // Fungsi Login
  const login = async (credentials: object) => {
    const res: any = await $fetch(`${config.public.apiBase}/loginSso`, {
      method: 'POST',
      body: credentials
    })
    token.value = res.access_token
    await fetchUser()
    // Ambil nilai role dari state user atau dari response register
  const role = Number(user.value?.role_id ?? res.user?.role_id)

  // Percabangan halaman berdasarkan role
    if (role === 4) {
      return navigateTo('/user')
    } 
    
    if ([1, 2, 3].includes(role)) {
      return navigateTo('/admin')
    }

  return navigateTo('/')
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

  return { token, user, fetchUser, login, register, logout ,hasRole}
}