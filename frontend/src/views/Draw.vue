<template>
  <v-container fluid class="draw-page">
    <v-row>
      <v-col cols="12">
        <v-card elevation="4" rounded="lg" class="island-card">
          <v-card-title class="text-h4 pa-6 d-flex align-center bg-gradient">
            <v-icon size="40" class="mr-2">mdi-palette-outline</v-icon>
            🏝️ Doodle Canvas
            <v-spacer></v-spacer>
            <v-btn
              color="success"
              variant="elevated"
              prepend-icon="mdi-content-save"
              @click="saveDrawingDialog = true"
              :disabled="paths.length === 0"
              size="large"
              class="save-doodle-btn"
            >
              Save Doodle
            </v-btn>
          </v-card-title>
          
          <v-divider></v-divider>
          
          <!-- Drawing Tools -->
          <v-card-text class="pa-6">
            <v-row class="mb-6 tools-section">
              <v-col cols="12" md="auto" class="d-flex align-center gap-3">
                <v-chip color="deep-purple-darken-1" variant="elevated">
                  <v-icon start>mdi-tools</v-icon>
                  Tools
                </v-chip>
                <v-btn-toggle
                  v-model="tool"
                  color="deep-purple-darken-2"
                  mandatory
                  variant="outlined"
                  divided
                  elevation="2"
                >
                  <v-btn value="pen" size="large">
                    <v-icon>mdi-pencil</v-icon>
                    <span class="ml-2">Pen</span>
                  </v-btn>
                  <v-btn value="eraser" size="large">
                    <v-icon>mdi-eraser</v-icon>
                    <span class="ml-2">Eraser</span>
                  </v-btn>
                </v-btn-toggle>
              </v-col>

              <v-col cols="12" md="auto" class="d-flex align-center gap-3">
                <v-chip color="pink-darken-1" variant="elevated">
                  <v-icon start>mdi-palette</v-icon>
                  Color
                </v-chip>
                <input 
                  type="color" 
                  v-model="currentColor" 
                  class="color-picker"
                  :disabled="tool === 'eraser'"
                />
                <div class="color-presets">
                  <button
                    v-for="color in colorPresets"
                    :key="color"
                    @click="currentColor = color"
                    :style="{ background: color }"
                    :class="{ 'active': currentColor === color && tool === 'pen' }"
                    class="preset-btn"
                    :disabled="tool === 'eraser'"
                  ></button>
                </div>
              </v-col>

              <v-col cols="12" md="auto" class="d-flex align-center gap-3">
                <v-chip color="orange-darken-1" variant="elevated">
                  <v-icon start>mdi-brush</v-icon>
                  Size: {{ brushSize }}px
                </v-chip>
                <v-slider
                  v-model="brushSize"
                  :min="1"
                  :max="30"
                  :step="1"
                  thumb-label
                  color="deep-purple-darken-2"
                  style="max-width: 200px"
                ></v-slider>
              </v-col>

              <v-spacer></v-spacer>

              <v-col cols="12" md="auto" class="d-flex gap-2">
                <v-btn
                  color="orange-darken-1"
                  variant="tonal"
                  prepend-icon="mdi-undo"
                  @click="undo"
                  :disabled="paths.length === 0"
                  size="large"
                >
                  Undo
                </v-btn>
                <v-btn
                  color="error"
                  variant="tonal"
                  prepend-icon="mdi-delete"
                  @click="confirmClear"
                  size="large"
                >
                  Clear
                </v-btn>
              </v-col>
            </v-row>

            <!-- Canvas -->
            <div class="canvas-container">
              <div class="canvas-hint" v-if="paths.length === 0">
                <v-icon size="60" color="grey-lighten-2">mdi-draw</v-icon>
                <p class="text-h6 text-grey mt-2">Start doodling! 🎨</p>
              </div>
              <canvas
                ref="canvas"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart.prevent="handleTouchStart"
                @touchmove.prevent="handleTouchMove"
                @touchend.prevent="stopDrawing"
                class="drawing-canvas"
              ></canvas>
            </div>
            
            <!-- Canvas Stats -->
            <div class="canvas-stats mt-4 text-center">
              <v-chip class="ma-1" color="blue-grey-lighten-4">
                <v-icon start>mdi-brush-variant</v-icon>
                {{ paths.length }} strokes
              </v-chip>
              <v-chip class="ma-1" color="blue-grey-lighten-4">
                <v-icon start>mdi-image-size-select-large</v-icon>
                {{ canvas?.width }}x{{ canvas?.height }}px
              </v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Clear Confirmation Dialog -->
    <v-dialog v-model="clearDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h5">Clear Canvas?</v-card-title>
        <v-card-text>
          Are you sure you want to clear your doodle? This action cannot be undone!
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="clearDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="elevated" @click="clearCanvas">
            Clear All
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Save Drawing Dialog -->
    <v-dialog v-model="saveDrawingDialog" max-width="500">
      <v-card>
        <v-card-title class="text-h5 bg-gradient pa-6">
          <v-icon size="30" class="mr-2">mdi-island</v-icon>
          Save to DoodleVerse
        </v-card-title>
        <v-card-text class="pa-6">
          <v-text-field
            v-model="drawingTitle"
            label="Doodle Title *"
            variant="outlined"
            :rules="[v => !!v || 'Title is required']"
            class="mb-4"
            prepend-inner-icon="mdi-format-title"
            counter="50"
            maxlength="50"
          ></v-text-field>
          <v-alert type="info" variant="tonal" class="mb-2">
            <v-icon start>mdi-account-circle</v-icon>
            Posting as Doodler: {{ userName }}
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn size="large" @click="saveDrawingDialog = false">Cancel</v-btn>
          <v-btn
            color="deep-purple-darken-2"
            variant="elevated"
            @click="saveDrawing"
            :loading="saving"
            :disabled="!drawingTitle"
            size="large"
          >
            <v-icon start>mdi-send</v-icon>
            Share to Island
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success Snackbar -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="3000"
    >
      {{ snackbarText }}
      <template v-slot:actions>
        <v-btn
          variant="text"
          @click="snackbar = false"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import '@/styles/draw.css'

