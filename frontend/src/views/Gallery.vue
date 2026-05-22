<template>
  <div class="gallery-page">
    <!-- Page header -->
    <div class="gallery-header">
      <div class="gallery-header-left">
        <h1 class="page-title">{{ t('galleryPage.title') }}</h1>
        <p class="page-sub">{{ t('galleryPage.subtitle') }}</p>
      </div>
      <div class="gallery-header-right d-none d-sm-flex align-center gap-3">
        <div class="stat-pill">
          <v-icon size="14" class="mr-1">mdi-image-outline</v-icon>
          {{ drawings.length }} {{ t('galleryPage.artworks') }}
        </div>
        <div class="stat-pill">
          <v-icon size="14" color="error" class="mr-1">mdi-heart-outline</v-icon>
          {{ totalVotes }}
        </div>
        <div class="stat-pill">
          <v-icon size="14" class="mr-1">mdi-account-outline</v-icon>
          {{ uniqueArtists }} {{ t('galleryPage.artists') }}
        </div>
      </div>
    </div>

    <div class="gallery-header-mobile-stats d-flex d-sm-none">
      <div class="stat-pill">
        <v-icon size="14" class="mr-1">mdi-image-outline</v-icon>
        {{ drawings.length }} {{ t('galleryPage.artworks') }}
      </div>
      <div class="stat-pill">
        <v-icon size="14" color="error" class="mr-1">mdi-heart-outline</v-icon>
        {{ totalVotes }}
      </div>
      <div class="stat-pill">
        <v-icon size="14" class="mr-1">mdi-account-outline</v-icon>
        {{ uniqueArtists }} {{ t('galleryPage.artists') }}
      </div>
    </div>

    <!-- Weekly theme banner -->
    <div v-if="weeklyTheme" class="theme-banner"
      :style="{ '--theme-color': weeklyTheme.color_hex }">
      <div class="theme-banner-inner">
        <div class="theme-badge">
          <span class="theme-week-label">This Week's Theme</span>
        </div>
        <div class="theme-content">
          <span class="theme-emoji">{{ weeklyTheme.emoji }}</span>
          <div class="theme-text">
            <h2 class="theme-name">{{ weeklyTheme.theme_name }}</h2>
            <p class="theme-desc">{{ weeklyTheme.description }}</p>
          </div>
        </div>
        <div class="theme-footer">
          <div class="theme-timer">
            <span class="timer-label">Next theme in</span>
            <div class="timer-blocks">
              <div class="timer-block">
                <span class="timer-val">{{ String(countdown.days).padStart(2,'0') }}</span>
                <span class="timer-unit">d</span>
              </div>
              <span class="timer-sep">:</span>
              <div class="timer-block">
                <span class="timer-val">{{ String(countdown.hours).padStart(2,'0') }}</span>
                <span class="timer-unit">h</span>
              </div>
              <span class="timer-sep">:</span>
              <div class="timer-block">
                <span class="timer-val">{{ String(countdown.minutes).padStart(2,'0') }}</span>
                <span class="timer-unit">m</span>
              </div>
              <span class="timer-sep">:</span>
              <div class="timer-block">
                <span class="timer-val">{{ String(countdown.seconds).padStart(2,'0') }}</span>
                <span class="timer-unit">s</span>
              </div>
            </div>
          </div>
          <v-btn size="small" variant="flat" class="theme-draw-btn"
            :style="{ background: weeklyTheme.color_hex }"
            @click="$router.push('/draw')">
            <v-icon size="14" class="mr-1">mdi-draw</v-icon>
            Draw for this theme
          </v-btn>
        </div>
      </div>
    </div>

    <!-- Gallery tabs -->
    <div class="gallery-tab-bar">
      <button class="g-tab" :class="{ 'g-tab--active': galleryTab === 'all' }" @click="switchTab('all')">
        <v-icon size="15" class="mr-1">mdi-image-multiple-outline</v-icon>
        All Art
      </button>
      <button class="g-tab" :class="{ 'g-tab--active': galleryTab === 'week' }" @click="switchTab('week')">
        <v-icon size="15" class="mr-1">mdi-calendar-week</v-icon>
        This Week
      </button>
      <button class="g-tab" :class="{ 'g-tab--active': galleryTab === 'free' }" @click="switchTab('free')">
        <v-icon size="15" class="mr-1">mdi-image-outline</v-icon>
        Free Gallery
      </button>
      <button class="g-tab" :class="{ 'g-tab--active': galleryTab === 'archive' }" @click="switchTab('archive')">
        <v-icon size="15" class="mr-1">mdi-archive-outline</v-icon>
        Past Weeks
      </button>
      <button v-if="isAuthenticated" class="g-tab" :class="{ 'g-tab--active': galleryTab === 'mine' }" @click="switchTab('mine')">
        <v-icon size="15" class="mr-1">mdi-account-outline</v-icon>
        My Art
      </button>
    </div>

    <!-- Archive view -->
    <template v-if="galleryTab === 'archive'">
      <!-- Week list -->
      <div v-if="!selectedArchiveWeek" class="archive-container">
        <div v-if="loadingArchive" class="gallery-loading">
          <v-progress-circular indeterminate color="primary" size="40" width="3" />
        </div>
        <div v-else-if="weeklyArchive.length === 0" class="gallery-empty">
          <v-icon size="56" color="primary" class="mb-3">mdi-archive-outline</v-icon>
          <h3>No archived weeks yet</h3>
          <p>Come back next week to see the first archive!</p>
        </div>
        <div v-else class="archive-weeks-grid">
          <div v-for="week in weeklyArchive" :key="week.id"
            class="archive-week-card"
            :style="{ '--wc': week.color_hex }"
            @click="loadArchiveWeek(week)">
            <div class="awc-glow" />
            <span class="awc-emoji">{{ week.emoji }}</span>
            <div class="awc-info">
              <div class="awc-name">{{ week.theme_name }}</div>
              <div class="awc-dates">{{ formatWeekDates(week) }}</div>
              <div class="awc-desc">{{ week.description }}</div>
            </div>
            <v-icon size="16" class="awc-arrow">mdi-chevron-right</v-icon>
          </div>
        </div>
      </div>

      <!-- Selected archive week's top drawings -->
      <div v-else class="archive-container">
        <div class="archive-week-header">
          <v-btn variant="text" size="small" class="back-btn" @click="selectedArchiveWeek = null">
            <v-icon size="16" class="mr-1">mdi-arrow-left</v-icon>
            All Archives
          </v-btn>
          <div class="awh-theme" :style="{ '--wc': selectedArchiveWeek.color_hex }">
            <span class="awh-emoji">{{ selectedArchiveWeek.emoji }}</span>
            <div>
              <h2 class="awh-name">{{ selectedArchiveWeek.theme_name }}</h2>
              <p class="awh-dates">Top 20 from {{ formatWeekDates(selectedArchiveWeek) }}</p>
            </div>
          </div>
        </div>

        <div v-if="loadingArchiveDrawings" class="gallery-loading">
          <v-progress-circular indeterminate color="primary" size="40" width="3" />
        </div>
        <div v-else-if="archiveDrawings.length === 0" class="gallery-empty">
          <v-icon size="48" color="primary" class="mb-3">mdi-image-off-outline</v-icon>
          <h3>No drawings that week</h3>
          <p>No artwork was submitted during this theme.</p>
        </div>
        <div v-else class="drawings-grid archive-drawings-grid">
          <div v-for="(drawing, index) in archiveDrawings" :key="drawing.id" class="drawing-card archive-card">
            <div class="archive-rank" :class="'rank-' + (index + 1)">
              {{ index < 3 ? ['🥇','🥈','🥉'][index] : '#' + (index + 1) }}
            </div>
            <div class="card-preview" @click="viewDrawing(drawing)">
              <img :src="drawing.thumbnail || ''" class="preview-canvas" :alt="drawing.title" />
              <div class="preview-overlay">
                <v-icon size="28" color="white">mdi-eye-outline</v-icon>
              </div>
            </div>
            <div class="card-body">
              <div class="card-meta">
                <span class="card-title">{{ drawing.title }}</span>
                <span class="card-author">{{ drawing.user?.username || drawing.creator_name || drawing.artist_name || t('common.anonymous') }}</span>
              </div>
              <div class="card-actions">
                <span class="archive-votes">
                  <v-icon size="14" color="error">mdi-heart</v-icon>
                  {{ drawing.votes_count || 0 }}
                </span>
                <span class="card-date">{{ formatDate(drawing.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- All / This Week views -->
    <template v-else>
      <!-- Toolbar -->
      <div class="gallery-toolbar">
        <v-btn-toggle v-model="sortBy" mandatory density="compact" class="sort-toggle">
          <v-btn value="recent" size="small">
            <v-icon size="14" class="mr-1">mdi-clock-outline</v-icon>
            {{ t('common.recent') }}
          </v-btn>
          <v-btn value="popular" size="small">
            <v-icon size="14" class="mr-1">mdi-fire</v-icon>
            {{ t('common.popular') }}
          </v-btn>
        </v-btn-toggle>

        <!-- Time period (only when Popular) -->
        <v-select
          v-if="sortBy === 'popular'"
          v-model="popularPeriod"
          :items="periodOptions"
          item-title="label"
          item-value="value"
          hide-details
          variant="outlined"
          density="compact"
          class="period-select"
          @update:model-value="fetchDrawings"
        />

        <v-text-field
          v-model="searchQuery"
          prepend-inner-icon="mdi-magnify"
          :placeholder="activeTag ? ('#' + activeTag) : 'Search titles, descriptions, #tags…'"
          clearable
          hide-details
          variant="outlined"
          density="compact"
          class="search-input"
          @click:clear="clearSearch"
        >
          <template v-if="activeTag" #prepend-inner>
            <v-chip size="x-small" color="primary" closable class="tag-chip mr-1"
              @click:close="clearSearch">
              #{{ activeTag }}
            </v-chip>
          </template>
        </v-text-field>

        <v-btn color="primary" prepend-icon="mdi-draw" size="small" rounded="lg"
          @click="$router.push('/draw')">
          {{ t('galleryPage.createArt') }}
        </v-btn>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="gallery-loading">
        <v-progress-circular indeterminate color="primary" size="48" width="4" />
        <p class="mt-3">{{ t('galleryPage.loading') }}</p>
      </div>

      <!-- Grid -->
      <div v-else-if="filteredDrawings.length > 0" class="drawings-grid">
        <div v-for="drawing in filteredDrawings" :key="drawing.id" class="drawing-card">
          <div class="card-preview" @click="viewDrawing(drawing)">
            <img :src="drawing.thumbnail || ''" class="preview-canvas" :alt="drawing.title" />
            <div class="preview-overlay">
              <v-icon size="28" color="white">mdi-eye-outline</v-icon>
            </div>
          </div>
          <div class="card-body">
            <div class="card-meta">
              <span class="card-title">{{ drawing.title }}</span>
              <span class="card-author clickable-author"
                @click.stop="drawing.user?.username && $router.push('/profile/' + drawing.user.username)">
                {{ drawing.user?.username || drawing.creator_name || drawing.artist_name || t('common.anonymous') }}
              </span>
            </div>
            <p v-if="drawing.description" class="card-desc">{{ truncate(drawing.description, 80) }}</p>
            <div v-if="drawing.theme" class="card-theme-badge" :style="{ background: drawing.theme.color_hex + '22', borderColor: drawing.theme.color_hex + '55' }">
              <span>{{ drawing.theme.emoji }} {{ drawing.theme.theme_name }}</span>
            </div>
            <div class="card-actions">
              <v-btn
                variant="text"
                density="compact"
                :color="drawing.has_voted ? 'error' : 'default'"
                :disabled="!isAuthenticated"
                @click="toggleVote(drawing)"
                class="vote-btn"
              >
                <v-icon size="16" class="mr-1">{{ drawing.has_voted ? 'mdi-heart' : 'mdi-heart-outline' }}</v-icon>
                {{ drawing.votes_count || 0 }}
              </v-btn>
              <span class="card-date">{{ formatDate(drawing.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty / no results -->
      <div v-else class="gallery-empty">
        <v-icon size="56" color="primary" class="mb-3">mdi-image-off-outline</v-icon>
        <h3>{{ searchQuery ? t('galleryPage.noArtworkFound') : (galleryTab === 'week' ? 'No art this week yet' : galleryTab === 'mine' ? 'No drawings yet' : t('galleryPage.noDoodlesYet')) }}</h3>
        <p>{{ searchQuery ? t('galleryPage.tryDifferentSearch') : (galleryTab === 'week' ? "Be the first to draw for this week's theme!" : galleryTab === 'mine' ? 'Start creating — your drawings will appear here.' : t('galleryPage.firstDoodler')) }}</p>
        <v-btn v-if="searchQuery" variant="outlined" size="small" @click="searchQuery = ''" class="mt-3">
          {{ t('galleryPage.clearSearch') }}
        </v-btn>
        <v-btn v-else color="primary" size="small" @click="$router.push('/draw')" class="mt-3">
          {{ t('galleryPage.createFirstDoodle') }}
        </v-btn>
      </div>
    </template>

  <!-- Detail Modal -->
  <v-dialog v-model="showDetailModal" max-width="860">
    <v-card v-if="selectedDrawing" class="detail-modal">
      <div class="detail-header">
        <div class="detail-author-row">
          <v-avatar size="36" class="mr-3 flex-shrink-0">
            <v-img v-if="selectedDrawing.user?.avatar_thumbnail" :src="selectedDrawing.user.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(selectedDrawing.user.avatar_thumbnail, selectedDrawing.user?.username || selectedDrawing.user?.name)" />
            <v-icon v-else size="32">mdi-account-circle</v-icon>
          </v-avatar>
          <div>
            <h2 class="detail-title">{{ selectedDrawing.title }}</h2>
            <p class="detail-author clickable-author"
              @click="selectedDrawing.user?.username && $router.push('/profile/' + selectedDrawing.user.username)">
              {{ t('galleryPage.byAuthor', { name: selectedDrawing.user?.username || selectedDrawing.creator_name || selectedDrawing.artist_name || t('common.anonymous') }) }}
            </p>
          </div>
        </div>
        <v-btn icon variant="text" size="small" @click="showDetailModal = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <div class="detail-canvas-wrap">
        <img :src="selectedDrawing.thumbnail || ''" class="detail-canvas" :alt="selectedDrawing.title" />
      </div>

      <div class="detail-footer">
        <div class="detail-chips">
          <v-chip size="small" variant="tonal" prepend-icon="mdi-calendar">{{ formatDate(selectedDrawing.created_at) }}</v-chip>
          <v-chip size="small" variant="tonal" prepend-icon="mdi-heart" color="error">{{ selectedDrawing.votes_count || 0 }}</v-chip>
          <v-chip v-if="selectedDrawing.is_free" size="small" variant="tonal" prepend-icon="mdi-image-outline">Free Gallery</v-chip>
          <v-chip v-else-if="selectedDrawing.theme" size="small" variant="tonal" :color="selectedDrawing.theme.color_hex">
            {{ selectedDrawing.theme.emoji }} {{ selectedDrawing.theme.theme_name }}
          </v-chip>
        </div>
        <!-- Description with clickable hashtags -->
        <p v-if="selectedDrawing.description" class="detail-desc">
          <span v-for="(part, i) in parseDescription(selectedDrawing.description)" :key="i">
            <a v-if="part.type === 'tag'" class="detail-hashtag" @click="searchByTag(part.text)">{{ part.text }}</a>
            <span v-else>{{ part.text }}</span>
          </span>
        </p>
        <div class="detail-actions">
          <v-btn
            :color="selectedDrawing.has_voted ? 'error' : 'default'"
            :variant="selectedDrawing.has_voted ? 'flat' : 'outlined'"
            size="small"
            :disabled="!isAuthenticated"
            @click="toggleVote(selectedDrawing)"
          >
            <v-icon start size="14">{{ selectedDrawing.has_voted ? 'mdi-heart' : 'mdi-heart-outline' }}</v-icon>
            {{ selectedDrawing.has_voted ? t('galleryPage.loved') : t('galleryPage.love') }}
          </v-btn>
          <v-btn variant="outlined" size="small" @click="shareDrawing(selectedDrawing)">
            <v-icon start size="14">mdi-share-variant</v-icon>
            {{ t('galleryPage.share') }}
          </v-btn>
          <v-btn v-if="isAuthenticated" variant="outlined" size="small" color="primary" @click="openShareToChat(selectedDrawing)">
            <v-icon start size="14">mdi-message-arrow-right-outline</v-icon>
            Send to Chat
          </v-btn>
        </div>
      </div>

      <!-- Comments -->
      <v-divider />
      <div class="comments-section">
        <p class="comments-heading">
          <v-icon size="16" class="mr-1">mdi-comment-outline</v-icon>
          {{ t('galleryPage.commentsCount', { count: comments.length }) }}
        </p>
        <div v-if="isAuthenticated" class="comment-input-row">
          <v-textarea v-model="newComment" :label="t('galleryPage.addComment')" rows="2"
            variant="outlined" hide-details density="compact" />
          <v-btn color="primary" :disabled="!newComment.trim()" @click="addComment" class="mt-2">
            {{ t('galleryPage.postComment') }}
          </v-btn>
        </div>
        <p v-else class="text-caption" style="color:var(--c-muted)">{{ t('galleryPage.loginToComment') }}</p>
        <div v-if="loadingComments" class="text-center pa-3">
          <v-progress-circular indeterminate color="primary" size="20" width="2" />
        </div>
        <div v-for="c in comments" :key="c.id" class="comment-item">
          <div class="comment-row">
            <v-avatar size="30" class="comment-avatar flex-shrink-0">
              <v-img v-if="c.user?.avatar_thumbnail" :src="c.user.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(c.user.avatar_thumbnail, c.user?.username || c.user?.name || c.user_name)" />
              <span v-else class="comment-avatar-letter">{{ (c.user?.username || c.user?.name || c.user_name || '?')[0].toUpperCase() }}</span>
            </v-avatar>
            <div class="comment-body">
              <div class="comment-header">
                <strong class="comment-user">{{ c.user?.username || c.user?.name || c.user_name }}</strong>
                <span class="comment-date">{{ formatDate(c.created_at) }}</span>
                <v-btn v-if="c.user?.id === currentUserId" icon variant="text" size="x-small" @click="deleteComment(c)" style="margin-left:auto">
                  <v-icon size="14">mdi-delete-outline</v-icon>
                </v-btn>
              </div>
              <p class="comment-text">{{ c.content }}</p>
            </div>
          </div>
        </div>
        <p v-if="!comments.length" class="text-caption text-center" style="color:var(--c-muted); padding:16px 0">
          {{ t('galleryPage.noComments') }}
        </p>
      </div>
    </v-card>
  </v-dialog>

  <!-- Share to Chat dialog -->
  <v-dialog v-model="shareToChatDialog" max-width="420">
    <v-card class="dlg-share-chat">
      <v-card-title class="pa-4 pb-2">Send Drawing to Chat</v-card-title>
      <v-card-text>
        <p class="text-caption mb-3" style="color:var(--c-muted)">Select a friend to send this drawing to:</p>
        <div v-if="loadingFriends" class="text-center pa-4">
          <v-progress-circular indeterminate color="primary" size="28" width="2" />
        </div>
        <div v-else-if="friends.length === 0" class="text-caption" style="color:var(--c-muted)">
          No friends yet. Add friends to share drawings!
        </div>
        <v-list v-else density="compact" class="friend-pick-list">
          <v-list-item v-for="f in friends" :key="f.id"
            :title="f.name" :subtitle="'@' + f.username"
            @click="sendDrawingToFriend(f)"
            class="friend-pick-item">
            <template #prepend>
              <v-avatar size="32" class="mr-2">
                <v-img v-if="f.avatar_thumbnail" :src="f.avatar_thumbnail" cover />
                <span v-else class="text-caption">{{ f.name?.charAt(0)?.toUpperCase() }}</span>
              </v-avatar>
            </template>
          </v-list-item>
        </v-list>
      </v-card-text>
      <v-card-actions class="pa-3 pt-0">
        <v-spacer />
        <v-btn variant="text" @click="shareToChatDialog = false">Cancel</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Snackbar -->
  <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
    {{ snackbarText }}
    <template #actions>
      <v-btn variant="text" @click="snackbar = false">Close</v-btn>
    </template>
  </v-snackbar>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'
import { openAvatarZoom } from '@/utils/avatarZoom'

const router = useRouter()
const { t, language } = useI18n()
const drawings = ref([])
const loading = ref(true)
const sortBy = ref('recent')
const searchQuery = ref('')
const activeTag = ref('')
const popularPeriod = ref('all')
const periodOptions = [
  { label: 'All time', value: 'all' },
  { label: 'This week', value: 'week' },
  { label: 'This month', value: 'month' },
  { label: 'Last 3 months', value: '3months' },
  { label: 'Last 6 months', value: '6months' },
  { label: 'This year', value: 'year' },
]
const showDetailModal = ref(false)
const selectedDrawing = ref(null)
const comments = ref([])
const newComment = ref('')
const loadingComments = ref(false)
const currentUserId = computed(() => {
  try { return JSON.parse(localStorage.getItem('user') || '{}').id ?? null } catch { return null }
})

// Share-to-chat
const shareToChatDialog = ref(false)
const shareToChatDrawing = ref(null)
const friends = ref([])
const loadingFriends = ref(false)

// Snackbar
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const showSnackbar = (text, color = 'success') => { snackbarText.value = text; snackbarColor.value = color; snackbar.value = true }

// Weekly theme state
const galleryTab = ref('all')
const weeklyTheme = ref(null)
const weeklyArchive = ref([])
const loadingArchive = ref(false)
const selectedArchiveWeek = ref(null)
const archiveDrawings = ref([])
const loadingArchiveDrawings = ref(false)

// Countdown to next Monday midnight
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 })
let countdownTimer = null

const updateCountdown = () => {
  const now = new Date()
  // Days until next Monday (ISO week starts Monday)
  const day = now.getDay() // 0=Sun,1=Mon,...,6=Sat
  const daysUntil = day === 1 ? 7 : (8 - day) % 7
  const nextMonday = new Date(now)
  nextMonday.setDate(now.getDate() + daysUntil)
  nextMonday.setHours(0, 0, 0, 0)
  const diff = nextMonday - now
  if (diff <= 0) {
    // New week just started — refresh the theme
    fetchWeeklyTheme()
    return
  }
  countdown.value = {
    days:    Math.floor(diff / 86400000),
    hours:   Math.floor((diff % 86400000) / 3600000),
    minutes: Math.floor((diff % 3600000)  / 60000),
    seconds: Math.floor((diff % 60000)    / 1000),
  }
}

// Check if user is authenticated
const isAuthenticated = computed(() => {
  return !!localStorage.getItem('token')
})

const totalVotes = computed(() => {
  return drawings.value.reduce((sum, drawing) => sum + (drawing.votes_count || 0), 0)
})

const uniqueArtists = computed(() => {
  const artistNames = drawings.value.map(d => d.artist_name || d.creator_name).filter(Boolean)
  return new Set(artistNames).size
})

const filteredDrawings = computed(() => {
  let result = [...drawings.value]
  
  // Tag filter
  if (activeTag.value) {
    const tagLower = activeTag.value.toLowerCase()
    result = result.filter(d =>
      d.description?.toLowerCase().includes('#' + tagLower) ||
      d.title?.toLowerCase().includes('#' + tagLower)
    )
  }

  // Text search filter
  if (searchQuery.value.trim() && !activeTag.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(drawing => 
      drawing.title?.toLowerCase().includes(query) ||
      drawing.description?.toLowerCase().includes(query) ||
      drawing.artist_name?.toLowerCase().includes(query) ||
      drawing.creator_name?.toLowerCase().includes(query)
    )
  }
  
  // Apply sort
  if (sortBy.value === 'popular') {
    result.sort((a, b) => (b.votes_count || 0) - (a.votes_count || 0))
  }
  
  return result
})

const fetchDrawings = async () => {
  loading.value = true
  try {
    const params = { sort: sortBy.value }

    // Section mapping
    if (galleryTab.value === 'week')  params.section = 'week'
    else if (galleryTab.value === 'free')  params.section = 'free'
    else if (galleryTab.value === 'mine')  params.section = 'mine'
    else params.section = 'all'

    // Time period for popular
    if (sortBy.value === 'popular' && popularPeriod.value !== 'all') {
      params.period = popularPeriod.value
    }

    const response = await api.get('/drawings', { params })
    drawings.value = response.data.data || response.data
    if (isAuthenticated.value) {
      for (const drawing of drawings.value) {
        await checkVoteStatus(drawing)
      }
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const fetchWeeklyTheme = async () => {
  try {
    const response = await api.get('/weekly-theme')
    weeklyTheme.value = response.data.theme
  } catch (error) {
    // silently ignore if backend not running
  }
}

const fetchArchiveList = async () => {
  if (loadingArchive.value) return
  loadingArchive.value = true
  try {
    const response = await api.get('/weekly-archive')
    weeklyArchive.value = response.data.weeks || []
  } catch (error) {
    console.error('Error loading archive:', error)
  } finally {
    loadingArchive.value = false
  }
}

const loadArchiveWeek = async (week) => {
  selectedArchiveWeek.value = week
  loadingArchiveDrawings.value = true
  archiveDrawings.value = []
  try {
    const response = await api.get(`/weekly-archive/${week.week_number}/${week.year}`)
    archiveDrawings.value = response.data.drawings || []
  } catch (error) {
    console.error('Error loading archive week:', error)
  } finally {
    loadingArchiveDrawings.value = false
  }
}

const switchTab = (tab) => {
  galleryTab.value = tab
  selectedArchiveWeek.value = null
  activeTag.value = ''
  searchQuery.value = ''
  if (tab === 'archive') {
    fetchArchiveList()
  } else {
    fetchDrawings()
  }
}

const formatWeekDates = (week) => {
  const start = new Date(week.starts_at)
  const end = new Date(week.ends_at)
  const opts = { month: 'short', day: 'numeric' }
  return `${start.toLocaleDateString('en-US', opts)} – ${end.toLocaleDateString('en-US', { ...opts, year: 'numeric' })}`
}

const checkVoteStatus = async (drawing) => {
  try {
    const response = await api.get(`/drawings/${drawing.id}/check-vote`)
    drawing.has_voted = response.data.has_voted
  } catch (error) {
    drawing.has_voted = false
  }
}

const toggleVote = async (drawing) => {
  try {
    if (drawing.has_voted) {
      await api.delete(`/drawings/${drawing.id}/vote`)
      drawing.votes_count--
      drawing.has_voted = false
    } else {
      const response = await api.post(`/drawings/${drawing.id}/vote`)
      drawing.votes_count = response.data.votes_count
      drawing.has_voted = true
    }
  } catch (error) {
    console.error('Error:', error)
  }
}

const viewDrawing = (drawing) => {
  selectedDrawing.value = drawing
  showDetailModal.value = true
  comments.value = []
  fetchComments(drawing.id)
}

const fetchComments = async (drawingId) => {
  loadingComments.value = true
  try {
    const res = await api.get(`/drawings/${drawingId}/comments`)
    comments.value = res.data.data || []
  } catch (error) {
    comments.value = []
  } finally {
    loadingComments.value = false
  }
}

const addComment = async () => {
  if (!newComment.value.trim() || !selectedDrawing.value) return
  const content = newComment.value.trim()
  newComment.value = ''
  try {
    const res = await api.post(`/drawings/${selectedDrawing.value.id}/comments`, { content })
    comments.value.unshift(res.data)
  } catch (error) {
    newComment.value = content
    showSnackbar('Failed to post comment', 'error')
  }
}

const deleteComment = async (comment) => {
  try {
    await api.delete(`/comments/${comment.id}`)
    comments.value = comments.value.filter(c => c.id !== comment.id)
  } catch (error) {
    showSnackbar('Failed to delete comment', 'error')
  }
}

const shareDrawing = async (drawing) => {
  const url = `${window.location.origin}/gallery?drawing=${drawing.id}`
  try {
    if (navigator.share) {
      await navigator.share({ title: drawing.title, url })
    } else {
      await navigator.clipboard.writeText(url)
      showSnackbar('Link copied to clipboard!')
    }
  } catch (e) {
    // user cancelled share — ignore
  }
}

const openShareToChat = async (drawing) => {
  shareToChatDrawing.value = drawing
  shareToChatDialog.value = true
  if (friends.value.length === 0) {
    loadingFriends.value = true
    try {
      const res = await api.get('/friends')
      friends.value = res.data.friends || []
    } catch (e) {
      friends.value = []
    } finally {
      loadingFriends.value = false
    }
  }
}

const sendDrawingToFriend = async (friend) => {
  if (!shareToChatDrawing.value) return
  const drawing = shareToChatDrawing.value
  shareToChatDialog.value = false
  try {
    // Get or create conversation with this friend
    const convRes = await api.post('/conversations', { friend_id: friend.id })
    const convId = convRes.data.id || convRes.data.conversation?.id
    const url = `${window.location.origin}/gallery?drawing=${drawing.id}`
    const content = `🎨 **${drawing.title}** — ${url}`
    await api.post(`/conversations/${convId}/messages`, { content })
    showSnackbar(`Drawing sent to ${friend.name}!`)
    router.push(`/messages/${convId}`)
  } catch (e) {
    showSnackbar('Failed to send drawing', 'error')
  }
}

// Hashtag and search helpers
const truncate = (str, len) => str && str.length > len ? str.slice(0, len) + '…' : str

const parseDescription = (text) => {
  if (!text) return []
  const parts = []
  const regex = /(#\w+)/g
  let last = 0
  let match
  while ((match = regex.exec(text)) !== null) {
    if (match.index > last) parts.push({ type: 'text', text: text.slice(last, match.index) })
    parts.push({ type: 'tag', text: match[1] })
    last = match.index + match[1].length
  }
  if (last < text.length) parts.push({ type: 'text', text: text.slice(last) })
  return parts
}

const searchByTag = (tag) => {
  const t = tag.replace(/^#/, '')
  activeTag.value = t
  searchQuery.value = ''
  showDetailModal.value = false
  // Switch to 'all' tab if on archive
  if (galleryTab.value === 'archive') switchTab('all')
}

const clearSearch = () => {
  searchQuery.value = ''
  activeTag.value = ''
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return t('common.today')
  if (diffDays === 1) return t('common.yesterday')
  if (diffDays < 7) return t('galleryPage.daysAgo', { count: diffDays })
  if (diffDays < 30) return t('galleryPage.weeksAgo', { count: Math.floor(diffDays / 7) })
  return date.toLocaleDateString(language.value === 'lv' ? 'lv-LV' : 'en-US')
}

watch(sortBy, () => { popularPeriod.value = 'all'; fetchDrawings() })
watch(popularPeriod, fetchDrawings)
onMounted(() => {
  fetchDrawings()
  fetchWeeklyTheme()
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  clearInterval(countdownTimer)
})
</script>

<style scoped>
/* Gallery page */
.gallery-page {
  min-height: 100vh;
  background: var(--c-bg);
  padding: 0 0 60px;
}

.gallery-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding: 36px 28px 20px;
  max-width: 1320px;
  margin: 0 auto;
  gap: 16px;
}

.gallery-header-mobile-stats {
  max-width: 1320px;
  margin: 0 auto;
  padding: 0 28px 12px;
  gap: 8px;
  overflow-x: auto;
  scrollbar-width: thin;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--c-text);
  margin-bottom: 4px;
}

.page-sub {
  font-size: 0.875rem;
  color: var(--c-muted);
}

.stat-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 999px;
  padding: 4px 12px;
  font-size: 0.78rem;
  color: var(--c-text-dim);
  white-space: nowrap;
}

.gallery-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 28px 20px;
  max-width: 1320px;
  margin: 0 auto;
  flex-wrap: wrap;
}

.gallery-toolbar .sort-toggle {
  background: var(--c-surface) !important;
  border: 1px solid var(--c-border) !important;
  border-radius: var(--r-md) !important;
  flex-shrink: 0;
}

.gallery-toolbar .search-input {
  flex: 1;
  min-width: 200px;
  max-width: 340px;
}

.gallery-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  color: var(--c-muted);
  font-size: 0.875rem;
}

.drawings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  padding: 0 28px;
  max-width: 1320px;
  margin: 0 auto;
}

.drawing-card {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  overflow: hidden;
  transition: border-color 180ms, transform 180ms;
}

.drawing-card:hover {
  border-color: rgba(124,58,237,0.4);
  transform: translateY(-2px);
}

.card-preview {
  position: relative;
  aspect-ratio: 4/3;
  cursor: pointer;
  overflow: hidden;
  background: #fff;
}

.preview-canvas {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  background: #fff;
}

.preview-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 180ms;
}

.card-preview:hover .preview-overlay { opacity: 1; }

.card-body {
  padding: 10px 12px;
}

.card-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-bottom: 8px;
}

.card-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-author {
  font-size: 0.75rem;
  color: var(--c-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.clickable-author {
  cursor: pointer;
  transition: color 0.15s;
}

.clickable-author:hover {
  color: var(--c-accent);
  text-decoration: underline;
}

.card-theme-badge {
  font-size: 0.7rem;
  padding: 2px 7px;
  border-radius: 999px;
  border: 1px solid;
  display: inline-block;
  margin-bottom: 4px;
  color: var(--c-text);
}

.card-desc {
  font-size: 0.72rem;
  color: var(--c-muted);
  margin: 2px 0 4px;
  line-height: 1.4;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.card-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.vote-btn {
  font-size: 0.78rem !important;
  color: var(--c-muted) !important;
  min-width: 0 !important;
  padding: 0 6px !important;
}

.card-date {
  font-size: 0.72rem;
  color: var(--c-muted);
}

.gallery-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  text-align: center;
  color: var(--c-muted);
}

.gallery-empty h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 8px;
}

.gallery-empty p {
  font-size: 0.875rem;
  color: var(--c-muted);
}

.detail-modal {
  background: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
}

.detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 16px 20px 12px;
  border-bottom: 1px solid var(--c-border);
}

.detail-author-row {
  display: flex;
  align-items: center;
}

.detail-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 2px;
}

