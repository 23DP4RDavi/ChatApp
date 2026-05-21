<template>
  <v-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :max-width="squareOnly ? 680 : 960"
    :fullscreen="isMobile"
    :eager="true"
    persistent
  >
    <v-card class="draw-dlg">
      <!-- ── Toolbar ── -->
      <div class="dd-toolbar" @click.stop>
        <div class="dd-toolbar-inner">
          <!-- Tool buttons -->
          <div class="dd-tool-group">
            <button v-for="tb in toolList" :key="tb.val"
              class="dd-btn" :class="{ active: tool === tb.val }"
              @click="tool = tb.val" :title="tb.label">
              <v-icon size="16">{{ tb.icon }}</v-icon>
            </button>
          </div>
          <div class="dd-sep" />

          <!-- Brush types (pen only) -->
          <template v-if="tool === 'pen'">
            <button v-for="bt in brushTypes" :key="bt.value"
              class="dd-btn" :class="{ active: brushType === bt.value }"
              @click="brushType = bt.value" :title="bt.label">
              <v-icon size="16">{{ bt.icon }}</v-icon>
            </button>
            <div class="dd-sep" />
          </template>

          <!-- Color (not when eraser) -->
          <template v-if="tool !== 'eraser'">
            <label class="dd-color-btn" :title="currentColor">
              <span class="dd-color-dot" :style="{ background: currentColor }" />
              <input type="color" v-model="currentColor" class="dd-hidden-input" />
            </label>
            <button v-for="c in colorPresets.slice(0, 7)" :key="c"
              class="dd-preset" :style="{ background: c }"
              :class="{ active: currentColor === c }"
              @click="currentColor = c" />
            <div class="dd-sep" />
          </template>

          <!-- Fill (shape tools) -->
          <template v-if="isShapeTool && tool !== 'line' && tool !== 'arrow'">
            <label class="dd-check">
              <input type="checkbox" v-model="shapeFill" />
              <span>Fill</span>
            </label>
            <div class="dd-sep" />
          </template>

          <!-- Size -->
          <span class="dd-size-label">{{ brushSize }}px</span>
          <input type="range" v-model.number="brushSize" min="1" max="30" step="1" class="dd-range" />
          <div class="dd-sep" />

          <!-- Undo / redo / clear -->
          <button class="dd-btn" :disabled="paths.length === 0" @click="undo" title="Undo">
            <v-icon size="16">mdi-undo</v-icon>
          </button>
          <button class="dd-btn" :disabled="redoStack.length === 0" @click="redo" title="Redo">
            <v-icon size="16">mdi-redo</v-icon>
          </button>
          <button class="dd-btn dd-btn--danger" @click="clearCanvas" title="Clear">
            <v-icon size="16">mdi-delete-outline</v-icon>
          </button>
        </div>
      </div>

      <!-- ── Mobile-only color + size strip (like Draw.vue style panel) ── -->
      <div class="dd-colorstrip" v-show="tool !== 'eraser'">
        <label class="dd-cs-picker" :title="currentColor">
          <span class="dd-cs-dot" :style="{ background: currentColor }" />
          <input type="color" v-model="currentColor" class="dd-hidden-input" />
        </label>
        <div class="dd-cs-swatches">
          <button
            v-for="c in colorPresets"
            :key="c"
            class="dd-cs-swatch"
            :style="{ background: c }"
            :class="{ active: currentColor === c }"
            @click="currentColor = c"
          />
        </div>
        <div class="dd-cs-size">
          <input type="range" v-model.number="brushSize" min="1" max="30" step="1" class="dd-cs-range" />
          <span class="dd-cs-val">{{ brushSize }}px</span>
        </div>
      </div>

      <!-- ── Canvas area ── -->
      <div class="dd-canvas-wrap">
        <div v-if="paths.length === 0 && !isDrawingNow" class="dd-hint">
          <v-icon size="36">mdi-draw</v-icon>
          <span>Start drawing</span>
        </div>
        <canvas ref="canvas"
          @pointerdown.prevent.stop="startDrawing"
          @pointermove.prevent.stop="draw"
          @pointerup.prevent.stop="stopDrawing"
          @pointercancel.prevent.stop="stopDrawing"
          class="dd-canvas" />
        <input v-if="textEditorVisible" ref="textEditorInput"
          v-model="textDraft"
          class="dd-text-input" :style="textInputStyle"
          placeholder="Type text…"
          @keydown.enter.prevent="commitTextInput"
          @keydown.esc.prevent="cancelTextInput" />
      </div>

      <!-- ── Footer ── -->
      <div class="dd-footer">
        <input
          v-if="showCaption && (!squareOnly || !isMobile)"
          v-model="caption"
          class="dd-caption-input"
          placeholder="Add a message… (optional)"
          maxlength="500"
          @keydown.enter.prevent="accept"
        />
        <div class="dd-footer-actions">
          <v-btn variant="text" class="dd-cancel-btn" @click="cancel">Cancel</v-btn>
          <v-btn color="primary" class="dd-send-btn" :disabled="paths.length === 0" @click="accept">
            <v-icon size="16" class="mr-1">mdi-check</v-icon>
            Save
          </v-btn>
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  squareOnly: { type: Boolean, default: false },
  showCaption: { type: Boolean, default: true },
  initialPaths: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'save'])
