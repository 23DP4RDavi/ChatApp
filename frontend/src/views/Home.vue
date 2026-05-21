<template>
  <div class="home-page">

    <!-- Galaxy background -->
    <div class="galaxy-bg" aria-hidden="true">
      <div class="nebula n1" ref="n1El"></div>
      <div class="nebula n2" ref="n2El"></div>
      <div class="nebula n3" ref="n3El"></div>
      <div class="nebula n4"></div>
      <div class="nebula n5"></div>
      <div class="nebula n6"></div>
      <div class="nebula n7"></div>
      <canvas ref="bgCanvas" class="bg-canvas"></canvas>
    </div>

    <!-- Constellation trail canvas -->
    <canvas ref="constellationCanvas" class="constellation-canvas" aria-hidden="true"></canvas>

    <!-- Floating art elements -->
    <div class="float-els" aria-hidden="true">
      <span class="float-el fe-1">🎨</span>
      <span class="float-el fe-2">✏️</span>
      <span class="float-el fe-3">🖌️</span>
      <span class="float-el fe-4">🌈</span>
      <span class="float-el fe-5">🎭</span>
      <span class="float-el fe-6">🖍️</span>
      <span class="float-el fe-7">⭐</span>
      <span class="float-el fe-8">💜</span>
    </div>

    <!-- ── Hero ─────────────────────────────── -->
    <section class="hero">
      <div class="hero-text" ref="heroTextEl">
        <div class="hero-badge">
          <v-icon size="13" class="mr-1">mdi-palette</v-icon>
          DoodleVerse
        </div>
        <h1 class="hero-title">{{ t('homePage.heroTitle') }}</h1>
        <p class="hero-sub">{{ t('homePage.heroSubtitle') }}</p>
        <div class="hero-actions">
          <v-btn color="primary" size="large" rounded="lg" @click="navigateTo('/draw')"
            prepend-icon="mdi-draw" class="hero-btn-primary">
            {{ t('homePage.startDrawingNow') }}
          </v-btn>
          <v-btn variant="outlined" size="large" rounded="lg"
            @click="navigateTo('/gallery')" class="hero-btn-outline">
            {{ t('homePage.browseGallery') }}
          </v-btn>
        </div>
      </div>


    </section>

    <!-- ── Stats bar ─────────────────────────── -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-num">{{ displayStats.totalDrawings }}<sup v-if="displayStats.totalDrawings > 0">+</sup></span>
        <span class="stat-lbl">{{ t('homePage.artworksCreated') }}</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-item">
        <span class="stat-num">{{ displayStats.activeDoodlers }}<sup v-if="displayStats.activeDoodlers > 0">+</sup></span>
        <span class="stat-lbl">{{ t('homePage.activeDoodlers') }}</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-item">
        <span class="stat-num">{{ displayStats.totalVotes }}<sup v-if="displayStats.totalVotes > 0">+</sup></span>
        <span class="stat-lbl">{{ t('homePage.communityVotes') }}</span>
      </div>
    </div>

    <!-- ── Features ──────────────────────────── -->
    <section class="features-section">
      <h2 class="section-title">{{ t('homePage.mainFeatures') }}</h2>
      <div class="features-grid">
        <div class="feat-card" @click="navigateTo('/draw')" @mousemove="onCardMove" @mouseleave="onCardLeave">
          <div class="feat-icon-wrap feat-icon-wrap--draw">
            <v-icon size="26">mdi-draw</v-icon>
          </div>
          <h3>{{ t('homePage.featureDrawingTitle') }}</h3>
          <p>{{ t('homePage.featureDrawingText') }}</p>
          <span class="feat-arrow">{{ t('homePage.featureDrawingAction') }} <v-icon size="14">mdi-arrow-right</v-icon></span>
        </div>
        <div class="feat-card" @click="navigateTo('/gallery')" @mousemove="onCardMove" @mouseleave="onCardLeave">
          <div class="feat-icon-wrap feat-icon-wrap--gallery">
            <v-icon size="26">mdi-image-multiple-outline</v-icon>
          </div>
          <h3>{{ t('homePage.featureGalleryTitle') }}</h3>
          <p>{{ t('homePage.featureGalleryText') }}</p>
          <span class="feat-arrow">{{ t('homePage.featureGalleryAction') }} <v-icon size="14">mdi-arrow-right</v-icon></span>
        </div>
        <div class="feat-card" @click="navigateTo('/messages')" @mousemove="onCardMove" @mouseleave="onCardLeave">
          <div class="feat-icon-wrap feat-icon-wrap--messages">
            <v-icon size="26">mdi-message-outline</v-icon>
          </div>
          <h3>{{ t('homePage.featureMessagesTitle') }}</h3>
          <p>{{ t('homePage.featureMessagesText') }}</p>
          <span class="feat-arrow">{{ t('homePage.featureMessagesAction') }} <v-icon size="14">mdi-arrow-right</v-icon></span>
        </div>
      </div>
    </section>

    <!-- ── How it works ──────────────────────── -->
    <section class="how-section">
      <h2 class="section-title">{{ t('homePage.howItWorks') }}</h2>
      <div class="steps-track">
        <div class="step-card" v-for="n in 4" :key="n" @mousemove="onCardMove" @mouseleave="onCardLeave">
          <div class="step-num">{{ n }}</div>
          <h3>{{ t(`homePage.step${n}Title`) }}</h3>
          <p>{{ t(`homePage.step${n}Text`) }}</p>
        </div>
      </div>
    </section>

    <!-- ── CTA ───────────────────────────────── -->
    <section class="cta-section">
      <div class="cta-glow" aria-hidden="true"></div>
      <h2>{{ isLoggedIn ? t('homePage.ctaTitleLoggedIn') : t('homePage.ctaTitle') }}</h2>
      <p>{{ isLoggedIn ? t('homePage.ctaTextLoggedIn') : t('homePage.ctaText') }}</p>
      <div class="cta-actions">
        <v-btn color="primary" size="x-large" rounded="lg"
          @click="navigateTo(isLoggedIn ? '/draw' : '/auth')"
          class="cta-primary-btn">
          {{ isLoggedIn ? t('homePage.ctaPrimaryLoggedIn') : t('homePage.ctaPrimary') }}
        </v-btn>
        <v-btn variant="outlined" size="x-large" rounded="lg" @click="navigateTo('/gallery')">
          {{ isLoggedIn ? t('homePage.ctaSecondaryLoggedIn') : t('homePage.ctaSecondary') }}
        </v-btn>
      </div>
    </section>

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

