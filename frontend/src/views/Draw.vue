<template>
  <div class="draw-page">

    <!-- ── Top toolbar ──────────────────────────────────────────── -->
    <div class="draw-toolbar">
      <div class="dt-left">
        <span class="dt-title">Draw</span>
      </div>

      <div class="dt-center">
        <div class="dt-group">
          <button class="dt-btn" :disabled="paths.length === 0" @click="undo" :title="t('drawPage.undo')">
            <v-icon size="19">mdi-undo</v-icon>
          </button>
          <button class="dt-btn" :disabled="redoStack.length === 0" @click="redo" :title="t('drawPage.redo')">
            <v-icon size="19">mdi-redo</v-icon>
          </button>
        </div>
        <div class="dt-group">
          <button class="dt-btn dt-btn--danger" @click="confirmClear" :title="t('drawPage.clearCanvas')">
            <v-icon size="19">mdi-delete-outline</v-icon>
          </button>
          <button class="dt-btn" :disabled="paths.length === 0" @click="downloadDrawing" title="Download PNG">
            <v-icon size="19">mdi-download-outline</v-icon>
          </button>
          <button class="dt-btn" @click="toggleOrientation" :title="canvasLandscape ? 'Switch to portrait' : 'Switch to landscape'">
            <v-icon size="19">{{ canvasLandscape ? 'mdi-phone-rotate-portrait' : 'mdi-phone-rotate-landscape' }}</v-icon>
          </button>
        </div>
        <div v-if="weeklyTheme" class="dt-theme-pill">
          <v-icon size="13">mdi-calendar-star</v-icon>
          {{ weeklyTheme.emoji }} {{ weeklyTheme.theme_name }}
        </div>
      </div>

      <div class="dt-right">
        <button class="dt-save-btn" :disabled="paths.length === 0" @click="saveDrawingDialog = true">
          <v-icon size="16">mdi-upload-outline</v-icon>
          Upload
        </button>
      </div>
    </div>

    <!-- ── Workspace ─────────────────────────────────────────────── -->
    <div class="draw-workspace">

      <!-- Backdrop (mobile sheet overlay) -->
      <div class="sidebar-backdrop" :class="{ 'is-visible': sidebarOpen }" @click="sidebarOpen = false" />

      <!-- Sidebar — desktop: left panel | mobile: bottom sheet -->
      <aside class="draw-sidebar" :class="{ 'is-open': sidebarOpen }">
        <div class="sb-drag-handle" />

        <!-- Tabs (mobile only) -->
        <div class="sb-tabs">
          <button class="sb-tab" :class="{ active: mobilePanel === 'tools' }"   @click="mobilePanel = 'tools'">{{ t('drawPage.tools') }}</button>
          <button class="sb-tab" :class="{ active: mobilePanel === 'style' }"   @click="mobilePanel = 'style'">Style</button>
          <button class="sb-tab" :class="{ active: mobilePanel === 'actions' }" @click="mobilePanel = 'actions'">Actions</button>
        </div>

        <!-- Panel: Tools -->
        <div class="sb-panel" :class="{ 'sb-panel--hidden': mobilePanel !== 'tools' }">
          <div class="sb-section">
            <p class="sb-label">{{ t('drawPage.tools') }}</p>
            <div class="sb-tools">
              <button class="sb-tool-btn" :class="{ active: tool === 'pen' }"       @click="tool = 'pen'"       :title="t('drawPage.pen')">       <v-icon size="22">mdi-pencil</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'eraser' }"    @click="tool = 'eraser'"    :title="t('drawPage.eraser')">    <v-icon size="22">mdi-eraser</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'bucket' }"    @click="tool = 'bucket'"    :title="t('drawPage.bucket')">    <v-icon size="22">mdi-format-color-fill</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'text' }"      @click="tool = 'text'"      :title="t('drawPage.text')">      <v-icon size="22">mdi-format-text</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'line' }"      @click="tool = 'line'"      :title="t('drawPage.line')">      <v-icon size="22">mdi-vector-line</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'rectangle' }" @click="tool = 'rectangle'" :title="t('drawPage.rectangle')"> <v-icon size="22">mdi-rectangle-outline</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'circle' }"    @click="tool = 'circle'"    :title="t('drawPage.circle')">    <v-icon size="22">mdi-circle-outline</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'triangle' }"  @click="tool = 'triangle'"  :title="t('drawPage.triangle')">  <v-icon size="22">mdi-triangle-outline</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'star' }"      @click="tool = 'star'"      :title="t('drawPage.star')">      <v-icon size="22">mdi-star-outline</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'arrow' }"     @click="tool = 'arrow'"     :title="t('drawPage.arrow')">     <v-icon size="22">mdi-arrow-top-right</v-icon></button>
              <button class="sb-tool-btn" :class="{ active: tool === 'select' }"    @click="tool = 'select'"    :title="t('drawPage.select')">    <v-icon size="22">mdi-selection</v-icon></button>
            </div>
          </div>
          <div class="sb-divider" />
          <div class="sb-section">
            <div class="sb-copy-paste-row">
              <button class="sb-action-btn" @click="copyFromCanvas">
                <v-icon size="16">mdi-content-copy</v-icon>
                <span>{{ t('drawPage.copyTool') }}</span>
              </button>
              <button class="sb-action-btn" @click="pasteFromClipboard">
                <v-icon size="16">mdi-clipboard-outline</v-icon>
                <span>{{ t('drawPage.pasteTool') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Panel: Style -->
        <div class="sb-panel" :class="{ 'sb-panel--hidden': mobilePanel !== 'style' }">
          <div class="sb-section" :class="{ 'sb-disabled': tool === 'eraser' }">
            <p class="sb-label">{{ t('drawPage.color') }}</p>
            <label class="sb-color-row">
              <span class="sb-color-swatch" :style="{ background: currentColor }" />
              <span class="sb-color-hex">{{ currentColor }}</span>
              <input type="color" v-model="currentColor" :disabled="tool === 'eraser'" class="sb-color-input" />
            </label>
            <div class="sb-presets">
              <button v-for="color in colorPresets" :key="color"
                class="sb-preset"
                :style="{ background: color }"
                :class="{ active: currentColor === color }"
                :disabled="tool === 'eraser'"
                @click="currentColor = color" />
            </div>
          </div>
          <div class="sb-divider" />
          <div class="sb-section">
            <p class="sb-label">{{ t('drawPage.size') }}: <span class="sb-size-val">{{ brushSize }}</span></p>
            <div class="sb-size-preview">
              <span class="sb-size-dot"
                :style="{
                  width:  Math.min(brushSize * 2.2, 48) + 'px',
                  height: Math.min(brushSize * 2.2, 48) + 'px',
                  background: tool === 'eraser' ? 'var(--c-border-md)' : currentColor
                }" />
            </div>
            <input type="range" v-model.number="brushSize" min="1" max="30" step="1" class="sb-range" />
          </div>
          <div class="sb-divider" />
          <div class="sb-section" :class="{ 'sb-disabled': tool !== 'pen' }">
            <p class="sb-label">{{ t('drawPage.brushType') }}</p>
            <div class="sb-brush-row">
              <button v-for="bt in brushTypes" :key="bt.value"
                class="sb-brush-btn" :class="{ active: brushType === bt.value }"
                @click="brushType = bt.value" :title="bt.label">
                <v-icon size="20">{{ bt.icon }}</v-icon>
              </button>
            </div>
          </div>
          <div class="sb-divider" />
          <div class="sb-section" :class="{ 'sb-disabled': !isShapeTool }">
            <label class="sb-check-row">
              <input v-model="shapeFill" type="checkbox" :disabled="!isShapeTool || tool === 'line' || tool === 'arrow'" />
              <span>{{ t('drawPage.fillShape') }}</span>
            </label>
          </div>
        </div>

        <!-- Panel: Actions (mobile only — desktop uses toolbar) -->
        <div class="sb-panel sb-panel--mobile-only" :class="{ 'sb-panel--hidden': mobilePanel !== 'actions' }">
          <div class="sb-section">
            <div class="sb-act-row">
              <button class="sb-act sb-act--icon" :disabled="paths.length === 0"     @click="undo"            :title="t('drawPage.undo')">         <v-icon size="18">mdi-undo</v-icon></button>
              <button class="sb-act sb-act--icon" :disabled="redoStack.length === 0"  @click="redo"            :title="t('drawPage.redo')">         <v-icon size="18">mdi-redo</v-icon></button>
              <button class="sb-act sb-act--icon sb-act--danger"                      @click="confirmClear"   :title="t('drawPage.clearCanvas')">  <v-icon size="18">mdi-delete-outline</v-icon></button>
              <button class="sb-act sb-act--icon" :disabled="paths.length === 0"     @click="downloadDrawing" title="Download PNG">                 <v-icon size="18">mdi-download-outline</v-icon></button>
              <button class="sb-act sb-act--icon" @click="toggleOrientation" :title="canvasLandscape ? 'Switch to portrait' : 'Switch to landscape'"><v-icon size="18">{{ canvasLandscape ? 'mdi-phone-rotate-portrait' : 'mdi-phone-rotate-landscape' }}</v-icon></button>
            </div>
            <button class="sb-act sb-act--primary" :disabled="paths.length === 0" @click="saveDrawingDialog = true">
              <v-icon size="18">mdi-content-save-outline</v-icon>
              {{ t('drawPage.saveDoodle') }}
            </button>
          </div>
        </div>
      </aside>

      <!-- Canvas column -->
      <div class="canvas-col">
        <!-- Theme reminder strip (mobile only) -->
        <div v-if="weeklyTheme" class="mobile-theme-strip">
          <v-icon size="13">mdi-calendar-star</v-icon>
          {{ weeklyTheme.emoji }} {{ weeklyTheme.theme_name }}
        </div>
        <div class="canvas-wrap">
          <div v-if="paths.length === 0" class="canvas-hint">
            <v-icon size="48" style="color:var(--c-muted)">mdi-draw</v-icon>
            <p>{{ t('drawPage.startDoodling') }}</p>
          </div>
          <input v-if="textEditorVisible"
            ref="textEditorInput"
            v-model="textDraft"
            class="canvas-text-input"
            :style="textInputStyle"
            :placeholder="t('drawPage.textPlaceholder') || 'Type text...'"
            @keydown.enter.prevent="commitTextInput"
            @keydown.esc.prevent="cancelTextInput" />
          <canvas ref="canvas"
            @mousedown="startDrawing" @mousemove="draw" @mouseup="stopDrawing" @mouseleave="stopDrawing"
            @touchstart.prevent="handleTouchStart" @touchmove.prevent="handleTouchMove" @touchend.prevent="stopDrawing"
            class="draw-canvas" />
        </div>
      </div>

    </div><!-- end .draw-workspace -->

    <!-- Mobile dock — always visible on phones, hidden on desktop -->
    <div class="mobile-dock">
      <button class="dock-btn" :class="{ active: tool === 'pen' }"    @click="tool = 'pen'">    <v-icon size="23">mdi-pencil</v-icon></button>
      <button class="dock-btn" :class="{ active: tool === 'eraser' }" @click="tool = 'eraser'"> <v-icon size="23">mdi-eraser</v-icon></button>
      <button class="dock-btn" :class="{ active: tool === 'text' }"   @click="tool = 'text'">   <v-icon size="23">mdi-format-text</v-icon></button>
      <button class="dock-btn" :class="{ active: tool === 'select' }" @click="tool = 'select'"> <v-icon size="23">mdi-selection</v-icon></button>
      <label class="dock-color-btn">
        <span class="dock-color-dot" :style="{ background: currentColor }" />
        <input type="color" v-model="currentColor" class="dock-color-input" />
      </label>
      <button class="dock-btn" :disabled="paths.length === 0"     @click="undo"><v-icon size="23">mdi-undo</v-icon></button>
      <button class="dock-btn" :disabled="redoStack.length === 0" @click="redo"><v-icon size="23">mdi-redo</v-icon></button>
      <button class="dock-btn dock-more-btn" :class="{ active: sidebarOpen }" @click="sidebarOpen = !sidebarOpen">
        <v-icon size="23">{{ sidebarOpen ? 'mdi-close' : 'mdi-dots-grid' }}</v-icon>
      </button>
    </div>

    <!-- Clear dialog -->
    <v-dialog v-model="clearDialog" max-width="380">
      <v-card class="dlg-card">
        <div class="dlg-head"><h3>{{ t('drawPage.clearConfirm') }}</h3></div>
        <div class="dlg-body"><p>{{ t('drawPage.clearWarn') }}</p></div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="clearDialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="error" @click="clearCanvas">{{ t('drawPage.clearAll') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Save dialog -->
    <v-dialog v-model="saveDrawingDialog" max-width="500">
      <v-card class="dlg-card">
        <div class="dlg-head"><h3>{{ t('drawPage.saveTo') }}</h3></div>
        <div class="dlg-body">
          <!-- Title -->
          <v-text-field v-model="drawingTitle" :label="t('drawPage.doodleTitle')"
            variant="outlined" prepend-inner-icon="mdi-format-title"
            counter="50" maxlength="50" hide-details="auto" class="mb-3" />

          <!-- Description -->
          <v-textarea v-model="drawingDescription"
            label="Description (optional)"
            placeholder="Describe your drawing… use #hashtags to categorize it"
            variant="outlined" rows="3" counter="500" maxlength="500"
            prepend-inner-icon="mdi-text" hide-details="auto" class="mb-3" auto-grow />

          <!-- Destination: Theme or Free Gallery -->
          <div class="save-dest-row">
            <button class="save-dest-btn" :class="{ active: saveDestination === 'free' }"
              @click="saveDestination = 'free'">
              <v-icon size="20" class="mb-1">mdi-image-multiple-outline</v-icon>
              <span class="dest-label">Free Gallery</span>
              <span class="dest-sub">Always visible, never archived</span>
            </button>
            <button class="save-dest-btn" :class="{ active: saveDestination === 'theme', disabled: !weeklyTheme }"
              :disabled="!weeklyTheme"
              @click="weeklyTheme && (saveDestination = 'theme')">
              <v-icon size="20" class="mb-1">mdi-calendar-star</v-icon>
              <span class="dest-label">
                {{ weeklyTheme ? weeklyTheme.emoji + ' This Week' : 'No Active Theme' }}
              </span>
              <span class="dest-sub">
                {{ weeklyTheme ? weeklyTheme.theme_name : 'No weekly theme right now' }}
              </span>
            </button>
          </div>

          <p class="save-meta mt-3">{{ t('drawPage.postingAs') }} <strong>{{ userName }}</strong></p>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="saveDrawingDialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="primary" :loading="saving" :disabled="!drawingTitle.trim()" @click="saveDrawing">
            <v-icon size="16" class="mr-1">mdi-upload-outline</v-icon>
            Upload to Gallery
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
      {{ snackbarText }}
      <template #actions>
        <v-btn variant="text" @click="snackbar = false">{{ t('common.close') }}</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

const router = useRouter()
const { t } = useI18n()
const canvas = ref(null)
const ctx = ref(null)
const isDrawing = ref(false)
const paths = ref([])
const currentPath = ref([])
const sidebarOpen = ref(false)
const mobilePanel = ref('tools')
const canvasLandscape = ref(false) // false = portrait (default on mobile), true = landscape

// Drawing settings
const tool = ref('pen')
const currentColor = ref('#000000')
const brushSize = ref(3)
const brushType = ref('pen')
const redoStack = ref([])
const shapeFill = ref(false)
const shapeStartPoint = ref(null)
const shapeEndPoint = ref(null)
const textEditorVisible = ref(false)
const textDraft = ref('')
const textPoint = ref({ x: 0, y: 0 })
const textEditorInput = ref(null)
const floatingLayer = ref(null)
const floatingLayerDragging = ref(false)
const floatingLayerResizing = ref(false)
const floatingLayerResizeHandle = ref(null)
const floatingLayerOffset = ref({ x: 0, y: 0 })
const selectionStart = ref(null)
const selectionEnd = ref(null)
const isSelecting = ref(false)
const selectionBuffer = ref(null)
const imageCache = new Map()

const shapeTools = ['line', 'rectangle', 'circle', 'triangle', 'star', 'arrow']
const isShapeTool = computed(() => shapeTools.includes(tool.value))

const brushTypes = [
  { value: 'pen',    label: 'Round',   icon: 'mdi-circle' },
  { value: 'square', label: 'Square',  icon: 'mdi-square' },
  { value: 'marker', label: 'Marker',  icon: 'mdi-marker' },
  { value: 'spray',  label: 'Spray',   icon: 'mdi-spray' },
]

// Color presets
const colorPresets = [
  '#000000', '#ffffff', '#ef4444', '#f97316', '#eab308',
  '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280',
  '#7c2d12', '#065f46', '#1e3a8a', '#4c1d95', '#be185d'
]

// Save dialog
const saveDrawingDialog = ref(false)
const clearDialog = ref(false)
const drawingTitle = ref('')
const drawingDescription = ref('')
const saveDestination = ref('free') // 'free' | 'theme'
const saving = ref(false)
const tagTheme = ref(false)
const weeklyTheme = ref(null)
const userName = ref('')

// Snackbar
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Computed properties
const currentDrawingColor = computed(() => 
  tool.value === 'eraser' ? '#FFFFFF' : currentColor.value
)

const currentDrawingWidth = computed(() =>
  tool.value === 'eraser' ? brushSize.value * 3 : brushSize.value
)

const textFontSize = computed(() => Math.max(12, brushSize.value * 4))

const textInputStyle = computed(() => {
  if (!canvas.value) return {}
  const rect = canvas.value.getBoundingClientRect()
  const scaleX = rect.width / canvas.value.width
  const scaleY = rect.height / canvas.value.height
  return {
    left: `${rect.left + textPoint.value.x * scaleX}px`,
    top: `${rect.top + textPoint.value.y * scaleY - textFontSize.value}px`,
    color: currentColor.value,
    fontSize: `${Math.max(12, textFontSize.value * scaleY)}px`,
  }
})

const getFloatingLayerHandles = () => {
  if (!floatingLayer.value) return {}
  const { x, y, width: w, height: h } = floatingLayer.value
  const mx = x + w / 2
  const my = y + h / 2
  return {
    nw: { x, y },
    n: { x: mx, y },
    ne: { x: x + w, y },
    w: { x, y: my },
    e: { x: x + w, y: my },
    sw: { x, y: y + h },
    s: { x: mx, y: y + h },
    se: { x: x + w, y: y + h },
  }
}

const getResizeCursorForHandle = (handle) => {
  const cursors = {
    n: 'n-resize',
    s: 's-resize',
    e: 'e-resize',
    w: 'w-resize',
    nw: 'nw-resize',
    ne: 'ne-resize',
    sw: 'sw-resize',
    se: 'se-resize',
  }
  return cursors[handle] || 'default'
}

const getFloatingLayerHandleAt = (pos) => {
  if (!floatingLayer.value) return null
  const { x, y, width: w, height: h } = floatingLayer.value
  if (w < 6 || h < 6) return null
  const handles = getFloatingLayerHandles()
  const threshold = 12
  for (const [name, handle] of Object.entries(handles)) {
    if (Math.abs(pos.x - handle.x) <= threshold && Math.abs(pos.y - handle.y) <= threshold) return name
  }
  return null
}

const isPointInFloatingLayer = (pos) => {
  if (!floatingLayer.value) return false
  const { x, y, width: w, height: h } = floatingLayer.value
  return pos.x >= x && pos.x <= x + w && pos.y >= y && pos.y <= y + h
}

const normalizeRect = (start, end) => {
  const x = Math.min(start.x, end.x)
  const y = Math.min(start.y, end.y)
  const w = Math.abs(end.x - start.x)
  const h = Math.abs(end.y - start.y)
  return { x, y, w, h }
}

const drawShapeOnCtx = (c, shapeType, start, end, { color, width, fill = false }) => {
  c.save()
  c.strokeStyle = color
  c.fillStyle = color
  c.lineWidth = width
  c.lineCap = 'round'
  c.lineJoin = 'round'

  if (shapeType === 'line') {
    c.beginPath()
    c.moveTo(start.x, start.y)
    c.lineTo(end.x, end.y)
    c.stroke()
    c.restore()
    return
  }

  if (shapeType === 'arrow') {
    const dx = end.x - start.x
    const dy = end.y - start.y
    const angle = Math.atan2(dy, dx)
    const headLen = Math.max(10, width * 4)
    c.beginPath()
    c.moveTo(start.x, start.y)
    c.lineTo(end.x, end.y)
    c.stroke()
    c.beginPath()
    c.moveTo(end.x, end.y)
    c.lineTo(end.x - headLen * Math.cos(angle - Math.PI / 6), end.y - headLen * Math.sin(angle - Math.PI / 6))
    c.lineTo(end.x - headLen * Math.cos(angle + Math.PI / 6), end.y - headLen * Math.sin(angle + Math.PI / 6))
    c.closePath()
    c.fill()
    c.restore()
    return
  }

  const { x, y, w, h } = normalizeRect(start, end)

  if (shapeType === 'rectangle') {
    if (fill) c.fillRect(x, y, w, h)
    c.strokeRect(x, y, w, h)
    c.restore()
    return
  }

  if (shapeType === 'circle') {
    c.beginPath()
    c.ellipse(x + w / 2, y + h / 2, Math.max(1, w / 2), Math.max(1, h / 2), 0, 0, Math.PI * 2)
    if (fill) c.fill()
    c.stroke()
    c.restore()
    return
  }

  const drawPolygon = (points) => {
    if (!points.length) return
    c.beginPath()
    c.moveTo(points[0].x, points[0].y)
    for (let i = 1; i < points.length; i++) c.lineTo(points[i].x, points[i].y)
    c.closePath()
    if (fill) c.fill()
    c.stroke()
  }

  if (shapeType === 'triangle') {
    drawPolygon([
      { x: x + w / 2, y },
      { x, y: y + h },
      { x: x + w, y: y + h },
    ])
    c.restore()
    return
  }

  if (shapeType === 'star') {
    const cx = x + w / 2
    const cy = y + h / 2
    const outer = Math.max(1, Math.min(w, h) / 2)
    const inner = outer * 0.45
    const points = []
    for (let i = 0; i < 10; i++) {
      const r = i % 2 === 0 ? outer : inner
      const a = (-Math.PI / 2) + (i * Math.PI / 5)
      points.push({ x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) })
    }
    drawPolygon(points)
  }

  c.restore()
}