const isMobile = ref(false)

const syncViewportMode = () => {
  isMobile.value = window.innerWidth <= 959
}

// ── Canvas refs ──────────────────────────────────────────────────────────────
const canvas = ref(null)
const ctx = ref(null)
const isDrawingNow = ref(false)
const paths = ref([])
const currentPath = ref([])
const redoStack = ref([])

// ── Tool state ───────────────────────────────────────────────────────────────
const tool = ref('pen')
const currentColor = ref('#000000')
const brushSize = ref(4)
const brushType = ref('pen')
const shapeFill = ref(false)
const shapeStartPoint = ref(null)
const shapeEndPoint = ref(null)

// ── Text tool ────────────────────────────────────────────────────────────────
const textEditorVisible = ref(false)
const textDraft = ref('')
const textPoint = ref({ x: 0, y: 0 })
const textEditorInput = ref(null)

// ── Floating layer ───────────────────────────────────────────────────────────
const floatingLayer = ref(null)
const floatingLayerDragging = ref(false)
const floatingLayerResizing = ref(false)
const floatingLayerResizeHandle = ref(null)
const floatingLayerOffset = ref({ x: 0, y: 0 })

const imageCache = new Map()

// ── Static lists ─────────────────────────────────────────────────────────────
const toolList = [
  { val: 'pen',       label: 'Pen',       icon: 'mdi-pencil' },
  { val: 'eraser',    label: 'Eraser',    icon: 'mdi-eraser' },
  { val: 'bucket',    label: 'Fill',      icon: 'mdi-format-color-fill' },
  { val: 'text',      label: 'Text',      icon: 'mdi-format-text' },
  { val: 'line',      label: 'Line',      icon: 'mdi-vector-line' },
  { val: 'rectangle', label: 'Rectangle', icon: 'mdi-rectangle-outline' },
  { val: 'circle',    label: 'Circle',    icon: 'mdi-circle-outline' },
  { val: 'triangle',  label: 'Triangle',  icon: 'mdi-triangle-outline' },
  { val: 'star',      label: 'Star',      icon: 'mdi-star-outline' },
  { val: 'arrow',     label: 'Arrow',     icon: 'mdi-arrow-top-right' },
]

const brushTypes = [
  { value: 'pen',    label: 'Round',  icon: 'mdi-circle' },
  { value: 'square', label: 'Square', icon: 'mdi-square' },
  { value: 'marker', label: 'Marker', icon: 'mdi-marker' },
  { value: 'spray',  label: 'Spray',  icon: 'mdi-spray' },
]

const colorPresets = [
  '#000000', '#ffffff', '#ef4444', '#f97316', '#eab308',
  '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280',
]

const shapeTools = ['line', 'rectangle', 'circle', 'triangle', 'star', 'arrow']
const isShapeTool = computed(() => shapeTools.includes(tool.value))

const currentDrawingColor = computed(() => tool.value === 'eraser' ? '#FFFFFF' : currentColor.value)
const currentDrawingWidth = computed(() => tool.value === 'eraser' ? brushSize.value * 3 : brushSize.value)
const textFontSize = computed(() => Math.max(12, brushSize.value * 4))

const textInputStyle = computed(() => {
  if (!canvas.value) return {}
  const rect = canvas.value.getBoundingClientRect()
  const scaleX = rect.width / canvas.value.width
  const scaleY = rect.height / canvas.value.height
  return {
    left: `${rect.left + textPoint.value.x * scaleX}px`,
    top:  `${rect.top  + textPoint.value.y * scaleY - textFontSize.value}px`,
    color: currentColor.value,
    fontSize: `${Math.max(12, textFontSize.value * scaleY)}px`,
  }
})

const clonePaths = (source) => JSON.parse(JSON.stringify(Array.isArray(source) ? source : []))

const hydrateInitialPaths = () => {
  paths.value = clonePaths(props.initialPaths)
  redoStack.value = []
  currentPath.value = []
  floatingLayer.value = null
  textEditorVisible.value = false
  textDraft.value = ''
  caption.value = ''
  tool.value = 'pen'
}

const initAndRedrawCanvas = () => {
  initCanvas()
  redrawCanvas()
}

