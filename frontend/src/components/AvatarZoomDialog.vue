<template>
  <v-dialog v-model="isOpen" max-width="720" class="avatar-zoom-dialog" @keydown.esc="closeDialog">
    <v-card class="avatar-zoom-card">
      <div class="avatar-zoom-head">
        <div class="avatar-zoom-label">{{ label || 'Profile picture' }}</div>
        <v-btn icon variant="text" size="small" @click="closeDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>
      <div class="avatar-zoom-body" @click="closeDialog">
        <img :src="src" :alt="label || 'Profile picture'" class="avatar-zoom-image" @click.stop />
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { avatarZoomEventName } from '@/utils/avatarZoom'

const isOpen = ref(false)
const src = ref('')
const label = ref('')

const closeDialog = () => {
  isOpen.value = false
}

const handleOpen = (event) => {
  src.value = event.detail?.src || ''
  label.value = event.detail?.label || ''
  isOpen.value = !!src.value
}

onMounted(() => {
  window.addEventListener(avatarZoomEventName, handleOpen)
})

onUnmounted(() => {
  window.removeEventListener(avatarZoomEventName, handleOpen)
})
</script>

<style scoped>
.avatar-zoom-card {
  background: #14151d;
  border: 1px solid rgba(255,255,255,0.08);
  overflow: hidden;
}

.avatar-zoom-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.avatar-zoom-label {
  font-size: 0.95rem;
  font-weight: 700;
  color: rgba(255,255,255,0.92);
}

.avatar-zoom-body {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  background: radial-gradient(circle at top, rgba(124,58,237,0.16), transparent 45%), #101118;
}

.avatar-zoom-image {
  display: block;
  max-width: min(100%, 640px);
  max-height: min(80vh, 640px);
  width: auto;
  height: auto;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(0,0,0,0.45);
}
</style>
