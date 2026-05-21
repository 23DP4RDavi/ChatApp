<template>
  <div class="auth-page">
    <div class="auth-card-wrap">
      <div class="auth-brand">
        <v-icon size="28" color="primary" class="mr-2">mdi-palette</v-icon>
        <span>DoodleVerse</span>
      </div>

      <v-card class="auth-card" elevation="0">
        <div class="auth-card-header">
          <h1 class="auth-title">{{ isLogin ? t('auth.welcomeBack') : t('auth.join') }}</h1>
          <p class="auth-sub">{{ isLogin ? t('auth.signInShare') : t('auth.createAccountStart') }}</p>
        </div>

        <div class="auth-card-body">
          <v-form ref="form" @submit.prevent="handleSubmit">
            <v-text-field v-if="!isLogin" v-model="formData.name" :label="t('auth.yourName')"
              variant="outlined" prepend-inner-icon="mdi-account" density="comfortable"
              :rules="!isLogin ? [v => !!v || t('auth.yourName')] : []" class="mb-3" />

            <v-text-field v-if="!isLogin" v-model="formData.username" :label="t('auth.username')"
              variant="outlined" prepend-inner-icon="mdi-at" density="comfortable"
              :rules="!isLogin ? [v => !!v || t('auth.username')] : []" class="mb-3" />

            <v-text-field v-model="formData.email"
              :label="isLogin ? t('auth.emailOrUsername') : t('auth.email')"
              :type="isLogin ? 'text' : 'email'"
              variant="outlined" prepend-inner-icon="mdi-email" density="comfortable"
              :rules="[v => !!v || (isLogin ? t('auth.emailOrUsername') : t('auth.email'))]"
              class="mb-3" />

            <v-text-field v-model="formData.password"
              :label="isLogin ? t('auth.password') : t('auth.createPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined" prepend-inner-icon="mdi-lock" density="comfortable"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              :rules="[v => !!v || t('auth.password'), v => v.length >= 6 || t('auth.password')]"
              class="mb-3" />

            <v-text-field v-if="!isLogin" v-model="formData.password_confirmation"
              :label="t('auth.confirmPassword')"
              :type="showPassword ? 'text' : 'password'"
              variant="outlined" prepend-inner-icon="mdi-lock-check" density="comfortable"
              :rules="!isLogin ? [v => !!v || t('auth.confirmPassword'), v => v === formData.password || t('auth.confirmPassword')] : []"
              class="mb-3" />

            <v-alert v-if="error" type="error" variant="tonal" density="compact"
              closable class="mb-3" @click:close="error = ''">
              {{ error }}
            </v-alert>

            <v-btn type="submit" block color="primary" size="large" rounded="lg"
              :loading="loading" class="mb-4">
              <v-icon start>{{ isLogin ? 'mdi-login' : 'mdi-account-plus' }}</v-icon>
              {{ isLogin ? t('auth.signIn') : t('auth.signUp') }}
            </v-btn>

            <v-btn block variant="outlined" size="large" rounded="lg" class="mb-4"
              prepend-icon="mdi-google" :loading="googleLoading" @click="startGoogleLogin">
              {{ t('auth.continueWithGoogle') }}
            </v-btn>

            <v-divider class="mb-4" />

            <div class="auth-toggle">
              <span class="toggle-label">{{ isLogin ? t('auth.noAccount') : t('auth.hasAccount') }}</span>
              <v-btn variant="text" color="primary" density="compact" @click="toggleMode">
                {{ isLogin ? t('auth.signUp') : t('auth.signIn') }}
              </v-btn>
            </div>
          </v-form>
        </div>
      </v-card>
    </div>

    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000" rounded="lg">
      {{ snackbarText }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useI18n } from '@/composables/useI18n'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { getErrorMessage } = useErrorHandler()
const form = ref(null)
const isLogin = ref(true)
const showPassword = ref(false)
const loading = ref(false)
const googleLoading = ref(false)
const error = ref('')

const backendBaseUrl = (import.meta.env.VITE_BACKEND_URL
  || (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace(/\/api\/?$/, ''))
  .replace(/\/$/, '')

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
    const loginData = isLogin.value
      ? { login: formData.value.email || formData.value.username, password: formData.value.password }
      : formData.value

    const response = await api.post(endpoint, loginData)

    localStorage.setItem('token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    showSnackbar(isLogin.value ? t('auth.welcomeBackToast') : t('auth.accountCreatedToast'), 'success')

    setTimeout(() => {
      router.push('/')
    }, 1000)
  } catch (err) {
    console.error('Auth error:', err)
    error.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
}

const startGoogleLogin = () => {
  googleLoading.value = true
  window.location.href = `${backendBaseUrl}/api/auth/google/redirect`
}

const handleGoogleCallback = async () => {
  const token = typeof route.query.token === 'string' ? route.query.token : ''
  const oauthError = typeof route.query.oauth_error === 'string' ? route.query.oauth_error : ''

  if (oauthError) {
    error.value = t('auth.googleLoginFailed')
    await router.replace({ path: '/auth' })
    return
  }

  if (!token) {
    return
  }

  loading.value = true
  googleLoading.value = true

  try {
    localStorage.setItem('token', token)
    const response = await api.get('/user')
    localStorage.setItem('user', JSON.stringify(response.data.user))
    showSnackbar(t('auth.welcomeBackToast'), 'success')
    await router.replace('/')
  } catch (err) {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    error.value = getErrorMessage(err)
    await router.replace({ path: '/auth' })
  } finally {
    loading.value = false
    googleLoading.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  handleGoogleCallback()
})
</script>

<style scoped>
/* Auth page */
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--c-bg);
  padding: 24px;
}

.auth-card-wrap {
  width: 100%;
  max-width: 440px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.auth-brand {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--c-text);
}

.auth-card {
  background: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
  border-radius: var(--r-xl) !important;
  overflow: hidden;
}

.auth-card-header {
  padding: 28px 28px 0;
  text-align: center;
}

.auth-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--c-text);
  margin-bottom: 6px;
}

.auth-sub {
  font-size: 0.875rem;
  color: var(--c-muted);
}

.auth-card-body {
  padding: 24px 28px 28px;
}

.auth-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 0.85rem;
}

.toggle-label {
  color: var(--c-muted);
}
</style>