// ── Open / close ─────────────────────────────────────────────────────────────
watch(() => props.modelValue, (val) => {
  if (val) {
    hydrateInitialPaths()
    nextTick(() => {
      if (canvas.value) {
        initAndRedrawCanvas()
      } else {
        // Dialog animation not complete yet — retry after animation
        setTimeout(initAndRedrawCanvas, 350)
      }
      window.addEventListener('keydown', handleKeyDown)
      syncViewportMode()
    })
  } else {
    window.removeEventListener('keydown', handleKeyDown)
  }
})

const cancel = () => {
  emit('update:modelValue', false)
  resetState()
}

const caption = ref('')

const accept = () => {
  if (!canvas.value) return
  const dataUrl = canvas.value.toDataURL('image/png')
  emit('save', {
    dataUrl,
    paths: paths.value,
    width: canvas.value.width,
    height: canvas.value.height,
    caption: caption.value.trim(),
  })
  emit('update:modelValue', false)
  resetState()
}

const resetState = () => {
  paths.value = clonePaths(props.initialPaths)
  redoStack.value = []
  currentPath.value = []
  floatingLayer.value = null
  textEditorVisible.value = false
  textDraft.value = ''
  caption.value = ''
  tool.value = 'pen'
}

// ── Canvas init ───────────────────────────────────────────────────────────────
const initCanvas = () => {
  if (!canvas.value) return
  if (props.squareOnly) {
    canvas.value.width  = 600
    canvas.value.height = 600
  } else {
    canvas.value.width  = 820
    canvas.value.height = 480
  }
  ctx.value = canvas.value.getContext('2d', { willReadFrequently: true })
  ctx.value.lineCap  = 'round'
  ctx.value.lineJoin = 'round'
  ctx.value.fillStyle = '#FFFFFF'
  ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height)
}

// ── Coordinate helpers ────────────────────────────────────────────────────────
const getPos = (clientX, clientY) => {
  if (!canvas.value) return { x: 0, y: 0 }
  const rect = canvas.value.getBoundingClientRect()
  return {
    x: (clientX - rect.left) * (canvas.value.width  / rect.width),
    y: (clientY - rect.top)  * (canvas.value.height / rect.height),
  }
}
const getMousePos = (e) => getPos(e.clientX, e.clientY)
const getTouchPos = (e) => {
  const t = e.touches[0]
  return getPos(t.clientX, t.clientY)
}

// ── Floating layer helpers ────────────────────────────────────────────────────
const getFloatingLayerHandles = () => {
  if (!floatingLayer.value) return {}
  const { x, y, width: w, height: h } = floatingLayer.value
  const mx = x + w / 2, my = y + h / 2
  return {
    nw: {x,y}, n: {x:mx,y}, ne: {x:x+w,y},
    w:  {x,y:my},            e:  {x:x+w,y:my},
    sw: {x,y:y+h}, s: {x:mx,y:y+h}, se: {x:x+w,y:y+h},
  }
}
const getFloatingLayerHandleAt = (pos) => {
  if (!floatingLayer.value) return null
  const handles = getFloatingLayerHandles()
  const thr = 12
  for (const [name, h] of Object.entries(handles)) {
    if (Math.abs(pos.x - h.x) <= thr && Math.abs(pos.y - h.y) <= thr) return name
  }
  return null
}
const isPointInFloatingLayer = (pos) => {
  if (!floatingLayer.value) return false
  const { x, y, width: w, height: h } = floatingLayer.value
  return pos.x >= x && pos.x <= x+w && pos.y >= y && pos.y <= y+h
}
const discardFloatingLayer = () => {
  floatingLayer.value = null
  floatingLayerDragging.value = false
  floatingLayerResizing.value = false
  redrawCanvas()
}
const mergeFloatingLayer = () => {
  if (!floatingLayer.value) return
  paths.value.push({ ...floatingLayer.value })
  redoStack.value = []
  discardFloatingLayer()
}

// ── Redraw ────────────────────────────────────────────────────────────────────
const redrawCanvas = () => {
  if (!ctx.value || !canvas.value) return
  ctx.value.fillStyle = '#FFFFFF'
  ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height)
  ctx.value.lineCap  = 'round'
  ctx.value.lineJoin = 'round'

  for (const path of paths.value) {
    if (path.type === 'fill') {
      applyFloodFill(path.x, path.y, path.color)
    } else if (path.type === 'image') {
      drawCachedImage(path)
    } else if (path.type === 'shape') {
      drawShapeOnCtx(ctx.value, path.shapeType, path.start, path.end, {
        color: path.color, width: path.width, fill: path.fill,
      })
    } else if (path.points?.length) {
      replayStroke(path)
    }
  }

  if (floatingLayer.value?.type === 'image') drawFloatingLayerImage()
  if (floatingLayer.value) drawFloatingLayerOverlay()
}

