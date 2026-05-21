<template>
  <div class="settings-page">
    <div class="settings-inner">
      <!-- Header -->
      <div class="settings-header">
        <div>
          <h1 class="settings-title">{{ t('settingsPage.title') }}</h1>
          <p class="settings-sub">{{ t('settingsPage.subtitle') }}</p>
        </div>
        <span class="settings-badge">{{ profile.email }}</span>
      </div>

      <!-- Tabs -->
      <v-tabs v-model="activeTab" color="primary" density="compact" class="settings-tabs" show-arrows>
        <v-tab value="profile">{{ t('settingsPage.profileTab') }}</v-tab>
        <v-tab value="preferences">{{ t('settingsPage.preferencesTab') }}</v-tab>
        <v-tab value="notifications">{{ t('settingsPage.notificationsTab') }}</v-tab>
        <v-tab value="avatar">{{ t('settingsPage.avatarTab') }}</v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="settings-window pt-6">
        <!-- Profile tab -->
        <v-window-item value="profile">
          <div class="profile-columns">
            <div class="settings-section settings-card">
              <h3 class="section-label">{{ t('settingsPage.profileInfo') }}</h3>
              <div class="settings-grid">
                <v-text-field v-model="profile.name" :label="t('settingsPage.name')"
                  variant="outlined" prepend-inner-icon="mdi-account" hide-details="auto" />
                <v-text-field v-model="profile.username" :label="t('settingsPage.username')"
                  variant="outlined" prepend-inner-icon="mdi-at" hide-details="auto" />
                <v-text-field v-model="profile.email" :label="t('settingsPage.email')"
                  variant="outlined" prepend-inner-icon="mdi-email-outline" hide-details="auto" />
              </div>
            </div>

            <div class="settings-section settings-card">
              <h3 class="section-label">{{ t('settingsPage.security') }}</h3>
              <div class="settings-grid">
                <v-text-field v-model="profile.current_password" :label="t('settingsPage.currentPassword')"
                  type="password" variant="outlined" prepend-inner-icon="mdi-lock" hide-details="auto" />
                <v-text-field v-model="profile.password" :label="t('settingsPage.newPassword')"
                  type="password" variant="outlined" prepend-inner-icon="mdi-lock-plus" hide-details="auto" />
                <v-text-field v-model="profile.password_confirmation" :label="t('settingsPage.confirmPassword')"
                  type="password" variant="outlined" prepend-inner-icon="mdi-lock-check" hide-details="auto" />
              </div>
            </div>
          </div>
          <div class="settings-actions">
            <v-btn color="primary" :loading="savingProfile" prepend-icon="mdi-content-save" @click="saveProfile">
              {{ t('settingsPage.saveProfile') }}
            </v-btn>
          </div>
        </v-window-item>

        <!-- Preferences tab -->
        <v-window-item value="preferences">
          <div class="settings-section settings-card">
            <h3 class="section-label">{{ t('settingsPage.appearanceTitle') }}</h3>
            <div class="prefs-grid">
              <v-select :label="t('settingsPage.languageLabel')" variant="outlined"
                :model-value="language" :items="languageOptions"
                item-title="label" item-value="value"
                @update:modelValue="onLanguageChange" hide-details="auto" />
              <div class="pref-switches">
                <v-switch v-model="preferences.largeText" :label="t('settingsPage.largeText')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
                <v-switch v-model="preferences.highContrast" :label="t('settingsPage.highContrast')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
                <v-switch v-model="preferences.reducedMotion" :label="t('settingsPage.reducedMotion')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
                <v-switch v-model="preferences.compactMode" :label="t('settingsPage.compactMode')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
              </div>
            </div>
          </div>
          <div class="settings-section settings-card mt-4">
            <h3 class="section-label">{{ t('settingsPage.drawingDefaultsTitle') }}</h3>
            <div class="drawing-defaults-grid">
              <div class="pref-row">
                <span class="pref-row-label">{{ t('settingsPage.defaultBrushSize') }}: {{ preferences.defaultBrushSize }}px</span>
                <v-slider v-model="preferences.defaultBrushSize" :min="1" :max="20" :step="1"
                  hide-details density="compact" color="primary" @update:modelValue="savePreferences" />
              </div>
              <v-select v-model="preferences.defaultCanvasBg"
                :label="t('settingsPage.defaultCanvasBg')" variant="outlined"
                :items="canvasBgOptions" item-title="label" item-value="value"
                hide-details="auto" @update:modelValue="savePreferences" />
              <div class="pref-switches">
                <v-switch v-model="preferences.autoSave" :label="t('settingsPage.autoSave')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
                <v-switch v-model="preferences.showGrid" :label="t('settingsPage.showGrid')"
                  color="primary" hide-details @update:modelValue="savePreferences" />
              </div>
            </div>
          </div>
        </v-window-item>

        <!-- Notifications tab -->
        <v-window-item value="notifications">
          <div class="settings-section settings-card">
            <h3 class="section-label">{{ t('settingsPage.notificationsTitle') }}</h3>
            <div class="notif-list">
              <div class="notif-row">
                <div class="notif-info">
                  <span class="notif-label">{{ t('settingsPage.notifyVotes') }}</span>
                  <span class="notif-desc">{{ t('settingsPage.notifyVotesDesc') }}</span>
                </div>
                <v-switch v-model="notifications.votes" color="primary" hide-details @update:modelValue="saveNotifications" />
              </div>
              <div class="notif-row">
                <div class="notif-info">
                  <span class="notif-label">{{ t('settingsPage.notifyComments') }}</span>
                  <span class="notif-desc">{{ t('settingsPage.notifyCommentsDesc') }}</span>
                </div>
                <v-switch v-model="notifications.comments" color="primary" hide-details @update:modelValue="saveNotifications" />
              </div>
              <div class="notif-row">
                <div class="notif-info">
                  <span class="notif-label">{{ t('settingsPage.notifyFriendRequests') }}</span>
                  <span class="notif-desc">{{ t('settingsPage.notifyFriendRequestsDesc') }}</span>
                </div>
                <v-switch v-model="notifications.friendRequests" color="primary" hide-details @update:modelValue="saveNotifications" />
              </div>
              <div class="notif-row">
                <div class="notif-info">
                  <span class="notif-label">{{ t('settingsPage.notifyMessages') }}</span>
                  <span class="notif-desc">{{ t('settingsPage.notifyMessagesDesc') }}</span>
                </div>
                <v-switch v-model="notifications.messages" color="primary" hide-details @update:modelValue="saveNotifications" />
              </div>
              <div class="notif-row">
                <div class="notif-info">
                  <span class="notif-label">{{ t('settingsPage.notifyWeeklyTheme') }}</span>
                  <span class="notif-desc">{{ t('settingsPage.notifyWeeklyThemeDesc') }}</span>
                </div>
                <v-switch v-model="notifications.weeklyTheme" color="primary" hide-details @update:modelValue="saveNotifications" />
              </div>
            </div>
          </div>
        </v-window-item>

        <!-- Avatar tab -->
        <v-window-item value="avatar">
          <div class="settings-section settings-card">
            <h3 class="section-label">{{ t('settingsPage.avatarTitle') }}</h3>

            <div class="avatar-tools">
              <div class="avatar-tools-row">
                <button class="avatar-tool-btn" :class="{ active: avatarTool === 'pen' }" @click="avatarTool = 'pen'" :title="t('drawPage.pen')">
                  <v-icon size="16">mdi-pencil</v-icon>
                </button>
                <button class="avatar-tool-btn" :class="{ active: avatarTool === 'eraser' }" @click="avatarTool = 'eraser'" :title="t('drawPage.eraser')">
                  <v-icon size="16">mdi-eraser</v-icon>
                </button>
                <span class="tool-label">Color</span>
                <input v-model="avatarColor" type="color" class="color-picker" />
                <button
                  v-for="preset in avatarPresets"
                  :key="preset"
                  type="button"
                  class="preset-dot"
                  :class="{ active: avatarColor === preset }"
                  :style="{ background: preset }"
                  @click="avatarColor = preset"
                />
              </div>
              <div class="avatar-tools-row">
                <button
                  v-for="brush in avatarBrushTypes"
                  :key="brush.value"
                  type="button"
                  class="avatar-tool-btn"
                  :class="{ active: avatarBrushType === brush.value }"
                  :title="brush.label"
                  @click="avatarBrushType = brush.value"
                >
                  <v-icon size="16">{{ brush.icon }}</v-icon>
                </button>
                <span class="tool-label">Brush</span>
                <v-slider
                  v-model="avatarBrush"
                  :min="1"
                  :max="20"
                  :step="1"
                  hide-details
                  density="compact"
                  color="primary"
                  class="avatar-slider"
                />
                <span class="avatar-size-preview">
                  <span
                    class="avatar-size-dot"
                    :style="{
                      width: Math.min((avatarTool === 'eraser' ? avatarBrush * 3 : avatarBrush) * 1.5, 18) + 'px',
                      height: Math.min((avatarTool === 'eraser' ? avatarBrush * 3 : avatarBrush) * 1.5, 18) + 'px',
                      background: avatarTool === 'eraser' ? 'rgba(0,0,0,0.25)' : avatarColor,
                    }"
                  />
                </span>
                <v-btn size="small" variant="text" prepend-icon="mdi-undo" @click="undoAvatar" :disabled="avatarPaths.length === 0">
                  Undo
                </v-btn>
                <v-btn size="small" variant="text" prepend-icon="mdi-redo" @click="redoAvatar" :disabled="avatarRedoStack.length === 0">
                  Redo
                </v-btn>
              </div>
            </div>

            <!-- Preview + draw button -->
            <div class="avatar-preview-row">
              <div class="avatar-preview-wrap">
                <canvas
                  ref="avatarCanvas"
                  width="420"
                  height="420"
                  class="avatar-canvas"
                  @pointerdown.prevent="startAvatarDrawing"
                  @pointermove.prevent="drawAvatar"
                  @pointerup.prevent="stopAvatarDrawing"
                  @pointerleave.prevent="stopAvatarDrawing"
                  @pointercancel.prevent="stopAvatarDrawing"
                />
                <div v-if="avatarPaths.length === 0" class="avatar-empty-hint">
                  <v-icon size="32" color="rgba(255,255,255,0.2)">mdi-account-outline</v-icon>
                </div>
              </div>
              <div class="avatar-draw-actions">
                <div class="tool-label">Draw directly on the avatar preview.</div>
                <v-btn v-if="avatarPaths.length > 0" variant="outlined" color="error"
                  prepend-icon="mdi-delete" @click="clearAvatar">
                  {{ t('settingsPage.clearAvatar') }}
                </v-btn>
              </div>
            </div>

            <div class="settings-actions">
              <div />
              <v-btn color="primary" prepend-icon="mdi-content-save" :loading="savingAvatar" @click="saveAvatar">
                {{ t('settingsPage.saveAvatar') }}
              </v-btn>
            </div>
          </div>
        </v-window-item>
      </v-window>
    </div>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="2800">
      {{ snackbar.text }}
    </v-snackbar>

  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