export default {
  name: 'Home',
  setup() {
    const router = useRouter()
    const { t } = useI18n()
    const isLoggedIn = !!localStorage.getItem('token')
    const stats = ref({ totalDrawings: 0, totalVotes: 0, activeDoodlers: 0 })
    const displayStats = ref({ totalDrawings: 0, totalVotes: 0, activeDoodlers: 0 })
    const bgCanvas = ref(null)
    const constellationCanvas = ref(null)
    const heroTextEl = ref(null)
    const n1El = ref(null)
    const n2El = ref(null)
    const n3El = ref(null)
    let animFrameId = null
    let constFrameId = null
    let lastTrail = 0
    let lastConst = 0
    let lastConstX = -999
    let lastConstY = -999
    const constPoints = []

    const navigateTo = (path) => router.push(path)

    // ── Count-up animation ────────────────────────────────────────────
    const countUp = (end, key, duration = 1400) => {
      const start = performance.now()
      const tick = (now) => {
        const elapsed = Math.min((now - start) / duration, 1)
        const eased = 1 - Math.pow(1 - elapsed, 3)
        displayStats.value[key] = Math.round(end * eased)
        if (elapsed < 1) requestAnimationFrame(tick)
      }
      requestAnimationFrame(tick)
    }

    // ── Ambient background canvas ─────────────────────────────────────
    const initBgAnimation = () => {
      const canvas = bgCanvas.value
      if (!canvas) return
      const ctx = canvas.getContext('2d')

      const resize = () => {
        canvas.width = window.innerWidth
        canvas.height = window.innerHeight
      }
      resize()
      window.addEventListener('resize', resize)

      const palette = [
        'rgba(124,58,237,', 'rgba(236,72,153,', 'rgba(59,130,246,',
        'rgba(16,185,129,', 'rgba(245,158,11,', 'rgba(168,85,247,',
      ]

      const createStroke = () => ({
        points: [],
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        vx: (Math.random() - 0.5) * 1.0,
        vy: (Math.random() - 0.5) * 1.0,
        colorBase: palette[Math.floor(Math.random() * palette.length)],
        size: Math.random() * 2 + 0.5,
        alpha: Math.random() * 0.07 + 0.02,
        life: 0,
        maxLife: Math.random() * 260 + 100,
        wave: Math.random() * Math.PI * 2,
        waveAmp: Math.random() * 1.5 + 0.4,
        waveSpeed: Math.random() * 0.02 + 0.006,
      })

      const strokes = Array.from({ length: 10 }, createStroke)

      const drawBg = () => {
        animFrameId = requestAnimationFrame(drawBg)
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        strokes.forEach((s, i) => {
          s.life++
          s.wave += s.waveSpeed
          s.x += s.vx + Math.sin(s.wave) * s.waveAmp
          s.y += s.vy + Math.cos(s.wave * 0.7) * s.waveAmp
          s.points.push({ x: s.x, y: s.y })
          if (s.points.length > 60) s.points.shift()
          if (s.life >= s.maxLife || s.x < -200 || s.x > canvas.width + 200 || s.y < -200 || s.y > canvas.height + 200) {
            strokes[i] = createStroke(); return
          }
          if (s.points.length < 3) return
          const lr = s.life / s.maxLife
          const fade = lr < 0.15 ? lr / 0.15 : lr > 0.75 ? (1 - lr) / 0.25 : 1
          ctx.beginPath()
          ctx.moveTo(s.points[0].x, s.points[0].y)
          for (let j = 1; j < s.points.length - 1; j++) {
            const mx = (s.points[j].x + s.points[j + 1].x) / 2
            const my = (s.points[j].y + s.points[j + 1].y) / 2
            ctx.quadraticCurveTo(s.points[j].x, s.points[j].y, mx, my)
          }
          ctx.strokeStyle = s.colorBase + (s.alpha * fade) + ')'
          ctx.lineWidth = s.size
          ctx.lineCap = 'round'
          ctx.lineJoin = 'round'
          ctx.stroke()
        })
      }
      drawBg()
    }

    // ── Mouse: cursor paint trail + nebula parallax + constellation ───────────
    const trailColors = ['#a78bfa','#ec4899','#60a5fa','#34d399','#f59e0b','#f472b6','#06b6d4','#fb923c']
    const constRgb = ['167,139,250','192,132,252','232,121,249','129,140,248','96,165,250','52,211,153','244,114,182']

    const onMouseMove = (e) => {
      // Nebula parallax
      const rx = e.clientX / window.innerWidth - 0.5
      const ry = e.clientY / window.innerHeight - 0.5
      if (n1El.value) n1El.value.style.translate = `${rx * -30}px ${ry * -20}px`
      if (n2El.value) n2El.value.style.translate = `${rx * 22}px ${ry * 15}px`
      if (n3El.value) n3El.value.style.translate = `${rx * -16}px ${ry * 22}px`

      // Hero text counter-parallax
      if (heroTextEl.value) heroTextEl.value.style.translate = `${rx * 10}px ${ry * 6}px`

      const now = Date.now()

      // Constellation star point — only when moved far enough
      if (now - lastConst > 30) {
        const dx = e.clientX - lastConstX
        const dy = e.clientY - lastConstY
        if (dx * dx + dy * dy > 28 * 28) {
          lastConst = now
          lastConstX = e.clientX
          lastConstY = e.clientY
          constPoints.push({
            x: e.clientX,
            y: e.clientY,
            t: now,
            rgb: constRgb[Math.floor(Math.random() * constRgb.length)]
          })
        }
      }

      // Cursor paint splat trail
      if (now - lastTrail < 40) return
      lastTrail = now
      for (let i = 0; i < 2; i++) {
        const dot = document.createElement('span')
        const color = trailColors[Math.floor(Math.random() * trailColors.length)]
        const size = Math.random() * 9 + 4
        dot.className = 'cursor-splat'
        dot.style.left = `${e.clientX + (Math.random() - 0.5) * 16}px`
        dot.style.top  = `${e.clientY + (Math.random() - 0.5) * 16}px`
        dot.style.background = color
        dot.style.width  = `${size}px`
        dot.style.height = `${size}px`
        document.body.appendChild(dot)
        setTimeout(() => dot.remove(), 860)
      }
    }

    // ── Constellation canvas effect ──────────────────────────────────
    const initConstellationEffect = () => {
      const canvas = constellationCanvas.value
      if (!canvas) return
      canvas.width = window.innerWidth
      canvas.height = window.innerHeight
      const ctx = canvas.getContext('2d')
      window.addEventListener('resize', () => {
        canvas.width = window.innerWidth
        canvas.height = window.innerHeight
      })
      const LIFESPAN = 2600
      const CONNECT_DIST = 150

      const draw = () => {
        constFrameId = requestAnimationFrame(draw)
        ctx.clearRect(0, 0, canvas.width, canvas.height)
        const now = Date.now()
        // expire
        for (let i = constPoints.length - 1; i >= 0; i--) {
          if (now - constPoints[i].t > LIFESPAN) constPoints.splice(i, 1)
        }
        if (constPoints.length === 0) return
        // connections
        for (let i = 0; i < constPoints.length; i++) {
          for (let j = i + 1; j < constPoints.length; j++) {
            const dx = constPoints[i].x - constPoints[j].x
            const dy = constPoints[i].y - constPoints[j].y
            const dist = Math.sqrt(dx * dx + dy * dy)
            if (dist > CONNECT_DIST) continue
            const maxAge = Math.max((now - constPoints[i].t), (now - constPoints[j].t)) / LIFESPAN
            const proximity = 1 - dist / CONNECT_DIST
            const alpha = (1 - maxAge) * proximity * 0.55
            const grad = ctx.createLinearGradient(constPoints[i].x, constPoints[i].y, constPoints[j].x, constPoints[j].y)
            grad.addColorStop(0, `rgba(${constPoints[i].rgb},${alpha})`)
            grad.addColorStop(1, `rgba(${constPoints[j].rgb},${alpha})`)
            ctx.beginPath()
            ctx.moveTo(constPoints[i].x, constPoints[i].y)
            ctx.lineTo(constPoints[j].x, constPoints[j].y)
            ctx.strokeStyle = grad
            ctx.lineWidth = proximity * 1.4
            ctx.stroke()
          }
        }
        // stars
        constPoints.forEach(p => {
          const age = (now - p.t) / LIFESPAN
          const alpha = age < 0.08 ? age / 0.08 : age > 0.65 ? (1 - age) / 0.35 : 1
          const coreSize = 2.4 * (1 - age * 0.35)
          // glow halo
          const glow = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, coreSize * 7)
          glow.addColorStop(0,   `rgba(${p.rgb},${alpha * 0.55})`)
          glow.addColorStop(0.4, `rgba(${p.rgb},${alpha * 0.18})`)
          glow.addColorStop(1,   `rgba(${p.rgb},0)`)
          ctx.beginPath()
          ctx.arc(p.x, p.y, coreSize * 7, 0, Math.PI * 2)
          ctx.fillStyle = glow
          ctx.fill()
          // bright core
          ctx.beginPath()
          ctx.arc(p.x, p.y, coreSize, 0, Math.PI * 2)
          ctx.fillStyle = `rgba(${p.rgb},${alpha})`
          ctx.fill()
          // subtle cross glint
          ctx.save()
          ctx.translate(p.x, p.y)
          ctx.globalAlpha = alpha * 0.28
          ctx.strokeStyle = `rgba(${p.rgb},1)`
          ctx.lineWidth = 0.8
          ctx.lineCap = 'round'
          const ray = coreSize * 2.8
          ctx.beginPath(); ctx.moveTo(0, -ray); ctx.lineTo(0, ray); ctx.stroke()
          ctx.beginPath(); ctx.moveTo(-ray, 0); ctx.lineTo(ray, 0); ctx.stroke()
          ctx.restore()
        })
      }
      draw()
    }

    // ── Hero canvas mockup: 3D tilt on mouse ──────────────────────────
    const onCardMove = (e) => {
      const el = e.currentTarget
      const rect = el.getBoundingClientRect()
      const rx = ((e.clientY - rect.top)  / rect.height - 0.5) * -12
      const ry = ((e.clientX - rect.left) / rect.width  - 0.5) *  12
      el.style.transition = 'transform 0.07s ease-out, box-shadow 0.07s ease-out'
      el.style.transform  = `perspective(600px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-6px) scale(1.02)`
      el.style.boxShadow  = `0 16px 48px rgba(124,58,237,0.28), 0 0 0 1px rgba(124,58,237,0.28)`
    }

    const onCardLeave = (e) => {
      const el = e.currentTarget
      el.style.transition = 'transform 0.45s ease-out, box-shadow 0.45s ease-out'
      el.style.transform  = ''
      el.style.boxShadow  = ''
    }

    // ── Data loading ──────────────────────────────────────────────────
    const loadStats = async () => {
      try {
        const response = await api.get('/drawings')
        const drawings = response.data.data
        const totalDrawings = drawings.length
        const totalVotes = drawings.reduce((sum, d) => sum + d.votes_count, 0)
        const activeDoodlers = new Set(drawings.map(d => d.artist_name)).size
        stats.value = { totalDrawings, totalVotes, activeDoodlers }
        countUp(totalDrawings, 'totalDrawings')
        countUp(activeDoodlers, 'activeDoodlers', 1200)
        countUp(totalVotes, 'totalVotes', 1600)
      } catch {
        stats.value = { totalDrawings: 0, totalVotes: 0, activeDoodlers: 0 }
      }
    }

    onMounted(() => {
      loadStats()
      initBgAnimation()
      initConstellationEffect()
      document.addEventListener('mousemove', onMouseMove)
    })

    onUnmounted(() => {
      if (animFrameId) cancelAnimationFrame(animFrameId)
      if (constFrameId) cancelAnimationFrame(constFrameId)
      document.removeEventListener('mousemove', onMouseMove)
    })

    return {
      t, isLoggedIn, stats, displayStats, navigateTo,
      bgCanvas, constellationCanvas, heroTextEl, n1El, n2El, n3El,
      onCardMove, onCardLeave,
    }
  }
}
</script>

