<template>
  <div class="chat-page" :style="pageStyle">
    <ChatBox />
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import ChatBox from '@/components/ChatBox'

const NAV_HEIGHT = 72

export default {
  name: 'Chat',
  components: { ChatBox },
  setup() {
    const pageStyle = ref({})

    const updateHeight = () => {
      const h = window.visualViewport ? window.visualViewport.height : window.innerHeight
      pageStyle.value = { height: `${Math.max(120, h - NAV_HEIGHT)}px` }
    }

    onMounted(() => {
      updateHeight()
      if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', updateHeight)
        window.visualViewport.addEventListener('scroll', updateHeight)
      } else {
        window.addEventListener('resize', updateHeight)
      }
    })

    onUnmounted(() => {
      if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', updateHeight)
        window.visualViewport.removeEventListener('scroll', updateHeight)
      } else {
        window.removeEventListener('resize', updateHeight)
      }
    })

    return { pageStyle }
  }
}
</script>

<style scoped>
.chat-page {
  /* CSS fallback before JS runs; correct 72px nav offset */
  height: calc(100dvh - 72px);
  background: #1a1b1e;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

@media (max-width: 600px) {
  .chat-page { height: calc(100svh - 72px); }
}
</style>