.detail-author {
  font-size: 0.8rem;
  color: var(--c-muted);
}

.detail-canvas-wrap {
  background: #fff;
  padding: 12px;
}

.detail-canvas {
  width: 100%;
  height: auto;
  display: block;
  border-radius: var(--r-sm);
}

.detail-footer {
  display: flex;
  flex-direction: column;
  padding: 12px 20px;
  gap: 10px;
}

.detail-chips { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.detail-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.detail-desc {
  font-size: 0.85rem;
  color: var(--c-text-dim);
  line-height: 1.5;
  word-break: break-word;
}

.detail-hashtag {
  color: #a78bfa;
  cursor: pointer;
  text-decoration: none;
  font-weight: 600;
}
.detail-hashtag:hover { text-decoration: underline; }

.period-select {
  width: 150px;
  flex-shrink: 0;
}

.tag-chip {
  pointer-events: auto;
}

.comments-section {
  padding: 16px 20px 20px;
}

.comments-heading {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
}

.comment-input-row {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 16px;
}

.comment-item {
  padding: 10px 0;
  border-bottom: 1px solid var(--c-border);
}

.comment-item:last-child { border-bottom: none; }

.comment-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.comment-avatar {
  margin-top: 2px;
}

.comment-avatar-letter {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--c-text);
}