<!-- Global: cursor-splat appended to body, outside scoped component -->
<style>
.cursor-splat {
  position: fixed;
  border-radius: 50%;
  pointer-events: none;
  z-index: 9999;
  animation: cursor-splat-anim 0.85s ease-out forwards;
}
@keyframes cursor-splat-anim {
  0%   { transform: scale(0); opacity: 1; }
  28%  { transform: scale(1.5); opacity: 0.88; }
  100% { transform: scale(0.15); opacity: 0; }
}
</style>

<style scoped>
/* ── Page wrapper ── */
.home-page {
  min-height: 100vh;
  background: #080a10;
  color: var(--c-text);
  position: relative;
  overflow-x: hidden;
}

/* ── Constellation canvas ── */
.constellation-canvas {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

/* ── Star field ── */
.home-page::before {
  content: '';
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  background:
    radial-gradient(circle, rgba(255,255,255,0.9) 1px, transparent 1px) 0    0    / 113px  89px,
    radial-gradient(circle, rgba(255,255,255,0.6) 1px, transparent 1px) 58px  42px / 167px 137px,
    radial-gradient(circle, rgba(255,255,255,0.75) 1px, transparent 1px) 22px 73px /  97px 119px,
    radial-gradient(circle, rgba(210,190,255,0.7) 1px, transparent 1px) 80px  15px / 211px 177px,
    radial-gradient(circle, rgba(255,255,255,0.5) 1px, transparent 1px) 140px 60px / 131px 101px;
  animation: star-twinkle 5s ease-in-out infinite alternate;
}

@keyframes star-twinkle {
  from { opacity: 0.18; }
  to   { opacity: 0.34; }
}

/* ── Galaxy background ── */
.galaxy-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.nebula {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  /* `translate` CSS property composes with `transform` used by drift animation */
  transition: translate 0.35s ease-out;
}

.n1 {
  width: 700px; height: 560px;
  top: -180px; left: -200px;
  background: radial-gradient(ellipse, rgba(109,40,217,0.38) 0%, transparent 70%);
  animation: drift 22s ease-in-out infinite alternate;
}
.n2 {
  width: 640px; height: 700px;
  top: 15%; right: -180px;
  background: radial-gradient(ellipse, rgba(192,38,211,0.22) 0%, transparent 70%);
  animation: drift 28s ease-in-out infinite alternate-reverse;
}
.n3 {
  width: 600px; height: 450px;
  bottom: 0; left: 15%;
  background: radial-gradient(ellipse, rgba(59,130,246,0.18) 0%, transparent 70%);
  animation: drift 34s ease-in-out infinite alternate;
  animation-delay: -12s;
}
.n4 {
  width: 480px; height: 400px;
  top: 40%; left: 35%;
  background: radial-gradient(ellipse, rgba(236,72,153,0.12) 0%, transparent 70%);
  animation: drift 26s ease-in-out infinite alternate;
  animation-delay: -8s;
}
.n5 {
  width: 380px; height: 500px;
  bottom: 20%; right: 10%;
  background: radial-gradient(ellipse, rgba(16,185,129,0.11) 0%, transparent 70%);
  animation: drift 31s ease-in-out infinite alternate-reverse;
  animation-delay: -5s;
}
.n6 {
  width: 550px; height: 350px;
  top: 60%; left: -100px;
  background: radial-gradient(ellipse, rgba(245,158,11,0.09) 0%, transparent 70%);
  animation: drift 38s ease-in-out infinite alternate;
  animation-delay: -18s;
}
.n7 {
  width: 420px; height: 480px;
  top: 10%; left: 45%;
  background: radial-gradient(ellipse, rgba(124,58,237,0.14) 0%, transparent 70%);
  animation: drift 24s ease-in-out infinite alternate-reverse;
  animation-delay: -3s;
}

.bg-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0.55;
}

