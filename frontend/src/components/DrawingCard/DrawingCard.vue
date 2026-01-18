<template>
  <v-card class="drawing-card" elevation="0">
    <div class="drawing-preview" @click="$emit('view', drawing)">
      <canvas 
        :ref="el => canvasRef = el"
        width="400"
        height="300"
        class="preview-canvas"
      ></canvas>
    </div>
    
    <v-card-text>
      <h3 class="drawing-title">{{ drawing.title }}</h3>
      <p class="creator-name">by {{ drawing.creator_name }}</p>
    </v-card-text>
    
    <v-card-actions class="px-4 pb-4">
      <v-btn
        :class="['vote-btn', { voted: drawing.has_voted }]"
        @click="$emit('toggle-vote', drawing)"
        size="small"
        block
        variant="outlined"
      >
        <v-icon start>
          {{ drawing.has_voted ? 'mdi-heart' : 'mdi-heart-outline' }}
        </v-icon>
        {{ drawing.votes_count || 0 }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  drawing: {
    type: Object,
    required: true
  }
})

defineEmits(['toggle-vote', 'view'])

const canvasRef = ref(null)

const renderDrawing = () => {
  const canvas = canvasRef.value
  if (!canvas) return
  
  const ctx = canvas.getContext('2d')
  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  
  try {
    const data = JSON.parse(props.drawing.drawing_data)
    if (data.paths) {
      data.paths.forEach(path => {
        ctx.strokeStyle = path.color || '#000000'
        ctx.lineWidth = path.width || 2
        ctx.lineCap = 'round'
        ctx.lineJoin = 'round'
        ctx.beginPath()
        path.points.forEach((point, i) => {
          if (i === 0) ctx.moveTo(point.x, point.y)
          else ctx.lineTo(point.x, point.y)
        })
        ctx.stroke()
      })
    }
  } catch (e) {
    console.error('Render error:', e)
  }
}

onMounted(renderDrawing)
</script>

<style scoped src="./DrawingCard.css"></style>
