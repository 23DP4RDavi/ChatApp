<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-content">
        <div class="hero-stars">
          <span class="star"></span>
          <span class="star"></span>
          <span class="star"></span>
        </div>
        <h1 class="hero-title">Welcome to DoodleVerse!</h1>
        <p class="hero-subtitle">Draw, Connect & Share</p>
        <p class="hero-description">
          A creative social platform where artists come together to draw, message friends privately, 
          share their artwork, and connect with a vibrant community of creators from around the world!
        </p>
        <div class="hero-buttons">
          <button @click="navigateTo('/draw')" class="btn btn-primary">
            🎨 Start Drawing Now
          </button>
          <button @click="navigateTo('/gallery')" class="btn btn-secondary">
            🖼️ Browse Gallery
          </button>
        </div>
      </div>
      <div class="hero-illustration">
        <div class="floating-emoji"></div>
        <div class="floating-emoji"></div>
        <div class="floating-emoji"></div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
      <h2 class="section-title">Main Features</h2>
      <div class="features-grid">
        <div class="feature-card feature-primary">
          <div class="feature-icon">🎨</div>
          <h3>Interactive Drawing Canvas</h3>
          <p>Create stunning artwork with our easy-to-use drawing tools. Multiple colors, brush sizes, and undo/redo functionality</p>
          <button @click="navigateTo('/draw')" class="feature-btn">Try Drawing →</button>
        </div>
        <div class="feature-card feature-primary">
          <div class="feature-icon">💬</div>
          <h3>Private Messaging</h3>
          <p>Connect with friends through 1-to-1 messages. Share ideas, drawings, and build friendships privately</p>
          <button @click="navigateTo('/friends')" class="feature-btn">Find Friends →</button>
        </div>
        <div class="feature-card">
          <div class="feature-icon">🖼️</div>
          <h3>Community Gallery</h3>
          <p>Browse and discover amazing artwork from creators worldwide. Vote for your favorites and get inspired</p>
          <button @click="navigateTo('/gallery')" class="feature-btn">Explore Gallery →</button>
        </div>
        <div class="feature-card">
          <div class="feature-icon">�</div>
          <h3>Social Features</h3>
          <p>Build your profile, follow artists, share your creations, and become part of a thriving creative community</p>
          <button @click="navigateTo('/auth')" class="feature-btn">Join Now →</button>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
      <h2 class="section-title">Our Growing Community</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number">{{ stats.totalDrawings }}</div>
          <div class="stat-label">🎨 Artworks Created</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">{{ stats.activeDoodlers }}</div>
          <div class="stat-label">👨‍🎨 Active Doodlers</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">{{ stats.totalVotes }}</div>
          <div class="stat-label">💜 Community Votes</div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
      <h2 class="section-title">How It Works</h2>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-number">1</div>
          <h3>Create Account</h3>
          <p>Sign up for free and join our creative community</p>
        </div>
        <div class="step-card">
          <div class="step-number">2</div>
          <h3>Start Drawing</h3>
          <p>Use our canvas to create amazing artwork</p>
        </div>
        <div class="step-card">
          <div class="step-number">3</div>
          <h3>Share & Connect</h3>
          <p>Post your art and message friends privately</p>
        </div>
        <div class="step-card">
          <div class="step-number">4</div>
          <h3>Get Feedback</h3>
          <p>Receive votes and comments from the community</p>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <h2>Ready to Join Our Creative Community?</h2>
      <p>Start drawing, connecting with friends, and sharing art today!</p>
      <div class="cta-buttons">
        <button @click="navigateTo('/auth')" class="btn btn-large btn-primary">
          Get Started Free 🚀
        </button>
        <button @click="navigateTo('/gallery')" class="btn btn-large btn-outline">
          Browse Gallery First 👀
        </button>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'Home',
  setup() {
    const router = useRouter()
    const stats = ref({
      totalDrawings: 0,
      totalVotes: 0,
      activeDoodlers: 0
    })

    const navigateTo = (path) => {
      router.push(path)
    }

    const loadStats = async () => {
      try {
        const response = await api.get('/drawings')
        const drawings = response.data.data

        stats.value.totalDrawings = drawings.length
        stats.value.totalVotes = drawings.reduce((sum, d) => sum + d.votes_count, 0)
        stats.value.activeDoodlers = new Set(drawings.map(d => d.artist_name)).size
      } catch (error) {
        console.error('Failed to load stats:', error)
        stats.value = {
          totalDrawings: '',
          totalVotes: '',
          activeDoodlers: ''
        }
      }
    }

    onMounted(() => {
      loadStats()
    })

    return {
      stats,
      navigateTo
    }
  }
}
</script>

<style scoped>
.home-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  color: white;
}

/* Hero Section */
.hero-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 80px 60px;
  max-width: 1400px;
  margin: 0 auto;
  gap: 60px;
}

.hero-content {
  flex: 1;
  max-width: 600px;
}

.hero-stars {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  font-size: 24px;
}

.star {
  animation: twinkle 2s ease-in-out infinite;
}

.star:nth-child(2) {
  animation-delay: 0.3s;
}

.star:nth-child(3) {
  animation-delay: 0.6s;
}