const PREFERENCES_KEY = 'ui_preferences'

const { t, language, setLanguage } = useI18n()
const activeTab = ref('profile')
const savingProfile = ref(false)
const savingAvatar = ref(false)

const profile = ref({
  name: '',
  username: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: '',
  avatar_drawing_data: null,
  avatar_thumbnail: null,
})

const avatarCanvas = ref(null)
const avatarCtx = ref(null)
const avatarIsDrawing = ref(false)
const avatarPaths = ref([])
const avatarRedoStack = ref([])
const avatarCurrentPath = ref([])
const avatarColor = ref('#111827')
const avatarBrush = ref(4)
const avatarTool = ref('pen')
const avatarBrushType = ref('pen')
const avatarPresets = ['#111827', '#2563eb', '#db2777', '#16a34a', '#ea580c', '#7c3aed']
const avatarBrushTypes = [
  { value: 'pen', label: 'Round', icon: 'mdi-circle' },
  { value: 'square', label: 'Square', icon: 'mdi-square' },
  { value: 'marker', label: 'Marker', icon: 'mdi-marker' },
  { value: 'spray', label: 'Spray', icon: 'mdi-spray' },
]

const preferences = ref({
  largeText: false,
  highContrast: false,
  reducedMotion: false,
  compactMode: false,
  defaultBrushSize: 4,
  defaultCanvasBg: 'white',
  autoSave: false,
  showGrid: false,
})