const drawCachedImage = (layer) => {
  if (!ctx.value || !layer.src || !layer.width || !layer.height) return
  const cached = imageCache.get(layer.src)
  if (cached) {
    ctx.value.drawImage(cached, layer.x || 0, layer.y || 0, layer.width, layer.height)
    return
  }
  const img = new Image()
  img.onload = () => { imageCache.set(layer.src, img); redrawCanvas() }
  img.src = layer.src
}

const replayStroke = (path) => {
  if (!ctx.value) return
  const c = ctx.value
  c.save()
  c.globalAlpha  = path.brushType === 'marker' ? 0.5 : 1
  c.strokeStyle  = path.color
  c.lineWidth    = path.width
  c.lineCap      = path.brushType === 'square' ? 'square' : 'round'
  c.lineJoin     = path.brushType === 'square' ? 'miter'  : 'round'

  if (path.brushType === 'spray') {
    path.points.forEach(pt => {
      if (pt.dots) pt.dots.forEach(d => {
        c.beginPath(); c.arc(d.x, d.y, d.r, 0, Math.PI*2); c.fill()
      })
    })
    c.restore(); return
  }
  if (path.points.length < 2) {
    const pt = path.points[0]
    if (!pt) { c.restore(); return }
    c.fillStyle = path.color
    c.beginPath(); c.arc(pt.x, pt.y, path.width/2, 0, Math.PI*2); c.fill()
    c.restore(); return
  }
  c.beginPath()
  c.moveTo(path.points[0].x, path.points[0].y)
  for (let i = 1; i < path.points.length - 1; i++) {
    const mx = (path.points[i].x + path.points[i+1].x) / 2
    const my = (path.points[i].y + path.points[i+1].y) / 2
    c.quadraticCurveTo(path.points[i].x, path.points[i].y, mx, my)
  }
  const last = path.points[path.points.length - 1]
  c.lineTo(last.x, last.y)
  c.stroke()
  c.restore()
}

// ── Shape drawing ─────────────────────────────────────────────────────────────
const normalizeRect = (s, e) => ({
  x: Math.min(s.x, e.x), y: Math.min(s.y, e.y),
  w: Math.abs(e.x - s.x), h: Math.abs(e.y - s.y),
})

const drawShapeOnCtx = (c, shapeType, start, end, { color, width, fill = false }) => {
  c.save()
  c.strokeStyle = color; c.fillStyle = color; c.lineWidth = width
  c.lineCap = 'round'; c.lineJoin = 'round'

  if (shapeType === 'line') {
    c.beginPath(); c.moveTo(start.x, start.y); c.lineTo(end.x, end.y); c.stroke()
    c.restore(); return
  }
  if (shapeType === 'arrow') {
    const dx = end.x - start.x, dy = end.y - start.y
    const angle = Math.atan2(dy, dx), hl = Math.max(10, width * 4)
    c.beginPath(); c.moveTo(start.x, start.y); c.lineTo(end.x, end.y); c.stroke()
    c.beginPath()
    c.moveTo(end.x, end.y)
    c.lineTo(end.x - hl*Math.cos(angle - Math.PI/6), end.y - hl*Math.sin(angle - Math.PI/6))
    c.lineTo(end.x - hl*Math.cos(angle + Math.PI/6), end.y - hl*Math.sin(angle + Math.PI/6))
    c.closePath(); c.fill()
    c.restore(); return
  }

  const { x, y, w, h } = normalizeRect(start, end)
  if (shapeType === 'rectangle') {
    if (fill) c.fillRect(x, y, w, h); c.strokeRect(x, y, w, h); c.restore(); return
  }
  if (shapeType === 'circle') {
    c.beginPath()
    c.ellipse(x+w/2, y+h/2, Math.max(1,w/2), Math.max(1,h/2), 0, 0, Math.PI*2)
    if (fill) c.fill(); c.stroke(); c.restore(); return
  }

  const drawPoly = (pts) => {
    if (!pts.length) return
    c.beginPath(); c.moveTo(pts[0].x, pts[0].y)
    for (let i = 1; i < pts.length; i++) c.lineTo(pts[i].x, pts[i].y)
    c.closePath(); if (fill) c.fill(); c.stroke()
  }
  if (shapeType === 'triangle') {
    drawPoly([{x:x+w/2,y}, {x,y:y+h}, {x:x+w,y:y+h}])
    c.restore(); return
  }
  if (shapeType === 'star') {
    const cx=x+w/2, cy=y+h/2, outer=Math.max(1,Math.min(w,h)/2), inner=outer*0.45
    const pts=[]
    for (let i=0;i<10;i++){
      const r=i%2===0?outer:inner, a=(-Math.PI/2)+(i*Math.PI/5)
      pts.push({x:cx+r*Math.cos(a), y:cy+r*Math.sin(a)})
    }
    drawPoly(pts)
  }
  c.restore()
}

