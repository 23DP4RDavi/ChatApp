<template>
  <div class="cb">

    <!-- ═══ HEADER ════════════════════════════════════════════ -->
    <header class="cb-header">
      <div class="cb-header-left">
        <div class="cb-logo-icon">✦</div>
        <div class="cb-logo-text">
          <span class="cb-logo-title">PictoChat</span>
          <span class="cb-logo-sub">Global Room</span>
        </div>
      </div>
      <div class="cb-header-right">
        <div class="cb-online-pill">
          <span class="cb-online-dot"></span>
          <span>{{ onlineCount }} online</span>
        </div>
      </div>
    </header>

    <!-- ═══ MESSAGES ═════════════════════════════════════════ -->
    <div class="cb-msgs" ref="messagesContainer">
      <div v-if="loading" class="cb-state">
        <div class="cb-spinner"></div>
        <span>Loading messages…</span>
      </div>
      <div v-else-if="messages.length === 0" class="cb-state">
        <div class="cb-empty-icon">🎨</div>
        <span class="cb-empty-title">No messages yet</span>
        <span class="cb-empty-sub">Draw something and say hi!</span>
      </div>
      <div v-else class="cb-list">
        <!-- date divider helper -->
        <template v-for="(msg, idx) in messages" :key="msg.id">
          <div v-if="idx === 0 || !sameDay(messages[idx-1].created_at, msg.created_at)" class="cb-date-divider">
            <span>{{ formatDate(msg.created_at) }}</span>
          </div>
          <div :class="['cb-msg', { 'cb-msg--me': msg.user_id === currentUser?.id }]">
            <!-- Avatar (only for others) -->
            <div
              v-if="msg.user_id !== currentUser?.id"
              class="cb-avatar"
              :style="msg.user?.avatar_thumbnail ? {} : { background: userColor(msg.user_id, false) }"
            >
              <img
                v-if="msg.user?.avatar_thumbnail"
                :src="msg.user.avatar_thumbnail"
                :alt="getUserDisplayName(msg.user)"
                class="cb-avatar-img"
                @click.stop="openAvatarZoom(msg.user.avatar_thumbnail, getUserDisplayName(msg.user))"
              />
              <span v-else>{{ getUserInitial(msg.user) }}</span>
            </div>

            <div class="cb-bubble-group">
              <div v-if="msg.user_id !== currentUser?.id" class="cb-sender-name">{{ getUserDisplayName(msg.user) }}</div>

              <div class="cb-bubble" :style="msg.user_id === currentUser?.id ? { '--bubble-bg': userColor(msg.user_id, true) } : {}">
                <div
                  v-if="msg.drawing_data"
                  class="cb-drawing-wrap"
                  @click="openLightbox(msg)"
                >
                  <canvas
                    :ref="el => renderMsgDrawing(el, msg.drawing_data)"
                    :width="msgDrawingDim(msg.drawing_data).w"
                    :height="msgDrawingDim(msg.drawing_data).h"
                    class="cb-drawing"
                  />
                  <div v-if="msg.content && msg.content !== '\uD83C\uDFA8 Drawing'" class="cb-drawing-cap">
                    {{ msg.content }}
                  </div>
                </div>
                <p v-else class="cb-text">{{ msg.content }}</p>

                <div class="cb-meta">
                  <span class="cb-time">{{ formatTime(msg.created_at) }}</span>
                  <button
                    v-if="msg.user_id === currentUser?.id"
                    class="cb-del"
                    @click="deleteMessage(msg.id)"
                    title="Delete"
                  >✕</button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- ═══ DRAWING LIGHTBOX ══════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="lightboxOpen" class="cb-lightbox" @click.self="lightboxOpen = false">
        <div class="cb-lb-panel">
          <div class="cb-lb-bar">
            <span>Drawing</span>
            <button @click="lightboxOpen = false">✕ Close</button>
          </div>
          <canvas ref="lightboxCanvas" class="cb-lb-canvas" />
        </div>
      </div>
    </Teleport>

    <!-- ═══ BOTTOM PANEL ══════════════════════════════════════ -->
    <div class="cb-bottom">

      <!-- Not logged in -->
      <div v-if="!isAuthenticated" class="cb-locked">
        <span>🔒</span>
        <span>Log in to join the conversation</span>
      </div>

      <template v-else>

        <!-- Row 1: Tools -->
        <div v-show="drawPanelOpen" class="cb-draw-panel">
        <div class="cb-toolbar">
          <div class="cb-toolbar-section">
            <button class="cb-tbtn" :class="{ active: drawTool === 'pen' }"    @click="drawTool = 'pen'"      title="Pen">✏️</button>
            <button class="cb-tbtn" :class="{ active: drawTool === 'eraser' }" @click="drawTool = 'eraser'"   title="Eraser">⬜</button>
            <button class="cb-tbtn" :class="{ active: drawTool === 'bucket' }" @click="drawTool = 'bucket'"   title="Fill">🪣</button>
            <button class="cb-tbtn cb-tbtn--text" :class="{ active: drawTool === 'text' }" @click="drawTool = 'text'" title="Text">T</button>
          </div>
          <div class="cb-vsep" />
          <div class="cb-toolbar-section">
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'line' }"      @click="drawTool = 'line'"      title="Line">╱</button>
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'rectangle' }" @click="drawTool = 'rectangle'" title="Rectangle">□</button>
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'circle' }"    @click="drawTool = 'circle'"    title="Circle">○</button>
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'triangle' }"  @click="drawTool = 'triangle'"  title="Triangle">△</button>
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'star' }"      @click="drawTool = 'star'"      title="Star">☆</button>
            <button class="cb-tbtn cb-tbtn--sym" :class="{ active: drawTool === 'arrow' }"     @click="drawTool = 'arrow'"     title="Arrow">→</button>
          </div>
          <div class="cb-vsep" />
          <button class="cb-tbtn cb-tbtn--sym" :class="{ active: shapeFill }" :disabled="!isShapeTool" @click="shapeFill = !shapeFill" title="Fill shape">◼</button>
          <div class="cb-vsep" />
          <div class="cb-toolbar-section">
            <button class="cb-tbtn" :disabled="!drawPaths.length" @click="undo" title="Undo">↩</button>
            <button class="cb-tbtn" :disabled="!redoStack.length" @click="redo" title="Redo">↪</button>
            <button class="cb-tbtn" @click="clearCanvas" title="Clear canvas">🗑️</button>
          </div>
          <div class="cb-spacer" />
          <transition name="fade">
            <span v-if="drawPaths.length" class="cb-ready-hint">✏️ Ready to send</span>
          </transition>
        </div>

        <!-- Row 2: Colors + Size -->
        <div class="cb-style-row">
          <div class="cb-colors">
            <button
              v-for="c in DRAW_COLORS"
              :key="c"
              class="cb-swatch"
              :class="{ active: drawColor === c }"
              :style="{ background: c }"
              @click="drawColor = c"
            />
            <label class="cb-custom-color" title="Custom color">
              <span class="cb-swatch cb-swatch--custom" :style="{ background: drawColor }" />
              <input type="color" v-model="drawColor" class="cb-color-native" />
            </label>
          </div>
          <div class="cb-vsep" />
          <div class="cb-size-row">
            <span class="cb-size-preview" :style="{ width: Math.min(drawSize * 1.8, 18) + 'px', height: Math.min(drawSize * 1.8, 18) + 'px', background: drawTool === 'eraser' ? '#555' : drawColor }" />
            <input type="range" v-model.number="drawSize" min="1" max="20" step="1" class="cb-size-slider" />
            <span class="cb-size-val">{{ drawSize }}</span>
          </div>
        </div>

        <!-- Canvas area -->
        <div class="cb-canvas-wrap">
          <input
            v-if="textInputVisible"
            ref="textInputEl"
            v-model="textDraft"
            class="cb-text-input"
            :style="textInputStyle"
            placeholder="Type text…"
            @keydown.enter.prevent="commitText"
            @keydown.esc.prevent="cancelText"
            @blur="commitText"
          />
          <canvas
            ref="drawCanvas"
            class="cb-canvas"
            :style="{ cursor: cursorStyle }"
            @pointerdown.prevent="onPointerDown"
            @pointermove.prevent="onPointerMove"
            @pointerup.prevent="onPointerUp"
            @pointercancel.prevent="onPointerUp"
          />
        </div>

        </div><!-- /cb-draw-panel -->

        <!-- Caption / text + SEND -->
        <div class="cb-send-row">
          <button
            class="cb-tbtn cb-draw-toggle"
            :class="{ active: drawPanelOpen }"
            :title="drawPanelOpen ? 'Hide drawing tools' : 'Show drawing tools'"
            @click="toggleDrawPanel"
          >🎨</button>
          <input
            v-model="caption"
            class="cb-input"
            :placeholder="drawPaths.length ? 'Add a caption… (optional)' : 'Type a message…'"
            :disabled="sending"
            maxlength="1000"
            autocomplete="off"
            @keydown.enter.prevent="send"
          />
          <button
            class="cb-send-btn"
            :disabled="(!caption.trim() && !drawPaths.length) || sending"
            @click="send"
          >
            <span v-if="sending" class="cb-sending-dots">···</span>
            <span v-else>Send</span>
          </button>
        </div>

      </template>
    </div>

  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'
