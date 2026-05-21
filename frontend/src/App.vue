<template>
  <v-app>
    <AppHeader />
    <v-main class="app-main">
      <router-view v-slot="{ Component, route }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="route.name === 'Messages' ? 'Messages' : route.fullPath" />
        </transition>
      </router-view>
    </v-main>
    <AppFooter v-if="!['Draw', 'Messages', 'Chat'].includes(route.name)" />
  </v-app>
</template>

<script setup>
import { useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader'
import AppFooter from '@/components/AppFooter'

const route = useRoute()
</script>

<style scoped>
.app-main {
  background: var(--c-bg);
  min-height: 100vh;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 140ms ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