// ── Flood fill ────────────────────────────────────────────────────────────────
const hexToRgb = (hex) => [
  parseInt(hex.slice(1,3),16), parseInt(hex.slice(3,5),16), parseInt(hex.slice(5,7),16),
]

const applyFloodFill = (sx, sy, fillHex) => {
  if (!canvas.value || !ctx.value) return
  const c = canvas.value
  const [fr,fg,fb] = hexToRgb(fillHex)
  const imgData = ctx.value.getImageData(0, 0, c.width, c.height)
  const data = imgData.data
  const si = (sy * c.width + sx) * 4
  const [tr,tg,tb] = [data[si], data[si+1], data[si+2]]
  if (tr===fr && tg===fg && tb===fb) return
  const matches = (i) =>
    Math.abs(data[i]-tr)<32 && Math.abs(data[i+1]-tg)<32 && Math.abs(data[i+2]-tb)<32
  const visited = new Uint8Array(c.width * c.height)
  const stack = [[sx, sy]]
  while (stack.length) {
    const [x, y] = stack.pop()
    if (x<0||x>=c.width||y<0||y>=c.height) continue
    const vi = y*c.width+x; if (visited[vi]) continue
    const pi = vi*4; if (!matches(pi)) continue
    visited[vi]=1; data[pi]=fr; data[pi+1]=fg; data[pi+2]=fb; data[pi+3]=255
    stack.push([x+1,y],[x-1,y],[x,y+1],[x,y-1])
  }
  ctx.value.putImageData(imgData, 0, 0)
}

const floodFill = (sx, sy, fillHex) => {
  applyFloodFill(sx, sy, fillHex)
  paths.value.push({ type: 'fill', x: sx, y: sy, color: fillHex })
  redoStack.value = []
}

// ── Floating layer image rendering ────────────────────────────────────────────
const drawFloatingLayerImage = () => {
  if (!ctx.value || !floatingLayer.value || floatingLayer.value.type !== 'image') return
  const layer = floatingLayer.value
  const cached = imageCache.get(layer.src)
  if (cached) {
    ctx.value.drawImage(cached, layer.x||0, layer.y||0, layer.width, layer.height)
    return
  }
  const img = new Image()
  img.onload = () => { imageCache.set(layer.src, img); redrawCanvas() }
  img.src = layer.src
}

const drawFloatingLayerOverlay = () => {
  if (!ctx.value || !floatingLayer.value) return
  const { x, y, width: w, height: h } = floatingLayer.value
  if (w < 1 || h < 1) return
  const c = ctx.value
  c.save()
  c.setLineDash([6,4]); c.strokeStyle='#10b981'; c.lineWidth=2
  c.strokeRect(x, y, w, h); c.setLineDash([])
  const hs = 10, handles = getFloatingLayerHandles()
  c.fillStyle = '#10b981'
  Object.values(handles).forEach(h2 => c.fillRect(h2.x-hs/2, h2.y-hs/2, hs, hs))
  c.fillStyle = 'rgba(255,255,255,0.9)'
  Object.values(handles).forEach(h2 => c.fillRect(h2.x-(hs-3)/2, h2.y-(hs-3)/2, hs-3, hs-3))
  c.restore()
}

// ── Text ──────────────────────────────────────────────────────────────────────
const openTextEditorAt = (pos) => {
  textPoint.value = { x: pos.x, y: pos.y }
  textDraft.value = ''
  textEditorVisible.value = true
  nextTick(() => textEditorInput.value?.focus())
}

const cancelTextInput = () => {
  textEditorVisible.value = false
  textDraft.value = ''
}

const commitTextInput = async () => {
  if (!textEditorVisible.value) return
  const text = textDraft.value.trim()
  textEditorVisible.value = false
  textDraft.value = ''
  if (!text || !canvas.value) return
  if (floatingLayer.value) mergeFloatingLayer()

  const RENDER_SIZE = 400
  const fontFamily = 'Nunito, Segoe UI, sans-serif'
  const tmp = document.createElement('canvas')
  const tCtx = tmp.getContext('2d')
  tCtx.font = `${RENDER_SIZE}px ${fontFamily}`
  const measured = tCtx.measureText(text)
  const rW = Math.ceil(measured.width) + Math.ceil(RENDER_SIZE * 0.2)
  const rH = Math.ceil(RENDER_SIZE * 1.3)
  tmp.width = Math.max(rW, 1); tmp.height = Math.max(rH, 1)
  tCtx.font = `${RENDER_SIZE}px ${fontFamily}`
  tCtx.fillStyle = currentColor.value
  tCtx.textBaseline = 'top'
  tCtx.fillText(text, Math.ceil(RENDER_SIZE * 0.05), Math.ceil(RENDER_SIZE * 0.05))

  const src = tmp.toDataURL('image/png')
  const img = new Image()
  await new Promise(r => { img.onload = r; img.src = src })
  imageCache.set(src, img)

  const scale = textFontSize.value / RENDER_SIZE
  floatingLayer.value = {
    type: 'image',
    x: textPoint.value.x, y: textPoint.value.y,
    width:  Math.max(Math.ceil(rW * scale), 10),
    height: Math.max(Math.ceil(rH * scale), 10),
    src,
  }
  redrawCanvas()
}