.comment-body {
  flex: 1;
  min-width: 0;
}

.comment-user {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--c-text);
  margin-right: 8px;
}

.comment-date {
  font-size: 0.72rem;
  color: var(--c-muted);
}

.comment-text {
  font-size: 0.85rem;
  color: var(--c-text-dim);
  margin-top: 4px;
}

.theme-banner {
  max-width: 1320px;
  margin: 0 auto 8px;
  padding: 0 28px;
}

.theme-banner-inner {
  position: relative;
  border-radius: var(--r-xl);
  padding: 20px 24px 18px;
  background: linear-gradient(120deg, color-mix(in srgb, var(--theme-color) 22%, #111318), color-mix(in srgb, var(--theme-color) 10%, #1a1b1e));
  border: 1px solid color-mix(in srgb, var(--theme-color) 35%, transparent);
  display: flex;
  flex-direction: column;
  gap: 14px;
  overflow: hidden;
}

.theme-banner-inner::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: radial-gradient(ellipse at 0% 50%, color-mix(in srgb, var(--theme-color) 18%, transparent) 0%, transparent 60%);
  pointer-events: none;
}

.theme-badge {
  position: absolute;
  top: 12px;
  right: 16px;
}

.theme-week-label {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: color-mix(in srgb, var(--theme-color) 100%, white 30%);
  background: color-mix(in srgb, var(--theme-color) 18%, transparent);
  padding: 3px 10px;
  border-radius: 999px;
  border: 1px solid color-mix(in srgb, var(--theme-color) 30%, transparent);
}

.theme-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.theme-emoji {
  font-size: 2.8rem;
  line-height: 1;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,.4));
  flex-shrink: 0;
}

