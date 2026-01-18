<template>
  <v-app-bar elevation="0" class="app-header">
    <v-app-bar-title class="header-title" @click="$router.push('/')" style="cursor: pointer;">
    <span>DoodleVerse</span>
    </v-app-bar-title>

    <template v-slot:append>
      <div class="desktop-nav d-none d-sm-flex align-center">
        <v-btn variant="text" @click="$router.push('/')" class="nav-btn">
          <v-icon start>mdi-home</v-icon>
          Home
        </v-btn>
        <v-btn variant="text" @click="$router.push('/gallery')" class="nav-btn">
          <v-icon start>mdi-image-multiple</v-icon>
          Gallery
        </v-btn>
        <v-btn variant="text" @click="handleFriendsClick" class="nav-btn">
          <v-icon start>mdi-account-multiple</v-icon>
          Friends
        </v-btn>
        <v-btn variant="text" @click="handleMessagesClick" class="nav-btn">
          <v-icon start>mdi-message</v-icon>
          Messages
        </v-btn>
        <v-btn variant="text" @click="handleDrawClick" class="nav-btn">
          <v-icon start>mdi-draw</v-icon>
          Draw
        </v-btn>
        
        <v-chip v-if="user" class="user-chip mx-3" color="deep-purple-darken-2">
          <v-icon start>mdi-account</v-icon>
          {{ user.name }}
        </v-chip>
        
        <v-btn 
          v-if="!user"
          variant="elevated" 
          @click="$router.push('/auth')" 
          class="login-btn"
          prepend-icon="mdi-login"
        >
          Login
        </v-btn>
        
        <v-menu v-else>
          <template v-slot:activator="{ props }">
            <v-btn icon="mdi-account-circle" v-bind="props" class="nav-btn"></v-btn>
          </template>
          <v-list>
            <v-list-item @click="logout">
              <template v-slot:prepend>
                <v-icon>mdi-logout</v-icon>
              </template>
              <v-list-item-title>Logout</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </div>

      <v-app-bar-nav-icon class="d-flex d-sm-none" @click="drawer = !drawer"></v-app-bar-nav-icon>
    </template>
  </v-app-bar>

  <v-navigation-drawer v-model="drawer" temporary location="right" class="mobile-drawer">
    <v-list>
      <v-list-item v-if="user" class="user-info-item">
        <v-list-item-title class="text-h6">{{ user.name }}</v-list-item-title>
        <v-list-item-subtitle>{{ user.email }}</v-list-item-subtitle>
      </v-list-item>
      <v-divider v-if="user" class="my-2"></v-divider>
      
      <v-list-item @click="$router.push('/')" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-home</v-icon>
        </template>
        <v-list-item-title>Home</v-list-item-title>
      </v-list-item>
      
      <v-list-item @click="$router.push('/gallery')" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-image-multiple</v-icon>
        </template>
        <v-list-item-title>Gallery</v-list-item-title>
      </v-list-item>
      
      <v-list-item @click="handleFriendsClick" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-account-multiple</v-icon>
        </template>
        <v-list-item-title>Friends</v-list-item-title>
      </v-list-item>
      
      <v-list-item @click="handleMessagesClick" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-message</v-icon>
        </template>
        <v-list-item-title>Messages</v-list-item-title>
      </v-list-item>
      
      <v-list-item @click="handleDrawClick" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-draw</v-icon>
        </template>
        <v-list-item-title>Draw</v-list-item-title>
      </v-list-item>
      
      <v-divider class="my-2"></v-divider>
      
      <v-list-item v-if="!user" @click="$router.push('/auth')" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-login</v-icon>
        </template>
        <v-list-item-title>Login / Sign Up</v-list-item-title>
      </v-list-item>
      
      <v-list-item v-else @click="logout" class="mobile-nav-item">
        <template v-slot:prepend>
          <v-icon>mdi-logout</v-icon>
        </template>
        <v-list-item-title>Logout</v-list-item-title>
      </v-list-item>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const drawer = ref(false)
const user = ref(null)

onMounted(() => {
  loadUser()
})

const loadUser = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    try {
      user.value = JSON.parse(userData)
    } catch (e) {
      console.error('Error parsing user data:', e)
    }
  }
}

const handleDrawClick = () => {
  const token = localStorage.getItem('token')
  if (!token) {
    router.push('/auth')
  } else {
    router.push('/draw')
  }
  drawer.value = false
}

const handleFriendsClick = () => {
  const token = localStorage.getItem('token')
  if (!token) {
    router.push('/auth')
  } else {
    router.push('/friends')
  }
  drawer.value = false
}

const handleMessagesClick = () => {
  const token = localStorage.getItem('token')
  if (!token) {
    router.push('/auth')
  } else {
    router.push('/messages')
  }
  drawer.value = false
}

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  user.value = null
  drawer.value = false
  router.push('/')
}
</script>

<style scoped src="./AppHeader.css"></style>