// ─── Flood fill ──────────────────────────────────────────────────────────────
const hexToRgb = (hex) => [
  parseInt(hex.slice(1, 3), 16),
  parseInt(hex.slice(3, 5), 16),
  parseInt(hex.slice(5, 7), 16)
]

const applyFloodFill = (startX, startY, fillHex) => {
  if (!canvas.value || !ctx.value) return
  const c = canvas.value
  const [fr, fg, fb] = hexToRgb(fillHex)
  const imageData = ctx.value.getImageData(0, 0, c.width, c.height)
  const data = imageData.data
  const si = (startY * c.width + startX) * 4
  const [tr, tg, tb] = [data[si], data[si + 1], data[si + 2]]
  if (tr === fr && tg === fg && tb === fb) return
  const matches = (i) =>
    Math.abs(data[i] - tr) < 32 && Math.abs(data[i + 1] - tg) < 32 && Math.abs(data[i + 2] - tb) < 32
  const visited = new Uint8Array(c.width * c.height)
  const stack = [[startX, startY]]
  while (stack.length > 0) {
    const [x, y] = stack.pop()
    if (x < 0 || x >= c.width || y < 0 || y >= c.height) continue
    const vi = y * c.width + x
    if (visited[vi]) continue
    const pi = vi * 4
    if (!matches(pi)) continue
    visited[vi] = 1
    data[pi] = fr; data[pi + 1] = fg; data[pi + 2] = fb; data[pi + 3] = 255
    stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1])
  }
  ctx.value.putImageData(imageData, 0, 0)
}

