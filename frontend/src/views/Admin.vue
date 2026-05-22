<template>
  <div class="admin-page">

    <!-- Sidebar nav -->
    <aside class="admin-nav">
      <div class="admin-nav-brand">
        <span class="admin-nav-icon"><v-icon>mdi-shield-crown</v-icon></span>
        <div>
          <div class="admin-nav-title">{{ t('adminPage.title') }}</div>
          <div class="admin-nav-sub">{{ t('adminPage.brand') }}</div>
        </div>
      </div>

      <nav class="admin-nav-links">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['anl-btn', { 'anl-btn--active': activeTab === tab.id }]"
          @click="activeTab = tab.id"
        >
          <v-icon class="anl-icon">{{ tab.icon }}</v-icon>
          <span>{{ tab.label }}</span>
          <span v-if="stats[tab.statKey]" class="anl-badge">{{ stats[tab.statKey] }}</span>
        </button>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="admin-main">

      <!-- Access denied -->
      <div v-if="!isAdmin" class="admin-denied">
        <div class="admin-denied-icon"><v-icon size="64">mdi-cancel</v-icon></div>
        <h2>{{ t('adminPage.accessDenied') }}</h2>
        <p>{{ t('adminPage.accessDeniedText') }}</p>
      </div>

      <template v-else>

        <!-- Header bar -->
        <div class="admin-topbar">
          <h1 class="admin-topbar-title">{{ currentTab?.label }}</h1>
          <div class="admin-topbar-right">
            <input
              v-model="search"
              class="admin-search"
              :placeholder="t('adminPage.searchPlaceholder')"
              @input="onSearch"
            />
          </div>
        </div>

        <!-- ── STATS overview ───────────────────────────────────── -->
        <div v-if="activeTab === 'overview'" class="admin-stats-grid">
          <div v-for="s in statCards" :key="s.label" class="stat-card">
            <v-icon class="stat-icon">{{ s.icon }}</v-icon>
            <div class="stat-value">{{ stats[s.key] ?? '—' }}</div>
            <div class="stat-label">{{ s.label }}</div>
          </div>
        </div>

        <!-- ── USERS ────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'users'" class="admin-section">
          <div v-if="loading" class="admin-loading">{{ t('adminPage.loading') }}</div>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>{{ t('adminPage.id') }}</th><th>{{ t('adminPage.name') }}</th><th>{{ t('adminPage.username') }}</th><th>{{ t('adminPage.email') }}</th>
                <th>{{ t('adminPage.drawingsCount') }}</th><th>{{ t('adminPage.messagesCount') }}</th><th>{{ t('adminPage.admin') }}</th><th>{{ t('adminPage.joined') }}</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in items" :key="user.id">
                <td class="td-muted">{{ user.id }}</td>
                <td class="td-bold">{{ user.name }}</td>
                <td class="td-muted">@{{ user.username }}</td>
                <td class="td-muted">{{ user.email }}</td>
                <td>{{ user.drawings_count }}</td>
                <td>{{ user.messages_count }}</td>
                <td>
                  <span :class="user.is_admin ? 'badge badge--green' : 'badge badge--gray'">
                    {{ user.is_admin ? t('adminPage.yes') : t('adminPage.no') }}
                  </span>
                </td>
                <td class="td-muted">{{ fmtDate(user.created_at) }}</td>
                <td class="td-actions">
                  <button class="act-btn act-btn--warn" @click="openEditUser(user)">{{ t('adminPage.edit') }}</button>
                  <button class="act-btn act-btn--danger" @click="deleteUser(user.id)">{{ t('adminPage.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination :meta="paginationMeta" @change="loadPage" />
        </div>

        <!-- ── MESSAGES ─────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'messages'" class="admin-section">
          <div v-if="conversationFilter" class="admin-inline-filter">
            <span>
              Showing messages for conversation #{{ conversationFilter.id }}
              <strong>{{ conversationFilter.name }}</strong>
            </span>
            <button class="act-btn act-btn--warn" @click="clearConversationMessageFilter">Clear</button>
          </div>
          <div v-if="loading" class="admin-loading">{{ t('adminPage.loading') }}</div>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>{{ t('adminPage.id') }}</th><th>{{ t('adminPage.user') }}</th><th>Conversation</th><th>Channel</th><th>{{ t('adminPage.content') }}</th><th>{{ t('adminPage.type') }}</th><th>{{ t('adminPage.sent') }}</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="msg in items" :key="msg.id">
                <td class="td-muted">{{ msg.id }}</td>
                <td class="td-bold">{{ msg.user?.name || '—' }}</td>
                <td class="td-muted">{{ msg.conversation?.name || (msg.conversation?.type === 'group' ? 'Group' : 'Direct') || '—' }}</td>
                <td class="td-muted">{{ msg.channel?.name || '—' }}</td>
                <td class="td-content">{{ msg.drawing_data ? t('adminPage.drawing') : msg.content }}</td>
                <td><span :class="msg.drawing_data ? 'badge badge--purple' : 'badge badge--blue'">{{ msg.drawing_data ? t('adminPage.drawing') : t('adminPage.text') }}</span></td>
                <td class="td-muted">{{ fmtDate(msg.created_at) }}</td>
                <td class="td-actions">
                  <button class="act-btn act-btn--danger" @click="deleteMessage(msg.id)">{{ t('adminPage.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination :meta="paginationMeta" @change="loadPage" />
        </div>

        <!-- ── DRAWINGS ─────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'drawings'" class="admin-section">
          <div v-if="loading" class="admin-loading">{{ t('adminPage.loading') }}</div>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>{{ t('adminPage.id') }}</th><th>{{ t('adminPage.title') }}</th><th>{{ t('adminPage.author') }}</th><th>{{ t('adminPage.votes') }}</th><th>{{ t('adminPage.free') }}</th><th>{{ t('adminPage.created') }}</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in items" :key="d.id">
                <td class="td-muted">{{ d.id }}</td>
                <td class="td-bold">{{ d.title || '—' }}</td>
                <td>{{ d.user?.name || '—' }}</td>
                <td>{{ d.votes_count }}</td>
                <td><span :class="d.is_free ? 'badge badge--green' : 'badge badge--gray'">{{ d.is_free ? t('adminPage.yes') : t('adminPage.no') }}</span></td>
                <td class="td-muted">{{ fmtDate(d.created_at) }}</td>
                <td class="td-actions">
                  <button class="act-btn act-btn--warn" @click="openEditDrawing(d)">{{ t('adminPage.edit') }}</button>
                  <button class="act-btn act-btn--danger" @click="deleteDrawing(d.id)">{{ t('adminPage.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination :meta="paginationMeta" @change="loadPage" />
        </div>

        <!-- ── CONVERSATIONS ────────────────────────────────────── -->
        <div v-else-if="activeTab === 'conversations'" class="admin-section">
          <div v-if="loading" class="admin-loading">{{ t('adminPage.loading') }}</div>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>{{ t('adminPage.id') }}</th><th>{{ t('adminPage.convName') }}</th><th>{{ t('adminPage.convType') }}</th><th>{{ t('adminPage.members') }}</th><th>Channels</th><th>Messages</th><th>{{ t('adminPage.created') }}</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in items" :key="c.id">
                <td class="td-muted">{{ c.id }}</td>
                <td class="td-bold">{{ c.name || t('adminPage.directMessage') }}</td>
                <td><span :class="c.type === 'group' ? 'badge badge--purple' : 'badge badge--blue'">{{ c.type || 'dm' }}</span></td>
                <td>{{ c.participants_count }}</td>
                <td>
                  <div class="conv-channel-list">
                    <span v-if="!c.channels || c.channels.length === 0" class="td-muted">—</span>
                    <span v-else v-for="ch in c.channels" :key="ch.id" class="conv-channel-chip">
                      #{{ ch.name }}
                    </span>
                  </div>
                </td>
                <td>{{ c.messages_count ?? 0 }}</td>
                <td class="td-muted">{{ fmtDate(c.created_at) }}</td>
                <td class="td-actions">
                  <button class="act-btn act-btn--warn" @click="goToConversationMessages(c)">Messages</button>
                  <button class="act-btn act-btn--danger" @click="deleteConversation(c.id)">{{ t('adminPage.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
          <Pagination :meta="paginationMeta" @change="loadPage" />
        </div>

        <!-- ── WEEKLY THEMES ─────────────────────────────────────── -->
        <div v-else-if="activeTab === 'themes'" class="admin-section">
          <div class="admin-section-actions">
            <button class="primary-btn" @click="openNewTheme">{{ t('adminPage.newTheme') }}</button>
          </div>
          <div v-if="loading" class="admin-loading">{{ t('adminPage.loading') }}</div>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>{{ t('adminPage.id') }}</th><th>{{ t('adminPage.theme') }}</th><th>{{ t('adminPage.week') }}</th><th>{{ t('adminPage.dates') }}</th><th>{{ t('adminPage.status') }}</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="th in items" :key="th.id">
                <td class="td-muted">{{ th.id }}</td>
                <td class="td-bold">{{ th.emoji }} {{ th.theme_name }}</td>
                <td>W{{ th.week_number }} {{ th.year }}</td>
                <td class="td-muted">{{ fmtDate(th.starts_at) }} – {{ fmtDate(th.ends_at) }}</td>
                <td>
                  <span :class="isThemeActive(th) ? 'badge badge--green' : 'badge badge--gray'">
                    {{ isThemeActive(th) ? t('adminPage.active') : t('adminPage.inactive') }}
                  </span>
                </td>
                <td class="td-actions">
                  <button class="act-btn act-btn--warn" @click="openEditTheme(th)">{{ t('adminPage.edit') }}</button>
                  <button class="act-btn act-btn--danger" @click="deleteTheme(th.id)">{{ t('adminPage.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </template>
    </main>

    <!-- ── MODAL ──────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="modal.open" class="admin-modal-bg" @click.self="modal.open = false">
        <div class="admin-modal">
          <div class="admin-modal-header">
            <span>{{ modal.title }}</span>
            <button @click="modal.open = false">✕</button>
          </div>
          <form class="admin-modal-body" @submit.prevent="modal.onSave()">
            <template v-for="field in modal.fields" :key="field.key">
              <label class="admin-label">{{ field.label }}</label>
              <select v-if="field.type === 'select'" v-model="modal.data[field.key]" class="admin-input">
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <input
                v-else
                v-model="modal.data[field.key]"
                :type="field.type || 'text'"
                :placeholder="field.placeholder || ''"
                class="admin-input"
              />
            </template>
            <div class="admin-modal-footer">
              <button type="button" class="cancel-btn" @click="modal.open = false">{{ t('adminPage.cancel') }}</button>
              <button type="submit" class="primary-btn">{{ modal.saveLabel || t('adminPage.save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script>
import { h, ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'

// ── Tiny inline pagination component ─────────────────────────
const Pagination = {
  name: 'Pagination',
  props: { meta: Object },
  emits: ['change'],
  setup(props, { emit }) {
    return () => {
      if (!props.meta || props.meta.last_page <= 1) return null
      return h('div', { class: 'admin-pagination' }, [
        h('button', { disabled: props.meta.current_page <= 1, onClick: () => emit('change', props.meta.current_page - 1) }, '‹'),
        h('span', `Page ${props.meta.current_page} / ${props.meta.last_page}`),
        h('button', { disabled: props.meta.current_page >= props.meta.last_page, onClick: () => emit('change', props.meta.current_page + 1) }, '›'),
      ])
    }
  }
}

export default {
  name: 'Admin',
  components: { Pagination },

  setup() {
    const { t } = useI18n()
    // ── Auth guard ───────────────────────────────────────────
    const currentUser = computed(() => {
      const d = localStorage.getItem('user')
      return d ? JSON.parse(d) : null
    })
    const isAdmin = computed(() => !!currentUser.value?.is_admin)

    // ── Tabs ─────────────────────────────────────────────────
    const tabs = computed(() => [
      { id: 'overview',      label: t('adminPage.overview'),      icon: 'mdi-view-dashboard', statKey: null },
      { id: 'users',         label: t('adminPage.users'),         icon: 'mdi-account-group', statKey: 'users' },
      { id: 'messages',      label: t('adminPage.messages'),      icon: 'mdi-message', statKey: 'messages' },
      { id: 'drawings',      label: t('adminPage.drawings'),      icon: 'mdi-draw', statKey: 'drawings' },
      { id: 'conversations', label: t('adminPage.conversations'), icon: 'mdi-forum', statKey: 'conversations' },
      { id: 'themes',        label: t('adminPage.themes'),        icon: 'mdi-calendar-week', statKey: null },
    ])
    const statCards = computed(() => [
      { icon: 'mdi-account-group', label: t('adminPage.users'),         key: 'users' },
      { icon: 'mdi-message',       label: t('adminPage.messages'),      key: 'messages' },
      { icon: 'mdi-draw',          label: t('adminPage.drawings'),      key: 'drawings' },
      { icon: 'mdi-forum',         label: t('adminPage.conversations'), key: 'conversations' },
      { icon: 'mdi-comment',       label: t('adminPage.comments'),      key: 'comments' },
    ])

    const activeTab = ref('overview')
    const currentTab = computed(() => tabs.value.find(t => t.id === activeTab.value))

    // ── Data ─────────────────────────────────────────────────
    const stats         = ref({})
    const items         = ref([])
    const loading       = ref(false)
    const search        = ref('')
    const conversationFilter = ref(null)
    const paginationMeta = ref(null)
    let   searchTimer   = null

    const fmtDate = (ts) => ts ? new Date(ts).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—'

    const loadStats = async () => {
      const res = await api.get('/admin/stats')
      stats.value = res.data
    }

    const loadTab = async (page = 1) => {
      if (activeTab.value === 'overview') { await loadStats(); return }
      loading.value = true
      try {
        const endpoints = {
          users:         '/admin/users',
          messages:      '/admin/messages',
          drawings:      '/admin/drawings',
          conversations: '/admin/conversations',
          themes:        '/admin/themes',
        }
        const ep = endpoints[activeTab.value]
        if (!ep) return
        const params = { page }
        if (search.value) params.q = search.value
        if (activeTab.value === 'messages' && conversationFilter.value?.id) {
          params.conversation_id = conversationFilter.value.id
        }
        const res = await api.get(ep, { params })
        if (activeTab.value === 'themes') {
          items.value = res.data
          paginationMeta.value = null
        } else {
          items.value = res.data.data
          paginationMeta.value = res.data
        }
      } catch (e) {
        console.error(e)
      } finally {
        loading.value = false
      }
    }

    const loadPage = (page) => loadTab(page)
    const onSearch = () => {
      clearTimeout(searchTimer)
      searchTimer = setTimeout(() => loadTab(1), 400)
    }

    watch(activeTab, (tab) => {
      search.value = ''
      if (tab !== 'messages') conversationFilter.value = null
      loadTab(1)
    })

    onMounted(() => { if (isAdmin.value) loadTab(1) })

    // ── Modal ─────────────────────────────────────────────────
    const modal = ref({ open: false, title: '', fields: [], data: {}, onSave: () => {}, saveLabel: 'Save' })

    const openModal = (title, fields, data, onSave, saveLabel = 'Save') => {
      modal.value = { open: true, title, fields, data: { ...data }, onSave, saveLabel }
    }

    // ── User actions ──────────────────────────────────────────
    const openEditUser = (user) => {
      openModal(t('adminPage.editUser'), [
        { key: 'name',     label: t('adminPage.fieldName') },
        { key: 'username', label: t('adminPage.fieldUsername') },
        { key: 'email',    label: t('adminPage.fieldEmail'), type: 'email' },
        { key: 'password', label: t('adminPage.fieldPassword'), type: 'password', placeholder: '••••••••' },
        { key: 'is_admin', label: t('adminPage.fieldAdmin'), type: 'select', options: [{ value: false, label: t('adminPage.no') }, { value: true, label: t('adminPage.yes') }] },
      ], user, async () => {
        const payload = { ...modal.value.data }
        if (!payload.password) delete payload.password
        await api.put(`/admin/users/${user.id}`, payload)
        modal.value.open = false
        loadTab(1)
      })
    }

    const deleteUser = async (id) => {
      if (!confirm(t('adminPage.confirmDeleteUser'))) return
      await api.delete(`/admin/users/${id}`)
      loadTab(1)
    }

    // ── Message actions ───────────────────────────────────────
    const deleteMessage = async (id) => {
      if (!confirm(t('adminPage.confirmDeleteMessage'))) return
      await api.delete(`/admin/messages/${id}`)
      loadTab(1)
    }

    // ── Drawing actions ───────────────────────────────────────
    const openEditDrawing = (d) => {
      openModal(t('adminPage.editDrawing'), [
        { key: 'title',       label: t('adminPage.fieldTitle') },
        { key: 'description', label: t('adminPage.fieldDescription') },
        { key: 'is_free',     label: t('adminPage.fieldFree'), type: 'select', options: [{ value: false, label: t('adminPage.no') }, { value: true, label: t('adminPage.yes') }] },
      ], d, async () => {
        await api.put(`/admin/drawings/${d.id}`, modal.value.data)
        modal.value.open = false
        loadTab(1)
      })
    }

    const deleteDrawing = async (id) => {
      if (!confirm(t('adminPage.confirmDeleteDrawing'))) return
      await api.delete(`/admin/drawings/${id}`)
      loadTab(1)
    }

    // ── Conversation actions ──────────────────────────────────
    const deleteConversation = async (id) => {
      if (!confirm(t('adminPage.confirmDeleteConversation'))) return
      await api.delete(`/admin/conversations/${id}`)
      loadTab(1)
    }

    const goToConversationMessages = (conversation) => {
      conversationFilter.value = {
        id: conversation.id,
        name: conversation.name || t('adminPage.directMessage'),
      }
      activeTab.value = 'messages'
    }

    const clearConversationMessageFilter = () => {
      conversationFilter.value = null
      loadTab(1)
    }

    // ── Theme actions ─────────────────────────────────────────
    const isThemeActive = (th) => {
      if (!th.starts_at || !th.ends_at) return false
      const today = new Date(); today.setHours(0,0,0,0)
      return new Date(th.starts_at) <= today && today <= new Date(th.ends_at)
    }

    const themeFields = computed(() => [
      { key: 'theme_name',  label: t('adminPage.fieldThemeName') },
      { key: 'emoji',       label: t('adminPage.fieldEmoji'), placeholder: '🎨' },
      { key: 'week_number', label: t('adminPage.fieldWeekNumber'), type: 'number' },
      { key: 'year',        label: t('adminPage.fieldYear'), type: 'number' },
      { key: 'starts_at',   label: t('adminPage.fieldStarts'), type: 'date' },
      { key: 'ends_at',     label: t('adminPage.fieldEnds'), type: 'date' },
      { key: 'description', label: t('adminPage.fieldDescriptionOpt') },
    ])

    const openNewTheme = () => {
      const now = new Date()
      openModal(t('adminPage.newThemeTitle'), themeFields.value, {
        theme_name: '', emoji: '🎨', week_number: getWeekNumber(now), year: now.getFullYear(), starts_at: '', ends_at: '', description: ''
      }, async () => {
        await api.post('/admin/themes', modal.value.data)
        modal.value.open = false
        loadTab(1)
      }, t('adminPage.create'))
    }

    const openEditTheme = (th) => {
      openModal(t('adminPage.editTheme'), themeFields.value, th, async () => {
        await api.put(`/admin/themes/${th.id}`, modal.value.data)
        modal.value.open = false
        loadTab(1)
      })
    }

    const deleteTheme = async (id) => {
      if (!confirm(t('adminPage.confirmDeleteTheme'))) return
      await api.delete(`/admin/themes/${id}`)
      loadTab(1)
    }

    const getWeekNumber = (d) => {
      const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
      date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7))
      const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1))
      return Math.ceil((((date - yearStart) / 86400000) + 1) / 7)
    }

    return {
      isAdmin, t, tabs, statCards, activeTab, currentTab,
      stats, items, loading, search, paginationMeta,
      conversationFilter,
      modal, fmtDate, isThemeActive,
      onSearch, loadPage, loadTab,
      openEditUser, deleteUser,
      deleteMessage,
      openEditDrawing, deleteDrawing,
      deleteConversation,
      goToConversationMessages, clearConversationMessageFilter,
      openNewTheme, openEditTheme, deleteTheme,
    }
  }
}
</script>

<style scoped>
/* ── layout ─────────────────────────────────────────────────── */
.admin-page {
  display: flex;
  min-height: calc(100vh - 72px);
  background: #12131a;
  font-family: 'Nunito', system-ui, sans-serif;
}

/* ── sidebar nav ─────────────────────────────────────────────── */
.admin-nav {
  width: 220px;
  flex-shrink: 0;
  background: #16171e;
  border-right: 1px solid rgba(255,255,255,.06);
  display: flex;
  flex-direction: column;
  padding: 20px 12px;
  gap: 4px;
}
.admin-nav-brand {
  display: flex; align-items: center; gap: 10px;
  padding: 4px 8px 20px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  margin-bottom: 8px;
}
.admin-nav-icon { font-size: 24px; }
.admin-nav-title { font-size: 14px; font-weight: 700; color: #e0e1ef; }
.admin-nav-sub   { font-size: 11px; color: #5a5c78; }

.admin-nav-links { display: flex; flex-direction: column; gap: 2px; }
.anl-btn {
  display: flex; align-items: center; gap: 10px;
  width: 100%; padding: 9px 12px;
  background: none; border: none; border-radius: 10px;
  color: #6b6d88; font-size: 13.5px; font-weight: 600;
  cursor: pointer; text-align: left;
  transition: background 120ms, color 120ms;
}
.anl-btn:hover   { background: rgba(255,255,255,.05); color: #c0c2d8; }
.anl-btn--active { background: rgba(124,106,247,.15); color: #a89cf7; }
.anl-icon { font-size: 16px; width: 20px; text-align: center; }
.anl-badge {
  margin-left: auto;
  background: rgba(255,255,255,.1);
  border-radius: 20px;
  font-size: 11px; font-weight: 700; color: #888;
  padding: 1px 8px;
}

/* ── main content ────────────────────────────────────────────── */
.admin-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.admin-topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 28px 16px;
  border-bottom: 1px solid rgba(255,255,255,.06);
  background: #16171e;
}
.admin-topbar-title { font-size: 20px; font-weight: 800; color: #e0e1ef; margin: 0; }
.admin-search {
  height: 36px; padding: 0 14px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 20px;
  color: #d0d2e0; font-family: inherit; font-size: 13px;
  outline: none; width: 220px;
  transition: border-color 150ms;
}
.admin-search:focus { border-color: rgba(124,106,247,.5); }
.admin-search::placeholder { color: #3e4058; }

/* ── access denied ──────────────────────────────────────────── */
.admin-denied {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 12px;
  color: #5a5c78;
}
.admin-denied-icon { font-size: 64px; }
.admin-denied h2   { font-size: 22px; font-weight: 800; color: #8082a0; margin: 0; }
.admin-denied p    { font-size: 14px; margin: 0; }

/* ── stats grid ──────────────────────────────────────────────── */
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px; padding: 28px;
}
.stat-card {
  background: #1e2030;
  border: 1px solid rgba(255,255,255,.06);
  border-radius: 16px;
  padding: 24px 20px;
  display: flex; flex-direction: column; align-items: flex-start; gap: 8px;
  transition: border-color 150ms, transform 150ms;
}
.stat-card:hover { border-color: rgba(124,106,247,.3); transform: translateY(-2px); }
.stat-icon  { font-size: 28px; }
.stat-value { font-size: 32px; font-weight: 800; color: #e0e1ef; line-height: 1; }
.stat-label { font-size: 13px; color: #6b6d88; font-weight: 600; }

/* ── section ─────────────────────────────────────────────────── */
.admin-section { padding: 20px 28px; flex: 1; overflow-x: auto; }
.admin-section-actions { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.admin-loading { color: #5a5c78; font-size: 14px; padding: 32px 0; text-align: center; }

/* ── table ───────────────────────────────────────────────────── */
.admin-table {
  width: 100%; border-collapse: collapse;
  font-size: 13.5px;
}
.admin-table thead tr {
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.admin-table th {
  padding: 10px 12px; text-align: left;
  font-size: 11.5px; font-weight: 700; color: #5a5c78;
  text-transform: uppercase; letter-spacing: 0.6px;
  white-space: nowrap;
}
.admin-table tbody tr {
  border-bottom: 1px solid rgba(255,255,255,.04);
  transition: background 100ms;
}
.admin-table tbody tr:hover { background: rgba(255,255,255,.03); }
.admin-table td { padding: 10px 12px; color: #c0c2d8; vertical-align: middle; }

.td-muted   { color: #5a5c78 !important; }
.td-bold    { font-weight: 700; color: #e0e1ef !important; }
.td-content { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.td-actions { display: flex; gap: 6px; }

.admin-inline-filter {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
  padding: 10px 12px;
  border: 1px solid rgba(124,106,247,.35);
  background: rgba(124,106,247,.12);
  border-radius: 10px;
  color: #d5cffd;
  font-size: 13px;
}

.conv-channel-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.conv-channel-chip {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(91,142,245,.14);
  color: #9dbcfb;
  font-size: 11px;
  font-weight: 700;
  max-width: 180px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── badges ─────────────────────────────────────────────────── */
.badge {
  display: inline-block; padding: 2px 8px;
  border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge--green  { background: rgba(109,223,109,.15); color: #6ddf6d; }
.badge--gray   { background: rgba(255,255,255,.07); color: #5a5c78; }
.badge--blue   { background: rgba(91,142,245,.15);  color: #5b8ef5; }
.badge--purple { background: rgba(124,106,247,.15); color: #a89cf7; }

/* ── action buttons ─────────────────────────────────────────── */
.act-btn {
  padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;
  border: none; cursor: pointer; font-family: inherit; transition: opacity 120ms;
}
.act-btn:hover { opacity: .8; }
.act-btn--warn   { background: rgba(234,179,8,.15); color: #eab308; }
.act-btn--danger { background: rgba(239,68,68,.15);  color: #f87171; }

/* ── buttons ─────────────────────────────────────────────────── */
.primary-btn {
  padding: 8px 20px; border-radius: 10px;
  background: linear-gradient(135deg, #7c6af7, #5b8ef5);
  border: none; color: #fff; font-family: inherit;
  font-size: 13px; font-weight: 700; cursor: pointer;
  box-shadow: 0 2px 10px rgba(124,106,247,.35);
  transition: opacity 120ms, transform 100ms;
}
.primary-btn:hover  { opacity: .9; transform: translateY(-1px); }
.cancel-btn {
  padding: 8px 20px; border-radius: 10px;
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.09);
  color: #8082a0; font-family: inherit;
  font-size: 13px; font-weight: 700; cursor: pointer;
  transition: background 120ms;
}
.cancel-btn:hover { background: rgba(255,255,255,.1); }

/* ── pagination ─────────────────────────────────────────────── */
.admin-pagination {
  display: flex; align-items: center; gap: 12px;
  margin-top: 18px; justify-content: center;
  font-size: 13px; color: #6b6d88;
}
.admin-pagination button {
  width: 32px; height: 32px; border-radius: 8px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.09);
  color: #8082a0; font-size: 16px; cursor: pointer;
  transition: background 120ms;
}
.admin-pagination button:hover:not(:disabled) { background: rgba(255,255,255,.1); }
.admin-pagination button:disabled { opacity: .3; cursor: not-allowed; }

/* ── modal ───────────────────────────────────────────────────── */
.admin-modal-bg {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.7); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 9000;
}
.admin-modal {
  background: #1e2030;
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 18px;
  width: 420px; max-width: 94vw;
  box-shadow: 0 24px 60px rgba(0,0,0,.5);
  overflow: hidden;
}
.admin-modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px;
  background: #16171e;
  border-bottom: 1px solid rgba(255,255,255,.06);
  font-size: 15px; font-weight: 700; color: #e0e1ef;
}
.admin-modal-header button {
  background: none; border: none; color: #6b6d88;
  font-size: 16px; cursor: pointer; line-height: 1;
}
.admin-modal-header button:hover { color: #d0d2e0; }
.admin-modal-body {
  padding: 20px; display: flex; flex-direction: column; gap: 12px;
}
.admin-label { font-size: 12px; font-weight: 700; color: #6b6d88; letter-spacing: 0.4px; text-transform: uppercase; }
.admin-input {
  width: 100%; height: 38px; padding: 0 12px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.09); border-radius: 10px;
  color: #d0d2e0; font-family: inherit; font-size: 13.5px;
  outline: none; box-sizing: border-box;
  transition: border-color 150ms;
}
.admin-input:focus { border-color: rgba(124,106,247,.5); }
.admin-modal-footer {
  display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px;
}

/* ── responsive ──────────────────────────────────────────────── */
@media (max-width: 768px) {
  .admin-nav { width: 52px; padding: 16px 6px; }
  .admin-nav-brand .admin-nav-title,
  .admin-nav-brand .admin-nav-sub,
  .admin-nav-brand .admin-nav-icon { display: none; }
  .admin-nav-brand { padding-bottom: 12px; justify-content: center; }
  .anl-btn span:not(.anl-icon) { display: none; }
  .anl-badge { display: none; }
  .anl-btn { justify-content: center; padding: 10px; }
  .admin-section { padding: 14px; }
  .admin-stats-grid { padding: 14px; grid-template-columns: repeat(2, 1fr); }
  .admin-topbar { padding: 14px 16px; }
  .admin-search { width: 160px; }
}
</style>
