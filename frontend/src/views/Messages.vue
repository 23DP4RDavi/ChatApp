<template>
  <v-container fluid class="messages-page pa-0">
    <v-row no-gutters class="fill-height">
      <!-- Conversations List -->
      <v-col cols="12" md="4" class="conversations-sidebar">
        <v-card flat class="fill-height">
          <v-card-title class="pa-4">
            <h2>💬 Messages</h2>
          </v-card-title>

          <v-divider></v-divider>

          <v-list v-if="conversations.length > 0">
            <v-list-item
              v-for="conversation in conversations"
              :key="conversation.id"
              :active="selectedConversation?.id === conversation.id"
              @click="selectConversation(conversation)"
              class="conversation-item"
            >
              <template v-slot:prepend>
                <v-avatar color="deep-purple-lighten-4">
                  <span class="text-h6">{{ conversation.other_user?.name?.[0] }}</span>
                </v-avatar>
              </template>

              <v-list-item-title>
                {{ conversation.other_user?.name }}
              </v-list-item-title>
              <v-list-item-subtitle>
                {{ conversation.latest_message?.content || 'Start chatting...' }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>

          <v-empty-state
            v-else
            title="No conversations"
            text="Go to Friends to start messaging!"
            icon="mdi-message-outline"
            class="mt-8"
          ></v-empty-state>
        </v-card>
      </v-col>

      <!-- Chat Window -->
      <v-col cols="12" md="8" class="chat-window">
        <v-card v-if="selectedConversation" flat class="fill-height d-flex flex-column">
          <!-- Chat Header -->
          <v-card-title class="pa-4 border-b">
            <v-avatar size="40" color="deep-purple-lighten-4" class="mr-3">
              <span>{{ selectedConversation.other_user?.name?.[0] }}</span>
            </v-avatar>
            <div>
              <div class="text-h6">{{ selectedConversation.other_user?.name }}</div>
              <div class="text-caption">@{{ selectedConversation.other_user?.username }}</div>
            </div>
            
            <v-spacer></v-spacer>
            
            <v-btn
              icon
              variant="text"
              @click="showDrawingCanvas = !showDrawingCanvas"
            >
              <v-icon>{{ showDrawingCanvas ? 'mdi-message' : 'mdi-brush' }}</v-icon>
            </v-btn>
          </v-card-title>

          <v-divider></v-divider>

          <!-- Messages Area -->
          <v-card-text class="flex-grow-1 messages-container" ref="messagesContainer">
            <div v-if="!showDrawingCanvas">
              <div
                v-for="message in messages"
                :key="message.id"
                :class="['message-bubble', message.user_id === currentUserId ? 'own-message' : 'other-message']"
              >
                <div class="message-header">
                  <strong>{{ message.user.name }}</strong>
                  <span class="message-time">{{ formatTime(message.created_at) }}</span>
                </div>
                
                <div v-if="message.drawing_data" class="message-drawing">
                  <canvas
                    :ref="el => renderDrawing(el, message.drawing_data)"
                    width="300"
                    height="200"
                  ></canvas>
                </div>
                
                <div v-if="message.content" class="message-content">
                  {{ message.content }}
                </div>
              </div>
            </div>

            <!-- Drawing Canvas -->
            <div v-else class="drawing-area">
              <canvas
                ref="drawingCanvas"
                width="600"
                height="400"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                class="drawing-canvas"
              ></canvas>
              
              <div class="drawing-controls mt-4">
                <v-btn-toggle v-model="brushColor" mandatory>
                  <v-btn value="#000000"><v-icon>mdi-circle</v-icon></v-btn>
                  <v-btn value="#FF0000"><v-icon color="red">mdi-circle</v-icon></v-btn>
                  <v-btn value="#00FF00"><v-icon color="green">mdi-circle</v-icon></v-btn>
                  <v-btn value="#0000FF"><v-icon color="blue">mdi-circle</v-icon></v-btn>
                </v-btn-toggle>
                
                <v-slider
                  v-model="brushSize"
                  :min="1"
                  :max="20"
                  label="Size"
                  class="mx-4"
                  style="max-width: 200px"
                ></v-slider>
                
                <v-btn @click="clearCanvas" color="warning">Clear</v-btn>
                <v-btn @click="sendDrawing" color="primary">Send Drawing</v-btn>
              </div>
            </div>
          </v-card-text>

          <v-divider></v-divider>

          <!-- Message Input -->
          <v-card-actions v-if="!showDrawingCanvas" class="pa-4">
            <v-text-field
              v-model="newMessage"
              placeholder="Type a message..."
              variant="outlined"
              hide-details
              @keyup.enter="sendMessage"
            >
              <template v-slot:append-inner>
                <v-btn
                  icon
                  @click="sendMessage"
                  :disabled="!newMessage.trim()"
                >
                  <v-icon>mdi-send</v-icon>
                </v-btn>
              </template>
            </v-text-field>
          </v-card-actions>
        </v-card>

        <v-empty-state
          v-else
          title="Select a conversation"
          text="Choose a conversation from the list to start messaging"
          icon="mdi-chat-outline"
        ></v-empty-state>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const conversations = ref([])
const selectedConversation = ref(null)
const messages = ref([])
const newMessage = ref('')
const messagesContainer = ref(null)
const showDrawingCanvas = ref(false)
const drawingCanvas = ref(null)
const currentUserId = ref(null)

// Drawing state
const isDrawing = ref(false)
const brushColor = ref('#000000')
const brushSize = ref(5)
const drawingData = ref([])

let pollingInterval = null

// Get current user
const getCurrentUser = async () => {
  try {
    const response = await api.get('/user')
    currentUserId.value = response.data.user.id
  } catch (error) {
    console.error('Failed to get current user:', error)
  }
}

// Load conversations
const loadConversations = async () => {
  try {
    const response = await api.get('/conversations')
    conversations.value = response.data.conversations
    
    // If there's a conversation ID in the route, select it
    if (route.params.id) {
      const conv = conversations.value.find(c => c.id == route.params.id)
      if (conv) selectConversation(conv)
    }
  } catch (error) {
    console.error('Failed to load conversations:', error)
  }
}

// Select conversation
const selectConversation = async (conversation) => {
  selectedConversation.value = conversation
  await loadMessages(conversation.id)
  startPolling()
}

// Load messages
const loadMessages = async (conversationId) => {
  try {
    const response = await api.get(`/conversations/${conversationId}/messages`)
    messages.value = response.data.messages
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to load messages:', error)
  }
}

// Send text message
const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedConversation.value) return

  try {
    await api.post(`/conversations/${selectedConversation.value.id}/messages`, {
      content: newMessage.value
    })
    newMessage.value = ''
    await loadMessages(selectedConversation.value.id)
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

// Drawing functions
const startDrawing = (e) => {
  if (!drawingCanvas.value) return
  isDrawing.value = true
  const canvas = drawingCanvas.value
  const rect = canvas.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  
  drawingData.value.push({
    type: 'start',
    x, y,
    color: brushColor.value,
    size: brushSize.value
  })
}

const draw = (e) => {
  if (!isDrawing.value || !drawingCanvas.value) return
  
  const canvas = drawingCanvas.value
  const ctx = canvas.getContext('2d')
  const rect = canvas.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  
  // Draw line
  ctx.strokeStyle = brushColor.value
  ctx.lineWidth = brushSize.value
  ctx.lineCap = 'round'
  
  const lastPoint = drawingData.value[drawingData.value.length - 1]
  ctx.beginPath()
  ctx.moveTo(lastPoint.x, lastPoint.y)
  ctx.lineTo(x, y)
  ctx.stroke()
  
  drawingData.value.push({
    type: 'line',
    x, y,
    color: brushColor.value,
    size: brushSize.value
  })
}

const stopDrawing = () => {
  isDrawing.value = false
}

const clearCanvas = () => {
  if (!drawingCanvas.value) return
  const canvas = drawingCanvas.value
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  drawingData.value = []
}

const sendDrawing = async () => {
  if (drawingData.value.length === 0 || !selectedConversation.value) return

  try {
    await api.post(`/conversations/${selectedConversation.value.id}/messages`, {
      content: '🎨 Drawing',
      drawing_data: drawingData.value
    })
    clearCanvas()
    showDrawingCanvas.value = false
    await loadMessages(selectedConversation.value.id)
  } catch (error) {
    console.error('Failed to send drawing:', error)
  }
}

const renderDrawing = (canvas, data) => {
  if (!canvas || !data) return
  
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)
  
  data.forEach((point, i) => {
    if (point.type === 'start' || i === 0) {
      ctx.beginPath()
      ctx.moveTo(point.x, point.y)
    } else if (point.type === 'line') {
      ctx.strokeStyle = point.color
      ctx.lineWidth = point.size
      ctx.lineCap = 'round'
      const prevPoint = data[i - 1]
      ctx.moveTo(prevPoint.x, prevPoint.y)
      ctx.lineTo(point.x, point.y)
      ctx.stroke()
    }
  })
}