const floodFill = (startX, startY, fillHex) => {
  applyFloodFill(startX, startY, fillHex)
  paths.value.push({ type: 'fill', x: startX, y: startY, color: fillHex })
  redoStack.value = []
}

const drawTextOnCtx = (c, { x, y, text, color, fontSize, fontFamily }) => {
  if (!text) return
  c.save()
  c.fillStyle = color || '#000000'
  c.textBaseline = 'top'
  c.font = `${fontSize || 16}px ${fontFamily || 'Nunito, Segoe UI, sans-serif'}`
  c.fillText(text, x, y)
  c.restore()
}

const drawFloatingLayerImage = () => {
  if (!ctx.value || !floatingLayer.value || floatingLayer.value.type !== 'image') return
  const layer = floatingLayer.value
  if (!layer.src || !layer.width || !layer.height) return

  const cached = imageCache.get(layer.src)
  if (cached) {
    ctx.value.drawImage(cached, layer.x || 0, layer.y || 0, layer.width, layer.height)
    return
  }

  const img = new Image()
  img.onload = () => {
    imageCache.set(layer.src, img)
    redrawCanvas()
  }
  img.src = layer.src
}

const drawSelectionOutline = () => {
  if (!ctx.value || !selectionStart.value || !selectionEnd.value) return
  const c = ctx.value
  const { x, y, w, h } = normalizeRect(selectionStart.value, selectionEnd.value)
  c.save()
  c.setLineDash([4, 4])
  c.strokeStyle = '#3b82f6'
  c.lineWidth = 2
  c.strokeRect(x, y, w, h)
  c.setLineDash([])
  c.restore()
}

