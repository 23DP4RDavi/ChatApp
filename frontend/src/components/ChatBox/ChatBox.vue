<template>
  <div class="chat-container">
    <div class="chat-header">
      <div class="chat-header-content">
        <v-icon size="28" class="mr-2">mdi-chat</v-icon>
        <h2>Community Chat</h2>
      </div>
      <span class="online-count">
        <v-icon size="16" class="mr-1">mdi-account-multiple</v-icon>
        {{ onlineCount }} online
      </span>
    </div>

    <div class="messages-container" ref="messagesContainer">
      <div v-if="loading" class="loading-state">
        <v-progress-circular indeterminate color="purple"></v-progress-circular>
        <p>Loading messages...</p>
      </div>

      <div v-else-if="messages.length === 0" class="empty-state">
        <v-icon size="64" class="mb-4">mdi-chat-outline</v-icon>
        <h3>No messages yet</h3>
        <p>Be the first to start the conversation! 💬</p>
      </div>

      <div v-else class="messages-list">
        <div
          v-for="message in messages"
          :key="message.id"
          :class="['message-item', { 'own-message': message.user_id === currentUser?.id }]"
        >
          <div class="message-avatar">
            <v-icon>mdi-account-circle</v-icon>
          </div>
          <div class="message-content">
            <div class="message-header">
              <span class="message-author">{{ message.user.name }}</span>
              <span class="message-time">{{ formatTime(message.created_at) }}</span>
            </div>
            <div class="message-text">{{ message.content }}</div>
            <button
              v-if="message.user_id === currentUser?.id"
              @click="deleteMessage(message.id)"
              class="delete-btn"
              title="Delete message"
            >
              <v-icon size="16">mdi-delete</v-icon>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="chat-input-container">
      <div v-if="!isAuthenticated" class="auth-prompt">
        <v-icon class="mr-2">mdi-lock</v-icon>
        <span>Please <a href="/auth">login</a> to join the conversation</span>
      </div>
      <form v-else @submit.prevent="sendMessage" class="chat-input-form">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Type your message..."
          maxlength="1000"
          class="message-input"
          :disabled="sending"
        />
        <button type="submit" class="send-btn" :disabled="!newMessage.trim() || sending">
          <v-icon>{{ sending ? 'mdi-loading mdi-spin' : 'mdi-send' }}</v-icon>
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'ChatBox',
  setup() {
    const router = useRouter()
    const messages = ref([])
    const newMessage = ref('')
    const loading = ref(true)
    const sending = ref(false)
    const messagesContainer = ref(null)
    const pollInterval = ref(null)
    const onlineCount = ref(0)

    const currentUser = computed(() => {
      const userData = localStorage.getItem('user')
      return userData ? JSON.parse(userData) : null
    })

    const isAuthenticated = computed(() => {
      return !!localStorage.getItem('token')
    })

    const formatTime = (timestamp) => {
      const date = new Date(timestamp)
      const now = new Date()
      const diff = now - date
      
      // Less than a minute
      if (diff < 60000) {
        return 'Just now'
      }
      // Less than an hour
      if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000)
        return `${minutes}m ago`
      }
      // Less than a day
      if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000)
        return `${hours}h ago`
      }
      // Show time
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }

    const scrollToBottom = () => {
      nextTick(() => {
        if (messagesContainer.value) {
          const container = messagesContainer.value
          container.scrollTop = container.scrollHeight
        }
      })
    }

    const loadMessages = async () => {
      try {
        const response = await api.get('/messages?per_page=100')
        messages.value = response.data.data.reverse()
        loading.value = false
        scrollToBottom()
      } catch (error) {
        console.error('Failed to load messages:', error)
        loading.value = false
      }
    }

    const pollNewMessages = async () => {
      if (messages.value.length === 0) return

      try {
        const lastId = messages.value[messages.value.length - 1].id
        const response = await api.get(`/messages/new?last_id=${lastId}`)
        
        if (response.data.data.length > 0) {
          messages.value.push(...response.data.data)
          scrollToBottom()
        }
      } catch (error) {
        console.error('Failed to poll new messages:', error)
      }
    }

    const sendMessage = async () => {
      if (!newMessage.value.trim() || sending.value) return

      sending.value = true
      try {
        const response = await api.post('/messages', {
          content: newMessage.value.trim(),
          type: 'text'
        })

        messages.value.push(response.data.data)
        newMessage.value = ''
        scrollToBottom()
      } catch (error) {
        console.error('Failed to send message:', error)
        alert('Failed to send message. Please try again.')
      } finally {
        sending.value = false
      }
    }

    const deleteMessage = async (messageId) => {
      if (!confirm('Delete this message?')) return

      try {
        await api.delete(`/messages/${messageId}`)
        messages.value = messages.value.filter(m => m.id !== messageId)
      } catch (error) {
        console.error('Failed to delete message:', error)
        alert('Failed to delete message.')
      }
    }

    const updateOnlineCount = () => {
      // Simulate online count (in real app, would track via WebSocket)
      onlineCount.value = Math.floor(Math.random() * 20) + 5
    }

    onMounted(() => {
      loadMessages()
      updateOnlineCount()
      
      // Poll for new messages every 3 seconds
      pollInterval.value = setInterval(() => {
        pollNewMessages()
      }, 3000)
    })

    onUnmounted(() => {
      if (pollInterval.value) {
        clearInterval(pollInterval.value)
      }
    })

    return {
      messages,
      newMessage,
      loading,
      sending,
      messagesContainer,
      currentUser,
      isAuthenticated,
      onlineCount,
      formatTime,
      sendMessage,
      deleteMessage
    }
  }
}
</script>

<style scoped src="@/styles/chatbox.css"></style>
