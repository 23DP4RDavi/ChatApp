import { createRouter, createWebHistory } from 'vue-router'
import Home from '@/views/Home.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/gallery',
    name: 'Gallery',
    component: () => import('@/views/Gallery.vue')
  },
  {
    path: '/friends',
    name: 'Friends',
    component: () => import('@/views/Friends.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/messages/:id?',
    name: 'Messages',
    component: () => import('@/views/Messages.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/chat',
    name: 'Chat',
    component: () => import('@/views/Chat.vue')
  },
  {
    path: '/draw',
    name: 'Draw',
    component: () => import('@/views/Draw.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/Settings.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/profile/:username',
    name: 'Profile',
    component: () => import('@/views/Profile.vue')
  },
  {
    path: '/auth',
    name: 'Auth',
    component: () => import('@/views/Auth.vue')
  },
  {
    path: '/complete-profile',
    name: 'CompleteProfile',
    component: () => import('@/views/CompleteProfile.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/invite/:token',
    name: 'Invite',
    component: () => import('@/views/Invite.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    name: 'Admin',
    component: () => import('@/views/Admin.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    redirect: '/auth'
  },
  {
    path: '/signup',
    redirect: '/auth'
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const isLazyLoadFailure = (error) => {
  const message = String(error?.message || '')
  return /Failed to fetch dynamically imported module|Importing a module script failed|Loading chunk [\d]+ failed|error loading dynamically imported module/i.test(message)
}

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  let user = null

  try {
    user = JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    user = null
  }

  const needsUsername = !!token && (!user || !String(user.username || '').trim())
  
  if (to.meta.requiresAuth && !token) {
    // Redirect to auth if trying to access protected route without token
    next('/auth')
  } else if (needsUsername && to.path !== '/complete-profile' && to.path !== '/auth') {
    next('/complete-profile')
  } else if (to.path === '/auth' && token) {
    // Redirect to home if already logged in
    next(needsUsername ? '/complete-profile' : '/')
  } else {
    next()
  }
})

router.onError((error, to) => {
  if (!isLazyLoadFailure(error) || !to?.fullPath) {
    return
  }

  window.location.assign(to.fullPath)
})

export default router