const NOTIFICATIONS_KEY = 'ui_notifications'

const notifications = ref({
  votes: true,
  comments: true,
  friendRequests: true,
  messages: true,
  weeklyTheme: false,
})

const canvasBgOptions = computed(() => [
  { label: t('settingsPage.canvasBgWhite'), value: 'white' },
  { label: t('settingsPage.canvasBgDark'), value: 'dark' },
  { label: t('settingsPage.canvasBgTransparent'), value: 'transparent' },
])

const languageOptions = [
  { label: 'Latviesu', value: 'lv' },
  { label: 'English', value: 'en' },
]

const snackbar = ref({
  show: false,
  text: '',
  color: 'success',
})

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

const loadUser = async () => {
  const response = await api.get('/user')
  const user = response.data.user

  profile.value.name = user.name || ''
  profile.value.username = user.username || ''
  profile.value.email = user.email || ''
  profile.value.avatar_drawing_data = user.avatar_drawing_data || null
  profile.value.avatar_thumbnail = user.avatar_thumbnail || null

  if (profile.value.avatar_drawing_data) {
    try {
      const parsed = JSON.parse(profile.value.avatar_drawing_data)
      avatarPaths.value = Array.isArray(parsed?.paths) ? parsed.paths : []
    } catch {
      avatarPaths.value = []
    }
  }

  await nextTick()
  initAvatarCanvas()
}

