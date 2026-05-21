<template>
  <div class="a11y-help" :class="{ open: isOpen }">
    <v-btn class="a11y-toggle" color="primary" @click="isOpen = !isOpen" prepend-icon="mdi-accessibility">
      {{ t('accessibility.openButton') }}
    </v-btn>

    <v-card v-if="isOpen" class="a11y-card" elevation="8">
      <v-card-title>{{ t('accessibility.title') }}</v-card-title>
      <v-card-subtitle>{{ t('accessibility.subtitle') }}</v-card-subtitle>

      <v-card-text>
        <v-btn block class="mb-2" variant="outlined" @click="toggleLargeText">
          {{ t('accessibility.increaseText') }}
        </v-btn>
        <v-btn block class="mb-2" variant="outlined" @click="toggleHighContrast">
          {{ t('accessibility.highContrast') }}
        </v-btn>
        <v-btn block class="mb-2" variant="outlined" @click="toggleReducedMotion">
          {{ t('accessibility.reduceMotion') }}
        </v-btn>
        <v-btn block color="error" variant="text" @click="resetAll">
          {{ t('accessibility.reset') }}
        </v-btn>

        <v-divider class="my-3"></v-divider>

        <h4 class="text-subtitle-1 mb-2">{{ t('accessibility.tipsTitle') }}</h4>
        <ul class="tips-list">
          <li>{{ t('accessibility.tip1') }}</li>
          <li>{{ t('accessibility.tip2') }}</li>
          <li>{{ t('accessibility.tip3') }}</li>
          <li>{{ t('accessibility.tip4') }}</li>
        </ul>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
const isOpen = ref(false)

const root = document.documentElement

const toggleClass = (className) => {
  root.classList.toggle(className)
}

const toggleLargeText = () => toggleClass('a11y-large-text')
const toggleHighContrast = () => toggleClass('a11y-high-contrast')
const toggleReducedMotion = () => toggleClass('a11y-reduced-motion')

const resetAll = () => {
  root.classList.remove('a11y-large-text')
  root.classList.remove('a11y-high-contrast')
  root.classList.remove('a11y-reduced-motion')
}
</script>

<style scoped>
.a11y-help {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 1000;
}

.a11y-toggle {
  border-radius: 999px;
}

.a11y-card {
  margin-top: 8px;
  width: min(360px, calc(100vw - 32px));
}

.tips-list {
  padding-left: 16px;
}
</style>

<style>
html.a11y-large-text {
  font-size: 18px;
}

html.a11y-high-contrast body {
  filter: contrast(1.2) saturate(1.05);
}

html.a11y-reduced-motion *,
html.a11y-reduced-motion *::before,
html.a11y-reduced-motion *::after {
  animation-duration: 0.01ms !important;
  animation-iteration-count: 1 !important;
  transition-duration: 0.01ms !important;
  scroll-behavior: auto !important;
}
</style>
