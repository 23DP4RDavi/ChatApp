<template>
  <footer class="app-footer">
    <div class="footer-content">
      <!-- Brand Section -->
      <div class="footer-section brand-section">
        <div class="footer-logo">
          <h3>DoodleVerse</h3>
        </div>
        <p class="footer-tagline">Where Artists Draw & Connect</p>
        <div class="social-links">
          <a href="#" class="social-icon" title="Discord">
            <v-icon>mdi-discord</v-icon>
          </a>
          <a href="#" class="social-icon" title="Twitter">
            <v-icon>mdi-twitter</v-icon>
          </a>
          <a href="#" class="social-icon" title="Instagram">
            <v-icon>mdi-instagram</v-icon>
          </a>
          <a href="#" class="social-icon" title="GitHub">
            <v-icon>mdi-github</v-icon>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-section">
        <h4>Explore</h4>
        <ul class="footer-links">
          <li><router-link to="/">🏠 Home</router-link></li>
          <li><router-link to="/gallery">🖼️ Gallery</router-link></li>
          <li><router-link to="/friends">👥 Friends</router-link></li>
          <li><router-link to="/messages">💬 Messages</router-link></li>
          <li><router-link to="/draw">🎨 Start Drawing</router-link></li>
        </ul>
      </div>

      <!-- Community -->
      <div class="footer-section">
        <h4>Community</h4>
        <ul class="footer-links">
          <li><a href="#">👥 About Us</a></li>
          <li><a href="#">📜 Guidelines</a></li>
          <li><a href="#">🎓 Tutorials</a></li>
          <li><a href="#">🏆 Featured Artists</a></li>
        </ul>
      </div>

      <!-- Stats & Support -->
      <div class="footer-section">
        <h4>Live Stats</h4>
        <div class="footer-stats">
          <div class="stat-item">
            <span class="stat-icon">👥</span>
            <span class="stat-value">{{ stats.totalUsers }}+</span>
            <span class="stat-label">Total Doodlers</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon">💚</span>
            <span class="stat-value">{{ stats.onlineUsers }}</span>
            <span class="stat-label">Doodlers Online</span>
          </div>
          <div class="stat-item">
            <span class="stat-icon">🎨</span>
            <span class="stat-value">{{ stats.artworks }}+</span>
            <span class="stat-label">Published Art</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
      <div class="footer-bottom-content">
        <p class="copyright">© {{ currentYear }} DoodleVerse. Made with 💜 for doodlers everywhere</p>
        <div class="footer-bottom-links">
          <a href="#">Privacy Policy</a>
          <span class="separator">•</span>
          <a href="#">Terms of Service</a>
          <span class="separator">•</span>
          <a href="#">Contact</a>
        </div>
      </div>
    </div>

    <!-- Floating Elements -->
    <div class="footer-decoration">
      <span class="floating-star">⭐</span>
      <span class="floating-star">✨</span>
      <span class="floating-star">🌟</span>
    </div>
  </footer>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

export default {
  name: 'AppFooter',
  setup() {
    const stats = ref({
      totalUsers: 0,
      onlineUsers: 0,
      artworks: 0
    })

    const currentYear = computed(() => new Date().getFullYear())

    const loadStats = async () => {
      try {
        // Load real stats from backend
        const response = await api.get('/stats')
        const data = response.data.data

        stats.value.totalUsers = data.total_users
        stats.value.onlineUsers = data.online_users
        stats.value.artworks = data.total_artworks
      } catch (error) {
        console.error('Failed to load stats:', error)
        // Fallback: try to get from drawings endpoint
        try {
          const response = await api.get('/drawings')
          const drawings = response.data.data
          
          stats.value.artworks = drawings.length
          const uniqueArtists = new Set(drawings.map(d => d.artist_name))
          stats.value.totalUsers = uniqueArtists.size
          stats.value.onlineUsers = Math.max(1, Math.floor(stats.value.totalUsers * 0.1))
        } catch (fallbackError) {
          // Final fallback
          stats.value = {
            totalUsers: 0,
            onlineUsers: 0,
            artworks: 0
          }
        }
      }
    }

    onMounted(() => {
      loadStats()
      
      // Refresh stats every 60 seconds
      setInterval(() => {
        loadStats()
      }, 60000)
    })

    return {
      stats,
      currentYear
    }
  }
}
</script>

<style scoped src="./AppFooter.css"></style>
