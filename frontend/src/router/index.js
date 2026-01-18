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
    path: '/draw',
    name: 'Draw',
    component: () => import('@/views/Draw.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/auth',
    name: 'Auth',
    component: () => import('@/views/Auth.vue')
  },
  {
    path: '/login',
    redirect: '/auth'
  },
  {
    path: '/signup',
    redirect: '/auth'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  
  if (to.meta.requiresAuth && !token) {
    // Redirect to auth if trying to access protected route without token
    next('/auth')
  } else if (to.path === '/auth' && token) {
    // Redirect to home if already logged in
    next('/')
  } else {
    next()
  }
})

export default router