import { openAvatarZoom } from '@/utils/avatarZoom'
import { getUserDisplayName, getUserInitial } from '@/utils/displayName'

export default {
  name: 'ChatBox',
  setup() {
    const { t, language } = useI18n()

    // ── Message state ────────────────────────────────────────────
    const messages          = ref([])
    const caption           = ref('')
    const lightboxOpen      = ref(false)
    const lightboxCanvas    = ref(null)
    const loading           = ref(true)
    const sending           = ref(false)
    const messagesContainer = ref(null)
    const pollInterval      = ref(null)
    const onlineCount       = ref(0)

    // ── Drawing state ────────────────────────────────────────────
    const drawCanvas       = ref(null)
    const drawPaths        = ref([])
    const redoStack        = ref([])
    const activePath       = ref(null)
    const isDown           = ref(false)
    const drawColor        = ref('#000000')
    const drawTool         = ref('pen')
    const drawSize         = ref(3)
    const shapeFill        = ref(false)
    const shapeStart       = ref(null)
    const textInputVisible = ref(false)
    const textDraft        = ref('')
    const textPoint        = ref({ x: 0, y: 0 })
    const textInputEl      = ref(null)

    const SHAPE_TOOLS = ['line', 'rectangle', 'circle', 'triangle', 'star', 'arrow']
    const isShapeTool = computed(() => SHAPE_TOOLS.includes(drawTool.value))

    // ── Draw panel visibility ──────────────────────────────────────
    const drawPanelOpen = ref(true)          // always open by default
    const toggleDrawPanel = () => {
      drawPanelOpen.value = !drawPanelOpen.value
      if (drawPanelOpen.value) nextTick(initCanvas)
    }

    // Visual viewport resize — no auto-hide; just a no-op kept for cleanup symmetry
    const onVVResize = () => {}

    const cursorStyle = computed(() => {
      if (drawTool.value === 'eraser') return 'cell'
      if (drawTool.value === 'text')   return 'text'
      return 'crosshair'
    })

    const textFontSize = computed(() => Math.max(12, drawSize.value * 3))

    const textInputStyle = computed(() => {
      const c = drawCanvas.value
      if (!c) return {}
      const rect = c.getBoundingClientRect()
      return {
        left:     (textPoint.value.x * rect.width  / c.width)  + 'px',
        top:      (textPoint.value.y * rect.height / c.height - textFontSize.value) + 'px',
        fontSize: textFontSize.value + 'px',
        color:    drawColor.value,
      }
    })

    const DRAW_COLORS = [
      '#000000', '#ffffff', '#ef4444', '#f97316',
      '#eab308', '#22c55e', '#3b82f6', '#8b5cf6',
      '#ec4899', '#6b7280', '#7c2d12', '#065f46',
    ]

    // ── Auth ─────────────────────────────────────────────────────
    const currentUser = computed(() => {
      const d = localStorage.getItem('user')
      return d ? JSON.parse(d) : null
    })
    const isAuthenticated = computed(() => !!localStorage.getItem('token'))

    // ── Formatting ───────────────────────────────────────────────
    const formatTime = (ts) => {
      const date = new Date(ts)
      return date.toLocaleTimeString(language.value === 'lv' ? 'lv-LV' : 'en-US', { hour: '2-digit', minute: '2-digit' })
    }

    const formatDate = (ts) => {
      const date = new Date(ts)
      const today = new Date()
      const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1)
      if (date.toDateString() === today.toDateString()) return 'Today'
      if (date.toDateString() === yesterday.toDateString()) return 'Yesterday'
      return date.toLocaleDateString(language.value === 'lv' ? 'lv-LV' : 'en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    }

    const sameDay = (a, b) => new Date(a).toDateString() === new Date(b).toDateString()

    // ── User colours ─────────────────────────────────────────────
    const C_OTHER = ['#1a6b3a','#8c3318','#7a5c10','#5c1a8c','#1a6b6b','#6b1a3a','#2a6b1a','#1a3a8c']
    const C_OWN   = ['#1e4e9c','#2a7a9c','#1a4a6a','#2e5aac']
    const userColor = (uid, isMe) => {
      const p = isMe ? C_OWN : C_OTHER
      return p[((uid ?? 0) - 1 + p.length) % p.length]
    }

    // ── Scroll ───────────────────────────────────────────────────
    const scrollToBottom = () => {
      nextTick(() => {
        if (messagesContainer.value)
          messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
      })
    }

    // ── Message API ──────────────────────────────────────────────
    const loadMessages = async () => {
      try {
        const res = await api.get('/messages?per_page=100')
        messages.value = res.data.data.reverse()
        scrollToBottom()
      } catch (e) {
        console.error('load failed:', e)
      } finally {
        loading.value = false
      }
    }

    const pollNew = async () => {
      if (!messages.value.length) return
      try {
        const lastId = messages.value[messages.value.length - 1].id
        const res = await api.get(`/messages/new?last_id=${lastId}`)
        if (res.data.data.length) {
          messages.value.push(...res.data.data)
          scrollToBottom()
        }
      } catch (e) { /* silent */ }
    }

    const deleteMessage = async (id) => {
      if (!confirm(t('chatBox.deleteConfirm'))) return
      try {
        await api.delete(`/messages/${id}`)
        messages.value = messages.value.filter(m => m.id !== id)
      } catch (e) {
        alert(t('chatBox.deleteFailed'))
      }
    }

    // ── Send ─────────────────────────────────────────────────────
    const send = async () => {
      const hasDrawing = drawPaths.value.length > 0
      const hasText    = caption.value.trim()
      if ((!hasDrawing && !hasText) || sending.value) return

      sending.value = true
      try {
        if (hasDrawing) {
          const c = drawCanvas.value
          const res = await api.post('/messages', {
            content: hasText || '\uD83C\uDFA8 Drawing',
            type: 'drawing',
            drawing_data: { paths: drawPaths.value, width: c.width, height: c.height },
          })
          messages.value.push(res.data.data)
          clearCanvas()
          caption.value = ''
        } else {
          const res = await api.post('/messages', { content: hasText, type: 'text' })
          messages.value.push(res.data.data)
          caption.value = ''
        }
        scrollToBottom()
      } catch (e) {
        alert(t('chatBox.sendFailed'))
      } finally {
        sending.value = false
      }
    }

    // ── Drawing helpers ───────────────────────────────────────────
    const normalizeRect = (s, e) => ({
      x: Math.min(s.x, e.x), y: Math.min(s.y, e.y),
      w: Math.abs(e.x - s.x), h: Math.abs(e.y - s.y),
    })

    const drawShapeOnCtx = (ctx, shapeType, start, end, { color, width, fill = false }) => {
      ctx.save()
      ctx.strokeStyle = color; ctx.fillStyle = color
      ctx.lineWidth = width; ctx.lineCap = 'round'; ctx.lineJoin = 'round'
      if (shapeType === 'line') {
        ctx.beginPath(); ctx.moveTo(start.x, start.y); ctx.lineTo(end.x, end.y); ctx.stroke()
        ctx.restore(); return
      }
      if (shapeType === 'arrow') {
        const dx = end.x - start.x, dy = end.y - start.y
        const angle = Math.atan2(dy, dx), headLen = Math.max(10, width * 4)
        ctx.beginPath(); ctx.moveTo(start.x, start.y); ctx.lineTo(end.x, end.y); ctx.stroke()
        ctx.beginPath(); ctx.moveTo(end.x, end.y)
        ctx.lineTo(end.x - headLen * Math.cos(angle - Math.PI / 6), end.y - headLen * Math.sin(angle - Math.PI / 6))
        ctx.lineTo(end.x - headLen * Math.cos(angle + Math.PI / 6), end.y - headLen * Math.sin(angle + Math.PI / 6))
        ctx.closePath(); ctx.fill(); ctx.restore(); return
      }
      const { x, y, w, h } = normalizeRect(start, end)
      if (shapeType === 'rectangle') {
        if (fill) ctx.fillRect(x, y, w, h); ctx.strokeRect(x, y, w, h)
        ctx.restore(); return
      }
      if (shapeType === 'circle') {
        ctx.beginPath()
        ctx.ellipse(x + w / 2, y + h / 2, Math.max(1, w / 2), Math.max(1, h / 2), 0, 0, Math.PI * 2)
        if (fill) ctx.fill(); ctx.stroke(); ctx.restore(); return
      }
      const drawPoly = (pts) => {
        if (!pts.length) return
        ctx.beginPath(); ctx.moveTo(pts[0].x, pts[0].y)
        for (let i = 1; i < pts.length; i++) ctx.lineTo(pts[i].x, pts[i].y)
        ctx.closePath(); if (fill) ctx.fill(); ctx.stroke()
      }
      if (shapeType === 'triangle') {
        drawPoly([{ x: x + w / 2, y }, { x, y: y + h }, { x: x + w, y: y + h }])
        ctx.restore(); return
      }
      if (shapeType === 'star') {
        const cx = x + w / 2, cy = y + h / 2
        const outer = Math.max(1, Math.min(w, h) / 2), inner = outer * 0.45
        const pts = []
        for (let i = 0; i < 10; i++) {
          const r = i % 2 === 0 ? outer : inner
          const a = (-Math.PI / 2) + (i * Math.PI / 5)
          pts.push({ x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) })
        }
        drawPoly(pts)
      }
      ctx.restore()
    }

    const hexToRgb = (hex) => [
      parseInt(hex.slice(1, 3), 16),
      parseInt(hex.slice(3, 5), 16),
      parseInt(hex.slice(5, 7), 16),
    ]

    const applyFloodFill = (canvasEl, startX, startY, fillHex) => {
      const ctx = canvasEl.getContext('2d', { willReadFrequently: true })
      const [fr, fg, fb] = hexToRgb(fillHex)
      const imageData = ctx.getImageData(0, 0, canvasEl.width, canvasEl.height)
      const data = imageData.data
      const si = (startY * canvasEl.width + startX) * 4
      const [tr, tg, tb] = [data[si], data[si + 1], data[si + 2]]
      if (tr === fr && tg === fg && tb === fb) return
      const matches = (i) =>
        Math.abs(data[i] - tr) < 32 && Math.abs(data[i + 1] - tg) < 32 && Math.abs(data[i + 2] - tb) < 32
      const visited = new Uint8Array(canvasEl.width * canvasEl.height)
      const stack = [[startX, startY]]
      while (stack.length > 0) {
        const [x, y] = stack.pop()
        if (x < 0 || x >= canvasEl.width || y < 0 || y >= canvasEl.height) continue
        const vi = y * canvasEl.width + x
        if (visited[vi]) continue
        const pi = vi * 4
        if (!matches(pi)) continue
        visited[vi] = 1
        data[pi] = fr; data[pi + 1] = fg; data[pi + 2] = fb; data[pi + 3] = 255
        stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1])
      }
      ctx.putImageData(imageData, 0, 0)
    }

    const renderOnePath = (canvasEl, ctx, p) => {
      if (p.type === 'stroke') {
        if (!p.points || p.points.length === 0) return
        if (p.points.length === 1) {
          const pt = p.points[0]
          ctx.save()
          ctx.fillStyle = p.color
          const r = Math.max(0.5, p.size / 2)
          ctx.beginPath()
          ctx.arc(pt.x, pt.y, r, 0, Math.PI * 2)
          ctx.fill()
          ctx.restore()
          return
        }
        ctx.save()
        ctx.strokeStyle = p.color; ctx.lineWidth = p.size
        ctx.lineCap = 'round'; ctx.lineJoin = 'round'
        ctx.beginPath(); ctx.moveTo(p.points[0].x, p.points[0].y)
        for (let i = 1; i < p.points.length; i++) ctx.lineTo(p.points[i].x, p.points[i].y)
        ctx.stroke(); ctx.restore()
      } else if (p.type === 'shape') {
        drawShapeOnCtx(ctx, p.shapeType, p.start, p.end, { color: p.color, width: p.width, fill: p.fill })
      } else if (p.type === 'fill') {
        applyFloodFill(canvasEl, p.x, p.y, p.color)
      } else if (p.type === 'text') {
        ctx.save()
        ctx.fillStyle = p.color; ctx.textBaseline = 'top'
        ctx.font = `${p.fontSize}px Nunito, system-ui, sans-serif`
        ctx.fillText(p.text, p.x, p.y)
        ctx.restore()
      }
    }

    // ── Received drawing render ───────────────────────────────────
    const msgDrawingDim = (data) => {
      if (!data) return { w: 300, h: 180 }
      if (data.width && data.height)
        return { w: Math.min(data.width, 320), h: Math.min(data.height, 320 * data.height / data.width) }
      return { w: 300, h: 180 }
    }

    const renderMsgDrawing = (el, data) => {
      if (!el || !data) return
      const paths = data.paths || (Array.isArray(data) ? data : [])
      if (!paths.length) return
      const dim = msgDrawingDim(data)
      el.width = dim.w; el.height = dim.h
      const scaleX = dim.w / (data.width || dim.w)
      const scaleY = dim.h / (data.height || dim.h)
      const ctx = el.getContext('2d', { willReadFrequently: true })
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, dim.w, dim.h)
      paths.forEach(p => {
        if (p.type === 'fill') {
          applyFloodFill(el, Math.round(p.x * scaleX), Math.round(p.y * scaleY), p.color)
        } else {
          ctx.save(); ctx.scale(scaleX, scaleY)
          renderOnePath(el, ctx, p)
          ctx.restore()
        }
      })
    }

    const openLightbox = (msg) => {
      lightboxOpen.value = true
      nextTick(() => {
        if (lightboxCanvas.value) renderMsgDrawing(lightboxCanvas.value, msg.drawing_data)
      })
    }

    // ── Input canvas ─────────────────────────────────────────────
    const initCanvas = (attempt = 0) => {
      const c = drawCanvas.value
      if (!c) return
      const rect = c.getBoundingClientRect()
      if (!rect.width) {
        // Element not laid out yet (e.g. v-show just turned on) — retry
        if (attempt < 5) setTimeout(() => initCanvas(attempt + 1), 80)
        return
      }
      c.width  = Math.round(rect.width)
      c.height = Math.round(rect.height)
      redraw()
    }

    const redraw = (previewEnd = null) => {
      const c = drawCanvas.value
      if (!c) return
      const ctx = c.getContext('2d', { willReadFrequently: true })
      const { width: w, height: h } = c

      ctx.fillStyle = '#fff'
      ctx.fillRect(0, 0, w, h)

      ctx.strokeStyle = '#e4e4e4'
      ctx.lineWidth = 1
      for (let y = 20; y < h; y += 20) {
        ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke()
      }

      drawPaths.value.forEach(p => renderOnePath(c, ctx, p))
      if (activePath.value) renderOnePath(c, ctx, activePath.value)

      if (previewEnd && shapeStart.value && isShapeTool.value) {
        drawShapeOnCtx(ctx, drawTool.value, shapeStart.value, previewEnd, {
          color: drawColor.value, width: drawSize.value, fill: shapeFill.value,
        })
      }
    }

    const canvasXY = (e) => {
      const c = drawCanvas.value
      const r = c.getBoundingClientRect()
      return {
        x: (e.clientX - r.left) * (c.width  / r.width),
        y: (e.clientY - r.top)  * (c.height / r.height),
      }
    }

    // ── Undo / Redo ───────────────────────────────────────────────
    const undo = () => {
      if (!drawPaths.value.length) return
      redoStack.value.push(drawPaths.value.pop())
      redraw()
    }

    const redo = () => {
      if (!redoStack.value.length) return
      drawPaths.value.push(redoStack.value.pop())
      redraw()
    }

    // ── Text tool ─────────────────────────────────────────────────
    const openTextAt = (pos) => {
      textPoint.value = { ...pos }
      textDraft.value = ''
      textInputVisible.value = true
      nextTick(() => textInputEl.value?.focus())
    }

    const commitText = () => {
      if (!textInputVisible.value) return
      const text = textDraft.value.trim()
      textInputVisible.value = false
      textDraft.value = ''
      if (!text) return
      redoStack.value = []
      drawPaths.value.push({ type: 'text', text, x: textPoint.value.x, y: textPoint.value.y, color: drawColor.value, fontSize: textFontSize.value })
      redraw()
    }

    const cancelText = () => {
      textInputVisible.value = false
      textDraft.value = ''
    }

    // ── Pointer handlers ──────────────────────────────────────────
    const onPointerDown = (e) => {
      e.target.setPointerCapture(e.pointerId)
      const pos = canvasXY(e)

      if (drawTool.value === 'text') { openTextAt(pos); return }

      if (drawTool.value === 'bucket') {
        redoStack.value = []
        const x = Math.round(pos.x), y = Math.round(pos.y)
        drawPaths.value.push({ type: 'fill', x, y, color: drawColor.value })
        const c = drawCanvas.value
        if (c) applyFloodFill(c, x, y, drawColor.value)
        return
      }

      isDown.value = true
      if (isShapeTool.value) { shapeStart.value = { ...pos }; return }

      activePath.value = {
        type:   'stroke',
        color:  drawTool.value === 'eraser' ? '#ffffff' : drawColor.value,
        size:   drawTool.value === 'eraser' ? drawSize.value * 4 : drawSize.value,
        points: [pos],
      }

      // Render an immediate point so tap-only input leaves a dot.
      redraw()
    }

    const onPointerMove = (e) => {
      if (!isDown.value) return
      const pos = canvasXY(e)
      if (isShapeTool.value) { redraw(pos); return }
      if (activePath.value) { activePath.value.points.push(pos); redraw() }
    }

    const onPointerUp = (e) => {
      if (!isDown.value) return
      isDown.value = false

      if (isShapeTool.value && shapeStart.value) {
        const pos = e?.clientX !== undefined ? canvasXY(e) : shapeStart.value
        redoStack.value = []
        drawPaths.value.push({
          type: 'shape', shapeType: drawTool.value,
          start: { ...shapeStart.value }, end: { ...pos },
          color: drawColor.value, width: drawSize.value, fill: shapeFill.value,
        })
        shapeStart.value = null
        redraw()
        return
      }

      if (activePath.value) {
        const p = activePath.value
        if (p.points.length >= 1) { redoStack.value = []; drawPaths.value.push(p) }
        activePath.value = null
        redraw()
      }
    }

    const clearCanvas = () => {
      redoStack.value        = []
      drawPaths.value        = []
      activePath.value       = null
      shapeStart.value       = null
      isDown.value           = false
      textInputVisible.value = false
      redraw()
    }

    // ── Resize ───────────────────────────────────────────────────
    // NOTE: do NOT clear drawPaths here — on mobile, keyboard open/close fires
    // window.resize and would wipe the user's drawing.
    let resizeTimer = null
    const onResize = () => {
      clearTimeout(resizeTimer)
      resizeTimer = setTimeout(initCanvas, 250)
    }

    // ── Lifecycle ────────────────────────────────────────────────
    onMounted(() => {
      loadMessages()
      onlineCount.value = Math.floor(Math.random() * 20) + 5
      pollInterval.value = setInterval(pollNew, 3000)
      nextTick(initCanvas)
      window.addEventListener('resize', onResize)
      if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', onVVResize)
      }
    })

    onUnmounted(() => {
      if (pollInterval.value) clearInterval(pollInterval.value)
      window.removeEventListener('resize', onResize)
      clearTimeout(resizeTimer)
      if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', onVVResize)
      }
    })

    return {
      t, messages, caption, loading, sending,
      messagesContainer, currentUser, isAuthenticated, onlineCount,
      lightboxOpen, lightboxCanvas,
      drawCanvas, drawPaths, redoStack, drawColor, drawTool, drawSize,
      shapeFill, isShapeTool, cursorStyle, DRAW_COLORS,
      textInputVisible, textDraft, textInputEl, textInputStyle,
      drawPanelOpen, toggleDrawPanel,
      formatTime, formatDate, sameDay, userColor, deleteMessage,
      msgDrawingDim, renderMsgDrawing, openLightbox,
      onPointerDown, onPointerMove, onPointerUp,
      clearCanvas, undo, redo, commitText, cancelText, send,
    }
  }
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   SHELL
═══════════════════════════════════════════════ */
.cb {
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  background: #1a1b1e;
  font-family: 'Nunito', system-ui, sans-serif;
}

