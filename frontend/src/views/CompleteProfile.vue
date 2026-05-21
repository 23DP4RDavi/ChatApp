<template>
  <div class="complete-page">
    <div class="complete-wrap">
      <v-card class="complete-card" elevation="0">
        <div class="complete-header">
          <h1 class="complete-title">{{ t('auth.completeProfileTitle') }}</h1>
          <p class="complete-sub">{{ t('auth.completeProfileSubtitle') }}</p>
        </div>

        <div class="complete-body">
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4" closable @click:close="error = ''">
            {{ error }}
          </v-alert>

          <v-text-field
            v-model="username"
            :label="t('auth.username')"
            variant="outlined"
            prepend-inner-icon="mdi-at"
            density="comfortable"
            :hint="t('auth.usernameRequirements')"
            persistent-hint
          />

          <div class="complete-actions">
            <v-btn color="primary" size="large" rounded="lg" :loading="saving" @click="saveUsername">
              {{ t('auth.saveUsername') }}
            </v-btn>
            <v-btn variant="text" color="primary" :loading="loading" @click="reloadUser">
              {{ t('common.refresh') }}
            </v-btn>
          </div>
        </div>
      </v-card>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'
import { useErrorHandler } from '@/composables/useErrorHandler'

const router = useRouter()
const { t } = useI18n()
const { getErrorMessage } = useErrorHandler()

const username = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const user = ref(null)

const loadUserFromStorage = () => {
  try {
    user.value = JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    user.value = null
  }

  if (!user.value) return

  const current = String(user.value.username || '').trim()
  username.value = current

  if (current) {
    router.replace('/')
  }
}

const reloadUser = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/user')
    user.value = response.data.user
    localStorage.setItem('user', JSON.stringify(user.value))

    const current = String(user.value.username || '').trim()
    username.value = current

    if (current) {
      await router.replace('/')
    }
  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
}

const saveUsername = async () => {
  if (!user.value) {
    await reloadUser()
    if (!user.value) return
  }

  const value = String(username.value || '').trim()

  if (!/^[a-zA-Z0-9_]{3,50}$/.test(value)) {
    error.value = t('auth.usernameRequirements')
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      name: user.value.name,
      email: user.value.email,
      username: value
    }

    const response = await api.put('/user', payload)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    await router.replace('/')
  } catch (err) {
    error.value = getErrorMessage(err)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loadUserFromStorage()

  if (!user.value) {
    await reloadUser()
  }
})
</script>

<style scoped>
.complete-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: var(--c-bg);
}

.complete-wrap {
  width: 100%;
  max-width: 520px;
}

.complete-card {
  background: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
  border-radius: var(--r-xl) !important;
}

.complete-header {
  padding: 26px 26px 8px;
}

.complete-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--c-text);
}

.complete-sub {
  margin-top: 8px;
  color: var(--c-muted);
}

.complete-body {
  padding: 12px 26px 26px;
}

.complete-actions {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}

@media (max-width: 600px) {
  .complete-actions {
    flex-direction: column;
  }
}
</style>
