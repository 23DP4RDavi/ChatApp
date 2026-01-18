<template>
  <v-container fluid class="gallery-page">
    <!-- Floating Background Elements -->
    <div class="floating-clouds">
      <div class="cloud cloud-1">☁️</div>
      <div class="cloud cloud-2">☁️</div>
      <div class="cloud cloud-3">☁️</div>
    </div>
    <div class="floating-stars">
      <div class="star star-1">✨</div>
      <div class="star star-2">⭐</div>
      <div class="star star-3">✨</div>
      <div class="star star-4">⭐</div>
    </div>

    <!-- Hero Section -->
    <v-row class="hero-section mb-8">
      <v-col cols="12" class="text-center">
        <h1 class="cartoon-title bouncy-title">
          🎨 DoodleVerse Gallery 🏝️
        </h1>
        <p class="subtitle-text">
          Discover amazing artwork from our creative community! 
        </p>
        <div class="hero-actions">
          <v-btn
            size="x-large"
            class="create-btn"
            prepend-icon="mdi-draw"
            @click="$router.push('/draw')"
          >
            Create Your Art
          </v-btn>
          <v-btn
            size="x-large"
            class="chat-btn"
            prepend-icon="mdi-message"
            @click="$router.push('/messages')"
          >
            Messages
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- Stats Bar -->
    <v-row class="stats-bar mb-6">
      <v-col cols="12">
        <div class="stats-container">
          <div class="stat-box">
            <div class="stat-icon">🎨</div>
            <div class="stat-content">
              <div class="stat-value">{{ drawings.length }}</div>
              <div class="stat-label">Artworks</div>
            </div>
          </div>
          <div class="stat-box">
            <div class="stat-icon">💜</div>
            <div class="stat-content">
              <div class="stat-value">{{ totalVotes }}</div>
              <div class="stat-label">Total Loves</div>
            </div>
          </div>
          <div class="stat-box">
            <div class="stat-icon">👨‍🎨</div>
            <div class="stat-content">
              <div class="stat-value">{{ uniqueArtists }}</div>
              <div class="stat-label">Artists</div>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Sort Toggle -->
    <v-row class="mb-8">
      <v-col cols="12" class="d-flex justify-center align-center flex-wrap gap-4">
        <v-btn-toggle
          v-model="sortBy"
          class="sort-toggle"
          mandatory
          divided
        >
          <v-btn value="recent" class="toggle-btn">
            <v-icon start>mdi-clock-outline</v-icon>
            Recent
          </v-btn>
          <v-btn value="popular" class="toggle-btn">
            <v-icon start>mdi-fire</v-icon>
            Popular
          </v-btn>
        </v-btn-toggle>

        <v-text-field
          v-model="searchQuery"
          class="search-field"
          prepend-inner-icon="mdi-magnify"
          placeholder="Search artworks..."
          clearable
          hide-details
          variant="outlined"
          density="comfortable"
        ></v-text-field>
      </v-col>
    </v-row>

    <!-- Loading State -->
    <v-row v-if="loading" class="my-12">
      <v-col cols="12" class="text-center">
        <div class="loading-spinner">🎨</div>
        <p class="loading-text">Loading doodles...</p>
      </v-col>
    </v-row>

    <!-- Drawings Grid -->
    <v-row v-else-if="filteredDrawings.length > 0" class="drawings-grid">
      <v-col
        v-for="drawing in filteredDrawings"
        :key="drawing.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <v-card class="drawing-card" elevation="4">
          <div class="card-border-effect"></div>
          <div class="drawing-preview" @click="viewDrawing(drawing)">
            <canvas 
              :id="'canvas-' + drawing.id"
              width="400"
              height="300"
              class="preview-canvas"
            ></canvas>
            <div class="preview-overlay">
              <v-icon size="48" color="white">mdi-eye</v-icon>
            </div>
          </div>
          <v-card-text class="card-content">
            <h3 class="drawing-title">{{ drawing.title }}</h3>
            <p class="creator-name">✏️ by {{ drawing.creator_name || drawing.artist_name || 'Anonymous' }}</p>
            <p class="created-date">
              <v-icon size="14">mdi-calendar</v-icon>
              {{ formatDate(drawing.created_at) }}
            </p>
          </v-card-text>
          <v-card-actions class="card-actions">
            <v-btn
              :class="['vote-btn', { voted: drawing.has_voted }]"
              @click="toggleVote(drawing)"
              :disabled="!isAuthenticated"
            >
              <v-icon start class="heart-icon">{{ drawing.has_voted ? 'mdi-heart' : 'mdi-heart-outline' }}</v-icon>
              {{ drawing.votes_count || 0 }}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn
              icon
              size="small"
              class="share-btn"
              @click="shareDrawing(drawing)"
            >
              <v-icon>mdi-share-variant</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- No Results -->
    <v-row v-else-if="searchQuery && filteredDrawings.length === 0" class="empty-state">
      <v-col cols="12" class="text-center">
        <div class="empty-icon">🔍</div>
        <h3 class="empty-title">No artwork found</h3>
        <p class="empty-subtitle">Try a different search term</p>
        <v-btn
          class="clear-search-btn"
          @click="searchQuery = ''"
        >
          Clear Search
        </v-btn>
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-row v-else class="empty-state">
      <v-col cols="12" class="text-center">
        <div class="empty-icon">🏝️</div>
        <h3 class="empty-title">No doodles yet!</h3>
        <p class="empty-subtitle">Be the first doodler on the island!</p>
        <v-btn
          class="create-first-btn"
          size="x-large"
          @click="$router.push('/draw')"
        >
          <v-icon start>mdi-palette</v-icon>
          Create First Doodle
        </v-btn>
      </v-col>
    </v-row>
  </v-container>

  <!-- Drawing Detail Modal -->
  <v-dialog v-model="showDetailModal" max-width="900">
    <v-card v-if="selectedDrawing">
      <v-card-title class="d-flex justify-space-between align-center pa-4">
        <div>
          <h2>{{ selectedDrawing.title }}</h2>
          <p class="text-subtitle-2 text-grey">by {{ selectedDrawing.creator_name || selectedDrawing.artist_name || 'Anonymous' }}</p>
        </div>
        <v-btn icon variant="text" @click="showDetailModal = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>
      
      <v-divider></v-divider>
      
      <v-card-text class="pa-4">
        <div class="canvas-container" style="background: white; border-radius: 8px; padding: 16px;">
          <canvas 
            :id="'modal-canvas-' + selectedDrawing.id"
            width="800"
            height="600"
            style="width: 100%; height: auto; display: block; border: 1px solid #ddd; border-radius: 4px;"
          ></canvas>
        </div>
        
        <div class="mt-4 d-flex justify-space-between align-center">
          <div>
            <v-chip prepend-icon="mdi-calendar" size="small" class="mr-2">
              {{ formatDate(selectedDrawing.created_at) }}
            </v-chip>
            <v-chip prepend-icon="mdi-heart" size="small">
              {{ selectedDrawing.votes_count || 0 }} Loves
            </v-chip>
          </div>
          <div>
            <v-btn
              :color="selectedDrawing.has_voted ? 'pink' : 'grey'"
              :variant="selectedDrawing.has_voted ? 'elevated' : 'outlined'"
              @click="toggleVote(selectedDrawing)"
              :disabled="!isAuthenticated"
              class="mr-2"
            >
              <v-icon start>{{ selectedDrawing.has_voted ? 'mdi-heart' : 'mdi-heart-outline' }}</v-icon>
              {{ selectedDrawing.has_voted ? 'Loved' : 'Love' }}
            </v-btn>
            <v-btn color="primary" variant="outlined" @click="shareDrawing(selectedDrawing)">
              <v-icon start>mdi-share-variant</v-icon>
              Share
            </v-btn>
          </div>
        </div>

        <v-divider class="my-4"></v-divider>

        <!-- Comments Section -->
        <div class="comments-section">
          <h3 class="mb-3">
            <v-icon start>mdi-comment-multiple</v-icon>
            Comments ({{ comments.length }})
          </h3>

          <!-- Comment Input -->
          <div v-if="isAuthenticated" class="mb-4">
            <v-textarea
              v-model="newComment"
              label="Add a comment..."
              rows="2"
              variant="outlined"
              hide-details
            ></v-textarea>
            <v-btn
              color="primary"
              class="mt-2"
              @click="addComment"
              :disabled="!newComment.trim()"
            >
              <v-icon start>mdi-send</v-icon>
              Post Comment
            </v-btn>
          </div>
          <v-alert v-else type="info" variant="tonal" class="mb-4">
            Please login to leave a comment
          </v-alert>

          <!-- Comments List -->
          <div v-if="comments.length > 0" class="comments-list">
            <div
              v-for="comment in comments"
              :key="comment.id"
              class="comment-item mb-3 pa-3"
              style="background: #f5f5f5; border-radius: 8px; border-left: 3px solid #7B1FA2;"
            >
              <div class="d-flex justify-space-between align-center mb-2">
                <strong style="color: #333;">{{ comment.user_name }}</strong>
                <span class="text-caption" style="color: #666;">{{ formatDate(comment.created_at) }}</span>
              </div>
              <p class="mb-0" style="color: #444;">{{ comment.content }}</p>
            </div>
          </div>
          <div v-else class="text-center py-4" style="color: #666;">
            No comments yet. Be the first to comment!
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const drawings = ref([])
const loading = ref(true)
const sortBy = ref('recent')
const searchQuery = ref('')
const showDetailModal = ref(false)
const selectedDrawing = ref(null)
const comments = ref([])
const newComment = ref('')