/* ═══════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════ */
.cb-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  height: 58px;
  background: #16171a;
  border-bottom: 1px solid rgba(255,255,255,.06);
  flex-shrink: 0;
}
.cb-header-left  { display: flex; align-items: center; gap: 10px; }
.cb-logo-icon {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, #7c6af7, #5b8ef5);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; color: #fff;
  box-shadow: 0 2px 12px rgba(124,106,247,.4);
}
.cb-logo-title {
  display: block;
  font-size: 15px;
  font-weight: 700;
  color: #e8e9f0;
  line-height: 1.2;
  letter-spacing: 0.2px;
}
.cb-logo-sub {
  display: block;
  font-size: 11px;
  color: #6b6d80;
  line-height: 1.2;
}
.cb-online-pill {
  display: flex; align-items: center; gap: 6px;
  background: rgba(109,223,109,.08);
  border: 1px solid rgba(109,223,109,.2);
  border-radius: 20px;
  padding: 4px 12px;
  font-size: 12px;
  color: #6ddf6d;
  font-weight: 600;
}
.cb-online-dot {
  width: 7px; height: 7px;
  background: #6ddf6d;
  border-radius: 50%;
  box-shadow: 0 0 6px #6ddf6d;
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: .4; }
}

/* ═══════════════════════════════════════════════
   MESSAGES AREA
═══════════════════════════════════════════════ */
.cb-msgs {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 16px 16px 8px;
  display: flex;
  flex-direction: column;
  scroll-behavior: smooth;
}
.cb-msgs::-webkit-scrollbar       { width: 4px; }
.cb-msgs::-webkit-scrollbar-track { background: transparent; }
.cb-msgs::-webkit-scrollbar-thumb { background: #2e3040; border-radius: 4px; }

/* empty / loading */
.cb-state {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 10px;
  color: #4a4c60;
}
.cb-spinner {
  width: 28px; height: 28px;
  border: 3px solid #2e3040;
  border-top-color: #7c6af7;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.cb-empty-icon  { font-size: 40px; line-height: 1; }
.cb-empty-title { font-size: 15px; font-weight: 700; color: #5a5c70; }
.cb-empty-sub   { font-size: 13px; color: #3e4055; }

/* ── date divider ─────────────────────────────── */
.cb-date-divider {
  display: flex; align-items: center; gap: 10px;
  margin: 14px 0 8px;
}
.cb-date-divider::before,
.cb-date-divider::after {
  content: ''; flex: 1; height: 1px;
  background: rgba(255,255,255,.06);
}
.cb-date-divider span {
  font-size: 11px; font-weight: 600; color: #4a4c60;
  white-space: nowrap; letter-spacing: 0.4px;
}

/* ── message list ─────────────────────────────── */
.cb-list { display: flex; flex-direction: column; gap: 4px; }

/* ── single message row ───────────────────────── */
.cb-msg {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  max-width: 78%;
  align-self: flex-start;
}
.cb-msg--me { align-self: flex-end; flex-direction: row-reverse; }

.cb-avatar {
  width: 32px; height: 32px;
  border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: rgba(255,255,255,.9);
  text-shadow: 0 1px 2px rgba(0,0,0,.4);
  overflow: hidden;
}
.cb-avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 50%;
}

.cb-bubble-group {
  display: flex; flex-direction: column; gap: 2px;
  min-width: 0;
}

.cb-sender-name {
  font-size: 11.5px; font-weight: 700;
  color: #7c8090; margin-left: 4px;
  margin-bottom: 1px;
}

.cb-bubble {
  background: #252730;
  border-radius: 18px 18px 18px 4px;
  padding: 0;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,.05);
  transition: border-color 120ms;
  position: relative;
  --bubble-bg: #252730;
}
.cb-msg--me .cb-bubble {
  background: var(--bubble-bg, #2e4a8a);
  border-radius: 18px 18px 4px 18px;
  border-color: transparent;
}

.cb-text {
  margin: 0;
  padding: 10px 14px 6px;
  font-size: 14px;
  color: #d8dae8;
  line-height: 1.5;
  word-break: break-word;
}
.cb-msg--me .cb-text { color: rgba(255,255,255,.9); }

.cb-meta {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 6px; padding: 0 10px 6px;
}
.cb-time {
  font-size: 10.5px; color: rgba(255,255,255,.28);
  white-space: nowrap; line-height: 1;
}
.cb-del {
  display: none;
  background: none; border: none;
  color: rgba(255,255,255,.35);
  font-size: 10px; cursor: pointer; padding: 0;
  line-height: 1;
  transition: color 80ms;
}
.cb-del:hover { color: #f87171; }
.cb-bubble:hover .cb-del { display: block; }

/* drawing inside bubble */
.cb-drawing-wrap { cursor: zoom-in; }
.cb-drawing      { display: block; max-width: 100%; border-radius: 0; }
.cb-drawing-cap  {
  padding: 6px 14px 4px;
  font-size: 12px; color: #9a9bb0; font-style: italic;
}

/* ═══════════════════════════════════════════════
   LIGHTBOX
═══════════════════════════════════════════════ */
.cb-lightbox {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.8);
  display: flex; align-items: center; justify-content: center;
  z-index: 9000; backdrop-filter: blur(6px);
}
.cb-lb-panel {
  display: flex; flex-direction: column;
  background: #1e1f25;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 16px;
  overflow: hidden;
  max-width: 90vw; max-height: 90vh;
  box-shadow: 0 24px 60px rgba(0,0,0,.6);
}
.cb-lb-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px;
  background: #16171a;
  font-size: 13px; font-weight: 600; color: #9a9bb0;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.cb-lb-bar button {
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
  color: #9a9bb0; border-radius: 8px;
  font-size: 12px; cursor: pointer; padding: 4px 12px;
  font-family: inherit; transition: all 120ms;
}
.cb-lb-bar button:hover { background: rgba(255,255,255,.12); color: #d8dae8; }
.cb-lb-canvas { display: block; max-width: 100%; max-height: calc(90vh - 52px); }

/* ═══════════════════════════════════════════════
   BOTTOM PANEL
═══════════════════════════════════════════════ */
.cb-bottom {
  flex-shrink: 0;
  background: #16171a;
  border-top: 1px solid rgba(255,255,255,.06);
  display: flex; flex-direction: column;
}

.cb-locked {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  padding: 16px;
  font-size: 13px; color: #4a4c60; font-weight: 600;
}

/* toolbar */
.cb-toolbar {
  display: flex; align-items: center; gap: 4px;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(255,255,255,.04);
  overflow-x: auto; flex-shrink: 0;
  min-height: 44px;
}
.cb-toolbar::-webkit-scrollbar { display: none; }
.cb-toolbar-section { display: flex; align-items: center; gap: 3px; }

.cb-swatch {
  width: 20px; height: 20px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer; padding: 0; flex-shrink: 0;
  transition: transform 100ms, border-color 100ms;
  outline-offset: 2px;
}
.cb-swatch:hover         { transform: scale(1.25); border-color: rgba(255,255,255,.4); }
.cb-swatch.active        { border-color: #fff; transform: scale(1.2); box-shadow: 0 0 0 2px rgba(255,255,255,.3); }

.cb-vsep { width: 1px; height: 20px; background: rgba(255,255,255,.1); flex-shrink: 0; margin: 0 4px; }

.cb-tbtn {
  width: 30px; height: 30px; flex-shrink: 0;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 8px;
  color: #7c8090; font-size: 14px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; padding: 0; line-height: 1;
  transition: all 100ms;
}
.cb-tbtn:hover  { background: rgba(255,255,255,.1); color: #c8cad8; border-color: rgba(255,255,255,.15); }
.cb-tbtn.active { background: rgba(124,106,247,.2); border-color: rgba(124,106,247,.5); color: #a89cf7; }

.cb-dot-btn {
  width: 30px; height: 30px; flex-shrink: 0;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 8px;
  cursor: pointer; padding: 0;
  display: flex; align-items: center; justify-content: center;
  transition: all 100ms;
}
.cb-dot-btn::after {
  content: ''; display: block; border-radius: 50%;
  background: #5a5c70; width: 4px; height: 4px;
}
.cb-dot-btn.md::after { width:  8px; height:  8px; }
.cb-dot-btn.lg::after { width: 14px; height: 14px; }
.cb-dot-btn:hover           { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.15); }
.cb-dot-btn:hover::after    { background: #c8cad8; }
.cb-dot-btn.active          { background: rgba(124,106,247,.2); border-color: rgba(124,106,247,.5); }
.cb-dot-btn.active::after   { background: #a89cf7; }

.cb-spacer { flex: 1; }
.cb-ready-hint {
  font-size: 11.5px; color: #7c6af7; font-weight: 700;
  white-space: nowrap; flex-shrink: 0; letter-spacing: 0.3px;
}
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

/* draw panel (collapsible on mobile) */
.cb-draw-panel {
  display: flex;
  flex-direction: column;
}

/* draw toggle button (always visible, draw panel indicator) */
.cb-draw-toggle {
  font-size: 16px;
  flex-shrink: 0;
}

/* canvas */
.cb-canvas-wrap {
  flex-shrink: 0; background: #0d0e10;
  padding: 4px 12px 0;
  position: relative;
}
.cb-canvas {
  display: block; width: 100%; height: 120px;
  background: #fff; border-radius: 8px;
  touch-action: none; user-select: none; -webkit-user-select: none;
}

/* send row */
.cb-send-row {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px;
}
.cb-input {
  flex: 1; min-width: 0; height: 38px;
  padding: 0 14px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 20px;
  color: #d8dae8; font-family: inherit; font-size: 14px;
  outline: none; transition: border-color 150ms, background 150ms;
}
.cb-input:focus        { border-color: rgba(124,106,247,.5); background: rgba(255,255,255,.08); }
.cb-input::placeholder { color: #3e4055; }
.cb-input:disabled     { opacity: .4; }

.cb-send-btn {
  flex-shrink: 0; height: 38px; padding: 0 18px;
  background: linear-gradient(135deg, #7c6af7, #5b8ef5);
  border: none; border-radius: 20px;
  color: #fff; font-family: inherit; font-size: 13px; font-weight: 700;
  cursor: pointer; letter-spacing: 0.3px;
  transition: opacity 120ms, transform 100ms;
  box-shadow: 0 2px 12px rgba(124,106,247,.4);
}
.cb-send-btn:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
.cb-send-btn:active:not(:disabled) { transform: translateY(0); }
.cb-send-btn:disabled { opacity: .35; cursor: not-allowed; box-shadow: none; }
.cb-sending-dots { letter-spacing: 2px; }

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
/* ── style row ──────────────────────────────────────────── */
.cb-style-row {
  display: flex; align-items: center; gap: 4px;
  padding: 4px 12px 6px;
  border-bottom: 1px solid rgba(255,255,255,.04);
  flex-shrink: 0; overflow-x: auto; min-height: 36px;
}
.cb-style-row::-webkit-scrollbar { display: none; }
.cb-colors { display: flex; align-items: center; gap: 3px; flex-shrink: 0; }

.cb-custom-color {
  display: flex; align-items: center; cursor: pointer;
  position: relative; flex-shrink: 0;
}
.cb-swatch--custom { border-style: dashed !important; border-color: rgba(255,255,255,.4) !important; }
.cb-color-native {
  position: absolute; opacity: 0; width: 0; height: 0;
  pointer-events: none;
}
.cb-custom-color:hover .cb-swatch { transform: scale(1.25); }
.cb-custom-color input[type=color] { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; top: 0; left: 0; pointer-events: auto; }

.cb-size-row { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.cb-size-preview {
  flex-shrink: 0; border-radius: 50%; min-width: 4px; min-height: 4px;
  transition: width .1s, height .1s, background .15s;
}
.cb-size-slider {
  width: 72px; height: 4px;
  -webkit-appearance: none; appearance: none;
  background: rgba(255,255,255,.15); border-radius: 4px;
  outline: none; cursor: pointer; flex-shrink: 0;
}
.cb-size-slider::-webkit-slider-thumb {
  -webkit-appearance: none; appearance: none;
  width: 14px; height: 14px; border-radius: 50%;
  background: #7c6af7; cursor: pointer;
}
.cb-size-slider::-moz-range-thumb {
  width: 14px; height: 14px; border-radius: 50%;
  background: #7c6af7; cursor: pointer; border: none;
}
.cb-size-val { font-size: 11px; color: #6b6d80; min-width: 18px; text-align: center; }

/* tool button variants */
.cb-tbtn--text { font-weight: 800; font-size: 13px; }
.cb-tbtn--sym  { font-size: 15px; line-height: 1; }
.cb-tbtn:disabled { opacity: .3; cursor: not-allowed; }

/* text tool overlay */
.cb-text-input {
  position: absolute; z-index: 10;
  background: rgba(0,0,0,.65);
  border: 1.5px solid #7c6af7;
  border-radius: 4px;
  padding: 2px 8px;
  font-family: Nunito, system-ui, sans-serif;
  outline: none;
  min-width: 80px; max-width: 220px;
  pointer-events: auto;
}

@media (max-width: 600px) {
  .cb-header { padding: 0 14px; }
  .cb-msgs   { padding: 12px 10px 6px; }
  .cb-msg    { max-width: 88%; }
  .cb-canvas { height: 160px; }
  .cb-send-row { padding: 6px 10px; }
  .cb-toolbar  { padding: 6px 10px; gap: 3px; }
  .cb-tbtn { width: 34px; height: 34px; }
  .cb-swatch { width: 22px; height: 22px; }
}
@media (min-width: 960px) {
  .cb-canvas { height: 140px; }
}
</style>