// ── Pointer down ──────────────────────────────────────────────────────────────
const startDrawing = (e) => {
  const pos = getMousePos(e)

  if (floatingLayer.value) {
    const handle = getFloatingLayerHandleAt(pos)
    if (handle) {
      floatingLayerResizing.value = true
      floatingLayerResizeHandle.value = handle
      return
    }
    if (isPointInFloatingLayer(pos)) {
      floatingLayerDragging.value = true
      floatingLayerOffset.value = { x: pos.x - floatingLayer.value.x, y: pos.y - floatingLayer.value.y }
      return
    }
    mergeFloatingLayer()
  }

  if (tool.value === 'text') { openTextEditorAt(pos); return }
  if (tool.value === 'bucket') { floodFill(Math.round(pos.x), Math.round(pos.y), currentColor.value); return }

  isDrawingNow.value = true
  canvas.value?.setPointerCapture?.(e.pointerId)

  if (isShapeTool.value) {
    shapeStartPoint.value = pos
    shapeEndPoint.value   = pos
    return
  }

  currentPath.value = [pos]
  if (brushType.value === 'spray') currentPath.value = [{ dots: sprayDots(pos) }]
}

const sprayDots = (pos) => {
  const radius = brushSize.value * 3, count = brushSize.value * 4
  return Array.from({ length: count }, () => {
    const a = Math.random() * Math.PI * 2
    const r = Math.random() * radius
    return { x: pos.x + r*Math.cos(a), y: pos.y + r*Math.sin(a), r: Math.random()*1.5+0.5 }
  })
}

// ── Pointer move ──────────────────────────────────────────────────────────────
const draw = (e) => {
  const pos = getMousePos(e)

  if (floatingLayer.value) {
    const fl = floatingLayer.value
    if (floatingLayerDragging.value) {
      fl.x = pos.x - floatingLayerOffset.value.x
      fl.y = pos.y - floatingLayerOffset.value.y
      redrawCanvas(); return
    }
    if (floatingLayerResizing.value) {
      const h = floatingLayerResizeHandle.value
      const minSz = 10
      if (h.includes('e')) fl.width  = Math.max(minSz, pos.x - fl.x)
      if (h.includes('s')) fl.height = Math.max(minSz, pos.y - fl.y)
      if (h.includes('w')) { const nx=Math.min(pos.x,fl.x+fl.width-minSz); fl.width+=fl.x-nx; fl.x=nx }
      if (h.includes('n')) { const ny=Math.min(pos.y,fl.y+fl.height-minSz); fl.height+=fl.y-ny; fl.y=ny }
      redrawCanvas(); return
    }
    // hover cursor
    const handle = getFloatingLayerHandleAt(pos)
    if (handle) { canvas.value.style.cursor = handle+'-resize'; return }
    if (isPointInFloatingLayer(pos)) { canvas.value.style.cursor = 'move'; return }
  }

  if (!isDrawingNow.value) return

  if (isShapeTool.value) {
    shapeEndPoint.value = pos
    redrawCanvas()
    drawShapeOnCtx(ctx.value, tool.value, shapeStartPoint.value, pos, {
      color: currentDrawingColor.value, width: currentDrawingWidth.value, fill: shapeFill.value,
    })
    return
  }

  if (brushType.value === 'spray') {
    currentPath.value.push({ dots: sprayDots(pos) })
    currentPath.value.forEach(pt => {
      if (pt.dots) pt.dots.forEach(d => {
        ctx.value.fillStyle = currentDrawingColor.value
        ctx.value.beginPath(); ctx.value.arc(d.x, d.y, d.r, 0, Math.PI*2); ctx.value.fill()
      })
    })
    return
  }

  currentPath.value.push(pos)
  ctx.value.save()
  ctx.value.globalAlpha  = brushType.value === 'marker' ? 0.5 : 1
  ctx.value.strokeStyle  = currentDrawingColor.value
  ctx.value.lineWidth    = currentDrawingWidth.value
  ctx.value.lineCap      = brushType.value === 'square' ? 'square' : 'round'
  ctx.value.lineJoin     = brushType.value === 'square' ? 'miter'  : 'round'
  const pts = currentPath.value
  if (pts.length >= 2) {
    ctx.value.beginPath()
    ctx.value.moveTo(pts[0].x, pts[0].y)
    for (let i = 1; i < pts.length - 1; i++) {
      const mx = (pts[i].x + pts[i+1].x) / 2
      const my = (pts[i].y + pts[i+1].y) / 2
      ctx.value.quadraticCurveTo(pts[i].x, pts[i].y, mx, my)
    }
    ctx.value.lineTo(pts[pts.length-1].x, pts[pts.length-1].y)
    ctx.value.stroke()
  }
  ctx.value.restore()
}