.theme-text { min-width: 0; }

.theme-name {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--c-text);
  margin-bottom: 2px;
}

.theme-desc {
  font-size: 0.82rem;
  color: var(--c-text-dim);
  margin: 0;
}

.theme-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.theme-timer {
  display: flex;
  align-items: center;
  gap: 10px;
}

.timer-label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--c-muted);
}

.timer-blocks {
  display: flex;
  align-items: center;
  gap: 4px;
}

.timer-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(0,0,0,.35);
  border: 1px solid color-mix(in srgb, var(--theme-color) 25%, transparent);
  border-radius: 6px;
  padding: 4px 8px;
  min-width: 38px;
}

.timer-val {
  font-size: 1rem;
  font-weight: 800;
  color: var(--c-text);
  font-variant-numeric: tabular-nums;
  line-height: 1.1;
}

.timer-unit {
  font-size: 0.6rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--c-muted);
  letter-spacing: .06em;
}

.timer-sep {
  font-size: 1rem;
  font-weight: 800;
  color: var(--c-muted);
  margin-bottom: 10px;
}

.theme-draw-btn {
  color: #fff !important;
  font-size: 0.8rem !important;
  border-radius: var(--r-md) !important;
  font-weight: 700 !important;
  flex-shrink: 0;
}

.gallery-tab-bar {
  display: flex;
  align-items: center;
  gap: 4px;
  max-width: 1320px;
  margin: 0 auto 12px;
  padding: 0 28px;
}