// Check if user is authenticated
const isAuthenticated = computed(() => {
  return !!localStorage.getItem('token')
})

const totalVotes = computed(() => {
  return drawings.value.reduce((sum, drawing) => sum + (drawing.votes_count || 0), 0)
})

const uniqueArtists = computed(() => {
  const artistNames = drawings.value.map(d => d.artist_name || d.creator_name).filter(Boolean)
  return new Set(artistNames).size
})

const filteredDrawings = computed(() => {
  let result = [...drawings.value]
  
  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(drawing => 
      drawing.title?.toLowerCase().includes(query) ||
      drawing.artist_name?.toLowerCase().includes(query) ||
      drawing.creator_name?.toLowerCase().includes(query)
    )
  }
  
  // Apply sort
  if (sortBy.value === 'popular') {
    result.sort((a, b) => (b.votes_count || 0) - (a.votes_count || 0))
  }
  
  return result
})

const fetchDrawings = async () => {
  loading.value = true
  try {
    const response = await api.get(`/drawings?sort=${sortBy.value}`)
    drawings.value = response.data.data || response.data
    await nextTick()
    for (const drawing of drawings.value) {
      await checkVoteStatus(drawing)
      renderDrawing(drawing)
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const checkVoteStatus = async (drawing) => {
  try {
    const response = await api.get(`/drawings/${drawing.id}/check-vote`)
    drawing.has_voted = response.data.has_voted
  } catch (error) {
    drawing.has_voted = false
  }
}

const toggleVote = async (drawing) => {
  try {
    if (drawing.has_voted) {
      await api.delete(`/drawings/${drawing.id}/vote`)
      drawing.votes_count--
      drawing.has_voted = false
    } else {
      const response = await api.post(`/drawings/${drawing.id}/vote`)
      drawing.votes_count = response.data.votes_count
      drawing.has_voted = true
    }
  } catch (error) {
    console.error('Error:', error)
  }
}

const viewDrawing = (drawing) => {
  selectedDrawing.value = drawing
  showDetailModal.value = true
  comments.value = [] // Reset comments for now (will load from API later)
  
  nextTick(() => {
    const canvas = document.getElementById('modal-canvas-' + drawing.id)
    if (!canvas) return
    
    const ctx = canvas.getContext('2d')
    
    // Clear canvas with white background
    ctx.fillStyle = '#FFFFFF'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    
    try {
      const data = JSON.parse(drawing.drawing_data)
      
      if (data.paths && data.paths.length > 0) {
        // Calculate bounds to scale properly
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity
        
        data.paths.forEach(path => {
          path.points.forEach(point => {
            minX = Math.min(minX, point.x)
            minY = Math.min(minY, point.y)
            maxX = Math.max(maxX, point.x)
            maxY = Math.max(maxY, point.y)
          })
        })
        
        // Add padding
        const padding = 20
        const drawingWidth = maxX - minX
        const drawingHeight = maxY - minY
        
        // Calculate scale to fit canvas
        const scaleX = (canvas.width - padding * 2) / drawingWidth
        const scaleY = (canvas.height - padding * 2) / drawingHeight
        const scale = Math.min(scaleX, scaleY, 1) // Don't scale up, only down if needed
        
        // Calculate offset to center
        const offsetX = (canvas.width - drawingWidth * scale) / 2 - minX * scale
        const offsetY = (canvas.height - drawingHeight * scale) / 2 - minY * scale
        
        // Draw paths with scaling
        data.paths.forEach(path => {
          ctx.strokeStyle = path.color || '#000000'
          ctx.lineWidth = (path.width || 2) * scale
          ctx.lineCap = 'round'
          ctx.lineJoin = 'round'
          ctx.beginPath()
          
          path.points.forEach((point, i) => {
            const x = point.x * scale + offsetX
            const y = point.y * scale + offsetY
            
            if (i === 0) {
              ctx.moveTo(x, y)
            } else {
              ctx.lineTo(x, y)
            }
          })
          
          ctx.stroke()
        })
      }
    } catch (e) {
      console.error('Render error:', e)
      ctx.fillStyle = '#000000'
      ctx.font = '20px Arial'
      ctx.textAlign = 'center'
      ctx.fillText('Error loading drawing', canvas.width / 2, canvas.height / 2)
    }
  })
}

const addComment = async () => {
  if (!newComment.value.trim() || !selectedDrawing.value) return

  try {
    const userData = JSON.parse(localStorage.getItem('user') || '{}')
    
    // Add comment to local array (in real app, would POST to API)
    const comment = {
      id: Date.now(),
      drawing_id: selectedDrawing.value.id,
      user_name: userData.name || 'Anonymous',
      content: newComment.value,
      created_at: new Date().toISOString()
    }
    
    comments.value.unshift(comment)
    newComment.value = ''
    
    // TODO: Add API call to save comment
    // await api.post(`/drawings/${selectedDrawing.value.id}/comments`, {
    //   content: comment.content
    // })
  } catch (error) {
    console.error('Error adding comment:', error)
  }
}

const shareDrawing = (drawing) => {
  // Future: Share functionality
  console.log('Share drawing:', drawing)
  // Could use Web Share API or copy link to clipboard
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return `${diffDays} days ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`
  return date.toLocaleDateString()
}

const renderDrawing = (drawing) => {
  nextTick(() => {
    const canvas = document.getElementById('canvas-' + drawing.id)
    if (!canvas) return
    const ctx = canvas.getContext('2d')
    ctx.fillStyle = '#FFFFFF'
    ctx.fillRect(0, 0, canvas.width, canvas.height)
    try {
      const data = JSON.parse(drawing.drawing_data)
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
  })
}

watch(sortBy, fetchDrawings)
import { onMounted } from 'vue'
onMounted(fetchDrawings)
</script>

<style scoped src="@/styles/gallery.css"></style>