@keyframes twinkle {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.2);
  }
}

.hero-title {
  font-size: 4rem;
  font-weight: 900;
  margin: 0 0 20px 0;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 50%, #fbbf24 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.1;
}

.hero-subtitle {
  font-size: 1.8rem;
  color: #a78bfa;
  margin-bottom: 20px;
  font-weight: 600;
}

.hero-description {
  font-size: 1.2rem;
  color: #cbd5e1;
  line-height: 1.8;
  margin-bottom: 40px;
}

.hero-buttons {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.btn {
  font-family: 'Comic Sans MS', 'Comic Sans', cursive;
  padding: 16px 32px;
  font-size: 1.1rem;
  font-weight: 700;
  border: none;
  border-radius: 50px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5);
}

.btn-secondary {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #1e1b4b;
}

.btn-secondary:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(251, 191, 36, 0.5);
}

.btn-large {
  padding: 20px 48px;
  font-size: 1.3rem;
}

.hero-illustration {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  min-height: 400px;
}

.floating-emoji {
  font-size: 6rem;
  position: absolute;
  animation: float 3s ease-in-out infinite;
}

.floating-emoji:nth-child(1) {
  top: 0;
  left: 0;
}

.floating-emoji:nth-child(2) {
  top: 50%;
  right: 0;
  animation-delay: 1s;
}

.floating-emoji:nth-child(3) {
  bottom: 0;
  left: 50%;
  animation-delay: 2s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-30px) rotate(10deg);
  }
}

/* Features Section */
.features-section {
  padding: 80px 60px;
  max-width: 1400px;
  margin: 0 auto;
}

.section-title {
  text-align: center;
  font-size: 3rem;
  font-weight: 900;
  margin-bottom: 60px;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
}

.feature-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(139, 92, 246, 0.3);
  border-radius: 20px;
  padding: 40px 30px;
  text-align: center;
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-10px);
  border-color: rgba(236, 72, 153, 0.6);
  box-shadow: 0 15px 40px rgba(139, 92, 246, 0.3);
}

.feature-icon {
  font-size: 4rem;
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: 1.5rem;
  margin-bottom: 15px;
  color: #a78bfa;
}

.feature-card p {
  color: #cbd5e1;
  line-height: 1.6;
  font-size: 1.05rem;
  margin-bottom: 20px;
}

.feature-primary {
  border-color: rgba(236, 72, 153, 0.6);
  background: rgba(139, 92, 246, 0.1);
}

.feature-btn {
  font-family: 'Comic Sans MS', cursive;
  padding: 10px 20px;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  color: white;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  transition: all 0.3s ease;
  margin-top: auto;
}

.feature-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
}

/* How It Works Section */
.how-it-works-section {
  padding: 80px 60px;
  max-width: 1400px;
  margin: 0 auto;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
}

.step-card {
  text-align: center;
  padding: 40px 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  border: 2px solid rgba(139, 92, 246, 0.3);
  transition: all 0.3s ease;
  position: relative;
}

.step-card:hover {
  transform: translateY(-5px);
  border-color: rgba(251, 191, 36, 0.6);
}

.step-number {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 900;
  color: white;
  margin: 0 auto 20px;
}

.step-card h3 {
  font-size: 1.4rem;
  color: #a78bfa;
  margin-bottom: 10px;
}

.step-card p {
  color: #cbd5e1;
  line-height: 1.6;
}

/* Stats Section */
.stats-section {
  padding: 80px 60px;
  max-width: 1400px;
  margin: 0 auto;
  background: rgba(139, 92, 246, 0.1);
  border-radius: 30px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 40px;
}

.stat-card {
  text-align: center;
  padding: 30px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  border: 2px solid rgba(236, 72, 153, 0.3);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: scale(1.05);
  border-color: rgba(251, 191, 36, 0.6);
}

.stat-number {
  font-size: 4rem;
  font-weight: 900;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 10px;
}

.stat-label {
  font-size: 1.3rem;
  color: #cbd5e1;
  font-weight: 600;
}

/* CTA Section */
.cta-section {
  text-align: center;
  padding: 100px 60px;
  max-width: 900px;
  margin: 0 auto;
}

.cta-section h2 {
  font-size: 3rem;
  font-weight: 900;
  margin-bottom: 20px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.cta-section p {
  font-size: 1.3rem;
  color: #cbd5e1;
  margin-bottom: 40px;
  line-height: 1.6;
}

.cta-buttons {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-outline {
  background: transparent;
  border: 2px solid #8b5cf6;
  color: #a78bfa;
}

.btn-outline:hover {
  background: rgba(139, 92, 246, 0.1);
  border-color: #ec4899;
  color: #ec4899;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-section {
    flex-direction: column;
    padding: 40px 20px;
    gap: 40px;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-subtitle {
    font-size: 1.3rem;
  }

  .hero-description {
    font-size: 1rem;
  }

  .hero-buttons {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }

  .features-section,
  .stats-section,
  .cta-section {
    padding: 40px 20px;
  }

  .section-title {
    font-size: 2rem;
  }

  .features-grid,
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .cta-section h2 {
    font-size: 2rem;
  }
}
</style>