const saveProfile = async () => {
  savingProfile.value = true

  const payload = {
    name: profile.value.name,
    username: profile.value.username,
    email: profile.value.email,
  }

  if (profile.value.password) {
    payload.current_password = profile.value.current_password
    payload.password = profile.value.password
    payload.password_confirmation = profile.value.password_confirmation
  }

  try {
    const response = await api.put('/user', payload)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    window.dispatchEvent(new Event('user-updated'))

    profile.value.current_password = ''
    profile.value.password = ''
    profile.value.password_confirmation = ''

    showSnackbar(t('settingsPage.profileSaved'))
  } catch (error) {
    const fallback = t('settingsPage.saveFailed')
    const serverMessage = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {})?.flat()?.[0]
    showSnackbar(serverMessage || fallback, 'error')
  } finally {
    savingProfile.value = false
  }
}

const applyPreferences = () => {
  const root = document.documentElement

  root.classList.toggle('a11y-large-text', preferences.value.largeText)
  root.classList.toggle('a11y-high-contrast', preferences.value.highContrast)
  root.classList.toggle('a11y-reduced-motion', preferences.value.reducedMotion)
  root.classList.toggle('ui-compact', preferences.value.compactMode)
}

const loadPreferences = () => {
  const saved = localStorage.getItem(PREFERENCES_KEY)
  if (!saved) {
    applyPreferences()
    return
  }

  try {
    const parsed = JSON.parse(saved)
    preferences.value = {
      ...preferences.value,
      ...parsed,
    }
  } catch {
    // Keep defaults if parsing fails.
  }

  applyPreferences()
}

const savePreferences = () => {
  localStorage.setItem(PREFERENCES_KEY, JSON.stringify(preferences.value))
  applyPreferences()
  showSnackbar(t('settingsPage.preferencesSaved'))
}

const initAvatarCanvas = () => {
  if (!avatarCanvas.value) return

  avatarCtx.value = avatarCanvas.value.getContext('2d')
  clearAvatarBackground()
  redrawAvatarCanvas()
}

const clearAvatarBackground = () => {
  if (!avatarCtx.value || !avatarCanvas.value) return

  avatarCtx.value.fillStyle = '#FFFFFF'
  avatarCtx.value.fillRect(0, 0, avatarCanvas.value.width, avatarCanvas.value.height)
}

const redrawAvatarCanvas = () => {
  if (!avatarCtx.value) return

  clearAvatarBackground()

  avatarPaths.value.forEach((path) => {
    if (!Array.isArray(path.points) || path.points.length < 1) return

    const brushType = path.brushType || 'pen'

    if (brushType === 'spray') {
      avatarCtx.value.globalAlpha = 0.8
      avatarCtx.value.fillStyle = path.color
      path.points.forEach((point) => {
        if (!point.dots) return
        point.dots.forEach((dot) => {
          avatarCtx.value.beginPath()
          avatarCtx.value.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
          avatarCtx.value.fill()
        })
      })
      avatarCtx.value.globalAlpha = 1
      return
    }

    avatarCtx.value.strokeStyle = path.color
    avatarCtx.value.lineWidth = brushType === 'marker' ? path.width * 2.5 : path.width
    avatarCtx.value.lineCap = brushType === 'square' ? 'square' : 'round'
    avatarCtx.value.lineJoin = brushType === 'square' ? 'miter' : 'round'
    avatarCtx.value.globalAlpha = brushType === 'marker' ? 0.35 : 1
    avatarCtx.value.beginPath()

    path.points.forEach((point, index) => {
      if (index === 0) {
        avatarCtx.value.moveTo(point.x, point.y)
      } else {
        avatarCtx.value.lineTo(point.x, point.y)
      }
    })

    avatarCtx.value.stroke()
    avatarCtx.value.globalAlpha = 1
  })
}

