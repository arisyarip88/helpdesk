// middleware/auth.ts
export default defineNuxtRouteMiddleware((to, from) => {
  const { token, user, fetchUser } = useAuth()

  if (!token.value) {
    return navigateTo('/')
  }

  if (!user.value) {
    return fetchUser().then(() => {
      if (!user.value) {
        return navigateTo('/')
      }

      return enforceAreaAccess(to, user.value.role_id)
    })
  }

  return enforceAreaAccess(to, user.value.role_id)
})

function enforceAreaAccess(to, roleId) {
  const role = Number(roleId)
  const isAdminPage = to.path === '/admin' || to.path.startsWith('/admin/')
  const isUserPage = to.path === '/user' || to.path.startsWith('/user/')

  if (isAdminPage && role === 4) {
    return navigateTo('/user')
  }

  if (isUserPage && [1, 2, 3].includes(role)) {
    return navigateTo('/admin')
  }

  if (![1, 2, 3, 4].includes(role) && (isAdminPage || isUserPage)) {
    return navigateTo('/')
  }
}