@keyframes drift {
  from { transform: translate(0, 0) scale(1); }
  to   { transform: translate(50px, 35px) scale(1.12); }
}

/* ── Floating art emojis ── */
.float-els {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.float-el {
  position: absolute;
  font-size: 1.8rem;
  user-select: none;
  animation: float-wander linear infinite;
  opacity: 0;
}

.fe-1 { top: 12%; left:  8%; animation-duration: 15s; animation-delay:   0s; }
.fe-2 { top: 38%; right: 9%; animation-duration: 19s; animation-delay:  -4s; }
.fe-3 { top: 68%; left: 14%; animation-duration: 13s; animation-delay:  -7s; }
.fe-4 { top: 22%; left: 52%; animation-duration: 17s; animation-delay: -11s; }
.fe-5 { bottom: 18%; right: 22%; animation-duration: 21s; animation-delay: -14s; }
.fe-6 { top: 54%; right: 33%; animation-duration: 11s; animation-delay:  -2s; }
.fe-7 { bottom: 38%; left: 38%; animation-duration: 16s; animation-delay:  -8s; }
.fe-8 { top: 78%;  right: 7%; animation-duration: 14s; animation-delay:  -5s; }

@keyframes float-wander {
  0%   { transform: translate(0, 0) rotate(0deg) scale(0.8); opacity: 0; }
  8%   { opacity: 0.18; }
  45%  { transform: translate(28px, -38px) rotate(18deg) scale(1.1); opacity: 0.22; }
  90%  { opacity: 0.15; }
  100% { transform: translate(-12px, 18px) rotate(-8deg) scale(0.85); opacity: 0; }
}

/* ── All content above background ── */
.hero, .stats-bar, .features-section, .how-section, .cta-section {
  position: relative;
  z-index: 1;
}

/* ═══════════════════════════════════════
   Hero
═══════════════════════════════════════ */
.hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 48px;
  max-width: 1100px;
  margin: 0 auto;
  padding: 80px 40px 64px;
}

