<template>
  <v-container fluid class="auth-page">
    <v-row class="justify-center align-center min-vh-100">
      <v-col cols="12" sm="10" md="8" lg="6" xl="4">
        <v-card class="auth-card" elevation="24">
          <v-card-title class="auth-header pa-8 text-center">
            <div class="auth-icon mb-4">
              🏝️
            </div>
            <h1 class="auth-title">
              {{ isLogin ? 'Welcome Back Doodler!' : 'Join DoodleVerse' }}
            </h1>
            <p class="auth-subtitle mt-2">
              {{ isLogin ? 'Sign in to share your art' : 'Create account to start doodling' }}
            </p>
          </v-card-title>

          <v-card-text class="pa-8">
            <v-form ref="form" @submit.prevent="handleSubmit">
              <v-text-field
                v-if="!isLogin"
                v-model="formData.name"
                label="Your Name"
                variant="outlined"
                prepend-inner-icon="mdi-account"
                :rules="!isLogin ? [v => !!v || 'Name is required'] : []"
                class="mb-4"
                color="deep-purple-darken-2"
              ></v-text-field>

              <v-text-field
                v-if="!isLogin"
                v-model="formData.username"
                label="Username"
                variant="outlined"
                prepend-inner-icon="mdi-at"
                :rules="!isLogin ? [
                  v => !!v || 'Username is required',
                  v => /^[a-zA-Z0-9_]+$/.test(v) || 'Username can only contain letters, numbers, and underscores'
                ] : []"
                class="mb-4"
                color="deep-purple-darken-2"
              ></v-text-field>

              <v-text-field
                v-model="formData.email"
                label="Email"
                type="email"
                variant="outlined"
                prepend-inner-icon="mdi-email"
                :rules="[
                  v => !!v || 'Email is required',
                  v => /.+@.+\..+/.test(v) || 'Email must be valid'
                ]"
                class="mb-4"
                color="deep-purple-darken-2"
              ></v-text-field>

              <v-text-field
                v-model="formData.password"
                :label="isLogin ? 'Password' : 'Create Password'"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                prepend-inner-icon="mdi-lock"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                :rules="[
                  v => !!v || 'Password is required',
                  v => v.length >= 6 || 'Password must be at least 6 characters'
                ]"
                class="mb-4"
                color="deep-purple-darken-2"
              ></v-text-field>

              <v-text-field
                v-if="!isLogin"
                v-model="formData.password_confirmation"
                label="Confirm Password"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                prepend-inner-icon="mdi-lock-check"
                :rules="!isLogin ? [
                  v => !!v || 'Please confirm your password',
                  v => v === formData.password || 'Passwords must match'
                ] : []"
                class="mb-4"
                color="deep-purple-darken-2"
              ></v-text-field>

              <v-alert
                v-if="error"
                type="error"
                variant="tonal"
                class="mb-4"
                closable
                @click:close="error = ''"
              >
                {{ error }}
              </v-alert>

              <v-btn
                type="submit"
                block
                size="x-large"
                class="auth-submit-btn mb-4"
                :loading="loading"
              >
                <v-icon start>{{ isLogin ? 'mdi-login' : 'mdi-account-plus' }}</v-icon>
                {{ isLogin ? 'Sign In' : 'Create Account' }}
              </v-btn>

              <v-divider class="my-6"></v-divider>

              <div class="text-center">
                <p class="toggle-text mb-3">
                  {{ isLogin ? "Don't have an account?" : 'Already have an account?' }}
                </p>
                <v-btn
                  variant="text"
                  class="toggle-btn"
                  @click="toggleMode"
                >
                  {{ isLogin ? 'Sign Up' : 'Sign In' }}
                </v-btn>
              </div>
            </v-form>
          </v-card-text>
        </v-card>

        <!-- Floating decorations -->
        <div class="floating-elements">
          <div class="float-item float-1">🎨</div>
          <div class="float-item float-2">✨</div>
          <div class="float-item float-3">🖌️</div>
          <div class="float-item float-4">🌟</div>
        </div>
      </v-col>
    </v-row>

    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="3000"
    >
      {{ snackbarText }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import '@/styles/auth.css'

const router = useRouter()
const form = ref(null)
const isLogin = ref(true)
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

const formData = ref({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const toggleMode = () => {
  isLogin.value = !isLogin.value
  error.value = ''
  formData.value = {
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: ''
  }
}

const handleSubmit = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  loading.value = true
  error.value = ''

  try {
    const endpoint = isLogin.value ? '/login' : '/register'
    const response = await api.post(endpoint, formData.value)

    // Store token (backend returns it as access_token)
    localStorage.setItem('token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    showSnackbar(
      isLogin.value ? 'Welcome back! 🎉' : 'Account created successfully! 🎨',
      'success'
    )

    // Redirect to home after short delay
    setTimeout(() => {
      router.push('/')
    }, 1000)

  } catch (err) {
    console.error('Auth error:', err)
    error.value = err.response?.data?.message || 
                  (isLogin.value ? 'Invalid credentials. Please try again.' : 'Registration failed. Please try again.')
  } finally {
    loading.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}
</script>
