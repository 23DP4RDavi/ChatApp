<template>
  <v-app-bar elevation="0" class="app-bar" height="72">
    <!-- Logo -->
    <v-app-bar-title class="logo-title" @click="navigateTo('/')" style="cursor:pointer; max-width:180px;">
      <div class="d-flex align-center gap-2">
        <v-icon size="20" color="primary">mdi-palette</v-icon>
        <span class="logo-text">DoodleVerse</span>
      </div>
    </v-app-bar-title>

    <template #append>
      <div class="desktop-nav d-none d-md-flex align-center gap-1">
        <v-btn variant="text" size="small" class="nav-btn" :class="{ 'nav-btn--active': isActive('/') }" @click="navigateTo('/')">
          <v-icon size="16" class="mr-1">mdi-home-outline</v-icon>
          {{ t('common.home') }}
        </v-btn>
        <v-btn variant="text" size="small" class="nav-btn" :class="{ 'nav-btn--active': isActive('/gallery') }" @click="navigateTo('/gallery')">
          <v-icon size="16" class="mr-1">mdi-image-multiple-outline</v-icon>
          {{ t('common.gallery') }}
        </v-btn>
        <v-btn variant="text" size="small" class="nav-btn" :class="{ 'nav-btn--active': isActive('/messages') }" @click="handleMessagesClick">
          <v-icon size="16" class="mr-1">mdi-message-outline</v-icon>
          {{ t('common.messages') }}
        </v-btn>
        <v-btn variant="text" size="small" class="nav-btn" :class="{ 'nav-btn--active': isActive('/draw') }" @click="handleDrawClick">
          <v-icon size="16" class="mr-1">mdi-draw</v-icon>
          {{ t('common.draw') }}
        </v-btn>

        <v-divider vertical class="mx-2 nav-divider" />

        <v-btn variant="text" icon size="small" class="icon-btn" @click="toggleLanguage">
          <v-icon size="18">mdi-translate</v-icon>
        </v-btn>

        <template v-if="user">
          <v-menu
            v-model="notificationsMenu"
            :close-on-content-click="false"
            location="bottom end"
            @update:modelValue="onNotificationsMenuToggle"
          >
            <template #activator="{ props }">
              <v-badge
                :content="notificationCounts.total_unread"
                :model-value="notificationCounts.total_unread > 0"
                color="error"
                offset-x="2"
                offset-y="2"
              >
                <v-btn v-bind="props" icon variant="text" size="small" class="icon-btn">
                  <v-icon size="20">mdi-bell-outline</v-icon>
                </v-btn>
              </v-badge>
            </template>
            <v-card min-width="320" max-width="400" class="notif-dropdown">
              <div class="notif-header pa-3 d-flex align-center justify-space-between">
                <span class="text-body-2 font-weight-semibold">{{ t('common.notifications') }}</span>
                <v-chip v-if="notificationCounts.total_unread > 0" size="x-small" color="error">
                  {{ notificationCounts.total_unread }}
                </v-chip>
              </div>
              <v-divider />
              <v-list v-if="notifications.length > 0" class="py-0 notif-list">
                <v-list-item
                  v-for="notification in notifications"
                  :key="notification.id"
                  @click="openNotification(notification)"
                  class="notif-item"
                  density="compact"
                >
                  <template #prepend>
                    <v-icon size="16" :icon="notificationIcon(notification.type)" color="primary" />
                  </template>
                  <v-list-item-title class="text-body-2">{{ notification.title }}</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">{{ notification.text }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
              <div v-else class="text-center text-caption pa-4" style="color: var(--c-muted)">
                {{ t('header.allCaughtUp') }}
              </div>
            </v-card>
          </v-menu>

          <v-menu location="bottom end">
            <template #activator="{ props }">
              <v-btn variant="text" v-bind="props" class="user-btn ml-2">
                <v-avatar size="38" class="mr-2">
                  <v-img v-if="user.avatar_thumbnail" :src="user.avatar_thumbnail" cover />
                  <v-icon v-else size="34">mdi-account-circle</v-icon>
                </v-avatar>
                <span class="user-name-text">{{ user.name }}</span>
                <v-icon size="14" class="ml-1 chevron-icon">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="180" class="user-menu">
              <div class="user-menu-header pa-3">
                <div class="text-body-2 font-weight-semibold">{{ user.name }}</div>
                <div class="text-caption" style="color: var(--c-muted)">{{ user.username ? '@' + user.username : user.email }}</div>
              </div>
              <v-divider />
              <v-list-item prepend-icon="mdi-cog-outline" @click="handleSettingsClick">
                <v-list-item-title>{{ t('common.settings') }}</v-list-item-title>
              </v-list-item>
              <v-list-item v-if="user.is_admin" prepend-icon="mdi-shield-crown-outline" @click="navigateTo('/admin')">
                <v-list-item-title>Admin Panel</v-list-item-title>
              </v-list-item>
              <v-divider />
              <v-list-item prepend-icon="mdi-logout" @click="logout" class="logout-item">
                <v-list-item-title>{{ t('common.logout') }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>

        <v-btn
          v-else
          variant="flat"
          color="primary"
          size="small"
          rounded="lg"
          @click="navigateTo('/auth')"
          class="ml-2"
        >
          {{ t('common.login') }}
        </v-btn>
      </div>

      <v-app-bar-nav-icon class="d-flex d-md-none" @click="drawer = !drawer" />
    </template>
  </v-app-bar>

  <v-navigation-drawer v-model="drawer" temporary location="right" width="256" class="mobile-drawer">
    <!-- Brand header -->
    <div class="drawer-brand">
      <span class="drawer-logo-text">🎨 DoodleVerse</span>
      <span class="drawer-tagline">Where doodles come alive</span>
    </div>

    <div v-if="user" class="drawer-user pa-4 d-flex align-center">
      <v-avatar size="40" class="mr-3">
        <v-img v-if="user.avatar_thumbnail" :src="user.avatar_thumbnail" cover />
        <v-icon v-else size="36">mdi-account-circle</v-icon>
      </v-avatar>
      <div>
        <div class="text-body-2 font-weight-semibold">{{ user.name }}</div>
        <div class="text-caption" style="color: var(--c-muted)">{{ user.email }}</div>
      </div>
    </div>
    <v-divider v-if="user" />

    <v-list density="compact" nav class="mt-1">
      <v-list-item prepend-icon="mdi-home-outline" :title="t('common.home')"
        @click="navigateTo('/'); drawer = false" />
      <v-list-item prepend-icon="mdi-image-multiple-outline" :title="t('common.gallery')"
        @click="navigateTo('/gallery'); drawer = false" />
      <v-list-item prepend-icon="mdi-message-outline" :title="t('common.messages')"
        @click="handleMessagesClick(); drawer = false" />
      <v-list-item prepend-icon="mdi-draw" :title="t('common.draw')"
        @click="handleDrawClick(); drawer = false" />

      <v-divider class="my-2" />

      <v-list-item prepend-icon="mdi-translate"
        :title="language === 'lv' ? t('common.english') : t('common.latvian')"
        @click="toggleLanguage" />

      <template v-if="user">
        <v-list-item prepend-icon="mdi-bell-outline" :title="t('common.notifications')"
          @click="handleNotificationsClick(); drawer = false">
          <template #append>
            <v-badge v-if="notificationCounts.total_unread > 0"
              :content="notificationCounts.total_unread" color="error" />
          </template>
        </v-list-item>
        <v-list-item prepend-icon="mdi-cog-outline" :title="t('common.settings')"
          @click="handleSettingsClick(); drawer = false" />
        <v-list-item v-if="user.is_admin" prepend-icon="mdi-shield-crown-outline" title="Admin Panel"
          @click="navigateTo('/admin'); drawer = false" />
        <v-divider class="my-2" />
        <v-list-item prepend-icon="mdi-logout" :title="t('common.logout')"
          @click="logout" class="logout-item" />
      </template>
      <v-list-item v-else prepend-icon="mdi-login" :title="t('common.login')"
        @click="navigateTo('/auth'); drawer = false" />
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

const router = useRouter()
const route = useRoute()
const { t, language, toggleLanguage } = useI18n()
const drawer = ref(false)
const user = ref(null)
const notifications = ref([])
const notificationCounts = ref({
  likes: 0,
  friend_requests: 0,
  friend_accepts: 0,
  messages: 0,
  total_unread: 0,
})
const notificationsMenu = ref(false)

let notificationsInterval = null
let lastSeenTimestamp = null

const handleUserUpdated = () => {
  const wasLoggedIn = !!user.value
  loadUser()
  refreshUserFromApi()
  if (!wasLoggedIn && user.value) {
    loadLastSeen()
    fetchNotifications()
    startNotificationsPolling()
    startPresencePulse()
  }
}

const getLastSeenStorageKey = () => {
  if (!user.value?.id) return null
  return `notifications_last_seen_${user.value.id}`
}

const loadLastSeen = () => {
  const key = getLastSeenStorageKey()
  if (!key) return
  lastSeenTimestamp = localStorage.getItem(key)
}

const saveLastSeen = () => {
  const key = getLastSeenStorageKey()
  if (!key) return
  const nowIso = new Date().toISOString()
  localStorage.setItem(key, nowIso)
  lastSeenTimestamp = nowIso
}

const resetNotificationsState = () => {
  notifications.value = []
  notificationCounts.value = {
    likes: 0,
    friend_requests: 0,
    friend_accepts: 0,
    messages: 0,
    total_unread: 0,
  }
}

const refreshUserFromApi = async () => {
  const token = localStorage.getItem('token')
  if (!token) return

  try {
    const response = await api.get('/user')
    const freshUser = response.data?.user || null
    if (!freshUser) return

    user.value = freshUser
    localStorage.setItem('user', JSON.stringify(freshUser))
  } catch {
    // Keep local fallback when refresh fails.
  }
}

onMounted(() => {
  loadUser()
  refreshUserFromApi()
  window.addEventListener('user-updated', handleUserUpdated)
  if (user.value) {
    loadLastSeen()
    fetchNotifications()
    startNotificationsPolling()
    startPresencePulse()
  }
})

onUnmounted(() => {
  window.removeEventListener('user-updated', handleUserUpdated)
  stopNotificationsPolling()
  stopPresencePulse()
})

watch(() => route.fullPath, () => {
  const wasLoggedIn = !!user.value
  loadUser()
  refreshUserFromApi()

  if (!wasLoggedIn && user.value) {
    loadLastSeen()
    fetchNotifications()
    startNotificationsPolling()
    startPresencePulse()
  }

  if (wasLoggedIn && !user.value) {
    stopNotificationsPolling()
    stopPresencePulse()
    resetNotificationsState()
  }
})

const loadUser = () => {
  const userData = localStorage.getItem('user')
  if (!userData) {
    user.value = null
    return
  }

  if (userData) {
    try {
      user.value = JSON.parse(userData)
    } catch (e) {
      console.error('Error parsing user data:', e)
    }
  }
}

const fetchNotifications = async () => {
  const token = localStorage.getItem('token')
  if (!token || !user.value) return

  try {
    const params = {}
    if (lastSeenTimestamp) {
      params.since = lastSeenTimestamp
    }

    const response = await api.get('/notifications', { params })
    notifications.value = response.data?.data?.notifications ?? []
    notificationCounts.value = response.data?.data?.counts ?? notificationCounts.value
  } catch (error) {
    console.error('Failed to load notifications:', error)
  }
}

const startNotificationsPolling = () => {
  stopNotificationsPolling()
  notificationsInterval = setInterval(fetchNotifications, 15000)
}

const stopNotificationsPolling = () => {
  if (notificationsInterval) {
    clearInterval(notificationsInterval)
    notificationsInterval = null
  }
}

// ── Presence pulse ────────────────────────────────────────────────────────────
let presenceInterval = null

const pulse = () => {
  if (user.value) api.post('/presence/pulse').catch(() => {})
}

const startPresencePulse = () => {
  stopPresencePulse()
  pulse()
  presenceInterval = setInterval(pulse, 60000)
}

const stopPresencePulse = () => {
  if (presenceInterval) {
    clearInterval(presenceInterval)
    presenceInterval = null
  }
}

const navigateTo = async (path) => {
  notificationsMenu.value = false
  drawer.value = false

  if (route.fullPath === path || route.path === path) {
    return
  }

  try {
    await router.push(path)
  } catch (error) {
    console.error('Navigation failed:', error)
  }
}

const notificationIcon = (type) => {
  if (type === 'like') return 'mdi-heart'
  if (type === 'friend_request') return 'mdi-account-plus'
  if (type === 'friend_accept') return 'mdi-account-check'
  if (type === 'message') return 'mdi-message'
  return 'mdi-bell'
}

const openNotification = (notification) => {
  if (notification.type === 'message' && notification.meta?.conversation_id) {
    navigateTo(`/messages/${notification.meta.conversation_id}`)
  } else if (notification.type === 'friend_request' || notification.type === 'friend_accept') {
    navigateTo('/friends')
  } else if (notification.type === 'like') {
    navigateTo('/gallery')
  }
}

const markNotificationsAsRead = async () => {
  saveLastSeen()
  notificationCounts.value = {
    ...notificationCounts.value,
    total_unread: 0,
  }
}

const onNotificationsMenuToggle = async (isOpen) => {
  if (isOpen) {
    await fetchNotifications()
    await markNotificationsAsRead()
  }
}

const handleNotificationsClick = async () => {
  if (!user.value) return

  notificationsMenu.value = true
  drawer.value = false
}

const handleDrawClick = () => {
  navigateTo('/draw')
}

const handleFriendsClick = () => {
  navigateTo('/friends')
}

const handleMessagesClick = () => {
  navigateTo('/messages')
}

const handleSettingsClick = () => {
  navigateTo('/settings')
}

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

const logout = () => {
  stopNotificationsPolling()
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  user.value = null
  resetNotificationsState()
  drawer.value = false
  navigateTo('/')
}
</script>

<style scoped>
/* AppHeader */
.app-bar {
  background: rgba(11, 12, 16, 0.9) !important;
  backdrop-filter: blur(18px) saturate(1.6) !important;
  -webkit-backdrop-filter: blur(18px) saturate(1.6) !important;
  border-bottom: none !important;
  overflow: visible !important;
  box-shadow: 0 1px 24px rgba(0, 0, 0, 0.6) !important;
}

.app-bar::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(
    90deg,
    #7c3aed 0%,
    #ec4899 20%,
    #f59e0b 40%,
    #10b981 60%,
    #3b82f6 80%,
    #7c3aed 100%
  );
  background-size: 300% 100%;
  animation: header-stripe 7s linear infinite;
  pointer-events: none;
}