// ── Pointer up ────────────────────────────────────────────────────────────────
const stopDrawing = () => {
  if (floatingLayerDragging.value || floatingLayerResizing.value) {
    floatingLayerDragging.value = false
    floatingLayerResizing.value = false
    floatingLayerResizeHandle.value = null
    return
  }
  if (!isDrawingNow.value) return
  isDrawingNow.value = false

  if (isShapeTool.value && shapeStartPoint.value && shapeEndPoint.value) {
    paths.value.push({
      type: 'shape', shapeType: tool.value,
      start: shapeStartPoint.value, end: shapeEndPoint.value,
      color: currentDrawingColor.value, width: currentDrawingWidth.value, fill: shapeFill.value,
    })
    shapeStartPoint.value = null; shapeEndPoint.value = null
    redoStack.value = []
    return
  }

  if (currentPath.value.length === 0) return
  paths.value.push({
    type: 'stroke',
    brushType: brushType.value,
    color: currentDrawingColor.value,
    width: currentDrawingWidth.value,
    points: [...currentPath.value],
  })
  currentPath.value = []
  redoStack.value = []
}

// ── Undo / redo / clear ───────────────────────────────────────────────────────
const undo = () => {
  if (paths.value.length === 0) return
  redoStack.value.push(paths.value.pop())
  redrawCanvas()
}
const redo = () => {
  if (redoStack.value.length === 0) return
  paths.value.push(redoStack.value.pop())
  redrawCanvas()
}
const clearCanvas = () => {
  paths.value = []; redoStack.value = []; currentPath.value = []
  discardFloatingLayer()
  if (ctx.value && canvas.value) {
    ctx.value.fillStyle = '#FFFFFF'
    ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height)
  }
}

// ── Keyboard ──────────────────────────────────────────────────────────────────
const handleKeyDown = (e) => {
  if (!floatingLayer.value) return
  if (e.key === 'Enter') { e.preventDefault(); mergeFloatingLayer() }
  if (e.key === 'Escape') { e.preventDefault(); discardFloatingLayer() }
}

onUnmounted(() => window.removeEventListener('keydown', handleKeyDown))
onMounted(() => {
  syncViewportMode()
  window.addEventListener('resize', syncViewportMode)
})

onUnmounted(() => {
  window.removeEventListener('resize', syncViewportMode)
})
</script>

<style scoped>
.draw-dlg {
  background: #0d0e13 !important;
  border-radius: 14px !important;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: min(88vh, 760px);
}

/* Toolbar */
.dd-toolbar {
  display: flex;
  align-items: center;
  padding: 8px 8px;
  background: #111218;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
}

.dd-toolbar::-webkit-scrollbar { display: none; }

.dd-toolbar-inner {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 3px;
  min-width: max-content;
}

.dd-btn {
  width: 30px; height: 30px;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  border-radius: 6px;
  color: rgba(255,255,255,0.45);
  cursor: pointer;
  transition: background 120ms, border-color 120ms, color 120ms;
  flex-shrink: 0;
}
.dd-btn:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.7); }
.dd-btn.active {
  background: rgba(124,58,237,0.2);
  border-color: rgba(124,58,237,0.55);
  color: #c4b5fd;
}
.dd-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.dd-btn--danger:hover { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.4); color: #fca5a5; }

.dd-tool-group { display: flex; gap: 2px; flex-wrap: wrap; }

.dd-sep { width: 1px; height: 22px; background: rgba(255,255,255,0.08); margin: 0 4px; flex-shrink: 0; }

.dd-color-btn {
  width: 30px; height: 30px;
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 6px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  position: relative;
}
.dd-color-dot {
  width: 20px; height: 20px;
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,0.15);
  display: block;
}
.dd-hidden-input { position: absolute; opacity: 0; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; pointer-events: auto; }