const clearSelection = () => {
  selectionStart.value = null
  selectionEnd.value = null
  isSelecting.value = false
  redrawCanvas()
}

const selectAllCanvas = () => {
  if (!canvas.value) return
  tool.value = 'select'
  selectionStart.value = { x: 0, y: 0 }
  selectionEnd.value = { x: canvas.value.width, y: canvas.value.height }
  isSelecting.value = false
  redrawCanvas()
}

const drawFloatingLayerOverlay = () => {
  if (!ctx.value || !floatingLayer.value) return
  const { x, y, width: w, height: h } = floatingLayer.value
  if (w < 1 || h < 1) return
  const c = ctx.value
  c.save()
  c.setLineDash([6, 4])
  c.strokeStyle = '#10b981'
  c.lineWidth = 2
  c.strokeRect(x, y, w, h)
  c.setLineDash([])
  const hs = 12
  const handles = getFloatingLayerHandles()
  c.fillStyle = '#10b981'
  Object.values(handles).forEach((handle) => {
    c.fillRect(handle.x - hs / 2, handle.y - hs / 2, hs, hs)
  })
  c.fillStyle = 'rgba(255, 255, 255, 0.95)'
  Object.values(handles).forEach((handle) => {
    c.fillRect(handle.x - (hs - 3) / 2, handle.y - (hs - 3) / 2, hs - 3, hs - 3)
  })
  c.restore()
}

const discardFloatingLayer = () => {
  floatingLayer.value = null
  floatingLayerDragging.value = false
  floatingLayerResizing.value = false
  floatingLayerResizeHandle.value = null
  redrawCanvas()
}

const mergeFloatingLayer = () => {
  if (!floatingLayer.value) return
  const layer = { ...floatingLayer.value }
  paths.value.push(layer)
  redoStack.value = []
  discardFloatingLayer()
  showSnackbar(t('drawPage.layerMerged') || 'Layer merged!', 'success')
}

const copyFromCanvas = async () => {
  if (!canvas.value) return
  try {
    if (selectionStart.value && selectionEnd.value) {
      const { x, y, w, h } = normalizeRect(selectionStart.value, selectionEnd.value)
      if (w > 0 && h > 0) {
        const copyCanvas = document.createElement('canvas')
        copyCanvas.width = w
        copyCanvas.height = h
        const copyCtx = copyCanvas.getContext('2d')
        copyCtx.drawImage(canvas.value, x, y, w, h, 0, 0, w, h)
        selectionBuffer.value = {
          type: 'image',
          x: x,
          y: y,
          width: w,
          height: h,
          src: copyCanvas.toDataURL('image/png')
        }
        showSnackbar(t('drawPage.selectionCopied') || 'Selection copied!', 'success')
        return
      }
    }
    const blob = await new Promise((resolve) => canvas.value.toBlob(resolve, 'image/png'))
    if (!blob) throw new Error('toBlob failed')
    const copyCanvas = document.createElement('canvas')
    copyCanvas.width = canvas.value.width
    copyCanvas.height = canvas.value.height
    const copyCtx = copyCanvas.getContext('2d')
    copyCtx.drawImage(canvas.value, 0, 0)
    selectionBuffer.value = {
      type: 'image',
      x: 0,
      y: 0,
      width: canvas.value.width,
      height: canvas.value.height,
      src: copyCanvas.toDataURL('image/png')
    }
    showSnackbar(t('drawPage.canvasCopied') || 'Canvas copied!', 'success')
  } catch {
    showSnackbar(t('drawPage.copyFailed') || 'Failed to copy. Clipboard unavailable.', 'warning')
  }
}

const createFloatingLayerFromImage = async (source) => {
  const img = new Image()
  await new Promise((resolve, reject) => {
    img.onload = resolve
    img.onerror = reject
    img.src = source
  })
  imageCache.set(source, img)

  const defW = Math.min(200, canvas.value.width / 2)
  const defH = (img.height / img.width) * defW
  floatingLayer.value = {
    type: 'image',
    x: (canvas.value.width - defW) / 2,
    y: (canvas.value.height - defH) / 2,
    width: defW,
    height: defH,
    src: source,
  }
  showSnackbar(t('drawPage.floatingLayerCreated') || 'Floating layer created. Drag to move, corners to resize, Enter to merge, Esc to cancel.', 'info')
  redrawCanvas()
  drawFloatingLayerOverlay()
}

const pasteFromClipboard = async () => {
  if (floatingLayer.value) {
    showSnackbar(t('drawPage.mergeFirstOrCancel') || 'Merge or cancel the current layer first.', 'warning')
    return
  }

  if (selectionBuffer.value?.src) {
    await createFloatingLayerFromImage(selectionBuffer.value.src)
    return
  }

  try {
    if (navigator.clipboard?.read) {
      const items = await navigator.clipboard.read()
      for (const item of items) {
        const imageType = item.types.find((type) => type.startsWith('image/'))
        if (!imageType) continue
        const blob = await item.getType(imageType)
        const dataUrl = await new Promise((resolve) => {
          const reader = new FileReader()
          reader.onload = () => resolve(reader.result)
          reader.readAsDataURL(blob)
        })
        await createFloatingLayerFromImage(dataUrl)
        return
      }
    }
    showSnackbar(t('drawPage.noClipboardImage') || 'No image in clipboard.', 'warning')
  } catch {
    showSnackbar(t('drawPage.pasteUnavailable') || 'Clipboard paste unavailable.', 'warning')
  }
}

const openTextEditorAt = (pos) => {
  textPoint.value = { x: pos.x, y: pos.y }
  textDraft.value = ''
  textEditorVisible.value = true
  nextTick(() => {
    textEditorInput.value?.focus()
  })
}

const commitTextInput = async () => {
  if (!textEditorVisible.value) return
  const raw = textDraft.value
  const text = raw.trim()
  textEditorVisible.value = false
  textDraft.value = ''
  if (!text || !canvas.value) return

  // If another floating layer is active, merge it first
  if (floatingLayer.value) mergeFloatingLayer()

  // Always render at a high fixed resolution so the text stays crisp when scaled up
  const RENDER_SIZE = 400
  const fontFamily = 'Nunito, Segoe UI, sans-serif'

  const tmpCanvas = document.createElement('canvas')
  const tmpCtx = tmpCanvas.getContext('2d')
  tmpCtx.font = `${RENDER_SIZE}px ${fontFamily}`
  const measured = tmpCtx.measureText(text)
  const renderW = Math.ceil(measured.width) + Math.ceil(RENDER_SIZE * 0.2)
  const renderH = Math.ceil(RENDER_SIZE * 1.3)

  tmpCanvas.width = Math.max(renderW, 1)
  tmpCanvas.height = Math.max(renderH, 1)
  tmpCtx.font = `${RENDER_SIZE}px ${fontFamily}`
  tmpCtx.fillStyle = currentColor.value
  tmpCtx.textBaseline = 'top'
  tmpCtx.fillText(text, Math.ceil(RENDER_SIZE * 0.05), Math.ceil(RENDER_SIZE * 0.05))

  const src = tmpCanvas.toDataURL('image/png')
  const img = new Image()
  await new Promise((resolve) => { img.onload = resolve; img.src = src })
  imageCache.set(src, img)

  // Scale display size down to match the chosen font size
  const fontSize = textFontSize.value
  const scale = fontSize / RENDER_SIZE
  const displayW = Math.max(Math.ceil(renderW * scale), 10)
  const displayH = Math.max(Math.ceil(renderH * scale), 10)

  floatingLayer.value = {
    type: 'image',
    x: textPoint.value.x,
    y: textPoint.value.y,
    width: displayW,
    height: displayH,
    src,
  }

  showSnackbar(t('drawPage.floatingLayerCreated') || 'Drag to move · resize at corners · Enter to stamp · Esc to cancel', 'info')
  redrawCanvas()
}

const cancelTextInput = () => {
  textEditorVisible.value = false
  textDraft.value = ''
}

const handlePasteEvent = async (e) => {
  if (floatingLayer.value) return
  const items = e.clipboardData?.items
  if (!items) return
  for (const item of items) {
    if (!item.type.startsWith('image/')) continue
    const file = item.getAsFile()
    if (!file) continue
    e.preventDefault()
    const dataUrl = await new Promise((resolve) => {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.readAsDataURL(file)
    })
    await createFloatingLayerFromImage(dataUrl)
    return
  }
}