const router = useRouter()
const canvas = ref(null)
const ctx = ref(null)
const isDrawing = ref(false)
const paths = ref([])
const currentPath = ref([])

// Drawing settings
const tool = ref('pen')
const currentColor = ref('#000000')
const brushSize = ref(3)

// Color presets
const colorPresets = [
  '#000000', '#FF0000', '#FF7F00', '#FFFF00', '#00FF00',
  '#0000FF', '#4B0082', '#9400D3', '#FFFFFF', '#808080'
]

// Save dialog
const saveDrawingDialog = ref(false)
const clearDialog = ref(false)
const drawingTitle = ref('')
const saving = ref(false)
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
  tool.value === 'eraser' ? brushSize.value * 2 : brushSize.value
)

onMounted(() => {
  initCanvas()
  window.addEventListener('resize', handleResize)
  loadUserName()
})

const loadUserName = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    try {
      const user = JSON.parse(userData)
      userName.value = user.name || 'Anonymous'
    } catch (e) {
      userName.value = 'Anonymous'
    }
  }
}

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

const initCanvas = () => {
  nextTick(() => {
    if (!canvas.value) return
    
    // Get the display size
    const displayWidth = canvas.value.offsetWidth
    const displayHeight = 600
    
    // Set the canvas internal size to match display size
    // This ensures 1:1 pixel ratio
    canvas.value.width = displayWidth
    canvas.value.height = displayHeight
    
    ctx.value = canvas.value.getContext('2d', { willReadFrequently: true })
    setupCanvasContext()
    clearCanvasBackground()
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

const startPath = (pos) => {
  isDrawing.value = true
  currentPath.value = [{
    x: pos.x,
    y: pos.y,
    color: currentDrawingColor.value,
    width: currentDrawingWidth.value
  }]
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
  if (!isDrawing.value || !ctx.value || currentPath.value.length === 0) return
  
  const lastPoint = currentPath.value[currentPath.value.length - 1]
  
  ctx.value.strokeStyle = currentDrawingColor.value
  ctx.value.lineWidth = currentDrawingWidth.value
  
  ctx.value.beginPath()
  ctx.value.moveTo(lastPoint.x, lastPoint.y)
  ctx.value.lineTo(pos.x, pos.y)
  ctx.value.stroke()
  
  currentPath.value.push({
    x: pos.x,
    y: pos.y,
    color: currentDrawingColor.value,
    width: currentDrawingWidth.value
  })
}

const draw = (e) => {
  const pos = getMousePos(e)
  drawLine(pos)
}

const handleTouchMove = (e) => {
  const pos = getTouchPos(e)
  drawLine(pos)
}

const stopDrawing = () => {
  if (isDrawing.value && currentPath.value.length > 1) {
    paths.value.push({
      points: [...currentPath.value],
      color: currentDrawingColor.value,
      width: currentDrawingWidth.value
    })
  }
  currentPath.value = []
  isDrawing.value = false
}

const undo = () => {
  if (paths.value.length === 0) return
  paths.value.pop()
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
  redrawCanvas()
  clearDialog.value = false
}

const redrawCanvas = () => {
  if (!ctx.value || !canvas.value) return
  
  clearCanvasBackground()
  
  paths.value.forEach(path => {
    if (!path.points || path.points.length === 0) return
    
    ctx.value.strokeStyle = path.color
    ctx.value.lineWidth = path.width
    
    ctx.value.beginPath()
    path.points.forEach((point, index) => {
      if (index === 0) {
        ctx.value.moveTo(point.x, point.y)
      } else {
        ctx.value.lineTo(point.x, point.y)
      }
    })
    ctx.value.stroke()
  })
}

const saveDrawing = async () => {
  if (!drawingTitle.value.trim() || paths.value.length === 0) {
    showSnackbar('Please add a title and draw something!', 'warning')
    return
  }
  
  saving.value = true
  
  try {
    const drawingData = { paths: paths.value }
    const thumbnail = canvas.value.toDataURL('image/png', 0.3)
    
    await api.post('/drawings', {
      title: drawingTitle.value.trim(),
      drawing_data: JSON.stringify(drawingData),
      thumbnail: thumbnail
    })
    
    showSnackbar('Drawing saved successfully! 🎉', 'success')
    
    saveDrawingDialog.value = false
    drawingTitle.value = ''
    
    setTimeout(() => router.push('/'), 1500)
    
  } catch (error) {
    console.error('Error saving drawing:', error)
    const message = error.response?.data?.message || 'Failed to save drawing. Please try again.'
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
</script>