.dd-preset {
  width: 20px; height: 20px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  flex-shrink: 0;
  transition: transform 100ms;
}
.dd-preset:hover { transform: scale(1.2); }
.dd-preset.active { border-color: #c4b5fd; transform: scale(1.1); }

.dd-check {
  display: flex; align-items: center; gap: 5px;
  font-size: 0.74rem; color: rgba(255,255,255,0.5);
  cursor: pointer; user-select: none;
  flex-shrink: 0;
}
.dd-check input { accent-color: #7c3aed; }

.dd-size-label {
  font-size: 0.72rem; color: rgba(255,255,255,0.4);
  white-space: nowrap; flex-shrink: 0;
}

.dd-range {
  width: 80px; flex-shrink: 0;
  accent-color: #7c3aed;
}

/* Canvas wrap */
.dd-canvas-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0b0c11;
  position: relative;
  overflow: auto;
  padding: 12px;
  min-height: 200px;
}

.dd-canvas {
  background: #ffffff;
  border-radius: 6px;
  cursor: crosshair;
  touch-action: none;
  max-width: 100%;
  max-height: 100%;
  box-shadow: 0 0 0 1px rgba(255,255,255,0.07), 0 4px 20px rgba(0,0,0,0.5);
}

/* Mobile color strip is hidden on desktop (colors are in the scrollable toolbar) */
.dd-colorstrip { display: none; }

.dd-hint {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  color: rgba(255,255,255,0.18);
  font-size: 0.82rem;
  pointer-events: none;
  z-index: 1;
}

.dd-text-input {
  position: fixed;
  min-width: 100px; max-width: 280px;
  padding: 4px 6px;
  border: 1.5px solid rgba(124,58,237,0.5);
  border-radius: 6px;
  background: rgba(255,255,255,0.97);
  color: #111;
  outline: none;
  z-index: 9999;
}

/* Footer */
.dd-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-top: 1px solid rgba(255,255,255,0.06);
  background: #111218;
  flex-shrink: 0;
}

.dd-footer-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.dd-caption-input {
  flex: 1;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 13px;
  padding: 7px 12px;
  outline: none;
  min-width: 0;
}
.dd-caption-input::placeholder { color: rgba(255,255,255,0.3); }
.dd-caption-input:focus { border-color: #7c3aed; background: rgba(124,58,237,0.08); }

@media (max-width: 959px) {
  .draw-dlg {
    border-radius: 0 !important;
    min-height: 100dvh;
    height: 100dvh;
  }

  /* Toolbar: bigger touch targets, still scrollable */
  .dd-toolbar {
    padding: 8px 10px;
    -webkit-overflow-scrolling: touch;
  }

  .dd-btn,
  .dd-color-btn {
    width: 42px;
    height: 42px;
    border-radius: 10px;
  }

  .dd-color-dot {
    width: 26px;
    height: 26px;
    border-radius: 6px;
  }

  .dd-preset {
    width: 26px;
    height: 26px;
  }

  .dd-range {
    width: 120px;
  }

  .dd-size-label {
    font-size: 0.78rem;
  }

  /* Mobile color strip */
  .dd-colorstrip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #111218;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
  }
  .dd-colorstrip::-webkit-scrollbar { display: none; }
  .dd-cs-picker {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    flex-shrink: 0;
  }
  .dd-cs-dot {
    width: 28px; height: 28px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.4);
    display: block;
    flex-shrink: 0;
  }
  .dd-cs-picker .dd-hidden-input {
    position: absolute; opacity: 0;
    width: 100%; height: 100%;
    top: 0; left: 0;
    cursor: pointer;
    pointer-events: auto;
  }
  .dd-cs-swatches {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
  }
  .dd-cs-swatch {
    width: 24px; height: 24px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: transform 100ms, border-color 100ms;
  }
  .dd-cs-swatch.active {
    border-color: #fff;
    transform: scale(1.2);
    box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
  }
  .dd-cs-size {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
    margin-left: auto;
  }
  .dd-cs-range {
    width: 80px; height: 4px;
    -webkit-appearance: none; appearance: none;
    background: rgba(255,255,255,0.15);
    border-radius: 4px;
    outline: none;
    cursor: pointer;
  }
  .dd-cs-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: #7c3aed;
    cursor: pointer;
  }
  .dd-cs-val {
    font-size: 11px;
    color: rgba(255,255,255,0.5);
    min-width: 26px;
    text-align: right;
    flex-shrink: 0;
  }

  /* Canvas: square, centered — NOT stretching to fill the entire screen */
  .dd-canvas-wrap {
    align-items: center;
    justify-content: center;
    padding: 8px;
    min-height: 0;
  }

  .dd-canvas {
    /* Square canvas: limited by screen width OR available height (minus chrome + safe area) */
    width:  min(calc(100vw - 16px), calc(100dvh - 320px));
    height: min(calc(100vw - 16px), calc(100dvh - 320px));
    max-width: none;
    max-height: none;
    border-radius: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.5);
  }

  /* Footer: stacked, thumb-friendly */
  .dd-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    padding: 12px 14px;
    padding-bottom: max(14px, env(safe-area-inset-bottom));
  }

  .dd-caption-input {
    font-size: 16px; /* prevent iOS zoom */
    padding: 11px 14px;
    border-radius: 10px;
  }

  .dd-footer-actions {
    width: 100%;
    gap: 10px;
  }

  .dd-cancel-btn,
  .dd-send-btn {
    flex: 1;
    min-height: 48px;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
  }

  .dd-send-btn {
    flex: 2; /* send button wider than cancel */
  }
}
</style>