const getAvatarCoordinates = (event) => {
  if (!avatarCanvas.value) return { x: 0, y: 0 }

  const rect = avatarCanvas.value.getBoundingClientRect()
  const source = event.touches?.[0] || event
  return {
    x: source.clientX - rect.left,
    y: source.clientY - rect.top,
  }
}

const startAvatarDrawing = (event) => {
  if (!avatarCtx.value) return

  avatarIsDrawing.value = true
  const { x, y } = getAvatarCoordinates(event)

  avatarCurrentPath.value = [{ x, y }]
  avatarPaths.value.push({
    color: avatarTool.value === 'eraser' ? '#FFFFFF' : avatarColor.value,
    width: avatarTool.value === 'eraser' ? avatarBrush.value * 3 : avatarBrush.value,
    brushType: avatarTool.value === 'eraser' ? 'eraser' : avatarBrushType.value,
    points: avatarCurrentPath.value,
  })
  avatarRedoStack.value = []
}

const drawAvatar = (event) => {
  if (!avatarIsDrawing.value || !avatarCtx.value) return

  const { x, y } = getAvatarCoordinates(event)
  const strokeColor = avatarTool.value === 'eraser' ? '#FFFFFF' : avatarColor.value
  const strokeWidth = avatarTool.value === 'eraser' ? avatarBrush.value * 3 : avatarBrush.value
  const brushType = avatarTool.value === 'eraser' ? 'eraser' : avatarBrushType.value

  if (brushType === 'spray') {
    const density = 20
    const radius = strokeWidth * 3
    const dots = []
    avatarCtx.value.fillStyle = strokeColor
    avatarCtx.value.globalAlpha = 0.8
    for (let index = 0; index < density; index += 1) {
      const angle = Math.random() * Math.PI * 2
      const distance = Math.sqrt(Math.random()) * radius
      const dot = {
        x: x + distance * Math.cos(angle),
        y: y + distance * Math.sin(angle),
        r: Math.max(0.5, strokeWidth * 0.18),
      }
      dots.push(dot)
      avatarCtx.value.beginPath()
      avatarCtx.value.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
      avatarCtx.value.fill()
    }
    avatarCtx.value.globalAlpha = 1
    avatarCurrentPath.value.push({ x, y, dots })
    return
  }

  avatarCurrentPath.value.push({ x, y })

  avatarCtx.value.strokeStyle = strokeColor
  avatarCtx.value.lineWidth = brushType === 'marker' ? strokeWidth * 2.5 : strokeWidth
  avatarCtx.value.lineCap = brushType === 'square' ? 'square' : 'round'
  avatarCtx.value.lineJoin = brushType === 'square' ? 'miter' : 'round'
  avatarCtx.value.globalAlpha = brushType === 'marker' ? 0.35 : 1

  const points = avatarCurrentPath.value
  const previous = points[points.length - 2]

  if (!previous) return

  avatarCtx.value.beginPath()
  avatarCtx.value.moveTo(previous.x, previous.y)
  avatarCtx.value.lineTo(x, y)
  avatarCtx.value.stroke()
  avatarCtx.value.globalAlpha = 1
}

const stopAvatarDrawing = () => {
  avatarIsDrawing.value = false
}

const undoAvatar = () => {
  if (avatarPaths.value.length === 0) return
  avatarRedoStack.value.push(avatarPaths.value.pop())
  redrawAvatarCanvas()
}

const redoAvatar = () => {
  if (avatarRedoStack.value.length === 0) return
  avatarPaths.value.push(avatarRedoStack.value.pop())
  redrawAvatarCanvas()
}