// Poll for new messages
const pollMessages = async () => {
  if (!selectedConversation.value || messages.value.length === 0) return

  const lastId = messages.value[messages.value.length - 1].id

  try {
    const response = await api.get(`/conversations/${selectedConversation.value.id}/messages/new`, {
      params: { last_id: lastId }
    })
    
    if (response.data.data.length > 0) {
      messages.value.push(...response.data.data)
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Failed to poll messages:', error)
  }
}

const startPolling = () => {
  stopPolling()
  pollingInterval = setInterval(pollMessages, 2000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  getCurrentUser()
  loadConversations()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.messages-page {
  height: calc(100vh - 64px);
}

.conversations-sidebar {
  border-right: 1px solid rgba(0, 0, 0, 0.12);
  max-height: calc(100vh - 64px);
  overflow-y: auto;
}

.chat-window {
  height: calc(100vh - 64px);
}

.messages-container {
  overflow-y: auto;
  max-height: calc(100vh - 280px);
}

.message-bubble {
  margin: 12px 0;
  padding: 12px;
  border-radius: 12px;
  max-width: 70%;
}

.own-message {
  background-color: #E1BEE7;
  margin-left: auto;
  text-align: right;
}

.other-message {
  background-color: #F5F5F5;
}

.message-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
  font-size: 0.875rem;
}

.message-time {
  color: #666;
  font-size: 0.75rem;
}

.message-content {
  word-wrap: break-word;
}

.message-drawing {
  margin: 8px 0;
}

.message-drawing canvas {
  border: 1px solid #ddd;
  border-radius: 8px;
}

.drawing-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
}

.drawing-canvas {
  border: 2px solid #7B1FA2;
  border-radius: 8px;
  cursor: crosshair;
  background: white;
}

.drawing-controls {
  display: flex;
  align-items: center;
  gap: 16px;
}

.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.conversation-item {
  cursor: pointer;
}
</style>