.hero-text {
  width: 100%;
  max-width: 520px;
  text-align: center;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: var(--c-accent-soft);
  border: 1px solid rgba(124,58,237,0.5);
  color: #c4b5fd;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 4px 14px;
  border-radius: 999px;
  margin-bottom: 28px;
  box-shadow: 0 0 16px rgba(124,58,237,0.3);
}

.hero-title {
  font-size: clamp(2rem, 4.5vw, 3.2rem);
  font-weight: 900;
  line-height: 1.12;
  margin-bottom: 18px;
  background: linear-gradient(135deg, #a78bfa 0%, #c084fc 35%, #e879f9 70%, #818cf8 100%);
  background-size: 200% 200%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 24px rgba(167,139,250,0.4));
  animation: shimmer 7s ease-in-out infinite alternate;
}

@keyframes shimmer {
  from { background-position: 0% 50%; }
  to   { background-position: 100% 50%; }
}

.hero-sub {
  font-size: 1.05rem;
  color: var(--c-text-dim);
  line-height: 1.7;
  margin-bottom: 36px;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

/* ── Canvas mockup (3D tilt via JS) ── */
.hero-visual {
  flex: 0 0 380px;
  will-change: transform;
}

.canvas-mockup {
  background: #16181f;
  border: 1px solid rgba(124,58,237,0.35);
  border-radius: 14px;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(124,58,237,0.15),
    0 24px 60px rgba(0,0,0,0.6),
    0 0 80px rgba(124,58,237,0.12);
}

.cm-titlebar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  background: #1e2028;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.cm-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--c);
}