onMounted(() => {
  initCanvas()
  loadUserName()
  loadWeeklyTheme()
  window.addEventListener('paste', handlePasteEvent)
  window.addEventListener('keydown', handleCanvasKeyDown)
})

const handleCanvasKeyDown = (e) => {
  if (!floatingLayer.value) return
  if (e.key === 'Enter') {
    e.preventDefault()
    mergeFloatingLayer()
  } else if (e.key === 'Escape') {
    e.preventDefault()
    discardFloatingLayer()
  }
}

const loadUserName = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    try {
      const user = JSON.parse(userData)
      userName.value = user.username || user.name || 'Anonymous'
    } catch (e) {
      userName.value = 'Anonymous'
    }
  }
}

const loadWeeklyTheme = async () => {
  try {
    const res = await api.get('/weekly-theme')
    weeklyTheme.value = res.data.theme
  } catch (e) {
    // ignore
  }
}

onUnmounted(() => {
  window.removeEventListener('paste', handlePasteEvent)
  window.removeEventListener('keydown', handleCanvasKeyDown)
})

const initCanvas = () => {
  nextTick(() => {
    if (!canvas.value) return

    let w, h
    if (window.innerWidth <= 959) {
      // Mobile: fill available drawing area (below navbar, above dock)
      const baseW = Math.max(Math.floor(window.innerWidth - 16), 100)
      const baseH = Math.max(Math.floor(window.innerHeight - 72 - 64 - 20), 100)
      // Portrait = narrow×tall, Landscape = swap to wide×short
      w = canvasLandscape.value ? Math.max(baseW, baseH) : Math.min(baseW, baseH)
      h = canvasLandscape.value ? Math.min(baseW, baseH) : Math.max(baseW, baseH)
    } else {
      // Desktop: fill available area (viewport minus sidebar 260px + padding, minus navbar 72px + padding)
      const availW = Math.max(Math.floor(window.innerWidth - 260 - 48), 400)
      const availH = Math.max(Math.floor(window.innerHeight - 72 - 48), 400)
      if (canvasLandscape.value) {
        w = Math.max(availW, availH)
        h = Math.min(availW, availH)
      } else {
        w = Math.min(availW, availH)
        h = Math.max(availW, availH)
      }
    }

    canvas.value.width = w
    canvas.value.height = h
    
    ctx.value = canvas.value.getContext('2d', { willReadFrequently: true })
    setupCanvasContext()
    clearCanvasBackground()
  })
}

const toggleOrientation = () => {
  canvasLandscape.value = !canvasLandscape.value
  const savedPaths = [...paths.value]
  initCanvas()
  nextTick(() => {
    paths.value = savedPaths
    redrawCanvas()
  })
}

const setupCanvasContext = () => {
  if (!ctx.value) return
  ctx.value.lineCap = 'round'
  ctx.value.lineJoin = 'round'
}

const clearCanvasBackground = () => {
  if (!ctx.value || !canvas.value) return
  ctx.value.fillStyle = '#FFFFFF'
  ctx.value.fillRect(0, 0, canvas.value.width, canvas.value.height)
}

const handleResize = () => {
  if (!canvas.value) return
  const tempPaths = [...paths.value]
  initCanvas()
  paths.value = tempPaths
  redrawCanvas()
}

const getCanvasPosition = (clientX, clientY) => {
  if (!canvas.value) return { x: 0, y: 0 }
  const rect = canvas.value.getBoundingClientRect()
  const scaleX = canvas.value.width / rect.width
  const scaleY = canvas.value.height / rect.height
  return {
    x: (clientX - rect.left) * scaleX,
    y: (clientY - rect.top) * scaleY
  }
}

const getMousePos = (e) => getCanvasPosition(e.clientX, e.clientY)

const getTouchPos = (e) => {
  if (!e.touches || !e.touches[0]) return { x: 0, y: 0 }
  return getCanvasPosition(e.touches[0].clientX, e.touches[0].clientY)
}

const drawPoint = (c, point, color, width, brush) => {
  if (!c || !point) return
  c.save()
  c.globalAlpha = brush === 'marker' ? 0.35 : 1
  c.fillStyle = color
  if (brush === 'square') {
    const side = Math.max(1, width)
    c.fillRect(point.x - side / 2, point.y - side / 2, side, side)
  } else {
    c.beginPath()
    c.arc(point.x, point.y, Math.max(0.5, width / 2), 0, Math.PI * 2)
    c.fill()
  }
  c.restore()
}

const startPath = (pos) => {
  if (textEditorVisible.value) commitTextInput()

  if (floatingLayer.value) {
    const handle = getFloatingLayerHandleAt(pos)
    if (handle) {
      floatingLayerResizing.value = true
      floatingLayerResizeHandle.value = handle
      if (canvas.value) canvas.value.style.cursor = getResizeCursorForHandle(handle)
      return
    }
    if (isPointInFloatingLayer(pos)) {
      floatingLayerDragging.value = true
      floatingLayerOffset.value = {
        x: pos.x - floatingLayer.value.x,
        y: pos.y - floatingLayer.value.y,
      }
      if (canvas.value) canvas.value.style.cursor = 'grabbing'
      return
    }
  }

  if (tool.value === 'select') {
    isSelecting.value = true
    selectionStart.value = { x: pos.x, y: pos.y }
    selectionEnd.value = { x: pos.x, y: pos.y }
    return
  }

  if (tool.value === 'bucket') {
    floodFill(Math.round(pos.x), Math.round(pos.y), currentColor.value)
    return
  }

  if (tool.value === 'text') {
    openTextEditorAt(pos)
    return
  }

  if (isShapeTool.value) {
    isDrawing.value = true
    shapeStartPoint.value = { x: pos.x, y: pos.y }
    shapeEndPoint.value = { x: pos.x, y: pos.y }
    return
  }

  isDrawing.value = true
  const bType = tool.value === 'eraser' ? 'eraser' : brushType.value
  const color = currentDrawingColor.value
  const width = currentDrawingWidth.value

  if (bType === 'spray') {
    const density = 25
    const radius = width * 3
    const sprayDots = []
    if (ctx.value) {
      ctx.value.fillStyle = color
      ctx.value.globalAlpha = 0.8
      for (let i = 0; i < density; i++) {
        const angle = Math.random() * Math.PI * 2
        const r = Math.sqrt(Math.random()) * radius
        const dot = { x: pos.x + r * Math.cos(angle), y: pos.y + r * Math.sin(angle), r: Math.max(0.5, width * 0.18) }
        sprayDots.push(dot)
        ctx.value.beginPath()
        ctx.value.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
        ctx.value.fill()
      }
      ctx.value.globalAlpha = 1
    }
    currentPath.value = [{ x: pos.x, y: pos.y, color, width, dots: sprayDots }]
    return
  }

  currentPath.value = [{ x: pos.x, y: pos.y, color, width }]
  drawPoint(ctx.value, pos, color, bType === 'marker' ? width * 2.5 : width, bType)
}

const startDrawing = (e) => {
  const pos = getMousePos(e)
  startPath(pos)
}

const handleTouchStart = (e) => {
  const pos = getTouchPos(e)
  startPath(pos)
}

