<template>
  <footer class="app-footer">
    <div class="footer-inner">
      <!-- Brand -->
      <div class="footer-brand">
        <span class="footer-logo">DoodleVerse</span>
        <span class="footer-copy">© {{ currentYear }}</span>
      </div>

      <!-- Weekly theme -->
      <router-link
        v-if="weeklyTheme"
        to="/draw"
        class="theme-badge"
        :style="{ borderColor: weeklyTheme.color_hex + '55', background: weeklyTheme.color_hex + '18' }"
      >
        <span>{{ weeklyTheme.emoji }}</span>
        <span class="theme-label">{{ t('footer.weekTheme') }}: <strong>{{ weeklyTheme.theme_name }}</strong></span>
      </router-link>

      <!-- Draw CTA -->
      <router-link to="/draw" class="draw-cta">
        <v-icon size="14">mdi-pencil</v-icon>
        {{ t('common.draw') }}
      </router-link>

      <!-- Live stats -->
      <div class="footer-stats">
        <span class="stat-pill">
          <span class="stat-dot online" />
          {{ stats.onlineUsers }} {{ t('footer.doodlersOnline') }}
        </span>
        <span class="stat-pill">
          🖼️ {{ stats.artworks }} {{ t('footer.publishedArt') }}
        </span>
        <span class="stat-pill">
          🎨 {{ stats.totalUsers }} {{ t('footer.totalDoodlers') }}
        </span>
      </div>

      <!-- Language toggle -->
      <div class="footer-lang">
        <button
          class="lang-btn"
          :class="{ active: language === 'lv' }"
          @click="setLanguage('lv')"
        >LV</button>
        <button
          class="lang-btn"
          :class="{ active: language === 'en' }"
          @click="setLanguage('en')"
        >EN</button>
      </div>
    </div>
  </footer>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

export default {
  name: 'AppFooter',
  setup() {
    const { t, language, setLanguage } = useI18n()
    const stats = ref({ totalUsers: 0, onlineUsers: 0, artworks: 0 })
    const weeklyTheme = ref(null)
    const currentYear = computed(() => new Date().getFullYear())

    const loadStats = async () => {
      try {
        const res = await api.get('/stats')
        const d = res.data.data
        stats.value = { totalUsers: d.total_users, onlineUsers: d.online_users, artworks: d.total_artworks }
      } catch {
        // leave as zero
      }
    }

    const loadTheme = async () => {
      try {
        const res = await api.get('/weekly-theme')
        weeklyTheme.value = res.data.theme ?? null
      } catch {
        weeklyTheme.value = null
      }
    }

    onMounted(() => {
      loadStats()
      loadTheme()
      setInterval(loadStats, 60000)
    })

    return { t, language, setLanguage, stats, weeklyTheme, currentYear }
  }
}
</script>

<style scoped>
/* AppFooter Component Styles */
.app-footer {
  background: var(--c-sidebar, #1a1b1e);
  border-top: 1px solid var(--c-border, rgba(255,255,255,0.07));
  color: var(--c-text-dim, #adb5bd);
}

.footer-inner {
  max-width: 1320px;
  margin: 0 auto;
  padding: 14px 32px;
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: wrap;
}

/* Brand */
.footer-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.footer-logo {
  font-size: 0.95rem;
  font-weight: 900;
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
  background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.footer-copy {
  font-size: 0.78rem;
  color: var(--c-muted, #868e96);
}

/* Weekly theme badge */
.theme-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8rem;
  color: var(--c-text-dim, #adb5bd);
  border: 1px solid;
  border-radius: 20px;
  padding: 4px 12px;
  text-decoration: none;
  transition: opacity 120ms;
  white-space: nowrap;
  flex-shrink: 0;
}

.theme-badge:hover {
  opacity: 0.8;
}

.theme-label strong {
  color: var(--c-text, #dbdee1);
  font-weight: 700;
}

/* Draw CTA */
.draw-cta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #a78bfa;
  background: rgba(124,58,237,0.12);
  border: 1px solid rgba(124,58,237,0.25);
  border-radius: 20px;
  padding: 4px 12px;
  text-decoration: none;
  transition: background 120ms, border-color 120ms;
  flex-shrink: 0;
  white-space: nowrap;
}

.draw-cta:hover {
  background: rgba(124,58,237,0.22);
  border-color: rgba(124,58,237,0.45);
}

/* Stats */
.footer-stats {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-left: auto;
}

.stat-pill {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  color: var(--c-muted, #868e96);
  background: var(--c-surface, #25262b);
  border: 1px solid var(--c-border, rgba(255,255,255,0.07));
  border-radius: 20px;
  padding: 3px 10px;
  white-space: nowrap;
}

.stat-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.stat-dot.online {
  background: #22c55e;
  box-shadow: 0 0 5px rgba(34,197,94,0.5);
}

/* Language toggle */
.footer-lang {
  display: flex;
  gap: 2px;
  background: var(--c-surface, #25262b);
  border: 1px solid var(--c-border, rgba(255,255,255,0.07));
  border-radius: 8px;
  padding: 2px;
  flex-shrink: 0;
}

.lang-btn {
  background: none;
  border: none;
  color: var(--c-muted, #868e96);
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 120ms, color 120ms;
  letter-spacing: 0.04em;
}

.lang-btn:hover {
  color: var(--c-text, #dbdee1);
}

.lang-btn.active {
  background: var(--c-elevated, #383a40);
  color: var(--c-text, #dbdee1);
}

@media (max-width: 768px) {
  .footer-inner {
    padding: 12px 16px;
    gap: 12px;
  }

  .footer-stats {
    margin-left: 0;
    width: 100%;
  }

  .stat-pill {
    flex: 1;
    justify-content: center;
  }
}
</style>
