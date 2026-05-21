<template>
  <div class="invite-page">
    <v-card class="invite-card" max-width="440">
      <template v-if="loading">
        <div class="invite-loading">
          <v-progress-circular indeterminate color="primary" size="40" />
          <p class="mt-3">{{ t('invitePage.loading') }}</p>
        </div>
      </template>

      <template v-else-if="error">
        <div class="invite-error">
          <v-icon size="48" color="error" class="mb-3">mdi-link-off</v-icon>
          <h3>{{ t('invitePage.invalidInvite') }}</h3>
          <p>{{ error }}</p>
          <v-btn variant="tonal" color="primary" class="mt-4" @click="$router.push('/messages')">
            {{ t('invitePage.goToMessages') }}
          </v-btn>
        </div>
      </template>

      <template v-else-if="joined">
        <div class="invite-success">
          <v-icon size="48" color="success" class="mb-3">mdi-check-circle-outline</v-icon>
          <h3>{{ t('invitePage.joined') }}</h3>
          <p>{{ t('invitePage.youHaveJoined') }} <strong>{{ group?.name }}</strong></p>
          <v-btn color="primary" class="mt-4" @click="$router.push('/messages/' + conversationId)">
            {{ t('invitePage.openServer') }}
          </v-btn>
        </div>
      </template>

      <template v-else-if="group">
        <div class="invite-content">
          <div class="invite-server-icon">
            <v-avatar size="64" color="primary">
              <span style="font-size:1.4rem;font-weight:700">{{ group.name?.[0]?.toUpperCase() }}</span>
            </v-avatar>
          </div>
          <p class="invite-label">{{ t('invitePage.youveBeenInvited') }}</p>
          <h2 class="invite-group-name">{{ group.name }}</h2>
          <p class="invite-members">
            <v-icon size="14" class="mr-1">mdi-account-group-outline</v-icon>
            {{ group.member_count }} {{ group.member_count === 1 ? t('invitePage.member') : t('invitePage.members') }}
          </p>
          <div class="invite-actions mt-5">
            <v-btn variant="text" @click="$router.push('/')">{{ t('invitePage.decline') }}</v-btn>
            <v-btn color="primary" :loading="joining" @click="joinGroup">
              {{ t('invitePage.acceptInvite') }}
            </v-btn>
          </div>
        </div>
      </template>
    </v-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const joining = ref(false)
const joined = ref(false)
const error = ref(null)
const group = ref(null)
const conversationId = ref(null)

onMounted(async () => {
  try {
    const res = await api.get(`/invites/${route.params.token}`)
    group.value = res.data.group
  } catch (e) {
    error.value = e.response?.data?.message || t('invitePage.invalidOrExpired')
  } finally {
    loading.value = false
  }
})

const joinGroup = async () => {
  joining.value = true
  try {
    const res = await api.post(`/invites/${route.params.token}/join`)
    conversationId.value = res.data.conversation_id
    joined.value = true
  } catch (e) {
    error.value = e.response?.data?.message || t('invitePage.joinFailed')
  } finally {
    joining.value = false
  }
}
</script>

<style scoped>
.invite-page {
  min-height: calc(100vh - 72px);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--c-bg);
  padding: 24px;
}

.invite-card {
  background: var(--c-sidebar) !important;
  border: 1px solid var(--c-border) !important;
  border-radius: var(--r-xl) !important;
  width: 100%;
}

.invite-loading,
.invite-error,
.invite-success {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 32px;
  text-align: center;
}

.invite-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 32px;
  text-align: center;
}

.invite-server-icon { margin-bottom: 16px; }

.invite-label {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--c-muted);
  margin: 0 0 6px;
}

.invite-group-name {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--c-text);
  margin: 0 0 8px;
}

.invite-members {
  font-size: 0.85rem;
  color: var(--c-text-dim);
  display: flex;
  align-items: center;
}

.invite-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}
</style>