const drawLine = (pos) => {
  if (isSelecting.value && selectionStart.value) {
    selectionEnd.value = { x: pos.x, y: pos.y }
    redrawCanvas()
    drawSelectionOutline()
    return
  }

  if (floatingLayerDragging.value && floatingLayer.value) {
    floatingLayer.value.x = pos.x - floatingLayerOffset.value.x
    floatingLayer.value.y = pos.y - floatingLayerOffset.value.y
    redrawCanvas()
    drawFloatingLayerOverlay()
    return
  }

  if (floatingLayerResizing.value && floatingLayer.value && floatingLayerResizeHandle.value) {
    const layer = floatingLayer.value
    const handle = floatingLayerResizeHandle.value
    const minSize = 10
    if (handle === 'n' || handle === 'nw' || handle === 'ne') {
      const newH = layer.height - (pos.y - layer.y)
      if (newH >= minSize) {
        layer.height = newH
        layer.y = pos.y
      }
    }
    if (handle === 's' || handle === 'sw' || handle === 'se') {
      const newH = pos.y - layer.y
      if (newH >= minSize) layer.height = newH
    }
    if (handle === 'w' || handle === 'nw' || handle === 'sw') {
      const newW = layer.width - (pos.x - layer.x)
      if (newW >= minSize) {
        layer.width = newW
        layer.x = pos.x
      }
    }
    if (handle === 'e' || handle === 'ne' || handle === 'se') {
      const newW = pos.x - layer.x
      if (newW >= minSize) layer.width = newW
    }
    redrawCanvas()
    drawFloatingLayerOverlay()
    return
  }

  if (!isDrawing.value || !ctx.value) return

  if (isShapeTool.value) {
    if (!shapeStartPoint.value) return
    shapeEndPoint.value = { x: pos.x, y: pos.y }
    redrawCanvas()
    drawShapeOnCtx(ctx.value, tool.value, shapeStartPoint.value, shapeEndPoint.value, {
      color: currentDrawingColor.value,
      width: currentDrawingWidth.value,
      fill: shapeFill.value,
    })
    return
  }

  if (currentPath.value.length === 0) return
  if (tool.value === 'bucket') return
  const lastPoint = currentPath.value[currentPath.value.length - 1]
  const c = ctx.value
  const color = currentDrawingColor.value
  const width = currentDrawingWidth.value
  const bType = tool.value === 'eraser' ? 'eraser' : brushType.value

  if (bType === 'spray') {
    const density = 25
    const radius = width * 3
    c.fillStyle = color
    c.globalAlpha = 0.8
    const sprayDots = []
    for (let i = 0; i < density; i++) {
      const angle = Math.random() * Math.PI * 2
      const r = Math.sqrt(Math.random()) * radius
      const dot = { x: pos.x + r * Math.cos(angle), y: pos.y + r * Math.sin(angle), r: Math.max(0.5, width * 0.18) }
      sprayDots.push(dot)
      c.beginPath()
      c.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
      c.fill()
    }
    c.globalAlpha = 1
    currentPath.value.push({ x: pos.x, y: pos.y, color, width, dots: sprayDots })
  } else {
    c.globalAlpha = bType === 'marker' ? 0.35 : 1
    c.strokeStyle = color
    c.lineWidth = bType === 'marker' ? width * 2.5 : width
    c.lineCap = bType === 'square' ? 'square' : 'round'
    c.lineJoin = bType === 'square' ? 'miter' : 'round'
    c.beginPath()
    c.moveTo(lastPoint.x, lastPoint.y)
    c.lineTo(pos.x, pos.y)
    c.stroke()
    c.globalAlpha = 1
    currentPath.value.push({ x: pos.x, y: pos.y, color, width })
  }
}

const draw = (e) => {
  const pos = getMousePos(e)
  updateCanvasCursor(pos)
  drawLine(pos)
}

const updateCanvasCursor = (pos) => {
  if (!canvas.value) return
  if (floatingLayerDragging.value) {
    canvas.value.style.cursor = 'grabbing'
    return
  }
  if (floatingLayerResizing.value && floatingLayerResizeHandle.value) {
    canvas.value.style.cursor = getResizeCursorForHandle(floatingLayerResizeHandle.value)
    return
  }
  if (floatingLayer.value) {
    const handle = getFloatingLayerHandleAt(pos)
    if (handle) {
      canvas.value.style.cursor = getResizeCursorForHandle(handle)
      return
    }
    if (isPointInFloatingLayer(pos)) {
      canvas.value.style.cursor = 'grab'
      return
    }
  }
  canvas.value.style.cursor = 'crosshair'
}

const handleTouchMove = (e) => {
  const pos = getTouchPos(e)
  updateCanvasCursor(pos)
  drawLine(pos)
}

const stopDrawing = () => {
  floatingLayerDragging.value = false
  floatingLayerResizing.value = false
  floatingLayerResizeHandle.value = null
  if (canvas.value) canvas.value.style.cursor = 'crosshair'

  if (isSelecting.value) {
    isSelecting.value = false
    redrawCanvas()
    drawSelectionOutline()
    return
  }

  if (isDrawing.value && isShapeTool.value) {
    if (shapeStartPoint.value && shapeEndPoint.value) {
      paths.value.push({
        type: 'shape',
        shape: tool.value,
        start: { ...shapeStartPoint.value },
        end: { ...shapeEndPoint.value },
        color: currentDrawingColor.value,
        width: currentDrawingWidth.value,
        fill: shapeFill.value,
      })
      redoStack.value = []
    }
    shapeStartPoint.value = null
    shapeEndPoint.value = null
    isDrawing.value = false
    redrawCanvas()
    return
  }

  if (isDrawing.value && currentPath.value.length > 0) {
    const bType = tool.value === 'eraser' ? 'eraser' : brushType.value
    paths.value.push({
      type: 'stroke',
      points: [...currentPath.value],
      color: currentDrawingColor.value,
      width: currentDrawingWidth.value,
      brushType: bType
    })
    redoStack.value = []
  }
  currentPath.value = []
  isDrawing.value = false
  redrawCanvas()
}

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

const confirmClear = () => {
  if (paths.value.length > 0) {
    clearDialog.value = true
  } else {
    clearCanvas()
  }
}

const clearCanvas = () => {
  paths.value = []
  currentPath.value = []
  isDrawing.value = false
  textEditorVisible.value = false
  textDraft.value = ''
  discardFloatingLayer()
  redoStack.value = []
  redrawCanvas()
  clearDialog.value = false
}

const redrawCanvas = () => {
  if (!ctx.value || !canvas.value) return
  clearCanvasBackground()
  paths.value.forEach(path => {
    const c = ctx.value
    if (path.type === 'fill') {
      applyFloodFill(path.x, path.y, path.color)
      return
    }
    if (path.type === 'shape') {
      if (!path.start || !path.end || !path.shape) return
      drawShapeOnCtx(c, path.shape, path.start, path.end, {
        color: path.color || '#000000',
        width: path.width || 2,
        fill: !!path.fill,
      })
      return
    }
    if (path.type === 'image') {
      if (!path.src || !path.width || !path.height) return
      const cached = imageCache.get(path.src)
      if (cached) {
        c.drawImage(cached, path.x || 0, path.y || 0, path.width, path.height)
        return
      }
      const img = new Image()
      img.onload = () => {
        imageCache.set(path.src, img)
        redrawCanvas()
      }
      img.src = path.src
      return
    }
    if (path.type === 'text') {
      drawTextOnCtx(c, path)
      return
    }
    if (!path.points || path.points.length === 0) return
    const bType = path.brushType || 'pen'
    if (bType === 'spray') {
      c.globalAlpha = 0.8
      path.points.forEach(pt => {
        if (!pt.dots) return
        c.fillStyle = path.color
        pt.dots.forEach(dot => {
          c.beginPath()
          c.arc(dot.x, dot.y, dot.r, 0, Math.PI * 2)
          c.fill()
        })
      })
      c.globalAlpha = 1
      return
    }
    c.globalAlpha = bType === 'marker' ? 0.35 : 1
    c.strokeStyle = path.color
    c.lineWidth = bType === 'marker' ? path.width * 2.5 : path.width
    c.lineCap = bType === 'square' ? 'square' : 'round'
    c.lineJoin = bType === 'square' ? 'miter' : 'round'
    if (path.points.length === 1) {
      const pt = path.points[0]
      drawPoint(c, pt, path.color, bType === 'marker' ? path.width * 2.5 : path.width, bType)
      c.globalAlpha = 1
      return
    }
    c.beginPath()
    path.points.forEach((point, index) => {
      if (index === 0) c.moveTo(point.x, point.y)
      else c.lineTo(point.x, point.y)
    })
    c.stroke()
    c.globalAlpha = 1
  })

  drawFloatingLayerImage()
  if (selectionStart.value && selectionEnd.value) drawSelectionOutline()
  if (floatingLayer.value) drawFloatingLayerOverlay()
}

