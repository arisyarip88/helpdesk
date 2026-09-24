// middleware/role.ts
export default defineNuxtRouteMiddleware((to) => {
  const { user, hasRole } = useAuth()

  // 1. Cek apakah user sudah login
  if (!user.value) {
    return navigateTo('/login')
  }

  // 2. Ambil role yang diizinkan dari meta halaman
  const allowedRoles = to.meta.roles as string[] | undefined

  // 3. Jika halaman butuh role tertentu dan user tidak punya role tersebut
  if (allowedRoles && !hasRole(allowedRoles)) {
    return navigateTo('/unauthorized') // Halaman 403 / Ditolak
  }

  
})