<template>
  <div class="friends-page">
    <!-- Header + Search -->
    <div class="friends-header">
      <div>
        <h1 class="page-title">{{ t('friendsPage.title') }}</h1>
      </div>
    </div>

    <div class="friends-body">
      <!-- Search panel -->
      <div class="search-panel">
        <v-text-field
          v-model="searchQuery"
          :label="t('friendsPage.searchLabel')"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          clearable
          density="compact"
          hide-details
          @keyup.enter="searchUsers"
        />
        <v-btn color="primary" :disabled="!searchQuery" @click="searchUsers" rounded="lg" size="small">
          {{ t('friendsPage.search') || 'Search' }}
        </v-btn>
      </div>

      <!-- Search results -->
      <div v-if="searchResults.length > 0" class="result-list">
        <div v-for="user in searchResults" :key="user.id" class="user-row">
          <v-avatar size="36" color="primary" class="mr-3">
              <v-img v-if="user.avatar_thumbnail" :src="user.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(user.avatar_thumbnail, getUserDisplayName(user))" />
              <span v-else class="text-caption font-weight-bold">{{ getUserInitial(user) }}</span>
          </v-avatar>
          <div class="user-info">
            <span class="user-name">{{ getUserDisplayName(user) }}</span>
            <span class="user-handle">@{{ user.username }}</span>
          </div>
          <v-btn size="small" color="primary" variant="tonal" rounded="lg"
            @click="sendFriendRequest(user.username)">
            <v-icon start size="14">mdi-account-plus-outline</v-icon>
            {{ t('friendsPage.addFriend') }}
          </v-btn>
        </div>
      </div>

      <!-- Tabs -->
      <v-tabs v-model="tab" density="compact" class="friends-tabs">
        <v-tab value="friends">
          <v-icon start size="14">mdi-account-multiple-outline</v-icon>
          {{ t('friendsPage.tabFriends') }}
          <v-chip v-if="friends.length" size="x-small" class="ml-1">{{ friends.length }}</v-chip>
        </v-tab>
        <v-tab value="pending">
          <v-icon start size="14">mdi-clock-outline</v-icon>
          {{ t('friendsPage.tabPending') }}
          <v-chip v-if="pendingReceived.length" color="warning" size="x-small" class="ml-1">
            {{ pendingReceived.length }}
          </v-chip>
        </v-tab>
      </v-tabs>

      <v-window v-model="tab">
        <!-- Friends list -->
        <v-window-item value="friends">
          <div v-if="friends.length > 0" class="friends-list">
            <div v-for="friend in friends" :key="friend.id" class="user-row">
              <v-avatar size="36" color="secondary" class="mr-3">
                <v-img v-if="friend.avatar_thumbnail" :src="friend.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(friend.avatar_thumbnail, getUserDisplayName(friend))" />
                <span v-else class="text-caption font-weight-bold">{{ getUserInitial(friend) }}</span>
              </v-avatar>
              <div class="user-info">
                <span class="user-name clickable-name" @click="$router.push('/profile/' + friend.username)">{{ getUserDisplayName(friend) }}</span>
                <span class="user-handle">@{{ friend.username }}</span>
              </div>
              <span v-if="onlineIds.has(friend.id)" class="online-dot" title="Online" />
              <div class="row-actions">
                <v-btn size="small" variant="tonal" color="primary" rounded="lg"
                  @click="startConversation(friend.id)">
                  <v-icon start size="14">mdi-message-outline</v-icon>
                  {{ t('friendsPage.message') }}
                </v-btn>
                <v-btn size="small" variant="text" color="error" icon @click="removeFriend(friend.id)">
                  <v-icon size="16">mdi-account-minus-outline</v-icon>
                </v-btn>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <v-icon size="48" color="primary" class="mb-3">mdi-account-multiple-outline</v-icon>
            <p>{{ t('friendsPage.noFriends') }}</p>
            <span class="empty-hint">{{ t('friendsPage.noFriendsText') }}</span>
          </div>
        </v-window-item>

        <!-- Pending -->
        <v-window-item value="pending">
          <div v-if="pendingReceived.length > 0" class="pending-section">
            <p class="section-label">{{ t('friendsPage.receivedRequests') }}</p>
            <div v-for="req in pendingReceived" :key="req.id" class="user-row">
              <v-avatar size="36" color="warning" class="mr-3">
                <v-img v-if="req.user?.avatar_thumbnail" :src="req.user.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(req.user.avatar_thumbnail, getUserDisplayName(req.user))" />
                <span v-else class="text-caption font-weight-bold">{{ getUserInitial(req.user) }}</span>
              </v-avatar>
              <div class="user-info">
                <span class="user-name">{{ getUserDisplayName(req.user) }}</span>
                <span class="user-handle">@{{ req.user?.username }}</span>
              </div>
              <div class="row-actions">
                <v-btn size="small" color="success" variant="tonal" rounded="lg" @click="acceptRequest(req.id)">
                  {{ t('friendsPage.accept') }}
                </v-btn>
                <v-btn size="small" color="error" variant="text" @click="rejectRequest(req.id)">
                  {{ t('friendsPage.reject') }}
                </v-btn>
              </div>
            </div>
          </div>

          <div v-if="pendingSent.length > 0" class="pending-section">
            <p class="section-label">{{ t('friendsPage.sentRequests') }}</p>
            <div v-for="req in pendingSent" :key="req.id" class="user-row">
              <v-avatar size="36" color="info" class="mr-3">
                <v-img v-if="req.friend?.avatar_thumbnail" :src="req.friend.avatar_thumbnail" cover class="zoomable-avatar" @click.stop="openAvatarZoom(req.friend.avatar_thumbnail, getUserDisplayName(req.friend))" />
                <span v-else class="text-caption font-weight-bold">{{ getUserInitial(req.friend) }}</span>
              </v-avatar>
              <div class="user-info">
                <span class="user-name">{{ getUserDisplayName(req.friend) }}</span>
                <span class="user-handle">@{{ req.friend?.username }}</span>
              </div>
              <v-chip size="small" color="info" variant="tonal">{{ t('friendsPage.pending') }}</v-chip>
            </div>
          </div>

          <div v-if="!pendingReceived.length && !pendingSent.length" class="empty-state">
            <v-icon size="48" color="primary" class="mb-3">mdi-clock-outline</v-icon>
            <p>{{ t('friendsPage.noPending') }}</p>
            <span class="empty-hint">{{ t('friendsPage.noPendingText') }}</span>
          </div>
        </v-window-item>
      </v-window>
    </div>

    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000" rounded="lg">
      {{ snackbarText }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'
import { openAvatarZoom } from '@/utils/avatarZoom'
import { getUserDisplayName, getUserInitial } from '@/utils/displayName'

const router = useRouter()
const { t } = useI18n()
const tab = ref('friends')
const searchQuery = ref('')
const searchResults = ref([])
const friends = ref([])
const pendingReceived = ref([])
const pendingSent = ref([])
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const onlineIds = ref(new Set())
let onlineInterval = null

const searchUsers = async () => {
  if (!searchQuery.value) return

  try {
    const response = await api.get('/users/search', {
      params: { query: searchQuery.value }
    })
    searchResults.value = response.data.users
  } catch {
    showSnackbar(t('friendsPage.searchFailed'), 'error')
  }
}

const loadFriends = async () => {
  try {
    const response = await api.get('/friends')
    friends.value = response.data.friends
  } catch {
    showSnackbar(t('friendsPage.loadFailed'), 'error')
  }
}

const loadPending = async () => {
  try {
    const response = await api.get('/friends/pending')
    pendingReceived.value = response.data.received
    pendingSent.value = response.data.sent
  } catch {
    showSnackbar(t('friendsPage.loadFailed'), 'error')
  }
}

const sendFriendRequest = async (username) => {
  try {
    await api.post('/friends/request', { username })
    showSnackbar(t('friendsPage.requestSent'), 'success')
    searchResults.value = []
    searchQuery.value = ''
    loadPending()
  } catch (error) {
    showSnackbar(error.response?.data?.message || t('friendsPage.actionFailed'), 'error')
  }
}

const acceptRequest = async (id) => {
  try {
    await api.post(`/friends/${id}/accept`)
    showSnackbar(t('friendsPage.actionOk'), 'success')
    loadFriends()
    loadPending()
  } catch {
    showSnackbar(t('friendsPage.actionFailed'), 'error')
  }
}

const rejectRequest = async (id) => {
  try {
    await api.delete(`/friends/${id}/reject`)
    showSnackbar(t('friendsPage.actionOk'), 'info')
    loadPending()
  } catch {
    showSnackbar(t('friendsPage.actionFailed'), 'error')
  }
}

const removeFriend = async (id) => {
  try {
    await api.delete(`/friends/${id}`)
    showSnackbar(t('friendsPage.actionOk'), 'info')
    loadFriends()
  } catch {
    showSnackbar(t('friendsPage.actionFailed'), 'error')
  }
}

const startConversation = async (friendId) => {
  try {
    const response = await api.post('/conversations', { friend_id: friendId })
    router.push(`/messages/${response.data.conversation.id}`)
  } catch {
    showSnackbar(t('friendsPage.startConversationFailed'), 'error')
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const loadOnlineUsers = async () => {
  try {
    const res = await api.get('/users/online')
    onlineIds.value = new Set(res.data.online_ids || [])
  } catch (e) {
    // silently ignore
  }
}

onMounted(() => {
  loadFriends()
  loadPending()
  loadOnlineUsers()
  onlineInterval = setInterval(loadOnlineUsers, 60000)
})

onUnmounted(() => {
  clearInterval(onlineInterval)
})
</script>

<style scoped>
/* Friends page */
.friends-page {
  min-height: 100vh;
  background: var(--c-bg);
  padding-bottom: 60px;
}

.friends-header {
  padding: 36px 28px 16px;
  max-width: 760px;
  margin: 0 auto;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--c-text);
}

.friends-body {
  max-width: 760px;
  margin: 0 auto;
  padding: 0 28px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.search-panel {
  display: flex;
  gap: 12px;
  align-items: center;
}

.result-list,
.friends-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  overflow: hidden;
}

.user-row {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  gap: 8px;
  transition: background 150ms;
}

.user-row:hover {
  background: var(--c-elevated);
}

.user-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-handle {
  font-size: 0.75rem;
  color: var(--c-muted);
}

.row-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-shrink: 0;
}

.online-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
  box-shadow: 0 0 0 2px rgba(34,197,94,0.25);
  margin-right: 6px;
}

.clickable-name {
  cursor: pointer;
}

.clickable-name:hover {
  color: var(--c-accent);
  text-decoration: underline;
}

.friends-tabs {
  border-bottom: 1px solid var(--c-border);
}

.pending-section {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  overflow: hidden;
}

.section-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--c-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 10px 16px 4px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 56px 24px;
  text-align: center;
}

.empty-state p {
  font-size: 1rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 6px;
}

.empty-hint {
  font-size: 0.85rem;
  color: var(--c-muted);
}
</style>