const saveDrawing = async () => {
  if (!drawingTitle.value.trim() || paths.value.length === 0) {
    showSnackbar(t('drawPage.addTitleAndDraw'), 'warning')
    return
  }
  
  saving.value = true
  
  try {
    const isFree = saveDestination.value === 'free'
    const drawingData = { paths: paths.value, canvasWidth: canvas.value.width, canvasHeight: canvas.value.height }
    const thumbnail = canvas.value.toDataURL('image/png', 0.3)
    
    await api.post('/drawings', {
      title: drawingTitle.value.trim(),
      description: drawingDescription.value.trim() || null,
      drawing_data: JSON.stringify(drawingData),
      thumbnail: thumbnail,
      is_free: isFree,
      tag_theme: !isFree,
    })
    
    showSnackbar(t('drawPage.savedSuccess'), 'success')
    
    saveDrawingDialog.value = false
    drawingTitle.value = ''
    drawingDescription.value = ''
    saveDestination.value = 'free'
    
    setTimeout(() => router.push('/gallery'), 1500)
    
  } catch (error) {
    console.error('Error saving drawing:', error)
    const message = error.response?.data?.message || t('drawPage.saveFailed')
    showSnackbar(message, 'error')
  } finally {
    saving.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const downloadDrawing = () => {
  if (!canvas.value) return
  const link = document.createElement('a')
  link.download = `${drawingTitle.value || 'doodle'}.png`
  link.href = canvas.value.toDataURL('image/png')
  link.click()
}

watch(tool, (nextTool) => {
  if (nextTool !== 'text' && textEditorVisible.value) commitTextInput()
})
</script>

<style scoped>
/* ============================================================
   Draw Page — premium dark UI
   ============================================================ */

.draw-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 72px);
  background: var(--c-bg);
  overflow: hidden;
  position: relative;
}

/* ============================================================
   Top toolbar
   ============================================================ */
.draw-toolbar {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 48px;
  padding: 0 10px;
  background: #0d0e13;
  border-bottom: 1px solid rgba(255,255,255,0.055);
  flex-shrink: 0;
  z-index: 10;
}

.dt-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  min-width: 90px;
}

.dt-title {
  font-size: 0.82rem;
  font-weight: 800;
  color: rgba(255,255,255,0.45);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.dt-center {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

.dt-right {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  min-width: 90px;
  justify-content: flex-end;
}

.dt-group {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 10px;
  padding: 3px;
  gap: 1px;
  margin: 0 3px;
}

.dt-btn {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: rgba(255,255,255,0.5);
  border-radius: 7px;
  cursor: pointer;
  transition: background 120ms, color 120ms;
  outline: none;
  flex-shrink: 0;
}
.dt-left .dt-btn, .dt-right .dt-btn {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 8px;
}
.dt-btn:hover:not(:disabled) {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.95);
}
.dt-btn:disabled { opacity: 0.22; cursor: default; }
.dt-btn--danger:hover:not(:disabled) {
  color: #f85149;
  background: rgba(248,81,73,0.14);
}

.dt-sep {
  width: 1px;
  height: 18px;
  background: rgba(255,255,255,0.08);
  margin: 0 4px;
  flex-shrink: 0;
}

.dt-theme-pill {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 5px 13px;
  background: linear-gradient(135deg, rgba(124,58,237,0.18), rgba(99,102,241,0.12));
  border: 1px solid rgba(124,58,237,0.35);
  border-radius: 999px;
  font-size: 0.73rem;
  font-weight: 700;
  color: #d4b8ff;
  margin-left: 8px;
  white-space: nowrap;
  flex-shrink: 0;
  letter-spacing: 0.01em;
}

.dt-save-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 34px;
  padding: 0 16px;
  background: linear-gradient(135deg, #7c3aed, #5b21b6);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(124,58,237,0.3);
  transition: box-shadow 150ms, transform 100ms;
  flex-shrink: 0;
}
.dt-save-btn:hover:not(:disabled) {
  box-shadow: 0 4px 22px rgba(124,58,237,0.55);
  transform: translateY(-1px);
}
.dt-save-btn:active:not(:disabled) { transform: translateY(0); }
.dt-save-btn:disabled { opacity: 0.3; cursor: default; box-shadow: none; }

/* ============================================================
   Workspace
   ============================================================ */
.draw-workspace {
  flex: 1;
  display: flex;
  overflow: hidden;
  position: relative;
}

.canvas-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.mobile-theme-strip { display: none; }

/* ============================================================
   Sidebar
   ============================================================ */
.draw-sidebar {
  width: 220px;
  flex-shrink: 0;
  background: #0d0e13;
  border-right: 1px solid rgba(255,255,255,0.055);
  display: flex;
  flex-direction: column;
  padding: 12px 12px 20px;
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.08) transparent;
}

.draw-sidebar::-webkit-scrollbar { width: 3px; }
.draw-sidebar::-webkit-scrollbar-track { background: transparent; }
.draw-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

.sb-drag-handle {
  display: none;
  width: 38px;
  height: 4px;
  background: var(--c-border-md);
  border-radius: 2px;
  margin: 0 auto 14px;
  flex-shrink: 0;
}

.sb-section { padding: 2px 0 10px; }

.sb-disabled {
  opacity: 0.28;
  pointer-events: none;
}

.sb-divider {
  height: 1px;
  background: rgba(255,255,255,0.05);
  margin: 0 0 6px;
}

.sb-label {
  font-size: 0.59rem;
  font-weight: 800;
  letter-spacing: 0.13em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.25);
  margin: 0 0 8px;
  display: block;
}

.sb-tools {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 4px;
}

.sb-tool-btn {
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  color: rgba(255,255,255,0.42);
  cursor: pointer;
  transition: background 130ms, color 130ms, border-color 130ms, box-shadow 130ms;
  outline: none;
}

.sb-tool-btn:hover {
  background: rgba(255,255,255,0.08);
  color: rgba(255,255,255,0.85);
  border-color: rgba(255,255,255,0.14);
}

.sb-tool-btn.active {
  background: rgba(124,58,237,0.2);
  border-color: rgba(124,58,237,0.55);
  color: #c4b5fd;
  box-shadow: 0 0 0 1px rgba(124,58,237,0.2), inset 0 1px 0 rgba(255,255,255,0.05);
}

.sb-copy-paste-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px;
}

.sb-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  height: 38px;
  padding: 0 8px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  color: rgba(255,255,255,0.42);
  cursor: pointer;
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
  font-size: 0.76rem;
  font-weight: 600;
  transition: background 130ms, color 130ms, border-color 130ms;
  outline: none;
}

.sb-action-btn:hover {
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.82);
  border-color: rgba(255,255,255,0.13);
}

.sb-color-row {
  display: flex;
  align-items: center;
  gap: 9px;
  cursor: pointer;
  margin-bottom: 10px;
  position: relative;
  padding: 6px 8px;
  border-radius: 9px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  transition: background 130ms;
}

.sb-color-row:hover {
  background: rgba(255,255,255,0.06);
}

.sb-color-swatch {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1.5px solid rgba(255,255,255,0.18);
  flex-shrink: 0;
  box-shadow: 0 1px 6px rgba(0,0,0,0.4);
}

.sb-color-hex {
  font-size: 0.7rem;
  font-family: 'Courier New', monospace;
  color: rgba(255,255,255,0.38);
  flex: 1;
  letter-spacing: 0.04em;
}

.sb-color-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  width: 100%;
  cursor: pointer;
}

.sb-presets {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 5px;
}

.sb-preset {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  outline: none;
  transition: transform 120ms, box-shadow 120ms;
  box-shadow: 0 1px 4px rgba(0,0,0,0.4);
}

.sb-preset:hover {
  transform: scale(1.18);
  box-shadow: 0 2px 8px rgba(0,0,0,0.55);
}

.sb-preset.active {
  border-color: rgba(255,255,255,0.9);
  transform: scale(1.1);
  box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
}

.sb-preset:disabled {
  opacity: 0.35;
  cursor: default;
  transform: none;
}

.sb-size-val {
  color: var(--c-text-dim);
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
}

.sb-size-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 44px;
  background: rgba(255,255,255,0.03);
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.06);
  margin-bottom: 8px;
}

.sb-size-dot {
  display: block;
  border-radius: 50%;
  min-width: 4px;
  min-height: 4px;
  transition: width 80ms, height 80ms, background 80ms;
}

.sb-range {
  width: 100%;
  accent-color: #7c3aed;
  cursor: pointer;
  margin: 0;
  height: 20px;
}

.sb-brush-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 4px;
}

.sb-brush-btn {
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  color: rgba(255,255,255,0.4);
  cursor: pointer;
  transition: background 130ms, color 130ms, border-color 130ms, box-shadow 130ms;
  outline: none;
}

.sb-brush-btn:hover {
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.82);
}

.sb-brush-btn.active {
  background: rgba(124,58,237,0.2);
  border-color: rgba(124,58,237,0.5);
  color: #c4b5fd;
  box-shadow: 0 0 0 1px rgba(124,58,237,0.2);
}

.sb-check-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  color: rgba(255,255,255,0.42);
  cursor: pointer;
  padding: 5px 0;
}

.sb-check-row input {
  accent-color: #7c3aed;
  width: 15px;
  height: 15px;
  cursor: pointer;
}

.sb-act-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 5px;
  margin-bottom: 7px;
}

