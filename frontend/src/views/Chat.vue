<template>
  <div class="chat-page">
    <div class="chat-page-header">
      <h1 class="page-title">💬 Community Chat</h1>
      <p class="page-subtitle">Connect with fellow artists and share your creativity!</p>
    </div>

    <div class="chat-content">
      <ChatBox />
    </div>

    <div class="chat-tips">
      <div class="tip-card">
        <v-icon size="32" class="mb-2">mdi-lightbulb-outline</v-icon>
        <h3>Chat Tips</h3>
        <ul>
          <li>Be respectful and kind to others</li>
          <li>Share your artwork and get feedback</li>
          <li>Ask questions and help fellow artists</li>
          <li>Have fun and make friends! 🎨</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import ChatBox from '@/components/ChatBox'

export default {
  name: 'Chat',
  components: {
    ChatBox
  }
}
</script>

<style scoped src="@/styles/chat.css"></style>
