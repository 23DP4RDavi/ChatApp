<template>
  <v-container fluid class="friends-page">
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">🏝️ Friends</h1>

        <!-- Search Users -->
        <v-card class="mb-4">
          <v-card-text>
            <v-text-field
              v-model="searchQuery"
              label="Search doodlers by username"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              clearable
              @keyup.enter="searchUsers"
            ></v-text-field>

            <v-list v-if="searchResults.length > 0">
              <v-list-item
                v-for="user in searchResults"
                :key="user.id"
                :title="user.name"
                :subtitle="`@${user.username}`"
              >
                <template v-slot:append>
                  <v-btn
                    color="primary"
                    @click="sendFriendRequest(user.username)"
                  >
                    Add Friend
                  </v-btn>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>

        <!-- Tabs -->
        <v-tabs v-model="tab" color="deep-purple-darken-2">
          <v-tab value="friends">Friends</v-tab>
          <v-tab value="pending">Pending Requests</v-tab>
        </v-tabs>

        <v-window v-model="tab" class="mt-4">
          <!-- Friends List -->
          <v-window-item value="friends">
            <v-card>
              <v-card-text>
                <v-list v-if="friends.length > 0">
                  <v-list-item
                    v-for="friend in friends"
                    :key="friend.id"
                    :title="friend.name"
                    :subtitle="`@${friend.username}`"
                  >
                    <template v-slot:append>
                      <v-btn
                        color="primary"
                        class="mr-2"
                        @click="startConversation(friend.id)"
                      >
                        Message
                      </v-btn>
                      <v-btn
                        color="error"
                        variant="text"
                        @click="removeFriend(friend.id)"
                      >
                        Remove
                      </v-btn>
                    </template>
                  </v-list-item>
                </v-list>
                <v-empty-state
                  v-else
                  title="No friends yet"
                  text="Search for doodlers to add as friends!"
                  icon="mdi-account-multiple-outline"
                ></v-empty-state>
              </v-card-text>
            </v-card>
          </v-window-item>

          <!-- Pending Requests -->
          <v-window-item value="pending">
            <v-card class="mb-4" v-if="pendingReceived.length > 0">
              <v-card-title>Received Requests</v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item
                    v-for="request in pendingReceived"
                    :key="request.id"
                    :title="request.user.name"
                    :subtitle="`@${request.user.username}`"
                  >
                    <template v-slot:append>
                      <v-btn
                        color="success"
                        class="mr-2"
                        @click="acceptRequest(request.id)"
                      >
                        Accept
                      </v-btn>
                      <v-btn
                        color="error"
                        variant="text"
                        @click="rejectRequest(request.id)"
                      >
                        Reject
                      </v-btn>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>

            <v-card v-if="pendingSent.length > 0">
              <v-card-title>Sent Requests</v-card-title>
              <v-card-text>
                <v-list>
                  <v-list-item
                    v-for="request in pendingSent"
                    :key="request.id"
                    :title="request.friend.name"
                    :subtitle="`@${request.friend.username}`"
                  >
                    <template v-slot:append>
                      <v-chip color="info">Pending</v-chip>
                    </template>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>

            <v-empty-state
              v-if="pendingReceived.length === 0 && pendingSent.length === 0"
              title="No pending requests"
              text="You don't have any pending friend requests"
              icon="mdi-clock-outline"
            ></v-empty-state>
          </v-window-item>
        </v-window>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
      {{ snackbarText }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const tab = ref('friends')
const searchQuery = ref('')
const searchResults = ref([])
const friends = ref([])
const pendingReceived = ref([])
const pendingSent = ref([])
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const searchUsers = async () => {
  if (!searchQuery.value) return

  try {
    const response = await api.get('/users/search', {
      params: { query: searchQuery.value }
    })
    searchResults.value = response.data.users
  } catch (error) {
    showSnackbar('Failed to search users', 'error')
  }
}

const loadFriends = async () => {
  try {
    const response = await api.get('/friends')
    friends.value = response.data.friends
  } catch (error) {
    showSnackbar('Failed to load friends', 'error')
  }
}

const loadPending = async () => {
  try {
    const response = await api.get('/friends/pending')
    pendingReceived.value = response.data.received
    pendingSent.value = response.data.sent
  } catch (error) {
    showSnackbar('Failed to load pending requests', 'error')
  }
}

const sendFriendRequest = async (username) => {
  try {
    await api.post('/friends/request', { username })
    showSnackbar('Friend request sent!', 'success')
    searchResults.value = []
    searchQuery.value = ''
    loadPending()
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Failed to send friend request', 'error')
  }
}

const acceptRequest = async (id) => {
  try {
    await api.post(`/friends/${id}/accept`)
    showSnackbar('Friend request accepted!', 'success')
    loadFriends()
    loadPending()
  } catch (error) {
    showSnackbar('Failed to accept request', 'error')
  }
}

const rejectRequest = async (id) => {
  try {
    await api.delete(`/friends/${id}/reject`)
    showSnackbar('Friend request rejected', 'info')
    loadPending()
  } catch (error) {
    showSnackbar('Failed to reject request', 'error')
  }
}

const removeFriend = async (id) => {
  try {
    await api.delete(`/friends/${id}`)
    showSnackbar('Friend removed', 'info')
    loadFriends()
  } catch (error) {
    showSnackbar('Failed to remove friend', 'error')
  }
}

const startConversation = async (friendId) => {
  try {
    const response = await api.post('/conversations', { friend_id: friendId })
    router.push(`/messages/${response.data.conversation.id}`)
  } catch (error) {
    showSnackbar('Failed to start conversation', 'error')
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  loadFriends()
  loadPending()
})
</script>

<style scoped src="@/styles/friends.css"></style>