@keyframes header-stripe {
  0%   { background-position: 0%   50%; }
  100% { background-position: 300% 50%; }
}

.logo-text {
  font-family: 'Nunito', system-ui, sans-serif;
  font-weight: 900;
  font-size: 1.1rem;
  letter-spacing: -0.02em;
  background: linear-gradient(135deg, #a78bfa 0%, #c084fc 50%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 8px rgba(167, 139, 250, 0.35));
}

.desktop-nav {
  gap: 2px;
}

.nav-btn {
  font-family: 'Nunito', system-ui, sans-serif !important;
  font-weight: 600 !important;
  font-size: 0.8rem !important;
  color: var(--c-text-dim) !important;
  text-transform: none !important;
  border-radius: var(--r-sm) !important;
  padding: 0 10px !important;
  height: 34px !important;
  position: relative !important;
  transition: background 150ms ease, color 150ms ease !important;
}

.nav-btn:hover {
  background: rgba(124, 58, 237, 0.1) !important;
  color: var(--c-text) !important;
}

.nav-btn--active {
  color: #c4b5fd !important;
  background: var(--c-accent-soft) !important;
}

.nav-btn--active::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 50%;
  transform: translateX(-50%);
  width: 18px;
  height: 2px;
  background: linear-gradient(90deg, #a78bfa, #ec4899);
  border-radius: 2px;
}

.icon-btn {
  color: var(--c-muted) !important;
  border-radius: var(--r-sm) !important;
  transition: background 150ms ease, color 150ms ease !important;
}

.icon-btn:hover {
  background: rgba(124, 58, 237, 0.1) !important;
  color: var(--c-text) !important;
}

.nav-divider {
  opacity: 0.3;
  height: 20px !important;
  align-self: center;
}

.notif-dropdown {
  background-color: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
  border-radius: var(--r-md) !important;
}

.notif-header {
  border-bottom: none;
}

.notif-list {
  max-height: 320px;
  overflow-y: auto;
}

.notif-item {
  cursor: pointer;
  border-radius: var(--r-sm) !important;
}

.notif-item:hover {
  background: var(--c-accent-soft) !important;
}

.user-btn {
  text-transform: none !important;
  font-family: 'Nunito', system-ui, sans-serif !important;
  font-weight: 600 !important;
  font-size: 0.85rem !important;
  color: var(--c-text-dim) !important;
  border-radius: var(--r-sm) !important;
  height: 40px !important;
  padding: 0 10px !important;
  letter-spacing: 0 !important;
  transition: background 150ms ease, color 150ms ease !important;
}

.user-btn:hover {
  background: rgba(124, 58, 237, 0.1) !important;
  color: var(--c-text) !important;
}

.user-name-text {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chevron-icon {
  opacity: 0.45;
}

.user-menu-header {
  background: transparent;
}

.user-menu {
  background-color: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
  border-radius: var(--r-md) !important;
}

.logout-item:hover {
  color: var(--c-error) !important;
}

/* Mobile drawer */
.mobile-drawer {
  background: linear-gradient(180deg, #0c0d12 0%, #16171d 100%) !important;
  border-left: 1px solid rgba(124, 58, 237, 0.25) !important;
}

.drawer-brand {
  padding: 20px 16px 14px;
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.18) 0%, rgba(236, 72, 153, 0.1) 100%);
  border-bottom: 1px solid var(--c-border);
}

.drawer-logo-text {
  font-family: 'Nunito', system-ui, sans-serif;
  font-weight: 900;
  font-size: 1.25rem;
  letter-spacing: -0.02em;
  background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: block;
  margin-bottom: 3px;
}

.drawer-tagline {
  font-size: 0.72rem;
  color: var(--c-muted);
  font-weight: 500;
  display: block;
}

.drawer-user {
  background: rgba(255, 255, 255, 0.03) !important;
  border-bottom: 1px solid var(--c-border) !important;
}
</style>