const clearAvatar = () => {
  avatarPaths.value = []
  avatarRedoStack.value = []
  avatarCurrentPath.value = []
  redrawAvatarCanvas()
}

const saveAvatar = async () => {
  if (!avatarCanvas.value) return

  savingAvatar.value = true

  // Guarantee white background pixels are in place before capturing
  redrawAvatarCanvas()

  const payload = {
    name: profile.value.name,
    username: profile.value.username,
    email: profile.value.email,
    avatar_drawing_data: JSON.stringify({ paths: avatarPaths.value }),
    avatar_thumbnail: avatarCanvas.value.toDataURL('image/png', 0.75),
  }

  try {
    const response = await api.put('/user', payload)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    window.dispatchEvent(new Event('user-updated'))
    showSnackbar(t('settingsPage.avatarSaved'))
  } catch (error) {
    const fallback = t('settingsPage.saveFailed')
    const serverMessage = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {})?.flat()?.[0]
    showSnackbar(serverMessage || fallback, 'error')
  } finally {
    savingAvatar.value = false
  }
}

const loadNotifications = () => {
  const saved = localStorage.getItem(NOTIFICATIONS_KEY)
  if (!saved) return
  try {
    notifications.value = { ...notifications.value, ...JSON.parse(saved) }
  } catch {
    // keep defaults
  }
}

const saveNotifications = () => {
  localStorage.setItem(NOTIFICATIONS_KEY, JSON.stringify(notifications.value))
  showSnackbar(t('settingsPage.preferencesSaved'))
}

const onLanguageChange = (value) => {
  setLanguage(value)
  savePreferences()
}

onMounted(async () => {
  loadPreferences()
  loadNotifications()
  try {
    await loadUser()
  } catch {
    showSnackbar(t('settingsPage.saveFailed'), 'error')
  }
})

watch(activeTab, async (value) => {
  if (value !== 'avatar') return
  await nextTick()
  initAvatarCanvas()
})
</script>

<style scoped>
.settings-page {
  min-height: calc(100vh - 72px);
  background:
    radial-gradient(circle at 10% -20%, rgba(124,58,237,0.12), transparent 45%),
    radial-gradient(circle at 90% 120%, rgba(236,72,153,0.08), transparent 40%),
    var(--c-bg);
  padding: 28px 16px 36px;
}

.settings-inner {
  max-width: 980px;
  margin: 0 auto;
}

.settings-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 18px;
}

.settings-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--c-text);
}

.settings-sub {
  font-size: 0.85rem;
  color: var(--c-muted);
  margin-top: 2px;
}

.settings-badge {
  font-size: 0.78rem;
  color: var(--c-muted);
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--c-border-md);
  border-radius: 20px;
  padding: 4px 12px;
}

.settings-tabs {
  margin-bottom: 0;
  border-bottom: 1px solid var(--c-border);
}

.settings-window {
  margin-top: 4px;
}

.settings-section { margin-bottom: 16px; }

.settings-card {
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  padding: 16px;
}

.profile-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.section-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--c-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 12px;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
}

.settings-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-top: 16px;
  flex-wrap: wrap;
  gap: 10px;
}

.prefs-grid {
  display: grid;
  gap: 16px;
}

.pref-switches {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 4px;
}

/* Avatar tab */
.avatar-tools {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  padding: 12px 16px;
  margin-bottom: 16px;
}

.avatar-tools-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.avatar-tool-btn {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid var(--c-border-md);
  background: rgba(255,255,255,0.04);
  color: var(--c-text);
  cursor: pointer;
  transition: background 150ms ease, border-color 150ms ease, transform 150ms ease;
}

.avatar-tool-btn:hover {
  background: rgba(255,255,255,0.08);
  border-color: rgba(255,255,255,0.28);
}

.avatar-tool-btn.active {
  background: rgba(124,58,237,0.18);
  border-color: rgba(124,58,237,0.5);
  color: #d8b4fe;
}

.avatar-tools-row:last-child { margin-bottom: 0; }

.tool-label {
  font-size: 0.75rem;
  color: var(--c-muted);
  font-weight: 600;
  white-space: nowrap;
}

.color-picker {
  width: 30px;
  height: 26px;
  border: 1px solid var(--c-border-md);
  border-radius: 6px;
  padding: 0;
  cursor: pointer;
  background: none;
}

