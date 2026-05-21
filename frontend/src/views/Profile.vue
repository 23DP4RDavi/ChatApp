<template>
  <div class="profile-page">
    <div v-if="loading" class="profile-loading">
      <v-progress-circular indeterminate color="primary" size="48" width="4" />
    </div>

    <div v-else-if="!profile" class="profile-empty">
      <v-icon size="64" color="primary" class="mb-3">mdi-account-off-outline</v-icon>
      <h2>{{ t('profilePage.userNotFound') }}</h2>
      <v-btn variant="outlined" size="small" class="mt-3" @click="$router.push('/')">{{ t('profilePage.goHome') }}</v-btn>
    </div>

    <template v-else>
      <!-- Hero -->
      <div class="profile-hero">
        <div class="profile-avatar-wrap">
          <v-avatar size="96" class="profile-avatar">
            <v-img v-if="profile.avatar_thumbnail" :src="profile.avatar_thumbnail" cover />
            <span v-else class="profile-initials">{{ profile.name?.charAt(0)?.toUpperCase() }}</span>
          </v-avatar>
        </div>
        <div class="profile-info">
          <h1 class="profile-name">{{ profile.name }}</h1>
          <p class="profile-username">@{{ profile.username }}</p>
          <div class="profile-stats">
            <div class="stat-block">
              <span class="stat-value">{{ profile.drawings_count }}</span>
              <span class="stat-label">{{ t('profilePage.drawings') }}</span>
            </div>
            <div class="stat-block">
              <span class="stat-value">{{ profile.friends_count }}</span>
              <span class="stat-label">{{ t('profilePage.friends') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Drawings grid -->
      <div class="profile-section">
        <h2 class="profile-section-title">{{ t('profilePage.recentDrawings') }}</h2>
        <div v-if="profile.recent_drawings.length === 0" class="profile-empty-art">
          <v-icon size="40" color="primary" class="mb-2">mdi-image-off-outline</v-icon>
          <p>{{ t('profilePage.noDrawings') }}</p>
        </div>
        <div v-else class="profile-drawings-grid">
          <div v-for="drawing in profile.recent_drawings" :key="drawing.id"
            class="profile-drawing-card"
            @click="openDrawing(drawing)">
            <img v-if="drawing.thumbnail" :src="drawing.thumbnail" :alt="drawing.title" class="profile-drawing-thumb" />
            <div v-else class="profile-drawing-placeholder">
              <v-icon size="28" color="primary">mdi-draw</v-icon>
            </div>
            <div class="profile-drawing-meta">
              <span class="profile-drawing-title">{{ drawing.title }}</span>
              <span class="profile-drawing-votes">
                <v-icon size="12" color="error">mdi-heart</v-icon>
                {{ drawing.votes_count || 0 }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const profile = ref(null)
const loading = ref(true)

const fetchProfile = async () => {
  loading.value = true
  try {
    const res = await api.get(`/profile/${route.params.username}`)
    profile.value = res.data
  } catch (e) {
    profile.value = null
  } finally {
    loading.value = false
  }
}

const openDrawing = (drawing) => {
  router.push(`/gallery?drawing=${drawing.id}`)
}

onMounted(fetchProfile)
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: var(--c-bg);
  padding-bottom: 60px;
  max-width: 860px;
  margin: 0 auto;
  padding: 32px 24px 60px;
}

.profile-loading,
.profile-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 24px;
  color: var(--c-text-dim);
  text-align: center;
}

.profile-hero {
  display: flex;
  align-items: flex-start;
  gap: 28px;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-xl);
  padding: 32px;
  margin-bottom: 28px;
}

.profile-avatar {
  background: var(--c-card);
  border: 3px solid var(--c-accent);
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--c-accent);
}

.profile-initials {
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--c-accent);
}

.profile-name {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--c-text);
  margin-bottom: 4px;
}

.profile-username {
  color: var(--c-muted);
  font-size: 0.95rem;
  margin-bottom: 16px;
}

.profile-stats {
  display: flex;
  gap: 24px;
}

.stat-block {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--c-text);
  line-height: 1;
}

.stat-label {
  font-size: 0.78rem;
  color: var(--c-muted);
  margin-top: 2px;
}

.profile-section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 16px;
}

.profile-empty-art {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px;
  color: var(--c-muted);
}

.profile-drawings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
}

.profile-drawing-card {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.15s, border-color 0.15s;
}

.profile-drawing-card:hover {
  transform: translateY(-2px);
  border-color: var(--c-accent);
}

.profile-drawing-thumb {
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  display: block;
}

.profile-drawing-placeholder {
  width: 100%;
  aspect-ratio: 4/3;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--c-elevated);
}

.profile-drawing-meta {
  padding: 8px 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 4px;
}

.profile-drawing-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.profile-drawing-votes {
  font-size: 0.75rem;
  color: var(--c-muted);
  display: flex;
  align-items: center;
  gap: 3px;
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .profile-page {
    padding: 16px 14px 60px;
  }

  .profile-hero {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px 16px;
    gap: 16px;
  }

  .profile-stats {
    justify-content: center;
  }

  .profile-name {
    font-size: 1.4rem;
  }

  .profile-drawings-grid {
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  }
}
</style>
