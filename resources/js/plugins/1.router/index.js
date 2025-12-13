import { setupLayouts } from 'virtual:meta-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    ...setupLayouts(routes),
  ],
})


router.beforeEach((to, from, next) => {
  const isLoggedIn = !!localStorage.getItem('accessToken')
  const userData = JSON.parse(localStorage.getItem('userData') || '{}')
  const userRole = userData.role

  const publicPages = ['/login', '/register', '/forgot-password', 'login', 'register']
  const authRequired = !publicPages.includes(to.name) && !publicPages.includes(to.path)

  if (authRequired && !isLoggedIn) {
    return next({ path: '/login' })
  }

  if (isLoggedIn) {
    
    if (userRole === 'customer') {
      localStorage.removeItem('accessToken')
      localStorage.removeItem('userData')
      
      return next({ path: '/login' })
    }

    if (to.path === '/login' || to.name === 'login') {
      return userRole === 'kurir' 
        ? next({ path: '/couriers/dashboard' }) 
        : next({ path: '/' })
    }

    if (userRole === 'kurir' && !to.path.startsWith('/couriers')) {
      return next({ path: '/couriers/dashboard' })
    }
    if (userRole === 'admin' && to.path.startsWith('/couriers/')) {
      return next({ path: '/' })
    }
  }

  next()
})

export default function (app) {
  app.use(router)
}

export { router }
