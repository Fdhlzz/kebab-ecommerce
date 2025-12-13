import { setupLayouts } from 'virtual:meta-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    ...setupLayouts(routes),
  ],
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  const isLoggedIn = localStorage.getItem('accessToken')
  const userData = JSON.parse(localStorage.getItem('userData') || '{}')
  const userRole = userData.role

  const publicPages = ['login', 'register', 'forgot-password'] 
  const authRequired = !publicPages.includes(to.name)

  if (authRequired && !isLoggedIn) {
    return next({ name: 'login' })
  }

  if (isLoggedIn) {
    if (to.name === 'login') {
      return next({ path: '/' })
    }

    if (userRole === 'customer') {
      localStorage.removeItem('accessToken')
      localStorage.removeItem('userData')
      
      return next({ name: 'login' })
    }
  }

  next()
})

export default function (app) {
  app.use(router)
}

export { router }