.preset-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  outline: none;
  transition: border-color 150ms, transform 150ms;
}

.preset-dot.active,
.preset-dot:hover {
  border-color: var(--c-text);
  transform: scale(1.15);
}

.avatar-slider { flex: 1; min-width: 100px; }

.avatar-size-preview {
  width: 26px;
  height: 26px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.avatar-size-dot {
  border-radius: 999px;
  display: inline-block;
}

.avatar-preview-row {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.avatar-preview-wrap {
  position: relative;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid rgba(124,58,237,0.35);
  background: #fff;
  flex-shrink: 0;
}

.avatar-empty-hint {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.04);
}

.avatar-canvas {
  width: 100%;
  height: 100%;
  display: block;
  cursor: crosshair;
  touch-action: none;
}

.avatar-draw-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
}

.avatar-canvas-wrap {
  display: flex;
  width: 100%;
  max-width: 420px;
  background: #fff;
  border-radius: var(--r-md);
  overflow: hidden;
  border: 1px solid var(--c-border-md);
}

/* ── Vuetify field overrides for dark theme ── */
:deep(.v-field__outline__start),
:deep(.v-field__outline__end),
:deep(.v-field__outline__notch) {
  border-color: var(--c-border-md) !important;
  opacity: 1 !important;
}

:deep(.v-field:hover .v-field__outline__start),
:deep(.v-field:hover .v-field__outline__end),
:deep(.v-field:hover .v-field__outline__notch) {
  border-color: rgba(255,255,255,0.28) !important;
}

:deep(.v-field--focused .v-field__outline__start),
:deep(.v-field--focused .v-field__outline__end),
:deep(.v-field--focused .v-field__outline__notch) {
  border-color: var(--c-accent) !important;
}

:deep(.v-field__input),
:deep(.v-label) {
  color: var(--c-text-dim) !important;
}

:deep(.v-field--focused .v-label) {
  color: var(--c-accent) !important;
}

:deep(.v-field__prepend-inner .v-icon) {
  color: var(--c-muted) !important;
  opacity: 1;
}

:deep(.v-tabs .v-tab) {
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0;
  text-transform: none;
  min-width: 0;
  padding: 0 20px;
}

/* Drawing defaults */
.drawing-defaults-grid {
  display: grid;
  gap: 16px;
}

.pref-row {
  display: grid;
  gap: 4px;
}

.pref-row-label {
  font-size: 0.78rem;
  color: var(--c-muted);
  font-weight: 600;
}

/* Notifications tab */
.notif-list {
  display: flex;
  flex-direction: column;
}

.notif-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid var(--c-border);
}

.notif-row:last-child {
  border-bottom: none;
}

.notif-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
}

.notif-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--c-text);
}

.notif-desc {
  font-size: 0.78rem;
  color: var(--c-muted);
}

@media (max-width: 959px) {
  .profile-columns {
    grid-template-columns: 1fr;
  }

  .settings-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767px) {
  .settings-page {
    padding: 14px 10px 22px;
  }

  .settings-title {
    font-size: 1.2rem;
  }

  .settings-sub {
    font-size: 0.8rem;
  }

  .settings-badge {
    width: 100%;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .settings-card {
    padding: 12px;
    border-radius: 12px;
  }

  .settings-tabs {
    border-bottom: none;
    margin: 0 -2px;
  }

  :deep(.v-tabs) {
    overflow-x: auto;
    scrollbar-width: none;
  }

  :deep(.v-tabs::-webkit-scrollbar) {
    display: none;
  }

  :deep(.v-slide-group__content) {
    gap: 6px;
    padding-bottom: 4px;
  }

  :deep(.v-tabs .v-tab) {
    min-width: max-content;
    padding: 0 14px;
    height: 36px;
    border-radius: 999px;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--c-border);
  }

  .notif-row {
    align-items: flex-start;
    padding: 12px 0;
  }

  .avatar-preview-row {
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .avatar-preview-wrap {
    width: 180px;
    height: 180px;
  }

  .avatar-draw-actions {
    width: 100%;
  }

  .avatar-draw-actions :deep(.v-btn) {
    width: 100%;
  }

  .settings-actions {
    margin-top: 14px;
    justify-content: stretch;
  }

  .settings-actions :deep(.v-btn) {
    width: 100%;
  }
}
</style>
