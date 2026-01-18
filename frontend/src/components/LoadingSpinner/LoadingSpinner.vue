<template>
  <div class="loading-container text-center">
    <v-progress-circular
      :indeterminate="!value"
      :model-value="value"
      :color="color"
      :size="size"
    ></v-progress-circular>
    <p class="loading-message mt-4">{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: 'Loading...'
  },
  size: {
    type: [String, Number],
    default: 64
  },
  color: {
    type: String,
    default: '#9370DB'
  },
  value: {
    type: Number,
    default: null
  }
})
</script>

<style scoped src="./LoadingSpinner.css"></style>