.g-tab {
  display: inline-flex;
  align-items: center;
  padding: 7px 16px;
  border-radius: var(--r-md);
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--c-text-dim);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background 150ms, color 150ms, border-color 150ms;
}

.g-tab:hover {
  background: var(--c-surface);
  color: var(--c-text);
}

.g-tab--active {
  background: var(--c-surface);
  color: var(--c-text);
  border-color: var(--c-border-md);
}

.archive-container {
  max-width: 1320px;
  margin: 0 auto;
  padding: 0 28px 60px;
}

.archive-weeks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
}

.archive-week-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 16px;
  border-radius: var(--r-lg);
  background: var(--c-card);
  border: 1px solid var(--c-border);
  cursor: pointer;
  transition: border-color 160ms, transform 160ms;
  overflow: hidden;
}

.archive-week-card:hover {
  border-color: color-mix(in srgb, var(--wc, #7c3aed) 50%, transparent);
  transform: translateY(-2px);
}

.awc-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 0% 50%, color-mix(in srgb, var(--wc, #7c3aed) 12%, transparent), transparent 70%);
  pointer-events: none;
}

.awc-emoji {
  font-size: 2rem;
  line-height: 1;
  flex-shrink: 0;
}

.awc-info { flex: 1; min-width: 0; }

.awc-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.awc-dates {
  font-size: 0.72rem;
  color: var(--c-muted);
  margin-bottom: 3px;
}

.awc-desc {
  font-size: 0.78rem;
  color: var(--c-text-dim);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.awc-arrow { color: var(--c-muted); flex-shrink: 0; }

.archive-week-header {
  margin-bottom: 20px;
}

.back-btn {
  color: var(--c-text-dim) !important;
  font-size: 0.83rem !important;
  margin-bottom: 14px;
}

.awh-theme {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-radius: var(--r-xl);
  background: color-mix(in srgb, var(--wc, #7c3aed) 10%, #1a1b1e);
  border: 1px solid color-mix(in srgb, var(--wc, #7c3aed) 25%, transparent);
}

.awh-emoji { font-size: 2.4rem; line-height: 1; }

.awh-name {
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--c-text);
  margin: 0 0 3px;
}

.awh-dates {
  font-size: 0.8rem;
  color: var(--c-muted);
  margin: 0;
}

.archive-drawings-grid {
  margin-top: 4px;
}

.archive-card {
  position: relative;
}

.archive-rank {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
  font-size: 1rem;
  background: rgba(0,0,0,.65);
  border-radius: 8px;
  padding: 2px 8px;
  font-weight: 700;
  color: #fff;
}

.rank-1 { background: rgba(255,196,0,.85); color: #000; }
.rank-2 { background: rgba(168,168,168,.85); color: #000; }
.rank-3 { background: rgba(180,110,60,.85); color: #fff; }

.archive-votes {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.78rem;
  color: var(--c-muted);
  font-weight: 600;
}

@media (max-width: 959px) {
  .gallery-header,
  .gallery-toolbar,
  .gallery-tab-bar,
  .drawings-grid,
  .archive-container,
  .theme-banner,
  .gallery-header-mobile-stats {
    padding-left: 16px;
    padding-right: 16px;
  }

  .gallery-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding-top: 22px;
    padding-bottom: 14px;
  }

  .gallery-toolbar {
    gap: 10px;
    padding-bottom: 14px;
  }

  .gallery-toolbar .sort-toggle,
  .gallery-toolbar .period-select {
    width: 100%;
  }

  .gallery-toolbar .search-input {
    width: 100%;
    max-width: none;
    min-width: 0;
  }

  .gallery-tab-bar {
    overflow-x: auto;
    padding-bottom: 10px;
    scrollbar-width: none;
  }

  .gallery-tab-bar::-webkit-scrollbar {
    display: none;
  }

  .g-tab {
    white-space: nowrap;
    flex: 0 0 auto;
  }

  .theme-banner-inner {
    padding: 16px;
    gap: 12px;
  }

  .theme-badge {
    position: static;
    align-self: flex-start;
  }

  .theme-content {
    align-items: flex-start;
  }

  .theme-emoji {
    font-size: 2.3rem;
  }

  .theme-name {
    font-size: 1.05rem;
  }

  .theme-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .theme-timer {
    width: 100%;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .timer-blocks {
    flex-wrap: wrap;
    row-gap: 6px;
  }

  .theme-draw-btn {
    width: 100%;
    justify-content: center;
  }

  .drawings-grid {
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
  }

  .archive-weeks-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .archive-week-card {
    padding: 14px 12px;
  }

  .awh-theme {
    padding: 12px 14px;
    gap: 10px;
  }

  .awh-name {
    font-size: 1rem;
  }

  .detail-header,
  .detail-footer,
  .comments-section {
    padding-left: 14px;
    padding-right: 14px;
  }

  .detail-header {
    padding-top: 12px;
    padding-bottom: 10px;
  }
}

@media (max-width: 600px) {
  .gallery-page {
    padding-bottom: 24px;
  }

  .page-title {
    font-size: 1.35rem;
  }

  .page-sub {
    font-size: 0.8rem;
  }

  .gallery-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .gallery-toolbar > :last-child {
    width: 100%;
  }

  .gallery-toolbar > :last-child :deep(.v-btn__content) {
    width: 100%;
    justify-content: center;
  }

  .drawings-grid {
    grid-template-columns: 1fr;
  }

  .card-body {
    padding: 11px 10px;
  }

  .timer-block {
    min-width: 34px;
    padding: 4px 6px;
  }

  .timer-sep {
    margin-bottom: 8px;
  }

  .archive-week-card {
    gap: 10px;
  }

  .awc-emoji {
    font-size: 1.7rem;
  }

  .detail-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .detail-actions > * {
    width: 100%;
  }
}
</style>
