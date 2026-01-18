<template>
  <div class="chat-preview">
    <div class="preview-header" @click="$router.push('/chat')">
      <div class="header-left">
        <v-icon size="20" class="mr-2">mdi-chat</v-icon>
        <h3>Community Chat</h3>
      </div>
      <v-icon size="20">mdi-arrow-right</v-icon>
    </div>

    <div class="preview-messages">
      <div v-if="loading" class="loading">
        <v-progress-circular indeterminate size="20" width="2"></v-progress-circular>
      </div>

      <div v-else-if="recentMessages.length === 0" class="empty">
        <p>No messages yet. Be the first!</p>
      </div>

      <div v-else class="message-list">
        <div v-for="message in recentMessages" :key="message.id" class="preview-message">
          <div class="message-user">{{ message.user.name }}</div>
          <div class="message-content">{{ truncate(message.content, 50) }}</div>
        </div>
      </div>
    </div>

    <div class="preview-footer" @click="handleJoinChat">
      <v-icon size="16" class="mr-1">mdi-send</v-icon>
      <span>Join the conversation</span>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'ChatPreview',
  setup() {
    const router = useRouter()
    const recentMessages = ref([])
    const loading = ref(true)
    const pollInterval = ref(null)

    const truncate = (text, length) => {
      if (text.length <= length) return text
      return text.substring(0, length) + '...'
    }

    const loadRecentMessages = async () => {
      try {
        const response = await api.get('/messages?per_page=5')
        recentMessages.value = response.data.data.reverse().slice(-5)
        loading.value = false
      } catch (error) {
        console.error('Failed to load recent messages:', error)
        loading.value = false
      }
    }

    const handleJoinChat = () => {
      const token = localStorage.getItem('token')
      if (!token) {
        router.push('/auth')
      } else {
        router.push('/chat')
      }
    }

    onMounted(() => {
      loadRecentMessages()
      
      // Poll for updates every 5 seconds
      pollInterval.value = setInterval(() => {
        loadRecentMessages()
      }, 5000)
    })

    onUnmounted(() => {
      if (pollInterval.value) {
        clearInterval(pollInterval.value)
      }
    })

    return {
      recentMessages,
      loading,
      truncate,
      handleJoinChat
    }
  }
}
</script>

<style scoped>
.chat-preview {
  background: rgba(15, 23, 42, 0.95);
  border-radius: 15px;
  border: 2px solid rgba(139, 92, 246, 0.3);
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
  transition: all 0.3s ease;
}

.chat-preview:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(139, 92, 246, 0.4);
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
  color: white;
  cursor: pointer;
  transition: all 0.3s;
}

.preview-header:hover {
  background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
}

.header-left {
  display: flex;
  align-items: center;
}

.preview-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  font-family: 'Comic Sans MS', cursive;
}

.preview-messages {
  padding: 15px;
  min-height: 150px;
  max-height: 200px;
  overflow-y: auto;
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
}

.loading,
.empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 150px;
  color: #94a3b8;
  font-size: 0.9rem;
}

.message-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.preview-message {
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  border-left: 3px solid #8b5cf6;
  transition: all 0.2s;
}

.preview-message:hover {
  background: rgba(255, 255, 255, 0.08);
}

.message-user {
  font-size: 0.8rem;
  font-weight: 700;
  color: #a78bfa;
  margin-bottom: 4px;
}

.message-content {
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.4;
}

.preview-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  background: rgba(30, 27, 75, 0.8);
  border-top: 1px solid rgba(139, 92, 246, 0.3);
  color: #a78bfa;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.preview-footer:hover {
  background: rgba(139, 92, 246, 0.2);
  color: #c4b5fd;
}

/* Scrollbar */
.preview-messages::-webkit-scrollbar {
  width: 6px;
}

.preview-messages::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.preview-messages::-webkit-scrollbar-thumb {
  background: rgba(139, 92, 246, 0.5);
  border-radius: 3px;
}
</style>
