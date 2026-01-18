<template>
  <v-app>
    <!-- Reusable Header Component - Use everywhere! -->
    <AppHeader />

    <v-main>
      <router-view />
    </v-main>

    <!-- Universal Footer -->
    <AppFooter />
  </v-app>
</template>

<script setup>
import AppHeader from '@/components/AppHeader'
import AppFooter from '@/components/AppFooter'
</script>

