<template>
  <div class="empty-state text-center">
    <v-icon :size="iconSize" color="#9370DB">{{ icon }}</v-icon>
    <h3 class="empty-title">{{ title }}</h3>
    <p class="empty-message">{{ message }}</p>
    <v-btn
      v-if="actionText"
      class="empty-action-btn"
      variant="outlined"
      @click="$emit('action')"
    >
      <v-icon v-if="actionIcon" start>{{ actionIcon }}</v-icon>
      {{ actionText }}
    </v-btn>
  </div>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    default: 'mdi-tray'
  },
  iconSize: {
    type: [String, Number],
    default: 120
  },
  title: {
    type: String,
    default: 'Nothing here yet'
  },
  message: {
    type: String,
    default: 'Get started by creating something!'
  },
  actionText: {
    type: String,
    default: null
  },
  actionIcon: {
    type: String,
    default: null
  }
})

defineEmits(['action'])
</script>

<style scoped src="./EmptyState.css"></style>