.cm-title {
  font-size: 0.7rem;
  color: var(--c-muted);
  margin-left: 6px;
  font-family: 'Courier New', monospace;
}

.cm-canvas {
  display: block;
  width: 100%;
  height: 220px;
  background: #13141a;
}

.cm-toolbar {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: #1e2028;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.cm-tool {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--c-muted);
  cursor: default;
}

.cm-tool.active {
  background: var(--c-accent-soft);
  color: #c4b5fd;
}

.cm-sep { flex: 1; }

.cm-swatch {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,0.15);
}

/* ═══════════════════════════════════════
   Stats bar
═══════════════════════════════════════ */
.stats-bar {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  padding: 28px 24px;
  background: rgba(8,10,16,0.75);
  border-top: 1px solid var(--c-border);
  border-bottom: 1px solid var(--c-border);
  backdrop-filter: blur(12px);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 48px;
}

.stat-num {
  font-size: 2.1rem;
  font-weight: 800;
  color: #c4b5fd;
  line-height: 1;
  text-shadow: 0 0 18px rgba(167,139,250,0.6);
}

.stat-num sup {
  font-size: 0.55em;
  font-weight: 600;
  opacity: 0.7;
  vertical-align: super;
}

.stat-lbl {
  font-size: 0.75rem;
  color: var(--c-muted);
  margin-top: 5px;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

.stat-sep {
  width: 1px;
  height: 38px;
  background: var(--c-border);
}

/* ═══════════════════════════════════════
   Features
═══════════════════════════════════════ */
.features-section {
  max-width: 1100px;
  margin: 0 auto;
  padding: 72px 32px;
}

.section-title {
  text-align: center;
  font-size: 1.65rem;
  font-weight: 800;
  margin-bottom: 48px;
  color: var(--c-text);
  letter-spacing: -0.01em;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.feat-card {
  background: rgba(20,22,30,0.65);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  padding: 32px 28px;
  cursor: pointer;
  backdrop-filter: blur(10px);
  transition: border-color 200ms, transform 200ms, box-shadow 200ms, background 200ms;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.feat-card:hover {
  border-color: rgba(124,58,237,0.6);
  background: rgba(40,42,56,0.85);
}

.feat-icon-wrap {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
}

.feat-icon-wrap--draw    { background: rgba(167,139,250,0.15); color: #a78bfa; }
.feat-icon-wrap--gallery { background: rgba(52,211,153,0.15);  color: #34d399; }
.feat-icon-wrap--messages{ background: rgba(96,165,250,0.15);  color: #60a5fa; }

.feat-card h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--c-text);
  margin: 0;
}

.feat-card p {
  font-size: 0.875rem;
  color: var(--c-muted);
  line-height: 1.65;
  flex: 1;
  margin: 0;
}

.feat-arrow {
  font-size: 0.8rem;
  color: #a78bfa;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  opacity: 0;
  transition: opacity 180ms;
}

.feat-card:hover .feat-arrow {
  opacity: 1;
}

/* ═══════════════════════════════════════
   How it works
═══════════════════════════════════════ */
.how-section {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 32px 72px;
}

.steps-track {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  position: relative;
}

.steps-track::before {
  content: '';
  position: absolute;
  top: 22px;
  left: calc(12.5% + 10px);
  right: calc(12.5% + 10px);
  height: 1px;
  background: linear-gradient(90deg, rgba(124,58,237,0.5), rgba(236,72,153,0.5), rgba(124,58,237,0.5));
  z-index: 0;
}

.step-card {
  background: rgba(20,22,30,0.6);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  padding: 28px 20px;
  text-align: center;
  backdrop-filter: blur(8px);
  position: relative;
  z-index: 1;
  transition: transform 200ms, box-shadow 200ms;
}

.step-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 28px rgba(124,58,237,0.2);
}

.step-num {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--c-accent-soft);
  border: 1px solid rgba(124,58,237,0.55);
  color: #c4b5fd;
  font-size: 1.15rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 0 16px rgba(124,58,237,0.3);
}

.step-card h3 {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 8px;
}

.step-card p {
  font-size: 0.82rem;
  color: var(--c-muted);
  line-height: 1.6;
}

/* ═══════════════════════════════════════
   CTA
═══════════════════════════════════════ */
.cta-section {
  text-align: center;
  padding: 72px 24px 88px;
  position: relative;
  overflow: hidden;
}

.cta-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 55% at 50% 100%, rgba(124,58,237,0.22) 0%, transparent 65%);
  pointer-events: none;
}

.cta-section h2 {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: var(--c-text);
  margin-bottom: 14px;
  position: relative;
}

.cta-section p {
  font-size: 1rem;
  color: var(--c-text-dim);
  margin-bottom: 36px;
  max-width: 460px;
  margin-left: auto;
  margin-right: auto;
  position: relative;
}

.cta-actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 14px;
  position: relative;
}

/* ═══════════════════════════════════════
   Responsive
═══════════════════════════════════════ */
@media (max-width: 960px) {
  .hero {
    flex-direction: column;
    text-align: center;
    padding: 48px 24px 40px;
  }

  .hero-actions { justify-content: center; }

  .features-grid { grid-template-columns: 1fr; }
  .steps-track   { grid-template-columns: repeat(2, 1fr); }
  .steps-track::before { display: none; }
}

@media (max-width: 600px) {
  .hero { padding: 36px 16px 32px; }
  .stat-item { padding: 8px 24px; }
  .features-section, .how-section { padding-left: 16px; padding-right: 16px; }
  .steps-track { grid-template-columns: 1fr; }
}
</style>