.sb-act {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.03);
  color: rgba(255,255,255,0.4);
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 130ms, color 130ms, border-color 130ms;
  outline: none;
}

.sb-act:hover:not(:disabled) {
  background: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.85);
  border-color: rgba(255,255,255,0.13);
}

.sb-act:disabled {
  opacity: 0.22;
  cursor: default;
}

.sb-act--icon { height: 38px; }

.sb-act--danger { color: rgba(248,81,73,0.65); border-color: rgba(248,81,73,0.18); }
.sb-act--danger:hover:not(:disabled) {
  background: rgba(248,81,73,0.1);
  border-color: rgba(248,81,73,0.4);
  color: #f85149;
}

.sb-act--primary {
  width: 100%;
  background: #7c3aed;
  color: #fff;
  border-color: transparent;
  padding: 11px 14px;
  border-radius: 9px;
  font-size: 0.84rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.sb-act--primary:hover:not(:disabled) {
  background: #6d28d9;
  box-shadow: 0 4px 20px rgba(124,58,237,0.45);
}

.sb-act--primary:disabled { opacity: 0.3; }

/* ============================================================
   Canvas area
   ============================================================ */
.canvas-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
  padding: 16px;
  position: relative;
  background: #0b0c11;
  background-image:
    radial-gradient(ellipse at 30% 40%, rgba(124,58,237,0.05) 0%, transparent 60%),
    radial-gradient(ellipse at 70% 60%, rgba(59,130,246,0.03) 0%, transparent 60%);
}

.canvas-hint {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
  color: rgba(255,255,255,0.16);
  font-size: 0.88rem;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.draw-canvas {
  background: #ffffff;
  border-radius: 10px;
  cursor: crosshair;
  touch-action: none;
  max-width: 100%;
  max-height: 100%;
  box-shadow:
    0 0 0 1px rgba(255,255,255,0.07),
    0 8px 32px rgba(0,0,0,0.6),
    0 32px 80px rgba(0,0,0,0.3);
}

.canvas-text-input {
  position: fixed;
  min-width: 120px;
  max-width: 300px;
  padding: 5px 8px;
  border: 1.5px solid rgba(124, 58, 237, 0.5);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.97);
  color: #111;
  outline: none;
  z-index: 20;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.22);
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
}

/* ============================================================
   Dialogs
   ============================================================ */
.dlg-card {
  background: #111318 !important;
  border: 1px solid rgba(255,255,255,0.09) !important;
  border-radius: 14px !important;
}

.dlg-head {
  padding: 18px 22px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dlg-head h3 {
  font-size: 1rem;
  font-weight: 700;
  color: rgba(255,255,255,0.9);
}

.dlg-body { padding: 16px 22px; }

.dlg-foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 22px 18px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.save-meta {
  font-size: 0.82rem;
  color: var(--c-muted);
}

.save-dest-row {
  display: flex;
  gap: 10px;
}

.save-dest-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  background: rgba(255,255,255,0.03);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  color: var(--c-text);
  text-align: center;
}
.save-dest-btn:hover:not(:disabled) {
  border-color: rgba(124,58,237,0.4);
  background: rgba(124,58,237,0.06);
}
.save-dest-btn.active {
  border-color: #7c3aed;
  background: rgba(124,58,237,0.12);
}
.save-dest-btn.disabled, .save-dest-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.dest-label {
  font-size: 0.85rem;
  font-weight: 600;
  margin-top: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
.dest-sub {
  font-size: 0.72rem;
  color: var(--c-muted);
  margin-top: 2px;
}

.mobile-dock { display: none; }
.sidebar-backdrop { display: none; }
.sb-tabs { display: none; }
.sb-panel { display: contents; }
.sb-panel--mobile-only { display: none; }

/* ============================================================
   MOBILE <= 959px
   ============================================================ */
@media (max-width: 959px) {

  .draw-toolbar { display: none; }

  .draw-workspace { flex: 1; }

  .mobile-theme-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    height: 30px;
    background: rgba(124,58,237,0.13);
    border-bottom: 1px solid rgba(124,58,237,0.22);
    font-size: 0.72rem;
    font-weight: 700;
    color: #c4b5fd;
    flex-shrink: 0;
    letter-spacing: 0.01em;
  }

  .canvas-wrap {
    padding: 8px 8px 0;
    width: 100%;
    align-items: flex-start;
    justify-content: flex-start;
    background: #0b0c11;
    background-image: none;
  }

  .draw-canvas {
    max-width: 100%;
    max-height: calc(100vh - 72px - 64px - 16px - 30px);
    border-radius: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.65);
  }

  .sb-panel--mobile-only { display: block; }

  .draw-sidebar {
    position: fixed;
    inset: auto 0 0 0;
    z-index: 300;
    width: 100%;
    max-height: 65vh;
    border-right: none;
    border-top: 1px solid rgba(124,58,237,0.3);
    border-radius: 18px 18px 0 0;
    background: #111218;
    box-shadow: 0 -20px 60px rgba(0,0,0,0.85);
    transform: translateY(110%);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    padding: 0 14px calc(env(safe-area-inset-bottom, 10px) + 14px);
    overflow-y: auto;
  }

  .draw-sidebar.is-open { transform: translateY(0); }

  .sb-drag-handle { display: block; }

  .sb-tabs {
    display: flex;
    gap: 5px;
    padding: 6px 0 12px;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    background: #111218;
    z-index: 1;
  }

  .sb-tab {
    flex: 1;
    height: 36px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 9px;
    background: rgba(255,255,255,0.03);
    color: rgba(255,255,255,0.35);
    font-family: 'Nunito', system-ui, sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 140ms ease;
    outline: none;
    -webkit-tap-highlight-color: transparent;
  }

  .sb-tab.active {
    background: rgba(124,58,237,0.18);
    border-color: rgba(124,58,237,0.5);
    color: #c4b5fd;
    box-shadow: 0 0 0 1px rgba(124,58,237,0.15);
  }

  .sb-panel { display: block; }
  .sb-panel--hidden { display: none; }

  .sb-tools {
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
  }

  .sb-tool-btn  { height: 56px; border-radius: 10px; }
  .sb-brush-btn { height: 50px; }
  .sb-range     { height: 32px; }
  .sb-act--icon { height: 46px; }

  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(4px);
    z-index: 299;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.22s ease;
  }

  .sidebar-backdrop.is-visible {
    opacity: 1;
    pointer-events: auto;
  }

  .mobile-dock {
    display: flex;
    align-items: center;
    position: fixed;
    inset: auto 0 0 0;
    z-index: 200;
    height: calc(64px + env(safe-area-inset-bottom, 0px));
    padding: 0 8px env(safe-area-inset-bottom, 0px);
    background: rgba(13,14,19,0.96);
    backdrop-filter: blur(16px);
    border-top: 1px solid rgba(255,255,255,0.07);
    box-shadow: 0 -8px 40px rgba(0,0,0,0.75);
    gap: 2px;
  }

  .dock-btn {
    flex: 1;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: rgba(255,255,255,0.38);
    border-radius: 12px;
    cursor: pointer;
    transition: background 120ms, color 120ms;
    outline: none;
    -webkit-tap-highlight-color: transparent;
    position: relative;
  }

  .dock-btn::after {
    content: '';
    position: absolute;
    bottom: 6px;
    left: 50%;
    transform: translateX(-50%) scaleX(0);
    width: 18px;
    height: 3px;
    background: #7c3aed;
    border-radius: 2px;
    transition: transform 130ms ease;
  }

  .dock-btn.active {
    color: #c4b5fd;
    background: rgba(124,58,237,0.1);
  }

  .dock-btn.active::after {
    transform: translateX(-50%) scaleX(1);
  }

  .dock-btn:active:not(:disabled) { background: rgba(255,255,255,0.06); }

  .dock-btn:disabled {
    opacity: 0.2;
    pointer-events: none;
  }

  .dock-more-btn.active {
    background: rgba(124,58,237,0.12);
    color: #a78bfa;
  }

  .dock-color-btn {
    flex: 1;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 12px;
    position: relative;
    -webkit-tap-highlight-color: transparent;
    transition: background 120ms;
  }

  .dock-color-btn:active { background: rgba(255,255,255,0.06); }

  .dock-color-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2.5px solid rgba(255,255,255,0.55);
    display: block;
    box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    transition: transform 120ms;
  }

  .dock-color-btn:active .dock-color-dot { transform: scale(0.9); }

  .dock-color-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    width: 100%;
    cursor: pointer;
  }
}
</style>
