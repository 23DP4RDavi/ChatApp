<template>
  <div class="messages-page" :class="{ 'has-conv': selectedConversation && (selectedConversation.type === 'group' ? !!selectedChannel : true) }">
    <!-- Rail (server icons) -->
    <div class="msg-rail">
      <v-tooltip location="end" :text="t('messagesPage.directMessagesTooltip')">
        <template #activator="{ props }">
          <v-btn class="rail-pill" :class="{ 'rail-pill--active': activeHub === 'dm' }"
            icon variant="text" v-bind="props" @click="activeHub = 'dm'; selectedConversation = null">
            <v-icon>mdi-home-variant-outline</v-icon>
          </v-btn>
        </template>
      </v-tooltip>
      <div class="rail-divider" />
      <v-tooltip v-for="group in groupConversations" :key="group.id" location="end">
        <template #activator="{ props }">
          <v-btn class="rail-pill" :class="{ 'rail-pill--active': String(activeHub) === String(group.id) }"
            icon variant="text" v-bind="props" @click="selectConversation(group)">
            <v-badge v-if="group.unreadCount > 0" :content="group.unreadCount" color="error" overlap>
              <v-avatar size="32" color="secondary">
                <img v-if="getConversationAvatar(group)" :src="getConversationAvatar(group)" alt="" />
                <span v-else style="font-size:0.75rem;font-weight:700">{{ getConversationInitial(group) }}</span>
              </v-avatar>
            </v-badge>
            <v-avatar v-else size="32" color="secondary">
              <img v-if="getConversationAvatar(group)" :src="getConversationAvatar(group)" alt="" />
              <span v-else style="font-size:0.75rem;font-weight:700">{{ getConversationInitial(group) }}</span>
            </v-avatar>
          </v-btn>
        </template>
        <span>{{ group.name || t('messagesPage.unnamedGroup') }}</span>
      </v-tooltip>
      <v-btn class="rail-pill" icon variant="text" :title="t('messagesPage.createServer')" @click="openCreateGroupDialog">
        <v-icon>mdi-plus</v-icon>
      </v-btn>
    </div>

    <!-- Mobile quick-switch hub bar — always visible, outside the sliding sidebar -->
    <div class="mobile-hub-bar">
      <button class="mhub-btn" :class="{ 'mhub-btn--active': activeHub === 'dm' }"
        @click="activeHub = 'dm'; selectedConversation = null" title="Direct Messages">
        <v-icon size="18">mdi-home-variant-outline</v-icon>
      </button>
      <div class="mhub-divider" />
      <button v-for="group in groupConversations" :key="group.id"
        class="mhub-btn" :class="{ 'mhub-btn--active': String(activeHub) === String(group.id) }"
        @click="selectConversation(group)" :title="group.name || 'Server'">
        <v-avatar size="26" color="secondary">
          <img v-if="getConversationAvatar(group)" :src="getConversationAvatar(group)" alt="" />
          <span v-else style="font-size:0.65rem;font-weight:700">{{ getConversationInitial(group) }}</span>
        </v-avatar>
      </button>
      <button class="mhub-btn" @click="openCreateGroupDialog" title="Create server">
        <v-icon size="18">mdi-plus</v-icon>
      </button>
    </div>

    <!-- Sidebar -->
    <div class="msg-sidebar">

      <!-- ── GROUP SERVER SIDEBAR ── -->
      <template v-if="activeHub !== 'dm' && selectedConversation">
        <!-- Server header -->
        <div class="server-header">
          <v-avatar size="32" color="secondary" class="mr-2">
            <img v-if="getConversationAvatar(selectedConversation)" :src="getConversationAvatar(selectedConversation)" alt="" />
            <span v-else style="font-size:0.7rem;font-weight:700">{{ getConversationInitial(selectedConversation) }}</span>
          </v-avatar>
          <div class="server-header-info">
            <span class="server-name">{{ selectedConversation.name }}</span>
            <span class="server-member-count">
              <v-icon size="9" class="mr-1">mdi-account-multiple-outline</v-icon>{{ t('messagesPage.members').replace('{count}', selectedConversation.participants?.length || 0) }}
            </span>
          </div>
          <v-spacer />
          <v-btn v-if="isGroupOwner" icon variant="text" size="x-small" @click="openServerSettings" :title="t('messagesPage.serverSettings')">
            <v-icon size="15">mdi-cog-outline</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="x-small" @click="openInviteDialog" :title="t('messagesPage.invitePeople')">
            <v-icon size="15">mdi-account-plus-outline</v-icon>
          </v-btn>
        </div>

        <!-- Channel list -->
        <div class="channels-area">
          <div v-if="loadingChannels" class="channel-empty">
            <v-progress-circular indeterminate size="14" width="2" color="primary" />
          </div>

          <template v-else>
            <!-- One block per category -->
            <div v-for="group in groupedChannels" :key="group.name" class="channel-category-group">

              <!-- Category header -->
              <div class="channel-category-row"
                :class="{ 'cat-drag-over': dragOverCat === group.name && draggingChannel && !group.channels.find(c => c.id === draggingChannel.id) }"
                @click="toggleCategory(group.name)"
                @dragover.prevent="onCatDragOver($event, group.name)"
                @drop.prevent="onDropOnCategory(group.name)">
                <v-icon size="11" class="category-arrow mr-1"
                  :style="{ transform: collapsedCats.has(group.name) ? 'rotate(-90deg)' : 'rotate(0deg)' }">
                  mdi-chevron-down
                </v-icon>
                <span class="category-label">{{ group.name }}</span>
                <v-btn v-if="isGroupOwner" icon variant="text" size="x-small" class="category-add-btn"
                  @click.stop="openAddChannelWithCategory(group.name)" :title="t('messagesPage.addChannel')">
                  <v-icon size="13">mdi-plus</v-icon>
                </v-btn>
              </div>

              <!-- Channels in this category -->
              <div v-if="!collapsedCats.has(group.name)">
                <div v-for="ch in group.channels" :key="ch.id"
                  class="channel-item"
                  :class="{
                    'channel-item--active': selectedChannel?.id === ch.id,
                    'channel-item--dragging': draggingChannel?.id === ch.id,
                    'channel-item--drag-over': dragOverChannelId === ch.id,
                  }"
                  :draggable="isGroupOwner && hasFinePointer"
                  @dragstart="onDragStart($event, ch)"
                  @dragover.prevent="onChannelDragOver($event, ch)"
                  @drop.prevent="onDropOnChannel(ch)"
                  @dragend="onDragEnd"
                  @click="selectChannel(ch)">
                  <v-icon v-if="isGroupOwner && hasFinePointer" size="12" class="drag-handle mr-1">mdi-drag-vertical</v-icon>
                  <v-icon size="14" class="mr-1">{{ ch.type === 'announcement' ? 'mdi-bullhorn-outline' : 'mdi-pound' }}</v-icon>
                  <span class="channel-item-name">{{ ch.name }}</span>
                </div>
              </div>
            </div>

            <!-- Add first channel button when empty -->
            <div v-if="channels.length === 0" class="channel-empty">
              {{ t('messagesPage.noChannels') }}
            </div>

            <!-- Add new category (owner only) -->
            <div v-if="isGroupOwner" class="channel-category-row add-category-row"
              @click="openAddChannelWithCategory('')">
              <v-icon size="13" class="mr-1" style="opacity:0.4">mdi-plus</v-icon>
              <span class="category-label" style="opacity:0.45">{{ t('messagesPage.addCategory') || 'Add Category' }}</span>
            </div>
          </template>
        </div>

        <!-- Bottom user section -->
        <div class="sidebar-user-bar">
          <v-avatar size="26" color="primary" class="mr-2">
            <img v-if="currentUser?.avatar_thumbnail" :src="currentUser.avatar_thumbnail" alt="" />
            <v-icon v-else size="14">mdi-account</v-icon>
          </v-avatar>
          <span class="sidebar-user-name">{{ currentUser?.name || t('messagesPage.you') }}</span>
          <v-spacer />
          <v-btn icon variant="text" size="x-small" to="/settings" :title="t('messagesPage.settings')">
            <v-icon size="13">mdi-cog-outline</v-icon>
          </v-btn>
        </div>
      </template>

      <!-- ── DM SIDEBAR ── -->
      <template v-else>
        <div class="sidebar-head-search">
          <v-text-field v-model="conversationSearch" prepend-inner-icon="mdi-magnify"
            :placeholder="t('common.search')" variant="outlined" density="compact"
            hide-details clearable class="sidebar-search" />
          <v-btn icon variant="text" size="small" class="ml-1 add-friend-btn" :title="t('messagesPage.addFriendBtn')"
            @click="showAddFriendDialog = true">
            <v-icon size="18">mdi-account-plus-outline</v-icon>
          </v-btn>
        </div>

        <div class="dm-sections">

          <!-- Direct Messages -->
          <div class="dm-section-label">{{ t('messagesPage.directMessages').toUpperCase() }}</div>
          <div class="dm-section-items">
            <div v-if="directConversations.filter(c => !conversationSearch || matchConversation(c, conversationSearch.toLowerCase())).length === 0"
              class="sidebar-empty-sm">
              <v-icon size="22" color="primary" class="mb-1">mdi-message-outline</v-icon>
              <p>{{ conversationSearch ? t('messagesPage.noMatches') : t('messagesPage.noDirectMessages') }}</p>
            </div>
            <button v-for="conv in directConversations.filter(c => !conversationSearch || matchConversation(c, conversationSearch.toLowerCase()))"
              :key="conv.id" type="button" class="conv-item"
              :class="{ 'conv-item--active': selectedConversation?.id === conv.id && activeHub === 'dm' }"
              @click="selectConversation(conv)">
              <div class="conv-avatar-wrap">
                <v-avatar size="34" color="primary" class="conv-avatar">
                  <img v-if="getConversationAvatar(conv)" :src="getConversationAvatar(conv)" alt="" />
                  <span v-else style="font-size:0.78rem;font-weight:700">{{ getConversationInitial(conv) }}</span>
                </v-avatar>
                <span v-if="isUserOnline(conv)" class="online-dot"></span>
              </div>
              <div class="conv-info">
                <div class="conv-name">{{ getConversationTitle(conv) }}</div>
                <div class="conv-preview">{{ getConversationPreview(conv) }}</div>
              </div>
            </button>
          </div>

        </div>
      </template>
    </div>

    <!-- Chat window -->
    <div class="msg-chat">
      <template v-if="selectedConversation">
        <!-- Chat header -->
        <div class="chat-head">
          <v-btn icon variant="text" size="small" class="d-md-none mr-1"
            @click="selectedConversation?.type === 'group' ? selectedChannel = null : selectedConversation = null">
            <v-icon>mdi-arrow-left</v-icon>
          </v-btn>
          <div class="chat-head-avatar-wrap mr-2">
            <template v-if="selectedConversation.type === 'group' && selectedChannel">
              <div class="channel-head-icon">
                <v-icon size="18" color="default">{{ selectedChannel.type === 'announcement' ? 'mdi-bullhorn-outline' : 'mdi-pound' }}</v-icon>
              </div>
            </template>
            <template v-else>
              <v-avatar size="36" color="primary">
                <img v-if="getConversationAvatar(selectedConversation)" :src="getConversationAvatar(selectedConversation)" alt="" />
                <span v-else style="font-size:0.8rem;font-weight:700">{{ getConversationInitial(selectedConversation) }}</span>
              </v-avatar>
              <span v-if="isUserOnline(selectedConversation)" class="online-dot online-dot--head"></span>
            </template>
          </div>
          <div class="chat-head-info">
            <span class="chat-head-name">
              <template v-if="selectedConversation.type === 'group' && selectedChannel">
                {{ selectedChannel.name }}
                <span class="chat-head-channel-type">{{ selectedChannel.type }}</span>
              </template>
              <template v-else>{{ getConversationTitle(selectedConversation) }}</template>
            </span>
            <span class="chat-head-sub">
              <template v-if="selectedConversation.type === 'group'">
                {{ selectedConversation.name }} · {{ t('messagesPage.members').replace('{count}', selectedConversation.participants?.length || 0) }}
              </template>
              <template v-else>
                <span v-if="isUserOnline(selectedConversation)" class="online-badge">{{ t('messagesPage.onlineBadge') }}</span>
                <span v-else>{{ getConversationSubtitle(selectedConversation) }}</span>
              </template>
            </span>
          </div>
          <v-spacer />
          <v-btn v-if="selectedConversation.type === 'group' && pinnedMessages.length > 0"
            icon variant="text" size="small"
            :class="{ 'head-btn--active': showPinnedPanel }"
            @click="showPinnedPanel = !showPinnedPanel" :title="t('messagesPage.pinnedMessagesBtn')">
            <v-icon size="18">mdi-pin-outline</v-icon>
          </v-btn>
          <v-btn v-if="selectedConversation.type === 'group'"
            icon variant="text" size="small"
            :class="{ 'members-btn--active': showMembersPanel }"
            @click="showMembersPanel = !showMembersPanel" :title="t('messagesPage.membersBtn')">
            <v-icon size="18">mdi-account-group-outline</v-icon>
          </v-btn>
        </div>
        <v-divider />

        <!-- Pinned messages panel overlay -->
        <transition name="pinned-slide">
          <div v-if="showPinnedPanel" class="pinned-panel">
            <div class="pinned-panel-head">
              <v-icon size="14" class="mr-1">mdi-pin-outline</v-icon>
              {{ t('messagesPage.pinnedMessagesHeader') }}
              <v-spacer />
              <v-btn icon size="x-small" variant="text" @click="showPinnedPanel = false"><v-icon size="14">mdi-close</v-icon></v-btn>
            </div>
            <div class="pinned-panel-body">
              <div v-if="pinnedMessages.length === 0" class="pinned-empty">{{ t('messagesPage.noPinnedMessages') }}</div>
              <div v-for="pm in pinnedMessages" :key="pm.id" class="pinned-item" @click="scrollToMsg(pm.id); showPinnedPanel = false">
                <v-avatar size="24" color="secondary" class="mr-2 flex-shrink-0">
                  <img v-if="pm.user?.avatar_thumbnail" :src="pm.user.avatar_thumbnail" alt="" />
                  <span v-else style="font-size:0.6rem;font-weight:700">{{ pm.user?.name?.[0] }}</span>
                </v-avatar>
                <div class="pinned-item-body">
                  <span class="pinned-item-author">{{ pm.user?.name }}</span>
                  <span class="pinned-item-content">{{ pm.content || t('messagesPage.drawingLabel') }}</span>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <!-- Chat body: messages + members panel -->
        <div class="chat-body">
          <div class="messages-area">
          <div class="msg-main">

        <!-- Messages -->
        <div class="msg-stream" ref="messagesContainer">
          <template v-for="(group, date) in groupedMessages" :key="date">
            <div class="date-divider"><span>{{ date }}</span></div>
            <div v-for="message in group" :key="message.id"
              :id="`msg-${message.id}`"
              class="msg-row"
              :class="{ 'msg-row--grouped': message._grouped, 'msg-row--pinned': message.is_pinned, 'msg-row--has-reply': !!message.reply_to }">

              <!-- Reply reference bar -->
              <div v-if="message.reply_to" class="msg-reply-ref" @click.stop="scrollToMsg(message.reply_to.id)">
                <v-avatar size="14" color="secondary" class="mr-1 flex-shrink-0">
                  <img v-if="message.reply_to.user?.avatar_thumbnail" :src="message.reply_to.user.avatar_thumbnail" alt="" />
                  <span v-else style="font-size:0.5rem">{{ message.reply_to.user?.name?.[0] }}</span>
                </v-avatar>
                <span class="msg-reply-ref-author">{{ message.reply_to.user?.name }}</span>
                <span class="msg-reply-ref-content">{{ message.reply_to.content ? message.reply_to.content.slice(0, 80) : t('messagesPage.drawingLabel') }}</span>
              </div>

              <!-- Avatar column: full avatar for first in group, hover-timestamp for grouped -->
              <div class="msg-avatar-col">
                <v-avatar v-if="!message._grouped" size="36" class="msg-avatar-img" color="secondary">
                  <img v-if="message.user?.avatar_thumbnail" :src="message.user.avatar_thumbnail" alt="" />
                  <span v-else style="font-size:0.72rem;font-weight:700">{{ message.user?.name?.[0] }}</span>
                </v-avatar>
                <span v-else class="msg-side-time">{{ formatTime(message.created_at) }}</span>
              </div>

              <!-- Message body -->
              <div class="msg-body">
                <div v-if="!message._grouped" class="msg-meta">
                  <span class="msg-author"
                    :class="{ 'msg-author--self': message.user_id === currentUserId }"
                    :style="getMsgAuthorColor(message.user_id) ? { color: getMsgAuthorColor(message.user_id) } : {}">
                    {{ message.user?.name }}
                  </span>
                  <span v-for="role in getMsgAuthorRoles(message.user_id)" :key="role.id"
                    class="msg-role-badge" :style="{ background: role.color + '33', color: role.color, borderColor: role.color + '66' }">
                    {{ role.name }}
                  </span>
                  <span class="msg-timestamp">{{ formatTime(message.created_at) }}</span>
                  <span v-if="message.edited_at" class="msg-edited">{{ t('messagesPage.edited') }}</span>
                  <span v-if="message.is_pinned" class="msg-pin-badge" title="Pinned"><v-icon size="10">mdi-pin</v-icon></span>
                </div>

                <!-- Inline edit mode -->
                <div v-if="editingMessageId === message.id" class="msg-edit-box">
                  <textarea class="msg-edit-textarea" v-model="editMessageContent"
                    @keydown.enter.exact.prevent="submitEdit"
                    @keydown.esc.prevent="cancelEdit"
                    rows="2" maxlength="5000" autofocus></textarea>
                  <div class="msg-edit-hint">
                    <span>{{ t('messagesPage.editHint') }}</span>
                    <button class="link-btn" @click="cancelEdit">{{ t('messagesPage.editCancel') }}</button>
                    <span> · </span>
                    <button class="link-btn link-btn--primary" @click="submitEdit">{{ t('messagesPage.editSave') }}</button>
                  </div>
                </div>

                <!-- Normal content (not editing) -->
                <template v-else>
                  <div v-if="message.drawing_data" class="msg-drawing-card" @click="openDrawingLightbox(message)">
                    <canvas
                      :ref="el => renderDrawing(el, message.drawing_data)"
                      :width="getPreviewDim(message.drawing_data).w"
                      :height="getPreviewDim(message.drawing_data).h"
                      class="msg-drawing" />
                    <div v-if="message.content && message.content !== '\uD83C\uDFA8 Drawing'" class="msg-drawing-caption">
                      {{ message.content }}
                    </div>
                  </div>
                  <p v-else-if="message.content" class="msg-content">{{ message.content }}</p>
                </template>

                <!-- Reactions bar -->
                <div v-if="getMessageReactions(message).length > 0" class="msg-reactions">
                  <button v-for="r in getMessageReactions(message)" :key="r.emoji"
                    class="reaction-chip" :class="{ 'reaction-chip--mine': r.mine }"
                    @click="toggleReaction(message, r.emoji)">
                    {{ r.emoji }} {{ r.count }}
                  </button>
                  <button class="reaction-add-btn" @click.stop="openEmojiPicker(message, $event)">
                    <v-icon size="12">mdi-emoticon-plus-outline</v-icon>
                  </button>
                </div>
              </div>

              <!-- Hover actions row -->
              <div class="msg-actions">
                <button class="msg-action-btn" @click.stop="openEmojiPicker(message, $event)" :title="t('messagesPage.react')">
                  <v-icon size="13">mdi-emoticon-outline</v-icon>
                </button>
                <button class="msg-action-btn" @click.stop="startReply(message)" :title="t('messagesPage.replyAction')">
                  <v-icon size="13">mdi-reply-outline</v-icon>
                </button>
                <button v-if="message.user_id === currentUserId" class="msg-action-btn"
                  @click.stop="startEdit(message)" :title="t('messagesPage.editAction')">
                  <v-icon size="13">mdi-pencil-outline</v-icon>
                </button>
                <button v-if="selectedConversation?.type === 'group' && Number(selectedConversation?.owner_id) === Number(currentUserId)"
                  class="msg-action-btn" :class="{ 'msg-action-btn--active': message.is_pinned }"
                  @click.stop="togglePin(message)" :title="t('messagesPage.pinAction')">
                  <v-icon size="13">mdi-pin-outline</v-icon>
                </button>
                <button v-if="message.user_id === currentUserId || (selectedConversation?.type === 'group' && Number(selectedConversation?.owner_id) === Number(currentUserId))"
                  class="msg-action-btn msg-action-btn--danger"
                  @click.stop="deleteMsg(message.id)" :title="t('messagesPage.deleteAction')">
                  <v-icon size="13">mdi-delete-outline</v-icon>
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Typing indicator -->
        <transition name="typing-fade">
          <div v-if="typingUsers.length > 0" class="typing-indicator">
            <div class="typing-dots">
              <span class="typing-dot"></span>
              <span class="typing-dot"></span>
              <span class="typing-dot"></span>
            </div>
            <span class="typing-text">
              {{ typingUsers.length > 1
                ? t('messagesPage.typingMany').replace('{name}', typingUsers[0].name).replace('{count}', typingUsers.length - 1)
                : t('messagesPage.typingOne').replace('{name}', typingUsers[0].name) }}
            </span>
          </div>
        </transition>

        <!-- Reply compose bar -->
        <transition name="reply-slide">
          <div v-if="replyingTo" class="reply-compose-bar">
            <v-icon size="14" color="primary" class="mr-1">mdi-reply</v-icon>
            <span>{{ t('messagesPage.replyingTo') }} <strong>{{ replyingTo.user?.name }}</strong></span>
            <span class="reply-compose-preview">{{ replyingTo.content ? replyingTo.content.slice(0, 60) : t('messagesPage.drawingLabel') }}</span>
            <v-spacer />
            <v-btn icon size="x-small" variant="text" @click="replyingTo = null"><v-icon size="14">mdi-close</v-icon></v-btn>
          </div>
        </transition>

          </div><!-- end .msg-main -->

        <!-- Drawing canvas -->
        <Transition name="canvas-slide">
          <div v-if="showDrawingCanvas && selectedConversation && (selectedConversation.type === 'group' ? !!selectedChannel : true)" class="drawing-panel dp--visible" @click="showSizePopup && (showSizePopup = false)">

            <!-- Toolbar: tools + brush types + undo/redo/close (single scrollable row) -->
            <div class="dp-top-bar" @click.stop>
              <div class="dp-tool-inner">
                <button class="dp-tb-btn" :class="{ active: msgTool === 'pen' }" @click="msgTool = 'pen'" :title="t('messagesPage.toolPen')">
                  <v-icon size="15">mdi-pencil</v-icon>
                </button>
                <button class="dp-tb-btn" :class="{ active: msgTool === 'eraser' }" @click="msgTool = 'eraser'" :title="t('messagesPage.toolEraser')">
                  <v-icon size="15">mdi-eraser</v-icon>
                </button>
                <button class="dp-tb-btn" :class="{ active: msgTool === 'bucket' }" @click="msgTool = 'bucket'" :title="t('messagesPage.toolFill')">
                  <v-icon size="15">mdi-format-color-fill</v-icon>
                </button>
                <div class="dp-tb-sep" />
                <template v-if="msgTool === 'pen'">
                  <button v-for="bt in msgBrushTypes" :key="bt.value"
                    class="dp-tb-btn" :class="{ active: msgBrushType === bt.value }"
                    @click="msgBrushType = bt.value" :title="bt.label">
                    <v-icon size="15">{{ bt.icon }}</v-icon>
                  </button>
                </template>
                <div class="dp-tb-spacer" />
                <div class="dp-tb-actions">
                  <button class="dp-tb-btn" :disabled="msgDrawPaths.length === 0" @click="clearCanvas" :title="t('messagesPage.clearDrawing')">
                    <v-icon size="15">mdi-delete-outline</v-icon>
                  </button>
                  <div class="dp-tb-sep" />
                  <button class="dp-tb-btn" :disabled="msgDrawPaths.length === 0" @click="msgUndoDraw" :title="t('messagesPage.undoTool')">
                    <v-icon size="15">mdi-undo</v-icon>
                  </button>
                  <button class="dp-tb-btn" :disabled="msgRedoStack.length === 0" @click="msgRedoDraw" :title="t('messagesPage.redoTool')">
                    <v-icon size="15">mdi-redo</v-icon>
                  </button>
                  <div class="dp-tb-sep" />
                  <button class="dp-tb-btn dp-tb-btn--send" @click="sendDrawing" :disabled="!msgDrawPaths.length" :title="t('messagesPage.sendDrawing')">
                    <v-icon size="15">mdi-send</v-icon>
                  </button>
                  <button class="dp-tb-btn dp-tb-btn--close" @click="showDrawingCanvas = false" title="Close">
                    <v-icon size="15">mdi-close</v-icon>
                  </button>
                </div><!-- end .dp-tb-actions -->
              </div><!-- end .dp-tool-inner -->
            </div><!-- end .dp-top-bar -->

            <!-- Canvas -->
            <div class="dp-canvas-wrap" ref="drawingStage">
              <canvas ref="drawingCanvas"
                :width="canvasDimensions.width" :height="canvasDimensions.height"
                @pointerdown.prevent.stop="startDrawing" @pointermove.prevent.stop="draw"
                @pointerup.prevent.stop="stopDrawing" @pointercancel.prevent.stop="stopDrawing"
                :style="{ cursor: msgTool === 'bucket' ? 'cell' : 'crosshair' }"
                class="drawing-canvas" />
              <transition name="hint-fade">
                <div v-if="msgDrawPaths.length === 0" class="dp-hint">
                  <v-icon size="28">mdi-gesture</v-icon>
                  <span>{{ t('messagesPage.drawHint') }}</span>
                </div>
              </transition>
            </div>

            <!-- Bottom section: color strip + caption + actions -->
            <div class="dp-bottom-section" @click.stop>
              <!-- Scrollable color + size strip -->
              <div class="dp-color-strip">
                <div class="dp-color-inner">
                  <template v-if="msgTool !== 'eraser'">
                    <label class="dp-fb-color" :title="t('messagesPage.customColor').replace('{color}', brushColor)">
                      <span class="dp-fb-swatch" :style="{ background: brushColor }" />
                      <input type="color" v-model="brushColor" class="dp-fb-color-input" />
                    </label>
                    <button v-for="c in msgColorPresets.slice(0, 8)" :key="c"
                      class="dp-fb-preset"
                      :style="{ background: c }"
                      :class="{ active: brushColor === c }"
                      @click="brushColor = c" />
                    <div class="dp-tb-sep" />
                  </template>
                  <div class="dp-fb-size-wrap" @click.stop>
                    <button class="dp-tb-btn" @click="showSizePopup = !showSizePopup" :title="t('messagesPage.brushSizeLabel').replace('{size}', brushSize)">
                      <span class="dp-fb-size-dot"
                        :style="{
                          width: Math.min(brushSize * 1.4, 16) + 'px',
                          height: Math.min(brushSize * 1.4, 16) + 'px',
                          background: msgTool === 'eraser' ? 'rgba(0,0,0,0.3)' : brushColor
                        }" />
                    </button>
                    <transition name="popup-fade">
                      <div v-if="showSizePopup" class="dp-size-popup">
                        <span class="dp-sp-label">{{ brushSize }}px</span>
                        <input type="range" v-model.number="brushSize" min="1" max="20" step="1" class="dp-sp-range" />
                      </div>
                    </transition>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </Transition>

        <!-- Composer -->
        <div class="composer">
          <v-text-field v-model="newMessage" :placeholder="composerPlaceholder"
            variant="outlined" hide-details density="compact" class="composer-input"
            @keyup.enter="sendMessage" @keydown.esc="replyingTo = null">
            <template #append-inner>
              <v-menu v-model="showComposerEmojiPicker" location="top" :close-on-content-click="false">
                <template #activator="{ props }">
                  <v-btn icon size="small" variant="text" v-bind="props" :title="'Add emoji'">
                    <v-icon size="16">mdi-emoticon-outline</v-icon>
                  </v-btn>
                </template>
                <div ref="composerEmojiPickerHost" class="composer-emoji-picker"></div>
              </v-menu>
              <v-btn icon size="small" variant="text" @click="showDrawingCanvas = true" title="Draw">
                <v-icon size="16">mdi-brush-outline</v-icon>
              </v-btn>
              <v-btn icon size="small" color="primary" variant="flat"
                @click="sendMessage" :disabled="!newMessage.trim()">
                <v-icon size="16">mdi-send</v-icon>
              </v-btn>
            </template>
          </v-text-field>
        </div>

          </div><!-- end .messages-area -->

          <!-- Members panel -->
          <transition name="members-slide">
            <div v-if="showMembersPanel && selectedConversation?.type === 'group'" class="members-panel">
              <div class="members-section" v-if="onlineMembers.length > 0">
                <div class="members-section-label">{{ t('messagesPage.onlineCount').replace('{n}', onlineMembers.length) }}</div>
                <div v-for="m in onlineMembers" :key="m.id" class="member-row">
                  <div class="member-avatar-wrap">
                    <v-avatar size="28" color="secondary">
                      <img v-if="m.avatar_thumbnail" :src="m.avatar_thumbnail" alt="" />
                      <span v-else style="font-size:0.65rem;font-weight:700">{{ m.name?.[0] }}</span>
                    </v-avatar>
                    <span class="member-online-dot"></span>
                  </div>
                  <div class="member-info">
                    <span class="member-name" :style="getMsgAuthorColor(m.id) ? { color: getMsgAuthorColor(m.id) } : {}">{{ m.name }}</span>
                    <div class="member-roles">
                      <span v-for="role in getMsgAuthorRoles(m.id)" :key="role.id"
                        class="member-role-tag" :style="{ background: role.color + '22', color: role.color }">{{ role.name }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="members-section" v-if="offlineMembers.length > 0">
                <div class="members-section-label">{{ t('messagesPage.offlineCount').replace('{n}', offlineMembers.length) }}</div>
                <div v-for="m in offlineMembers" :key="m.id" class="member-row member-row--offline">
                  <div class="member-avatar-wrap">
                    <v-avatar size="28" color="secondary">
                      <img v-if="m.avatar_thumbnail" :src="m.avatar_thumbnail" alt="" />
                      <span v-else style="font-size:0.65rem;font-weight:700">{{ m.name?.[0] }}</span>
                    </v-avatar>
                  </div>
                  <div class="member-info">
                    <span class="member-name">{{ m.name }}</span>
                    <div class="member-roles">
                      <span v-for="role in getMsgAuthorRoles(m.id)" :key="role.id"
                        class="member-role-tag" :style="{ background: role.color + '22', color: role.color }">{{ role.name }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </transition>

        </div><!-- end .chat-body -->

      </template>

      <!-- No conversation selected -->
      <div v-else class="chat-empty">
        <template v-if="activeHub !== 'dm' && selectedConversation">
          <v-icon size="40" color="primary" class="mb-3">mdi-pound</v-icon>
          <h3>{{ t('messagesPage.selectChannel') }}</h3>
          <p>{{ t('messagesPage.selectChannelText') }}</p>
        </template>
        <template v-else>
          <v-icon size="56" color="primary" class="mb-3">mdi-chat-outline</v-icon>
          <h3>{{ t('messagesPage.selectConversation') }}</h3>
          <p>{{ t('messagesPage.selectConversationText') }}</p>
        </template>
      </div>
    </div>

    <!-- Emoji picker (teleported to body) -->
    <teleport to="body">
      <div v-if="showEmojiPicker" class="emoji-picker-overlay" @click.self="showEmojiPicker = false"
        :style="{ top: emojiPickerPos.top + 'px', left: emojiPickerPos.left + 'px' }">
        <div class="emoji-picker-popup">
          <button v-for="emoji in QUICK_EMOJIS" :key="emoji" class="emoji-btn"
            @click="toggleReaction(emojiPickerMsg, emoji); showEmojiPicker = false">{{ emoji }}</button>
        </div>
      </div>
    </teleport>

    <!-- Create group dialog -->
    <v-dialog v-model="showCreateGroupDialog" max-width="520">
      <v-card class="group-dlg">
        <div class="dlg-head">
          <h3>{{ t('messagesPage.createGroupTitle') }}</h3>
          <v-btn icon variant="text" size="small" @click="showCreateGroupDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <div class="dlg-body">
          <v-text-field v-model="newGroupName" :label="t('messagesPage.groupName')"
            :placeholder="t('messagesPage.groupNamePlaceholder')" variant="outlined"
            hide-details="auto" class="mb-4" />
          <v-select v-model="selectedGroupMemberIds" :items="groupFriendOptions"
            item-title="title" item-value="value" :label="t('messagesPage.groupMembers')"
            variant="outlined" chips multiple closable-chips hide-details="auto"
            :loading="loadingFriends" :disabled="loadingFriends || groupFriendOptions.length === 0" />
          <p v-if="!loadingFriends && groupFriendOptions.length === 0"
            class="text-caption mt-2" style="color:var(--c-muted)">
            {{ t('messagesPage.noFriendsToGroup') }}
          </p>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="showCreateGroupDialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="primary" :loading="isCreatingGroup" :disabled="!canCreateGroup"
            @click="createGroupConversation">
            {{ t('messagesPage.createGroup') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Drawing lightbox -->
    <transition name="lb-fade">
      <div v-if="lightboxMsg" class="drawing-lightbox" @click.self="closeDrawingLightbox">
        <button class="lb-close" @click="closeDrawingLightbox"><v-icon>mdi-close</v-icon></button>
        <div class="lb-inner">
          <canvas
            ref="lightboxCanvasRef"
            :width="getLightboxDim(lightboxMsg.drawing_data).w"
            :height="getLightboxDim(lightboxMsg.drawing_data).h"
            class="lb-canvas" />
          <div v-if="lightboxMsg.content && lightboxMsg.content !== '\uD83C\uDFA8 Drawing'" class="lb-caption">
            {{ lightboxMsg.content }}
          </div>
        </div>
      </div>
    </transition>

    <!-- Create channel dialog -->
    <v-dialog v-model="showCreateChannelDialog" max-width="440">
      <v-card class="group-dlg">
        <div class="dlg-head">
          <h3>{{ t('messagesPage.newChannelTitle') }}</h3>
          <v-btn icon variant="text" size="small" @click="showCreateChannelDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <div class="dlg-body">
          <v-btn-toggle v-model="newChannelType" mandatory density="compact" class="mb-4 channel-type-toggle">
            <v-btn value="text" size="small">
              <v-icon size="14" class="mr-1">mdi-pound</v-icon>
              {{ t('common.text') || 'Text' }}
            </v-btn>
            <v-btn value="announcement" size="small">
              <v-icon size="14" class="mr-1">mdi-bullhorn-outline</v-icon>
              {{ t('common.announcement') || 'Announcement' }}
            </v-btn>
          </v-btn-toggle>
          <v-text-field v-model="newChannelName" :label="t('messagesPage.channelNameLabel')" :placeholder="t('messagesPage.channelNamePlaceholder')"
            variant="outlined" hide-details="auto" density="compact"
            @keyup.enter="createChannel" />
          <v-text-field v-model="newChannelCategory"
            :label="t('messagesPage.channelCategoryLabel') || 'Category'"
            :placeholder="'Text Channels'"
            variant="outlined" hide-details="auto" density="compact" class="mt-3" />
          <div v-if="existingCategories.length" class="mt-2 d-flex flex-wrap gap-1">
            <v-chip v-for="cat in existingCategories" :key="cat" size="x-small" density="compact"
              :color="newChannelCategory === cat ? 'primary' : undefined"
              style="cursor:pointer" @click="newChannelCategory = cat">
              {{ cat }}
            </v-chip>
          </div>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="showCreateChannelDialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="primary" :disabled="!newChannelName.trim()" @click="createChannel">
            {{ t('messagesPage.createChannelBtn') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Invite dialog -->
    <v-dialog v-model="showInviteDialog" max-width="460">
      <v-card class="group-dlg">
        <div class="dlg-head">
          <h3>
            <v-icon size="16" class="mr-1">mdi-account-plus-outline</v-icon>
            {{ t('messagesPage.inviteLinkTitle') }}
          </h3>
          <v-btn icon variant="text" size="small" @click="showInviteDialog = false; inviteToken = ''; inviteUrl = ''">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <div class="dlg-body">
          <p class="text-caption mb-3" style="color:var(--c-muted)">
            {{ t('messagesPage.shareInviteLink').replace('{name}', selectedConversation?.name || '') }}
          </p>
          <div v-if="generatingInvite" class="channel-empty">
            <v-progress-circular indeterminate size="20" width="2" color="primary" />
          </div>
          <div v-else-if="inviteUrl" class="invite-link-row">
            <code class="invite-link-code">{{ inviteUrl }}</code>
            <v-btn icon size="small" variant="tonal" color="primary" @click="copyInviteLink" title="Copy">
              <v-icon size="16">mdi-content-copy</v-icon>
            </v-btn>
          </div>
          <div v-else class="channel-empty">{{ t('messagesPage.couldNotGenerate') }}</div>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" size="small" @click="generateInvite" :loading="generatingInvite">
            <v-icon size="14" class="mr-1">mdi-refresh</v-icon>
            {{ t('messagesPage.newLinkBtn') }}
          </v-btn>
          <v-btn color="primary" @click="copyInviteLink" :disabled="!inviteUrl">
            <v-icon size="14" class="mr-1">mdi-content-copy</v-icon>
            {{ t('messagesPage.copyLinkBtn') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Server settings dialog -->
    <v-dialog v-model="showServerSettings" max-width="680" scrollable>
      <v-card class="group-dlg server-settings-dlg">
        <div class="dlg-head">
          <h3>
            <v-icon size="16" class="mr-1">mdi-cog-outline</v-icon>
            {{ selectedConversation?.name }} — {{ t('messagesPage.serverSettings') }}
          </h3>
          <v-btn icon variant="text" size="small" @click="showServerSettings = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>

        <v-tabs v-model="serverSettingsTab" class="settings-tabs" density="compact">
          <v-tab value="overview">{{ t('messagesPage.overviewTab') }}</v-tab>
          <v-tab value="channels">{{ t('messagesPage.channelsTab') }}</v-tab>
          <v-tab value="roles">{{ t('messagesPage.rolesTab') }}</v-tab>
          <v-tab value="members">{{ t('messagesPage.membersTab') }}</v-tab>
        </v-tabs>

        <v-card-text class="settings-body pa-4">

          <!-- Overview tab -->
          <div v-if="serverSettingsTab === 'overview'">
            <div class="overview-layout">

              <!-- Server icon drawing -->
              <div class="server-icon-section">
                <div class="settings-section-head mb-2"><span>{{ t('messagesPage.serverIconLabel') }}</span></div>
                <div class="server-icon-preview-wrap">
                  <canvas ref="serverIconCanvas" width="200" height="200" class="server-icon-canvas" />
                  <div v-if="serverIconPaths.length === 0" class="server-icon-empty">
                    <v-icon size="28" color="rgba(255,255,255,0.2)">mdi-image-outline</v-icon>
                  </div>
                </div>
                <div class="server-icon-actions mt-2">
                  <v-btn size="small" color="primary" prepend-icon="mdi-draw" @click="serverIconDrawDialogOpen = true">
                    Draw Icon
                  </v-btn>
                  <v-btn v-if="serverIconPaths.length > 0" size="small" variant="outlined" color="error"
                    prepend-icon="mdi-delete-outline" @click="clearServerIcon">
                    {{ t('messagesPage.clearDrawing') }}
                  </v-btn>
                </div>
              </div>

              <!-- Server details -->
              <div class="server-details-section">
                <div class="settings-section-head mb-2"><span>{{ t('messagesPage.serverNameLabel') }}</span></div>
                <v-text-field v-model="serverEditName" :label="t('messagesPage.serverNameLabel')"
                  variant="outlined" hide-details="auto" density="compact" class="mb-2"
                  :rules="[v => (v && v.trim().length >= 2) || t('messagesPage.serverNameMin')]" />
              </div>

            </div>

            <div class="d-flex justify-end mt-4">
              <v-btn color="primary" :loading="savingServerSettings"
                :disabled="!serverEditName.trim() || serverEditName.trim().length < 2"
                @click="saveServerOverview">
                {{ t('messagesPage.saveChanges') }}
              </v-btn>
            </div>
          </div>

          <!-- Channels tab -->
          <div v-if="serverSettingsTab === 'channels'">
            <div class="settings-section-head">
              <span>{{ t('messagesPage.channelsTab') }}</span>
              <v-btn size="small" variant="tonal" color="primary" @click="showCreateChannelDialog = true; showServerSettings = false">
                <v-icon size="14" class="mr-1">mdi-plus</v-icon>
                {{ t('messagesPage.newChannelTitle') }}
              </v-btn>
            </div>
            <div v-for="ch in channels" :key="ch.id">
              <div class="settings-row">
                <v-icon size="15" class="mr-2">{{ ch.type === 'announcement' ? 'mdi-bullhorn-outline' : 'mdi-pound' }}</v-icon>
                <span class="flex-grow-1">{{ ch.name }}</span>
                <v-chip size="x-small" class="mr-2" label>{{ ch.type }}</v-chip>
                <v-btn icon size="x-small" variant="text"
                  :color="channelPermEditId === ch.id ? 'primary' : ''"
                  @click="toggleChannelPerms(ch)" title="Channel visibility">
                  <v-icon size="13">{{ (ch.allowed_role_ids && ch.allowed_role_ids.length) ? 'mdi-lock' : 'mdi-lock-open-outline' }}</v-icon>
                </v-btn>
                <v-btn icon size="x-small" variant="text" color="error"
                  @click="deleteChannel(ch.id)" :disabled="channels.length <= 1" title="Delete">
                  <v-icon size="13">mdi-delete-outline</v-icon>
                </v-btn>
              </div>
              <!-- Channel role-restriction panel -->
              <div v-if="channelPermEditId === ch.id" class="ch-perm-panel">
                <span class="ch-perm-label">Visible to:</span>
                <div class="ch-perm-roles">
                  <v-chip v-if="!(ch.allowed_role_ids && ch.allowed_role_ids.length)" size="x-small" color="success" variant="flat" label>
                    <v-icon start size="10">mdi-earth</v-icon> Everyone
                  </v-chip>
                  <v-chip v-for="roleId in (ch.allowed_role_ids || [])" :key="roleId" size="x-small"
                    :style="getRoleById(roleId) ? { color: getRoleById(roleId).color, borderColor: getRoleById(roleId).color + '66' } : {}"
                    variant="outlined" label closable
                    @click:close="removeChannelRole(ch, roleId)">
                    {{ getRoleById(roleId)?.name || `Role #${roleId}` }}
                  </v-chip>
                </div>
                <v-menu>
                  <template #activator="{ props }">
                    <v-btn v-bind="props" size="x-small" variant="tonal" color="primary" class="mt-1">
                      <v-icon size="12" class="mr-1">mdi-plus</v-icon> Restrict to role
                    </v-btn>
                  </template>
                  <v-list density="compact" min-width="160">
                    <v-list-item v-if="serverRoles.length === 0">
                      <v-list-item-title class="text-caption" style="color:var(--c-muted)">No roles yet</v-list-item-title>
                    </v-list-item>
                    <v-list-item v-for="role in serverRoles" :key="role.id" @click="addChannelRole(ch, role.id)">
                      <template #prepend>
                        <span class="role-dot mr-2" :style="{ background: role.color }"></span>
                      </template>
                      <v-list-item-title>{{ role.name }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </div>
            </div>
          </div>

          <!-- Roles tab -->
          <div v-if="serverSettingsTab === 'roles'">
            <div class="settings-section-head">
              <span>{{ t('messagesPage.rolesTab') }}</span>
              <v-btn size="small" variant="tonal" color="primary" @click="showCreateRoleDialog = true">
                <v-icon size="14" class="mr-1">mdi-plus</v-icon>
                {{ t('messagesPage.newRoleTitle') }}
              </v-btn>
            </div>
            <div v-if="loadingRoles" class="channel-empty">
              <v-progress-circular indeterminate size="16" width="2" color="primary" />
            </div>
            <div v-for="role in serverRoles" :key="role.id" class="settings-row">
              <span class="role-dot mr-2" :style="{ background: role.color }"></span>
              <span class="flex-grow-1">{{ role.name }}</span>
              <div class="role-perms mr-2">
                <v-chip v-for="perm in (role.permissions || [])" :key="perm" size="x-small" class="mr-1 mb-1" label>{{ perm }}</v-chip>
              </div>
              <v-btn icon size="x-small" variant="text" color="error" @click="deleteRole(role.id)" title="Delete role">
                <v-icon size="13">mdi-delete-outline</v-icon>
              </v-btn>
            </div>
            <div v-if="!loadingRoles && serverRoles.length === 0" class="channel-empty">
              {{ t('messagesPage.noRoles') }}
            </div>
          </div>

          <!-- Members tab -->
          <div v-if="serverSettingsTab === 'members'">
            <div class="settings-section-head">
              <span>{{ t('messagesPage.membersTab') }} ({{ serverMembers.length }})</span>
            </div>
            <div v-if="loadingMembers" class="channel-empty">
              <v-progress-circular indeterminate size="16" width="2" color="primary" />
            </div>
            <div v-for="member in serverMembers" :key="member.id" class="settings-row">
              <v-avatar size="28" color="secondary" class="mr-2">
                <img v-if="member.avatar_thumbnail" :src="member.avatar_thumbnail" alt="" />
                <span v-else style="font-size:0.65rem;font-weight:700">{{ member.name?.[0] }}</span>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-body-2">{{ member.name }} <v-chip v-if="member.is_owner" size="x-small" color="warning" label class="ml-1">{{ t('messagesPage.ownerChip') }}</v-chip></div>
                <div class="text-caption" style="color:var(--c-muted)">@{{ member.username }}</div>
                <div class="member-roles-row">
                  <v-chip v-for="role in member.roles" :key="role.id"
                    size="x-small" class="mr-1 mt-1" :style="{ borderColor: role.color, color: role.color }"
                    variant="outlined" label closable
                    @click:close="revokeRole(member.id, role.id)">
                    {{ role.name }}
                  </v-chip>
                </div>
              </div>
              <!-- Assign role dropdown -->
              <v-menu location="bottom end">
                <template #activator="{ props }">
                  <v-btn v-bind="props" size="x-small" variant="text" icon title="Assign role">
                    <v-icon size="14">mdi-plus-circle-outline</v-icon>
                  </v-btn>
                </template>
                <v-list density="compact" min-width="160">
                  <v-list-item v-if="serverRoles.length === 0">
                    <v-list-item-title class="text-caption" style="color:var(--c-muted)">{{ t('messagesPage.noRoles') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item v-for="role in serverRoles" :key="role.id"
                    @click="assignRole(member.id, role.id)">
                    <template #prepend>
                      <span class="role-dot mr-2" :style="{ background: role.color }"></span>
                    </template>
                    <v-list-item-title>{{ role.name }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </div>

        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Create role dialog -->
    <v-dialog v-model="showCreateRoleDialog" max-width="460">
      <v-card class="group-dlg">
        <div class="dlg-head">
          <h3>{{ t('messagesPage.newRoleTitle') }}</h3>
          <v-btn icon variant="text" size="small" @click="showCreateRoleDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <div class="dlg-body">
          <v-text-field v-model="newRoleName" :label="t('messagesPage.roleNameLabel')" :placeholder="t('messagesPage.roleNamePlaceholder')"
            variant="outlined" hide-details="auto" density="compact" class="mb-3" />
          <div class="d-flex align-center mb-3 gap-3">
            <span class="text-caption" style="color:var(--c-muted)">{{ t('messagesPage.roleColor') }}</span>
            <label class="role-color-picker">
              <span class="role-color-swatch" :style="{ background: newRoleColor }"></span>
              <input type="color" v-model="newRoleColor" />
            </label>
            <span class="text-caption" :style="{ color: newRoleColor }">{{ newRoleName || t('messagesPage.rolePreviewFallback') }}</span>
          </div>
          <div class="text-caption mb-1" style="color:var(--c-muted)">{{ t('messagesPage.permissions') }}</div>
          <div class="role-perms-grid">
            <v-checkbox v-for="perm in ALL_PERMISSIONS" :key="perm"
              v-model="newRolePermissions" :value="perm" :label="perm.replace(/_/g, ' ')"
              density="compact" hide-details class="perm-check" />
          </div>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="showCreateRoleDialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="primary" :disabled="!newRoleName.trim()" @click="createRole">
            {{ t('messagesPage.createRoleBtn') }}
          </v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Add Friend dialog -->
    <v-dialog v-model="showAddFriendDialog" max-width="440">
      <v-card class="group-dlg">
        <div class="dlg-head">
          <h3>
            <v-icon size="16" class="mr-1">mdi-account-plus-outline</v-icon>
            {{ t('messagesPage.addFriendTitle') }}
          </h3>
          <v-btn icon variant="text" size="small" @click="showAddFriendDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <div class="dlg-body">
          <p class="text-caption mb-3" style="color:var(--c-muted)">{{ t('messagesPage.addFriendDesc') }}</p>
          <v-text-field v-model="addFriendQuery" :label="t('messagesPage.addFriendLabel')" :placeholder="t('messagesPage.addFriendPlaceholder')"
            variant="outlined" hide-details="auto" density="compact" class="mb-3"
            prepend-inner-icon="mdi-magnify" :loading="addFriendSearching"
            @input="searchUsersForFriend" clearable @click:clear="addFriendResults = []" />

          <div v-if="addFriendResults.length > 0" class="add-friend-results">
            <div v-for="user in addFriendResults" :key="user.id" class="add-friend-row">
              <v-avatar size="32" color="primary" class="mr-2">
                <span style="font-size:0.72rem;font-weight:700">{{ (user.name || 'U')[0].toUpperCase() }}</span>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-body-2 font-weight-medium">{{ user.name }}</div>
                <div class="text-caption" style="color:var(--c-muted)">@{{ user.username }}</div>
              </div>
              <v-btn v-if="!addFriendSent.has(user.id)" size="small" color="primary" variant="tonal"
                @click="sendFriendRequest(user)">
                {{ t('messagesPage.addBtn') }}
              </v-btn>
              <v-chip v-else size="small" color="success" variant="tonal">
                <v-icon start size="12">mdi-check</v-icon>{{ t('messagesPage.sentChip') }}
              </v-chip>
            </div>
          </div>

          <div v-if="addFriendQuery && !addFriendSearching && addFriendResults.length === 0"
            class="sidebar-empty-sm mt-2">
            <v-icon size="22" color="primary" class="mb-1">mdi-account-search-outline</v-icon>
            <p>{{ t('messagesPage.noUsersFound') }}</p>
          </div>
        </div>
        <div class="dlg-foot">
          <v-btn variant="text" @click="showAddFriendDialog = false; addFriendQuery = ''; addFriendResults = []">{{ t('common.close') || t('common.cancel') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="2500" location="bottom right">
      {{ snackbar.text }}
    </v-snackbar>

    <!-- Full draw tool dialog -->
    <DrawDialog v-model="showDrawDialog" @save="onMsgDrawingSave" />

    <!-- Server icon draw dialog (1:1) -->
    <DrawDialog v-model="serverIconDrawDialogOpen" :square-only="true" @save="onServerIconDrawSave" />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useI18n } from '@/composables/useI18n'
import { renderPaths } from '@/utils/renderPaths'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import data from '@emoji-mart/data'
import { Picker } from 'emoji-mart'
import DrawDialog from '@/components/DrawDialog'

const route = useRoute()
const router = useRouter()
const { t, language } = useI18n()

const conversations = ref([])
const conversationSearch = ref('')
const activeHub = ref('dm')
const selectedConversation = ref(null)
const messages = ref([])
const newMessage = ref('')
const messagesContainer = ref(null)
const showDrawingCanvas = ref(window.innerWidth < 960)
const showDrawDialog = ref(false)
const drawingStage = ref(null)
const drawingCanvas = ref(null)
// Seed immediately from localStorage so isGroupOwner works before the API responds
const _storedUser = (() => { try { return JSON.parse(localStorage.getItem('user') || 'null') } catch { return null } })()
const currentUserId = ref(_storedUser?.id ?? null)
const currentUser = ref(_storedUser ?? null)
const showCreateGroupDialog = ref(false)
const newGroupName = ref('')
const availableFriends = ref([])
const selectedGroupMemberIds = ref([])
const loadingFriends = ref(false)
const isCreatingGroup = ref(false)


// Drawing state
const isDrawing = ref(false)
const brushColor = ref('#000000')
const brushSize = ref(5)
const drawingData = ref([])

// Extended drawing tools (message panel)
const msgTool = ref('pen')
const msgBrushType = ref('pen')
const msgDrawPaths = ref([])
const msgCurrentPath = ref([])
const msgRedoStack = ref([])
const showSizePopup = ref(false)
const drawingCaption = ref('')
const lightboxMsg = ref(null)
const lightboxCanvasRef = ref(null)

// Presence & UX state
const typingUsers = ref([])
const onlineUserIds = ref([])
const showMembersPanel = ref(false)
let onlinePollInterval = null

// Group server state
const channels = ref([])
const selectedChannel = ref(null)
const loadingChannels = ref(false)
const showServerSettings = ref(false)
const serverSettingsTab = ref('channels')
const serverRoles = ref([])
const serverMembers = ref([])
const loadingRoles = ref(false)
const loadingMembers = ref(false)
const showCreateChannelDialog = ref(false)
const newChannelName = ref('')
const newChannelType = ref('text')
const newChannelCategory = ref('Text Channels')
const showCreateRoleDialog = ref(false)
const newRoleName = ref('')
const newRoleColor = ref('#99aab5')
const newRolePermissions = ref([])
const showInviteDialog = ref(false)
const inviteToken = ref('')
const inviteUrl = ref('')
const generatingInvite = ref(false)

// Server overview / customisation
const serverEditName = ref('')
const serverIconCanvas = ref(null)
const serverIconCtx = ref(null)
const serverIconIsDrawing = ref(false)
const serverIconPaths = ref([])
const serverIconCurrentPath = ref([])
const serverIconColor = ref('#111827')
const serverIconBrush = ref(4)
const serverIconPresets = ['#111827', '#2563eb', '#db2777', '#16a34a', '#ea580c', '#7c3aed']
const serverIconDrawDialogOpen = ref(false)
const savingServerSettings = ref(false)

// Channel categories & drag-and-drop
const collapsedCats = ref(new Set())
const draggingChannel = ref(null)
const dragOverChannelId = ref(null)
const dragOverCat = ref(null)

// Add Friend dialog
const showAddFriendDialog = ref(false)
const addFriendQuery = ref('')
const addFriendResults = ref([])
const addFriendSearching = ref(false)
const addFriendSent = ref(new Set())

// Discord-style chat features
const replyingTo = ref(null)
const editingMessageId = ref(null)
const editMessageContent = ref('')
const showEmojiPicker = ref(false)
const emojiPickerMsg = ref(null)
const emojiPickerPos = ref({ top: 0, left: 0 })
const showComposerEmojiPicker = ref(false)
const composerEmojiPickerHost = ref(null)
const showPinnedPanel = ref(false)
const QUICK_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '👀', '🎉', '🔥', '✅', '💯']
let composerEmojiPicker = null

// Snackbar
const snackbar = ref({ show: false, text: '', color: 'success' })
const showSnackbarMsg = (text, color = 'success') => { snackbar.value = { show: true, text, color } }

const ALL_PERMISSIONS = ['manage_channels', 'manage_roles', 'invite_members', 'send_messages', 'delete_messages', 'manage_members']

// ── Channel role-visibility permissions ──────────────────────────
const channelPermEditId = ref(null)

const getRoleById = (id) => serverRoles.value.find(r => r.id === id || r.id === Number(id))

const toggleChannelPerms = (ch) => {
  channelPermEditId.value = channelPermEditId.value === ch.id ? null : ch.id
  // Ensure roles are loaded
  if (channelPermEditId.value && selectedConversation.value && serverRoles.value.length === 0) {
    loadRoles(selectedConversation.value.id)
  }
}

const updateChannelAllowedRoles = async (ch, roleIds) => {
  if (!selectedConversation.value) return
  try {
    await api.put(`/groups/${selectedConversation.value.id}/channels/${ch.id}`, {
      allowed_role_ids: roleIds,
    })
    const idx = channels.value.findIndex(c => c.id === ch.id)
    if (idx !== -1) channels.value[idx] = { ...channels.value[idx], allowed_role_ids: roleIds }
  } catch (e) {
    showSnackbarMsg('Failed to update channel permissions', 'error')
  }
}

const addChannelRole = (ch, roleId) => {
  const current = ch.allowed_role_ids || []
  if (current.includes(roleId)) return
  updateChannelAllowedRoles(ch, [...current, roleId])
}

const removeChannelRole = (ch, roleId) => {
  updateChannelAllowedRoles(ch, (ch.allowed_role_ids || []).filter(id => id !== roleId))
}

const msgBrushTypes = [
  { value: 'pen',    label: 'Round',  icon: 'mdi-circle' },
  { value: 'square', label: 'Square', icon: 'mdi-square' },
  { value: 'marker', label: 'Marker', icon: 'mdi-marker' },
  { value: 'spray',  label: 'Spray',  icon: 'mdi-spray' },
]

const msgColorPresets = [
  '#000000', '#ffffff', '#ef4444', '#f97316', '#eab308',
  '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280'
]

const canvasDimensions = ref({ width: 960, height: 540 })

// Group messages by date; mark consecutive same-user messages as grouped (no repeated avatar/name)
const groupedMessages = computed(() => {
  const groups = {}
  let prevMsg = null
  let prevDateStr = null
  messages.value.forEach((msg) => {
    const date = new Date(msg.created_at)
    const dateStr = date.toLocaleDateString(language.value === 'lv' ? 'lv-LV' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
    if (!groups[dateStr]) {
      groups[dateStr] = []
      if (dateStr !== prevDateStr) prevMsg = null // reset grouping across date boundaries
      prevDateStr = dateStr
    }
    const isGrouped = prevMsg !== null &&
      prevMsg.user_id === msg.user_id &&
      (date - new Date(prevMsg.created_at)) < 5 * 60 * 1000
    groups[dateStr].push({ ...msg, _grouped: isGrouped })
    prevMsg = msg
  })
  return groups
})

let pollingInterval = null

// --- Discord feature computed & helpers ---
const pinnedMessages = computed(() => messages.value.filter(m => m.is_pinned))

const memberRoleMap = computed(() => {
  const map = {}
  serverMembers.value.forEach(m => { map[m.id] = m.roles || [] })
  return map
})

const getMsgAuthorColor = (userId) => {
  const roles = memberRoleMap.value[userId]
  return roles?.find(r => r.color && r.color !== '#99aab5')?.color || null
}

const getMsgAuthorRoles = (userId) => memberRoleMap.value[userId] || []

const onlineMembers = computed(() =>
  serverMembers.value.filter(m => onlineUserIds.value.includes(m.id))
)

const offlineMembers = computed(() =>
  serverMembers.value.filter(m => !onlineUserIds.value.includes(m.id))
)

const composerPlaceholder = computed(() => {
  if (replyingTo.value) return t('messagesPage.replyToPlaceholder').replace('{name}', replyingTo.value.user?.name || '')
  if (selectedChannel.value) return t('messagesPage.messageChannelPlaceholder').replace('{channel}', selectedChannel.value.name)
  return t('messagesPage.typeMessage')
})

const getMessageReactions = (msg) => {
  if (!msg.reactions) return []
  return Object.entries(msg.reactions).map(([emoji, userIds]) => ({
    emoji,
    count: userIds.length,
    mine: userIds.includes(currentUserId.value),
  })).filter(r => r.count > 0)
}

const startReply = (msg) => { replyingTo.value = msg }
const startEdit = (msg) => { editingMessageId.value = msg.id; editMessageContent.value = msg.content || '' }
const cancelEdit = () => { editingMessageId.value = null; editMessageContent.value = '' }

const submitEdit = async () => {
  if (!editMessageContent.value.trim()) return
  try {
    const res = await api.put(`/messages/${editingMessageId.value}`, { content: editMessageContent.value.trim() })
    const updated = res.data.data
    const idx = messages.value.findIndex(m => m.id === updated.id)
    if (idx !== -1) messages.value[idx] = { ...messages.value[idx], ...updated }
    cancelEdit()
  } catch (e) {
    showSnackbarMsg(t('messagesPage.editFailed'), 'error')
  }
}

const openEmojiPicker = (msg, event) => {
  emojiPickerMsg.value = msg
  const rect = event.target.getBoundingClientRect()
  emojiPickerPos.value = {
    top: rect.top - 60,
    left: Math.min(rect.left, window.innerWidth - 280),
  }
  showEmojiPicker.value = true
}

const toggleReaction = async (msg, emoji) => {
  try {
    const res = await api.post(`/messages/${msg.id}/react`, { emoji })
    const idx = messages.value.findIndex(m => m.id === msg.id)
    if (idx !== -1) messages.value[idx] = { ...messages.value[idx], reactions: res.data.reactions }
  } catch (e) {
    showSnackbarMsg(t('messagesPage.reactFailed'), 'error')
  }
}

const addEmojiToComposer = (emoji) => {
  newMessage.value += emoji
  showComposerEmojiPicker.value = false
}

const mountComposerEmojiPicker = () => {
  if (!composerEmojiPickerHost.value || composerEmojiPicker) return

  composerEmojiPicker = new Picker({
    data,
    theme: 'dark',
    perLine: 8,
    previewPosition: 'none',
    searchPosition: 'top',
    skinTonePosition: 'none',
    onEmojiSelect: (emoji) => addEmojiToComposer(emoji.native),
  })

  composerEmojiPickerHost.value.innerHTML = ''
  composerEmojiPickerHost.value.appendChild(composerEmojiPicker)
}

watch(showComposerEmojiPicker, async (isOpen) => {
  if (!isOpen) return
  await nextTick()
  mountComposerEmojiPicker()
})

const togglePin = async (msg) => {
  try {
    const res = await api.patch(`/messages/${msg.id}/pin`)
    const idx = messages.value.findIndex(m => m.id === msg.id)
    if (idx !== -1) messages.value[idx] = { ...messages.value[idx], is_pinned: res.data.is_pinned }
    showSnackbarMsg(res.data.is_pinned ? t('messagesPage.messagePinned') : t('messagesPage.messageUnpinned'))
  } catch (e) {
    showSnackbarMsg(t('messagesPage.pinFailed'), 'error')
  }
}

const deleteMsg = async (id) => {
  try {
    await api.delete(`/messages/${id}`)
    messages.value = messages.value.filter(m => m.id !== id)
  } catch (e) {
    showSnackbarMsg(t('messagesPage.deleteFailed'), 'error')
  }
}

const scrollToMsg = (msgId) => {
  const el = document.getElementById(`msg-${msgId}`)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' })
}
let echoInstance = null
let echoChannel = null

const clamp = (value, min, max) => Math.min(Math.max(value, min), max)

const getCanvasDimensions = () => {
  const stage = drawingStage.value
  if (stage && stage.clientWidth > 0) {
    const w = stage.clientWidth
    return { width: w, height: Math.round(w * 3 / 4) } // always 4:3
  }
  return { width: 800, height: 600 }
}


const resizeDrawingCanvas = () => {
  if (!drawingCanvas.value) return

  const nextDimensions = getCanvasDimensions()
  if (!nextDimensions.width || !nextDimensions.height) return

  canvasDimensions.value = nextDimensions
  drawingCanvas.value.width = nextDimensions.width
  drawingCanvas.value.height = nextDimensions.height
  msgRedrawCanvas()
}

const matchConversation = (conversation, query) => {
  if (!query) return true

  if (conversation.type === 'group') {
    const name = conversation.name?.toLowerCase() || ''
    const preview = conversation.latest_message?.content?.toLowerCase() || ''
    const participants = Array.isArray(conversation.participants)
      ? conversation.participants.map((user) => user?.name?.toLowerCase() || '').join(' ')
      : ''

    return [name, preview, participants].some((field) => field.includes(query))
  }

  const name = conversation.other_user?.name?.toLowerCase() || ''
  const username = conversation.other_user?.username?.toLowerCase() || ''
  const preview = conversation.latest_message?.content?.toLowerCase() || ''

  return [name, username, preview].some((field) => field.includes(query))
}

const directConversations = computed(() => {
  return conversations.value.filter((conversation) => conversation.type !== 'group')
})

const groupConversations = computed(() => {
  return conversations.value.filter((conversation) => conversation.type === 'group')
})

const groupFriendOptions = computed(() => {
  return availableFriends.value.map((friend) => ({
    title: `${friend.name} (@${friend.username})`,
    value: friend.id,
  }))
})

const canCreateGroup = computed(() => {
  return newGroupName.value.trim().length >= 2 && !isCreatingGroup.value
})

const isGroupOwner = computed(() => {
  if (!selectedConversation.value || selectedConversation.value.type !== 'group') return false
  return Number(selectedConversation.value.owner_id) === Number(currentUserId.value)
})

// Drag-and-drop only works with a fine pointer (mouse); disable on touch devices
const hasFinePointer = typeof window !== 'undefined' && window.matchMedia('(pointer: fine)').matches

// Channels grouped by category, preserving position order
const groupedChannels = computed(() => {
  const seen = []
  const map = {}
  ;[...channels.value].sort((a, b) => a.position - b.position).forEach(ch => {
    const cat = ch.category || 'Text Channels'
    if (!map[cat]) { map[cat] = []; seen.push(cat) }
    map[cat].push(ch)
  })
  return seen.map(name => ({ name, channels: map[name] }))
})

const existingCategories = computed(() => groupedChannels.value.map(g => g.name))

// ── Channel drag-and-drop ──────────────────────────────────────────
const toggleCategory = (name) => {
  const s = new Set(collapsedCats.value)
  s.has(name) ? s.delete(name) : s.add(name)
  collapsedCats.value = s
}

const openAddChannelWithCategory = (catName) => {
  newChannelCategory.value = catName
  showCreateChannelDialog.value = true
}

const onDragStart = (e, ch) => {
  draggingChannel.value = ch
  e.dataTransfer.effectAllowed = 'move'
}

const onDragEnd = () => {
  draggingChannel.value = null
  dragOverChannelId.value = null
  dragOverCat.value = null
}

const onChannelDragOver = (e, ch) => {
  if (!draggingChannel.value || draggingChannel.value.id === ch.id) return
  dragOverChannelId.value = ch.id
  dragOverCat.value = null
}

const onCatDragOver = (e, catName) => {
  dragOverCat.value = catName
  dragOverChannelId.value = null
}

const onDropOnChannel = (targetCh) => {
  const src = draggingChannel.value
  if (!src || src.id === targetCh.id) { onDragEnd(); return }
  const arr = [...channels.value]
  const fromIdx = arr.findIndex(c => c.id === src.id)
  const toIdx   = arr.findIndex(c => c.id === targetCh.id)
  if (fromIdx === -1 || toIdx === -1) { onDragEnd(); return }
  // Move channel and adopt target's category
  const [moved] = arr.splice(fromIdx, 1)
  moved.category = targetCh.category || 'Text Channels'
  arr.splice(toIdx, 0, moved)
  channels.value = arr.map((ch, i) => ({ ...ch, position: i }))
  onDragEnd()
  saveChannelOrder()
}

const onDropOnCategory = (catName) => {
  const src = draggingChannel.value
  if (!src) { onDragEnd(); return }
  const arr = [...channels.value]
  const fromIdx = arr.findIndex(c => c.id === src.id)
  if (fromIdx === -1) { onDragEnd(); return }
  // Assign category and move to end of that category's group
  const maxPos = arr
    .filter(c => (c.category || 'Text Channels') === catName && c.id !== src.id)
    .reduce((m, c) => Math.max(m, c.position), -1)
  arr.splice(fromIdx, 1)
  const insertAt = arr.findIndex(c => (c.category || 'Text Channels') === catName && c.position > maxPos)
  const insertIdx = insertAt === -1 ? arr.length : insertAt + 1
  arr.splice(insertIdx, 0, { ...src, category: catName })
  channels.value = arr.map((ch, i) => ({ ...ch, position: i }))
  onDragEnd()
  saveChannelOrder()
}

const saveChannelOrder = async () => {
  if (!selectedConversation.value) return
  try {
    await api.post(`/groups/${selectedConversation.value.id}/channels/reorder`, {
      channels: channels.value.map(ch => ({
        id: ch.id,
        position: ch.position,
        category: ch.category || 'Text Channels',
      })),
    })
  } catch (e) {
    console.error('Failed to save channel order:', e)
  }
}

const getConversationTitle = (conversation) => {
  if (!conversation) return ''
  if (conversation.type === 'group') {
    return conversation.name || t('messagesPage.unnamedGroup')
  }
  return conversation.other_user?.name || t('common.anonymous')
}

const getConversationSubtitle = (conversation) => {
  if (!conversation) return ''
  if (conversation.type === 'group') {
    const count = Array.isArray(conversation.participants) ? conversation.participants.length : 0
    return t('messagesPage.members', { count })
  }

  return conversation.other_user?.username ? `@${conversation.other_user.username}` : ''
}

const getConversationPreview = (conversation) => {
  return conversation?.latest_message?.content || t('messagesPage.startChatting')
}

const getConversationAvatar = (conversation) => {
  if (!conversation) return ''
  if (conversation.type === 'group') {
    return conversation.avatar_thumbnail || ''
  }
  return conversation.other_user?.avatar_thumbnail || ''
}

const getConversationInitial = (conversation) => {
  return getConversationTitle(conversation)?.charAt(0)?.toUpperCase() || '#'
}

// Get current user
const getCurrentUser = async () => {
  try {
    const response = await api.get('/user')
    currentUserId.value = response.data.user.id
    currentUser.value = response.data.user
  } catch (error) {
    console.error('Failed to get current user:', error)
  }
}

// Load conversations
const loadConversations = async () => {
  try {
    const response = await api.get('/conversations')
    conversations.value = response.data.conversations
    
    // If there's a conversation ID in the route, select it
    if (route.params.id) {
      const conv = conversations.value.find(c => c.id == route.params.id)
      if (conv) await selectConversation(conv)
    }
  } catch (error) {
    console.error('Failed to load conversations:', error)
  }
}

const loadFriends = async () => {
  loadingFriends.value = true
  try {
    const response = await api.get('/friends')
    availableFriends.value = response.data.friends || []
  } catch (error) {
    console.error('Failed to load friends:', error)
  } finally {
    loadingFriends.value = false
  }
}


const openCreateGroupDialog = async () => {
  if (availableFriends.value.length === 0 && !loadingFriends.value) {
    await loadFriends()
  }

  showCreateGroupDialog.value = true
}

const resetGroupDialog = () => {
  newGroupName.value = ''
  selectedGroupMemberIds.value = []
}

const createGroupConversation = async () => {
  if (!canCreateGroup.value) return

  isCreatingGroup.value = true
  try {
    const response = await api.post('/conversations/group', {
      name: newGroupName.value.trim(),
      participant_ids: selectedGroupMemberIds.value,
    })

    const createdId = response.data?.conversation?.id

    await loadConversations()

    if (createdId) {
      const created = conversations.value.find((conversation) => String(conversation.id) === String(createdId))
      if (created) {
        await selectConversation(created)
      }
    }

    showCreateGroupDialog.value = false
    resetGroupDialog()
  } catch (error) {
    console.error('Failed to create group:', error)
  } finally {
    isCreatingGroup.value = false
  }
}

// ── Group server functions ────────────────────────────────────────
const loadChannels = async (groupId) => {
  loadingChannels.value = true
  try {
    const res = await api.get(`/groups/${groupId}/channels`)
    channels.value = res.data.channels || []
  } catch (e) {
    console.error('Failed to load channels:', e)
  } finally {
    loadingChannels.value = false
  }
}

const selectChannel = async (channel) => {
  selectedChannel.value = channel
  if (selectedConversation.value) {
    await loadMessages(selectedConversation.value.id, channel.id)
  }
}

const createChannel = async () => {
  if (!newChannelName.value.trim() || !selectedConversation.value) return
  try {
    await api.post(`/groups/${selectedConversation.value.id}/channels`, {
      name: newChannelName.value.trim(),
      type: newChannelType.value,
      category: newChannelCategory.value.trim() || 'Text Channels',
    })
    newChannelName.value = ''
    newChannelType.value = 'text'
    newChannelCategory.value = 'Text Channels'
    showCreateChannelDialog.value = false
    await loadChannels(selectedConversation.value.id)
  } catch (e) {
    console.error('Failed to create channel:', e)
  }
}

const deleteChannel = async (channelId) => {
  if (!selectedConversation.value) return
  try {
    await api.delete(`/groups/${selectedConversation.value.id}/channels/${channelId}`)
    await loadChannels(selectedConversation.value.id)
    if (selectedChannel.value?.id === channelId) {
      selectedChannel.value = channels.value[0] || null
      if (selectedChannel.value) await loadMessages(selectedConversation.value.id, selectedChannel.value.id)
      else messages.value = []
    }
  } catch (e) {
    console.error('Failed to delete channel:', e)
  }
}

const loadRoles = async (groupId) => {
  loadingRoles.value = true
  try {
    const res = await api.get(`/groups/${groupId}/roles`)
    serverRoles.value = res.data.roles || []
  } catch (e) {
    console.error('Failed to load roles:', e)
  } finally {
    loadingRoles.value = false
  }
}

const loadMembers = async (groupId) => {
  loadingMembers.value = true
  try {
    const res = await api.get(`/groups/${groupId}/members`)
    serverMembers.value = res.data.members || []
  } catch (e) {
    console.error('Failed to load members:', e)
  } finally {
    loadingMembers.value = false
  }
}

const createRole = async () => {
  if (!newRoleName.value.trim() || !selectedConversation.value) return
  try {
    await api.post(`/groups/${selectedConversation.value.id}/roles`, {
      name: newRoleName.value.trim(),
      color: newRoleColor.value,
      permissions: newRolePermissions.value,
    })
    newRoleName.value = ''
    newRoleColor.value = '#99aab5'
    newRolePermissions.value = []
    showCreateRoleDialog.value = false
    await loadRoles(selectedConversation.value.id)
  } catch (e) {
    console.error('Failed to create role:', e)
  }
}

const deleteRole = async (roleId) => {
  if (!selectedConversation.value) return
  try {
    await api.delete(`/groups/${selectedConversation.value.id}/roles/${roleId}`)
    await loadRoles(selectedConversation.value.id)
  } catch (e) {
    console.error('Failed to delete role:', e)
  }
}

const assignRole = async (userId, roleId) => {
  if (!selectedConversation.value) return
  try {
    await api.post(`/groups/${selectedConversation.value.id}/roles/${roleId}/assign/${userId}`)
    await loadMembers(selectedConversation.value.id)
  } catch (e) {
    console.error('Failed to assign role:', e)
  }
}

const revokeRole = async (userId, roleId) => {
  if (!selectedConversation.value) return
  try {
    await api.delete(`/groups/${selectedConversation.value.id}/roles/${roleId}/revoke/${userId}`)
    await loadMembers(selectedConversation.value.id)
  } catch (e) {
    console.error('Failed to revoke role:', e)
  }
}

const openInviteDialog = async () => {
  showInviteDialog.value = true
  if (!inviteToken.value) await generateInvite()
}

const generateInvite = async () => {
  if (!selectedConversation.value) return
  generatingInvite.value = true
  try {
    const res = await api.post(`/groups/${selectedConversation.value.id}/invites`)
    inviteToken.value = res.data.token
    inviteUrl.value = res.data.url
  } catch (e) {
    console.error('Failed to generate invite:', e)
  } finally {
    generatingInvite.value = false
  }
}

const copyInviteLink = async () => {
  if (!inviteUrl.value) return
  try {
    await navigator.clipboard.writeText(inviteUrl.value)
    showSnackbarMsg(t('messagesPage.linkCopied'))
  } catch (e) {
    console.error('Failed to copy:', e)
  }
}

const openServerSettings = async () => {
  showServerSettings.value = true
  serverSettingsTab.value = 'overview'
  serverEditName.value = selectedConversation.value?.name || ''
  serverIconPaths.value = []
  serverIconCurrentPath.value = []
  if (selectedConversation.value) {
    await Promise.all([
      loadRoles(selectedConversation.value.id),
      loadMembers(selectedConversation.value.id),
    ])
  }
}

// ── Server icon drawing ──────────────────────────────────────────
const initServerIconCanvas = () => {
  if (!serverIconCanvas.value) return
  serverIconCtx.value = serverIconCanvas.value.getContext('2d')
  redrawServerIcon()
}

const redrawServerIcon = () => {
  if (!serverIconCtx.value || !serverIconCanvas.value) return
  const ctx = serverIconCtx.value
  const { width, height } = serverIconCanvas.value
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, width, height)
  serverIconPaths.value.forEach((path) => {
    if (!path.points?.length) return
    ctx.strokeStyle = path.color
    ctx.lineWidth = path.width
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
    ctx.beginPath()
    path.points.forEach((pt, i) => {
      if (i === 0) ctx.moveTo(pt.x, pt.y)
      else ctx.lineTo(pt.x, pt.y)
    })
    ctx.stroke()
  })
}

const getServerIconCoords = (event) => {
  if (!serverIconCanvas.value) return { x: 0, y: 0 }
  const rect = serverIconCanvas.value.getBoundingClientRect()
  const src = event.touches?.[0] ?? event
  return { x: src.clientX - rect.left, y: src.clientY - rect.top }
}

const startServerIconDraw = (event) => {
  if (!serverIconCtx.value) return
  serverIconIsDrawing.value = true
  const pt = getServerIconCoords(event)
  const newPath = { color: serverIconColor.value, width: serverIconBrush.value, points: [pt] }
  serverIconPaths.value.push(newPath)
  serverIconCurrentPath.value = newPath.points
}

const drawServerIconStroke = (event) => {
  if (!serverIconIsDrawing.value || !serverIconCtx.value) return
  const pt = getServerIconCoords(event)
  serverIconCurrentPath.value.push(pt)
  const ctx = serverIconCtx.value
  const pts = serverIconCurrentPath.value
  const prev = pts[pts.length - 2]
  if (!prev) return
  ctx.strokeStyle = serverIconColor.value
  ctx.lineWidth = serverIconBrush.value
  ctx.lineCap = 'round'
  ctx.lineJoin = 'round'
  ctx.beginPath()
  ctx.moveTo(prev.x, prev.y)
  ctx.lineTo(pt.x, pt.y)
  ctx.stroke()
}

const stopServerIconDraw = () => { serverIconIsDrawing.value = false }

const undoServerIcon = () => {
  if (!serverIconPaths.value.length) return
  serverIconPaths.value.pop()
  redrawServerIcon()
}

const clearServerIcon = () => {
  serverIconPaths.value = []
  serverIconCurrentPath.value = []
  redrawServerIcon()
}

const onServerIconDrawSave = ({ dataUrl }) => {
  if (!serverIconCanvas.value) return
  const img = new Image()
  img.onload = () => {
    const ctx = serverIconCanvas.value.getContext('2d')
    ctx.fillStyle = '#fff'
    ctx.fillRect(0, 0, 200, 200)
    ctx.drawImage(img, 0, 0, 200, 200)
  }
  img.src = dataUrl
  // Mark as having content so the empty hint hides
  serverIconPaths.value = [{ _placeholder: true }]
}

const saveServerOverview = async () => {
  if (!selectedConversation.value) return
  savingServerSettings.value = true
  try {
    const payload = { name: serverEditName.value.trim() }
    if (serverIconCanvas.value) {
      payload.avatar_thumbnail = serverIconCanvas.value.toDataURL('image/png', 0.75)
    }
    const res = await api.put(`/groups/${selectedConversation.value.id}`, payload)
    const updated = res.data.conversation
    selectedConversation.value.name = updated.name
    selectedConversation.value.avatar_thumbnail = updated.avatar_thumbnail
    const idx = conversations.value.findIndex(c => c.id === updated.id)
    if (idx !== -1) {
      conversations.value[idx] = { ...conversations.value[idx], name: updated.name, avatar_thumbnail: updated.avatar_thumbnail }
    }
    showSnackbarMsg(t('messagesPage.serverSettingsSaved') || 'Server settings saved!')
  } catch (e) {
    console.error('Failed to save server settings:', e)
    showSnackbarMsg(e.response?.data?.message || t('settingsPage.saveFailed'), 'error')
  } finally {
    savingServerSettings.value = false
  }
}

watch([showServerSettings, serverSettingsTab], async ([show, tab]) => {
  if (show && tab === 'overview') {
    // Two ticks: first for dialog mount, second for canvas inside v-if
    await nextTick()
    await nextTick()
    // Extra safety for Vuetify dialog transition
    setTimeout(() => initServerIconCanvas(), 80)
  }
})

// ── Add Friend ────────────────────────────────────────────────────
let addFriendTimer = null
const searchUsersForFriend = () => {
  clearTimeout(addFriendTimer)
  if (!addFriendQuery.value?.trim()) { addFriendResults.value = []; return }
  addFriendTimer = setTimeout(async () => {
    addFriendSearching.value = true
    try {
      const res = await api.get('/users/search', { params: { query: addFriendQuery.value.trim() } })
      addFriendResults.value = res.data.users || []
    } catch { addFriendResults.value = [] }
    finally { addFriendSearching.value = false }
  }, 300)
}

const sendFriendRequest = async (user) => {
  try {
    await api.post('/friends/request', { username: user.username })
    addFriendSent.value = new Set([...addFriendSent.value, user.id])
    showSnackbarMsg(t('friendsPage.requestSent'))
  } catch (e) {
    showSnackbarMsg(e.response?.data?.message || t('friendsPage.actionFailed'), 'error')
  }
}

// ── Echo / WebSocket helpers ─────────────────────────────────────
const getAuthToken = () => localStorage.getItem('token') ?? ''

const ensureEcho = () => {
  if (echoInstance) return echoInstance
  window.Pusher = Pusher
  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_URL.replace(/\/api$/, '')}/api/broadcasting/auth`,
    auth: { headers: { Authorization: `Bearer ${getAuthToken()}` } },
  })
  return echoInstance
}

const subscribeToConversation = (conversationId) => {
  leaveConversationChannel()
  const echo = ensureEcho()
  // Update auth token in case it changed (guard against uninitialized connector)
  const authConfig = echo.connector?.pusher?.config?.auth
  if (authConfig?.headers) {
    authConfig.headers.Authorization = `Bearer ${getAuthToken()}`
  }
  echoChannel = echo.private(`conversation.${conversationId}`)
    .listen('.MessageSent', (e) => {
      if (e.message && !messages.value.find(m => m.id === e.message.id)) {
        messages.value.push(e.message)
        nextTick(() => scrollToBottom())
      }
    })
}

const leaveConversationChannel = () => {
  if (echoInstance && echoChannel) {
    echoInstance.leave(`conversation.${selectedConversation.value?.id}`)
    echoChannel = null
  }
}

// Select conversation
const selectConversation = async (conversation) => {
  activeHub.value = conversation.type === 'group' ? String(conversation.id) : 'dm'
  selectedConversation.value = conversation
  selectedChannel.value = null
  inviteToken.value = ''
  inviteUrl.value = ''
  if (String(route.params.id ?? '') !== String(conversation.id)) {
    router.replace(`/messages/${conversation.id}`)
  }

  if (conversation.type === 'group') {
    await loadChannels(conversation.id)
    loadMembers(conversation.id)
    messages.value = []
    // Auto-select the first text channel so the chat window is immediately usable
    const firstTextChannel = channels.value.find(ch => ch.type !== 'announcement') ?? channels.value[0]
    if (firstTextChannel) await selectChannel(firstTextChannel)
  } else {
    await loadMessages(conversation.id)
  }

  subscribeToConversation(conversation.id)
  startPolling()
}

watch(showDrawingCanvas, async (isVisible) => {
  if (!isVisible) return

  await nextTick()
  resizeDrawingCanvas()
})

// Load messages
const loadMessages = async (conversationId, channelId = null) => {
  try {
    const params = channelId ? { channel_id: channelId } : {}
    const response = await api.get(`/conversations/${conversationId}/messages`, { params })
    messages.value = response.data.messages
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to load messages:', error)
  }
}

// Send text message
const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedConversation.value) return
  if (selectedConversation.value.type === 'group' && !selectedChannel.value) return

  try {
    const payload = { content: newMessage.value }
    if (selectedChannel.value) payload.channel_id = selectedChannel.value.id
    if (replyingTo.value) payload.reply_to_id = replyingTo.value.id

    const response = await api.post(`/conversations/${selectedConversation.value.id}/messages`, payload)

    if (response.data?.data) {
      messages.value.push(response.data.data)
    }

    newMessage.value = ''
    replyingTo.value = null
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

// Drawing functions
const msgGetCanvasPos = (e) => {
  if (!drawingCanvas.value) return { x: 0, y: 0 }
  const rect = drawingCanvas.value.getBoundingClientRect()
  const pe = e.touches?.[0] ?? e
  return {
    x: clamp(pe.clientX - rect.left, 0, rect.width),
    y: clamp(pe.clientY - rect.top, 0, rect.height),
  }
}

const msgFloodFill = (px, py) => {
  if (!drawingCanvas.value) return
  const c = drawingCanvas.value
  const ctx = c.getContext('2d')
  const fillHex = brushColor.value
  const [fr, fg, fb] = [parseInt(fillHex.slice(1,3),16), parseInt(fillHex.slice(3,5),16), parseInt(fillHex.slice(5,7),16)]
  const imgData = ctx.getImageData(0, 0, c.width, c.height)
  const data = imgData.data
  const si = (py * c.width + px) * 4
  const [tr, tg, tb] = [data[si], data[si+1], data[si+2]]
  if (tr === fr && tg === fg && tb === fb) return
  const ok = (i) => Math.abs(data[i]-tr)<32 && Math.abs(data[i+1]-tg)<32 && Math.abs(data[i+2]-tb)<32
  const vis = new Uint8Array(c.width * c.height)
  const stack = [[px, py]]
  while (stack.length) {
    const [x, y] = stack.pop()
    if (x<0||x>=c.width||y<0||y>=c.height) continue
    const vi = y*c.width+x; if (vis[vi]) continue
    const pi = vi*4; if (!ok(pi)) continue
    vis[vi]=1; data[pi]=fr; data[pi+1]=fg; data[pi+2]=fb; data[pi+3]=255
    stack.push([x+1,y],[x-1,y],[x,y+1],[x,y-1])
  }
  ctx.putImageData(imgData, 0, 0)
  msgDrawPaths.value.push({ type:'fill', x:px, y:py, color:fillHex })
  msgRedoStack.value = []
}

const msgRedrawCanvas = () => {
  if (!drawingCanvas.value) return
  const c = drawingCanvas.value
  const ctx = c.getContext('2d')
  renderPaths(ctx, c, msgDrawPaths.value)
}

const msgUndoDraw = () => {
  if (!msgDrawPaths.value.length) return
  msgRedoStack.value.push(msgDrawPaths.value.pop())
  msgRedrawCanvas()
}

const msgRedoDraw = () => {
  if (!msgRedoStack.value.length) return
  msgDrawPaths.value.push(msgRedoStack.value.pop())
  msgRedrawCanvas()
}

const startDrawing = (e) => {
  if (!drawingCanvas.value) return
  const { x, y } = msgGetCanvasPos(e)
  if (msgTool.value === 'bucket') {
    msgFloodFill(Math.round(x), Math.round(y))
    return
  }
  isDrawing.value = true
  drawingCanvas.value.setPointerCapture?.(e.pointerId)
  msgCurrentPath.value = [{ x, y }]
  // legacy drawingData kept in sync for sendDrawing
  drawingData.value.push({ type:'start', x: x/drawingCanvas.value.width, y: y/drawingCanvas.value.height, color: brushColor.value, size: brushSize.value })
}

const draw = (e) => {
  if (!isDrawing.value || !drawingCanvas.value) return
  const c = drawingCanvas.value
  const ctx = c.getContext('2d')
  const { x, y } = msgGetCanvasPos(e)
  const last = msgCurrentPath.value[msgCurrentPath.value.length - 1]
  if (!last) return
  const bType = msgBrushType.value
  const color = brushColor.value
  const width = brushSize.value

  if (bType === 'spray') {
    const density = 18, radius = width * 3
    const dots = []
    ctx.fillStyle = color; ctx.globalAlpha = 0.8
    for (let i = 0; i < density; i++) {
      const angle = Math.random() * Math.PI * 2
      const r = Math.sqrt(Math.random()) * radius
      const dot = { x: x + r*Math.cos(angle), y: y + r*Math.sin(angle), r: Math.max(0.5, width*0.18) }
      dots.push(dot)
      ctx.beginPath(); ctx.arc(dot.x, dot.y, dot.r, 0, Math.PI*2); ctx.fill()
    }
    ctx.globalAlpha = 1
    msgCurrentPath.value.push({ x, y, dots })
  } else {
    ctx.globalAlpha = bType === 'marker' ? 0.35 : 1
    ctx.strokeStyle = bType === 'eraser' ? '#FFFFFF' : color
    ctx.lineWidth = bType === 'eraser' ? width * 3 : (bType === 'marker' ? width * 2.5 : width)
    ctx.lineCap = bType === 'square' ? 'square' : 'round'
    ctx.lineJoin = bType === 'square' ? 'miter' : 'round'
    ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(x, y); ctx.stroke()
    ctx.globalAlpha = 1
    msgCurrentPath.value.push({ x, y })
  }
  drawingData.value.push({ type:'line', x: x/c.width, y: y/c.height, color, size: width })
}

const stopDrawing = () => {
  if (isDrawing.value && msgCurrentPath.value.length > 1) {
    const bType = msgTool.value === 'eraser' ? 'eraser' : msgBrushType.value
    msgDrawPaths.value.push({
      type: 'stroke',
      points: msgCurrentPath.value.map(p => ({ ...p })),
      color: bType === 'eraser' ? '#FFFFFF' : brushColor.value,
      width: bType === 'eraser' ? brushSize.value * 3 : (bType === 'marker' ? brushSize.value * 2.5 : brushSize.value),
      brushType: bType
    })
    msgRedoStack.value = []
  }
  msgCurrentPath.value = []
  isDrawing.value = false
}

const clearCanvas = () => {
  if (!drawingCanvas.value) return
  const c = drawingCanvas.value
  const ctx = c.getContext('2d')
  ctx.fillStyle = '#FFFFFF'; ctx.fillRect(0, 0, c.width, c.height)
  drawingData.value = []
  msgDrawPaths.value = []
  msgCurrentPath.value = []
  msgRedoStack.value = []
  drawingCaption.value = ''
}

const sendDrawing = async () => {
  if (!selectedConversation.value) return
  if (selectedConversation.value.type === 'group' && !selectedChannel.value) return
  if (msgDrawPaths.value.length === 0) return
  try {
    const payload = {
      content: newMessage.value.trim() || '\uD83C\uDFA8 Drawing',
      drawing_data: {
        paths: msgDrawPaths.value,
        width: drawingCanvas.value?.width,
        height: drawingCanvas.value?.height
      }
    }
    if (selectedChannel.value) payload.channel_id = selectedChannel.value.id
    const response = await api.post(`/conversations/${selectedConversation.value.id}/messages`, payload)
    if (response.data?.data) messages.value.push(response.data.data)
    newMessage.value = ''
    clearCanvas()
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send drawing:', error)
  }
}

const openDrawingLightbox = (msg) => { lightboxMsg.value = msg }
const closeDrawingLightbox = () => { lightboxMsg.value = null }

// Handler for DrawDialog saves — sends with the same format as the simple panel
const onMsgDrawingSave = async ({ paths: drawPaths, width, height, caption }) => {
  if (!selectedConversation.value) return
  if (selectedConversation.value.type === 'group' && !selectedChannel.value) return
  try {
    const payload = {
      content: caption || '\uD83C\uDFA8 Drawing',
      drawing_data: { paths: drawPaths, width, height },
    }
    if (selectedChannel.value) payload.channel_id = selectedChannel.value.id
    const response = await api.post(`/conversations/${selectedConversation.value.id}/messages`, payload)
    if (response.data?.data) messages.value.push(response.data.data)
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send drawing:', error)
  }
}

// Compute original canvas dimensions — uses stored value or falls back to path bounding box
const getPathsSrcDim = (data) => {
  if (!data || Array.isArray(data)) return { w: 700, h: 420 }
  if (data.width && data.height) return { w: data.width, h: data.height }
  const paths = data.paths || []
  let maxX = 100, maxY = 100
  paths.forEach(p => {
    if (p.type === 'fill') { maxX = Math.max(maxX, p.x); maxY = Math.max(maxY, p.y) }
    else if (p.points) p.points.forEach(pt => {
      if (pt.dots) pt.dots.forEach(d => { maxX = Math.max(maxX, d.x); maxY = Math.max(maxY, d.y) })
      else { maxX = Math.max(maxX, pt.x); maxY = Math.max(maxY, pt.y) }
    })
  })
  return { w: Math.ceil(maxX + 40), h: Math.ceil(maxY + 40) }
}

const getPreviewDim = (data) => {
  if (!data || Array.isArray(data)) return { w: 280, h: 180 }
  const src = getPathsSrcDim(data)
  const s = Math.min(1, 280 / src.w)
  return { w: Math.round(src.w * s), h: Math.round(src.h * s) }
}

const getLightboxDim = (data) => {
  if (!data || Array.isArray(data)) return { w: 800, h: 600 }
  return getPathsSrcDim(data)
}

const scalePaths = (paths, sx, sy) => paths.map(p => {
  if (p.type === 'fill') return { ...p, x: p.x * sx, y: p.y * sy }
  return {
    ...p,
    width: p.width ? Math.max(0.5, p.width * Math.min(sx, sy)) : p.width,
    points: p.points?.map(pt => {
      if (pt.dots) return { dots: pt.dots.map(d => ({ ...d, x: d.x * sx, y: d.y * sy, r: d.r * Math.min(sx, sy) })) }
      return { ...pt, x: pt.x * sx, y: pt.y * sy }
    })
  }
})

const renderDrawing = (canvas, data) => {
  if (!canvas || !data) return
  const ctx = canvas.getContext('2d')
  if (Array.isArray(data)) {
    renderPaths(ctx, canvas, legacyToPaths(data, canvas))
    return
  }
  const paths = data.paths || []
  const src = getPathsSrcDim(data)
  const scaled = (src.w !== canvas.width || src.h !== canvas.height)
    ? scalePaths(paths, canvas.width / src.w, canvas.height / src.h)
    : paths
  renderPaths(ctx, canvas, scaled)
}

// Convert old { type:'start'/'line', x, y } normalized format to renderPaths format
const legacyToPaths = (data, canvas) => {
  if (!Array.isArray(data) || data.length === 0) return []
  const paths = []
  let currentPoints = null
  const w = canvas?.width || 1
  const h = canvas?.height || 1
  data.forEach((pt) => {
    const ax = pt.x <= 1 ? pt.x * w : pt.x
    const ay = pt.y <= 1 ? pt.y * h : pt.y
    if (pt.type === 'start') {
      if (currentPoints && currentPoints.length > 1) paths.push({ type:'stroke', points: currentPoints, color: currentPoints[0].color, width: currentPoints[0].size || 2, brushType:'pen' })
      currentPoints = [{ x: ax, y: ay, color: pt.color, size: pt.size }]
    } else if (pt.type === 'line' && currentPoints) {
      currentPoints.push({ x: ax, y: ay, color: pt.color, size: pt.size })
    }
  })
  if (currentPoints && currentPoints.length > 1) paths.push({ type:'stroke', points: currentPoints, color: currentPoints[0].color, width: currentPoints[0].size || 2, brushType:'pen' })
  return paths
}

// Poll for new messages
const pollMessages = async () => {
  if (!selectedConversation.value) return
  if (selectedConversation.value.type === 'group' && !selectedChannel.value) return

  const lastId = messages.value.length > 0 ? messages.value[messages.value.length - 1].id : 0
  const params = { last_id: lastId }
  if (selectedChannel.value) params.channel_id = selectedChannel.value.id

  try {
    const response = await api.get(`/conversations/${selectedConversation.value.id}/messages/new`, { params })
    if (response.data.data.length > 0) {
      const existingIds = new Set(messages.value.map(m => m.id))
      const newMsgs = response.data.data.filter(m => !existingIds.has(m.id))
      if (newMsgs.length > 0) {
        messages.value.push(...newMsgs)
        await nextTick()
        scrollToBottom()
      }
    }
  } catch (error) {
    console.error('Failed to poll messages:', error)
  }
}

const startPolling = () => {
  stopPolling()
  pollingInterval = setInterval(pollMessages, 2000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString(language.value === 'lv' ? 'lv-LV' : 'en-US', { hour: '2-digit', minute: '2-digit' })
}

const isUserOnline = (conv) => {
  if (!conv || conv.type === 'group') return false
  const uid = conv.other_user?.id
  return uid != null && onlineUserIds.value.includes(uid)
}

const fetchOnlineUsers = async () => {
  try {
    const response = await api.get('/users/online')
    onlineUserIds.value = response.data.online_ids || []
  } catch {
    // silently fail
  }
}

const pulsePresence = async () => {
  try { await api.post('/presence/pulse') } catch { /* ignore */ }
}

const openConversationFromRoute = () => {
  const routeConversationId = route.params.id

  if (!routeConversationId) return

  const conversation = conversations.value.find((item) => String(item.id) === String(routeConversationId))
  if (conversation && String(selectedConversation.value?.id ?? '') !== String(conversation.id)) {
    selectConversation(conversation)
  }
}

onMounted(async () => {
  await getCurrentUser()
  await loadConversations()
  openConversationFromRoute()
  window.addEventListener('resize', resizeDrawingCanvas)
  await nextTick()
  resizeDrawingCanvas()
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (lightboxMsg.value) { lightboxMsg.value = null; return }
      showSizePopup.value = false
    }
  })
  // Start presence heartbeat and online status polling
  pulsePresence()
  await fetchOnlineUsers()
  onlinePollInterval = setInterval(() => {
    pulsePresence()
    fetchOnlineUsers()
  }, 30000)
})

watch(lightboxMsg, async (msg) => {
  if (msg) {
    await nextTick()
    renderDrawing(lightboxCanvasRef.value, msg.drawing_data)
  }
})

watch(
  () => route.params.id,
  () => {
    openConversationFromRoute()
  }
)

watch(
  groupConversations,
  (nextGroups) => {
    if (activeHub.value === 'dm') return
    const exists = nextGroups.some((group) => String(group.id) === String(activeHub.value))
    if (!exists) {
      activeHub.value = 'dm'
    }
  },
  { deep: true }
)

watch(showCreateGroupDialog, (open) => {
  if (!open) {
    resetGroupDialog()
  }
})

onUnmounted(() => {
  stopPolling()
  if (onlinePollInterval) clearInterval(onlinePollInterval)
  leaveConversationChannel()
  if (echoInstance) { echoInstance.disconnect(); echoInstance = null }
  window.removeEventListener('resize', resizeDrawingCanvas)
})
</script>

<style scoped>
/* Page layout */
.messages-page {
  display: flex;
  height: calc(100vh - 72px);
  background: var(--c-bg);
  overflow: hidden;
}

/* Rail */
.msg-rail {
  width: 60px;
  flex-shrink: 0;
  background: var(--c-sidebar);
  border-right: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 0;
  gap: 4px;
  overflow-y: auto;
}

.rail-pill {
  width: 40px !important;
  height: 40px !important;
  border-radius: 12px !important;
  color: var(--c-muted) !important;
  transition: background 150ms, border-radius 150ms, color 150ms !important;
}

.rail-pill:hover {
  background: var(--c-elevated) !important;
  color: var(--c-text) !important;
  border-radius: 10px !important;
}

.rail-pill--active {
  background: var(--c-accent-soft) !important;
  color: #a78bfa !important;
  border-radius: 8px !important;
}

.rail-divider {
  width: 28px;
  height: 1px;
  background: var(--c-border-md);
  margin: 4px 0;
}

/* Sidebar */
.msg-sidebar {
  width: 240px;
  flex-shrink: 0;
  background: var(--c-sidebar);
  border-right: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-head {
  padding: 12px 12px 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex-shrink: 0;
}

/* Sidebar tabs */
.sb-tab-bar {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 10px 8px 6px;
  flex-shrink: 0;
  border-bottom: 1px solid var(--c-border);
}

.sb-tab {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 4px;
  border-radius: var(--r-sm);
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--c-muted);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: background 150ms, color 150ms;
  white-space: nowrap;
  overflow: hidden;
}

.sb-tab:hover { background: var(--c-surface); color: var(--c-text); }
.sb-tab.active { background: var(--c-accent-soft); color: var(--c-text); }

.sidebar-head-search {
  padding: 8px 8px 4px;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.sidebar-head-search .sidebar-search { flex: 1; }

.add-group-btn { flex-shrink: 0; color: var(--c-muted) !important; }

.msg-friend-btn { flex-shrink: 0; margin-left: auto; }

.sidebar-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--c-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 0 4px;
}

.sidebar-search { font-size: 0.85rem; }

/* ── Server sidebar (Discord-style) ─────────────────────────────── */
.server-header {
  display: flex;
  align-items: center;
  padding: 12px 10px 10px;
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
  gap: 4px;
}

.server-header-avatar { flex-shrink: 0; }

.server-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.channels-area {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.category-label {
  flex: 1;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--c-muted);
}

.channel-item {
  display: flex;
  align-items: center;
  padding: 5px 10px 5px 12px;
  margin: 1px 6px;
  border-radius: var(--r-sm);
  cursor: pointer;
  touch-action: manipulation;
  user-select: none;
  color: var(--c-text-dim);
  font-size: 0.85rem;
  transition: background 120ms, color 120ms;
}

.channel-item:hover { background: var(--c-surface); color: var(--c-text); }
.channel-item:hover .channel-del-btn { opacity: 1; }

.channel-item--active {
  background: var(--c-elevated);
  color: var(--c-text);
}

.channel-item-name {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.channel-del-btn {
  opacity: 0;
  transition: opacity 120ms;
}

/* ── Channel categories & drag ─────────────────── */
.channel-category-group { }

.category-arrow {
  color: var(--c-muted);
  transition: transform 150ms ease;
  flex-shrink: 0;
}

.channel-category-row {
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 14px 8px 2px 8px;
  gap: 4px;
}

.channel-category-row .category-add-btn {
  opacity: 0;
  transition: opacity 120ms;
}
.channel-category-row:hover .category-add-btn { opacity: 1; }

.cat-drag-over {
  background: rgba(124, 58, 237, 0.15);
  border-radius: var(--r-sm);
}

.add-category-row {
  padding-top: 8px;
  padding-bottom: 4px;
}

.drag-handle {
  opacity: 0;
  cursor: grab;
  color: var(--c-muted);
  transition: opacity 120ms;
  flex-shrink: 0;
}
.channel-item:hover .drag-handle { opacity: 0.5; }

.channel-item--dragging {
  opacity: 0.35;
}

.channel-item--drag-over {
  background: rgba(124, 58, 237, 0.2);
  color: var(--c-text);
}

.channel-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px;
  font-size: 0.75rem;
  color: var(--c-muted);
}

.sidebar-user-bar {
  display: flex;
  align-items: center;
  padding: 8px 10px;
  border-top: 1px solid var(--c-border);
  flex-shrink: 0;
  background: var(--c-sidebar);
}

.sidebar-user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--c-text-dim);
}

.dm-section-label {
  display: flex;
  align-items: center;
  padding: 10px 12px 4px;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--c-muted);
  flex-shrink: 0;
}

/* Channel head icon (instead of avatar for channels) */
.channel-head-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--r-sm);
  background: var(--c-surface);
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-head-channel-type {
  font-size: 0.65rem;
  font-weight: 500;
  color: var(--c-muted);
  margin-left: 6px;
  padding: 1px 5px;
  background: var(--c-surface);
  border-radius: 4px;
}

/* ── Server settings dialog ───────────────────────────────────── */
.server-settings-dlg { max-height: 80vh; }

.settings-tabs {
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
}

.settings-body { flex: 1; overflow-y: auto; }

.settings-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--c-text-dim);
}

.settings-row {
  display: flex;
  align-items: center;
  padding: 8px 4px;
  border-radius: var(--r-sm);
  gap: 6px;
  font-size: 0.85rem;
  border-bottom: 1px solid var(--c-border);
}

.role-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
  display: inline-block;
}

.role-perms { display: flex; flex-wrap: wrap; gap: 2px; }

.member-roles-row { display: flex; flex-wrap: wrap; }

/* Invite link */
.invite-link-row {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--c-surface);
  padding: 8px 12px;
  border-radius: var(--r-md);
}

.invite-link-code {
  flex: 1;
  font-family: monospace;
  font-size: 0.8rem;
  color: var(--c-text);
  word-break: break-all;
}

/* Role create dialog */
.role-color-picker {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 6px;
}

.role-color-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: inline-block;
  border: 2px solid rgba(255,255,255,0.15);
}

.role-color-picker input[type=color] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.role-perms-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2px;
}

.perm-check { font-size: 0.78rem; }

.channel-type-toggle { width: 100%; }

/* ── Server overview ────────────────────────────── */
.overview-layout {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}

.server-icon-section { flex-shrink: 0; }
.server-details-section { flex: 1; min-width: 160px; }

.server-icon-canvas-wrap {
  border: 2px solid var(--c-border);
  border-radius: var(--r-md);
  overflow: hidden;
  width: 200px;
  height: 200px;
}

.server-icon-preview-wrap {
  position: relative;
  border: 2px solid rgba(124,58,237,0.35);
  border-radius: 50%;
  overflow: hidden;
  width: 128px;
  height: 128px;
  background: #fff;
}

.server-icon-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.server-icon-empty {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.06);
}

.server-icon-canvas {
  display: block;
  cursor: crosshair;
}

.color-picker-sm {
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  border-radius: 50%;
  cursor: pointer;
}

.preset-dot-sm {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  padding: 0;
  flex-shrink: 0;
}

.preset-dot-sm.active {
  border-color: var(--c-text);
}

.avatar-tools-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.tool-label {
  font-size: 0.72rem;
  color: var(--c-muted);
  white-space: nowrap;
}

/* ── Add Friend dialog ──────────────────────────── */
.add-friend-btn { color: var(--c-muted) !important; }

.add-friend-results {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.add-friend-row {
  display: flex;
  align-items: center;
  padding: 8px 4px;
  border-radius: var(--r-sm);
  gap: 8px;
  border-bottom: 1px solid var(--c-border);
}

/* DM sidebar search */
.conv-list {
  flex: 1;
  overflow-y: auto;
  padding: 4px 8px 12px;
}

.conv-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: var(--r-md);
  cursor: pointer;
  touch-action: manipulation;
  user-select: none;
  transition: background 150ms;
  /* button reset */
  appearance: none;
  -webkit-appearance: none;
  border: none;
  background: transparent;
  width: 100%;
  text-align: left;
  font: inherit;
  color: inherit;
}

.conv-item:hover { background: var(--c-elevated); }

.conv-item--active {
  background: var(--c-accent-soft);
  color: var(--c-text);
}

.conv-avatar { flex-shrink: 0; }

/* Sidebar online dot uses .conv-avatar-wrap with relative positioning */

.conv-info {
  flex: 1;
  min-width: 0;
}

.conv-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conv-preview {
  font-size: 0.75rem;
  color: var(--c-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  text-align: center;
  color: var(--c-muted);
  font-size: 0.85rem;
}

/* Chat area */
.msg-chat {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: var(--c-bg);
  overflow: hidden;
  position: relative;
}

.chat-head {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  background: var(--c-sidebar);
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
}

.chat-head-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.chat-head-name {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--c-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chat-head-sub {
  font-size: 0.72rem;
  color: var(--c-muted);
}

/* Messages stream */
.msg-stream {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0 16px;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* ── Date divider improvements ───────────────────────────────────────── */
.date-divider {
  text-align: center;
  font-size: 0.7rem;
  color: var(--c-muted);
  margin: 20px 16px 6px;
  position: relative;
  font-weight: 600;
  letter-spacing: 0.04em;
}

.date-divider::before, .date-divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: calc(50% - 48px);
  height: 1px;
  background: var(--c-border);
}

.date-divider::before { left: 0; }
.date-divider::after { right: 0; }

/* ── Discord-style message rows ──────────────────────────────────────── */
.msg-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 2px 16px;
  position: relative;
  border-radius: 2px;
  transition: background 0.07s;
}

.msg-row:not(.msg-row--grouped) { margin-top: 18px; }
.msg-row--grouped { padding-top: 1px; padding-bottom: 1px; }
.msg-row:hover { background: rgba(255,255,255,0.028); }

/* Avatar column */
.msg-avatar-col {
  width: 36px;
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  padding-top: 2px;
}

.msg-avatar-img { flex-shrink: 0; }

/* Hover-revealed timestamp for grouped messages */
.msg-side-time {
  font-size: 0.62rem;
  color: transparent;
  white-space: nowrap;
  line-height: 1.8;
  transition: color 0.1s;
  user-select: none;
}
.msg-row:hover .msg-side-time { color: var(--c-muted); }

/* Body */
.msg-body { flex: 1; min-width: 0; }

.msg-meta {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 3px;
}

.msg-author {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--c-text);
  line-height: 1;
  cursor: pointer;
  transition: text-decoration 0.1s;
}
.msg-author:hover { text-decoration: underline; }
.msg-author--self { color: #a78bfa; }

.msg-timestamp {
  font-size: 0.68rem;
  color: var(--c-muted);
  font-weight: 400;
}

.msg-content {
  font-size: 0.9rem;
  color: var(--c-text);
  line-height: 1.55;
  word-break: break-word;
  white-space: pre-wrap;
  margin: 0;
}

/* Hover action buttons */
.msg-actions {
  position: absolute;
  top: -14px;
  right: 12px;
  display: flex;
  gap: 1px;
  background: var(--c-card);
  border: 1px solid var(--c-border-md);
  border-radius: var(--r-md);
  padding: 2px 3px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.1s;
  z-index: 2;
}
.msg-row:hover .msg-actions {
  opacity: 1;
  pointer-events: auto;
}

.msg-action-btn {
  width: 24px;
  height: 24px;
  border-radius: 5px;
  background: none;
  border: none;
  color: var(--c-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s, color 0.1s;
}
.msg-action-btn:hover {
  background: var(--c-elevated);
  color: var(--c-text);
}

.msg-drawing-card {
  display: inline-flex;
  flex-direction: column;
  border-radius: 10px;
  overflow: hidden;
  cursor: zoom-in;
  max-width: 280px;
  border: 1px solid var(--c-border);
  transition: box-shadow 0.15s, transform 0.12s;
  margin-top: 4px;
}
.msg-drawing-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.5); transform: scale(1.01); }

/* ── Typing indicator ────────────────────────────────────────────────── */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 16px 4px 64px;
  min-height: 28px;
}

.typing-dots { display: flex; gap: 4px; align-items: center; }

.typing-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--c-muted);
  animation: typing-bounce 1.2s ease-in-out infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing-bounce {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
  30% { transform: translateY(-5px); opacity: 1; }
}

.typing-text {
  font-size: 0.75rem;
  color: var(--c-muted);
  font-style: italic;
}

.typing-fade-enter-active, .typing-fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.typing-fade-enter-from, .typing-fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

/* ── Online presence dot ─────────────────────────────────────────────── */
.conv-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--c-success);
  border: 2px solid var(--c-sidebar);
}

.online-dot--head {
  border-color: var(--c-sidebar);
}

.chat-head-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.online-badge {
  color: var(--c-success);
  font-size: 0.7rem;
  font-weight: 600;
}

.members-btn--active {
  color: var(--c-accent) !important;
}

/* ── Better sidebar conversation items ───────────────────────────────── */
.conv-item--active .conv-name { color: #fff; }
.conv-item--active .conv-preview { color: rgba(255,255,255,0.6); }

/* ── Date divider improvements ───────────────────────────────────────── */

.msg-drawing {
  display: block;
  width: 100%;
  height: auto;
}

.msg-drawing-caption {
  padding: 5px 10px 6px;
  font-size: 0.8rem;
  color: var(--c-text);
  background: var(--c-card);
  border-top: 1px solid var(--c-border);
  word-break: break-word;
  line-height: 1.4;
}

/* Drawing lightbox */
.drawing-lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}
.lb-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.lb-canvas {
  display: block;
  max-width: 90vw;
  max-height: 82vh;
  width: auto;
  height: auto;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.8);
}
.lb-caption {
  margin-top: 14px;
  font-size: 1rem;
  color: rgba(255,255,255,0.85);
  text-align: center;
  max-width: 600px;
  word-break: break-word;
}
.lb-close {
  position: fixed;
  top: 16px;
  right: 16px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(255,255,255,0.12);
  border: none;
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.lb-close:hover { background: rgba(255,255,255,0.25); }
.lb-fade-enter-active, .lb-fade-leave-active { transition: opacity 0.18s; }
.lb-fade-enter-from, .lb-fade-leave-to { opacity: 0; }

/* Drawing panel — 4:3 landscape; desktop: right-side panel; mobile: always visible */
.drawing-panel {
  display: none;
  flex-direction: column;
  flex: 0 0 auto;
  overflow: hidden;
  background: var(--c-sidebar);
}
.drawing-panel.dp--visible {
  display: flex;
}

/* Desktop: side-by-side when canvas is open — use grid so composer sits below messages, not below canvas */
@media (min-width: 960px) {
  .messages-area:has(.drawing-panel.dp--visible) {
    display: grid;
    grid-template-columns: 1fr clamp(360px, 38vw, 500px);
    grid-template-rows: 1fr auto;
    grid-template-areas:
      "messages canvas"
      "composer canvas";
  }
  .messages-area:has(.drawing-panel.dp--visible) > .msg-main {
    grid-area: messages;
    min-width: 0;
    overflow: hidden;
  }
  .messages-area:has(.drawing-panel.dp--visible) > .drawing-panel {
    grid-area: canvas;
    justify-content: flex-end;
    border-left: 2px solid var(--c-border-md);
  }
  .messages-area:has(.drawing-panel.dp--visible) > .composer {
    grid-area: composer;
  }
}

/* Canvas panel slide-in / slide-out transition */
.canvas-slide-enter-active {
  transition: opacity 0.3s ease, transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.canvas-slide-leave-active {
  transition: opacity 0.22s ease, max-width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  max-width: clamp(360px, 38vw, 500px);
}
.canvas-slide-enter-from { opacity: 0; transform: translateX(32px); }
.canvas-slide-leave-to {
  opacity: 0;
  max-width: 0;
}

/* Canvas-wrap: always 4:3, canvas fills it 100% */
.dp-canvas-wrap {
  width: 100%;
  aspect-ratio: 4 / 3;
  flex-shrink: 0;
  position: relative;
  background: #fff;
  overflow: hidden;
}

.drawing-canvas {
  display: block;
  width: 100%;
  height: 100%;
  touch-action: none;
}

/* Empty state overlay */
.dp-hint {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  pointer-events: none;
  color: rgba(0, 0, 0, 0.18);
  font-size: 0.82rem;
  font-weight: 500;
  letter-spacing: 0.02em;
}

/* Top toolbar */
/* Toolbar: single scrollable row */
.dp-top-bar {
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  background: var(--c-sidebar);
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
  user-select: none;
}
.dp-top-bar::-webkit-scrollbar { display: none; }

.dp-tool-inner {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  min-width: max-content;
  gap: 2px;
  padding: 5px 8px;
  min-height: 40px;
}

.dp-tb-sep {
  width: 1px;
  height: 18px;
  background: var(--c-border-md);
  flex-shrink: 0;
  margin: 0 3px;
}

.dp-tb-spacer { flex: 1; min-width: 8px; }

.dp-tb-actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

/* Toolbar buttons */
.dp-tb-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--r-sm);
  background: none;
  border: 1.5px solid transparent;
  color: var(--c-text-dim);
  cursor: pointer;
  transition: background 0.12s, color 0.12s, border-color 0.12s;
  flex-shrink: 0;
}
.dp-tb-btn:hover:not(:disabled) { background: var(--c-card); color: var(--c-text); }
.dp-tb-btn:disabled { opacity: 0.28; cursor: not-allowed; }
.dp-tb-btn.active {
  background: var(--c-accent-soft);
  border-color: var(--c-accent);
  color: #a78bfa;
}
.dp-tb-btn--send:not(:disabled) {
  background: var(--c-accent-soft);
  border-color: var(--c-accent);
  color: #a78bfa;
}
.dp-tb-btn--send:not(:disabled):hover {
  background: var(--c-accent);
  color: #fff;
}

/* Color swatch button */
.dp-fb-color {
  display: flex;
  align-items: center;
  cursor: pointer;
  position: relative;
  margin: 0 3px;
}
.dp-fb-swatch {
  display: block;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.4);
  flex-shrink: 0;
  transition: transform 0.1s;
}
.dp-fb-color:hover .dp-fb-swatch { transform: scale(1.15); }
.dp-fb-color-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

/* Color presets */
.dp-fb-preset {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 1.5px solid transparent;
  cursor: pointer;
  transition: transform 0.1s, border-color 0.1s;
  flex-shrink: 0;
  margin: 0 1px;
}
.dp-fb-preset:hover { transform: scale(1.45); }
.dp-fb-preset.active { border-color: #a78bfa; transform: scale(1.3); }

/* Size trigger */
.dp-fb-size-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.dp-fb-size-dot {
  display: block;
  border-radius: 50%;
  min-width: 3px;
  min-height: 3px;
  transition: width 0.1s, height 0.1s, background 0.1s;
}

/* Size popup */
.dp-size-popup {
  position: absolute;
  bottom: calc(100% + 6px);
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(18, 19, 24, 0.96);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: var(--r-md);
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.55);
  z-index: 20;
  white-space: nowrap;
}
.dp-sp-label {
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.5);
  min-width: 28px;
  text-align: right;
}
.dp-sp-range {
  width: 90px;
  accent-color: var(--c-accent);
  cursor: pointer;
}

/* ── Bottom section (below canvas) ───────────────────── */
.dp-bottom-section {
  display: flex;
  flex-direction: column;
  background: var(--c-sidebar);
  border-top: 1px solid var(--c-border);
  flex-shrink: 0;
}

/* Scrollable color + size strip */
.dp-color-strip {
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  border-bottom: 1px solid var(--c-border);
}
.dp-color-strip::-webkit-scrollbar { display: none; }
.dp-color-inner {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  min-width: max-content;
  gap: 4px;
  padding: 5px 10px;
  min-height: 36px;
}

/* Full-width caption input */
.dp-caption-input {
  width: 100%;
  box-sizing: border-box;
  background: none;
  border: none;
  border-bottom: 1px solid var(--c-border);
  outline: none;
  color: var(--c-text);
  font-size: 0.88rem;
  padding: 9px 14px;
  caret-color: #a78bfa;
}
.dp-caption-input::placeholder { color: var(--c-muted); }

/* Action row: clear icon + cancel text btn + send btn */
.dp-action-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
}
.dp-clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: var(--r-sm);
  background: none;
  border: 1.5px solid rgba(248, 113, 113, 0.4);
  color: rgba(248, 113, 113, 0.75);
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.12s, color 0.12s;
}
.dp-clear-btn:not(:disabled):hover { background: rgba(248, 113, 113, 0.12); color: rgb(248, 113, 113); }
.dp-clear-btn:disabled { opacity: 0.28; cursor: not-allowed; }
.dp-cancel-btn {
  flex: 1;
  padding: 7px 10px;
  background: none;
  border: 1.5px solid var(--c-border-md);
  border-radius: var(--r-md);
  color: var(--c-muted);
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 500;
  text-align: center;
  transition: background 0.12s, color 0.12s;
}
.dp-cancel-btn:hover { background: var(--c-card); color: var(--c-text); }
.dp-send-fab {
  flex: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 8px 16px;
  background: var(--c-accent);
  color: #fff;
  border: none;
  border-radius: var(--r-md);
  font-size: 0.84rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, box-shadow 0.15s, transform 0.12s;
  box-shadow: 0 2px 10px rgba(124, 58, 237, 0.45);
}
.dp-send-fab:hover:not(:disabled) {
  background: #6d28d9;
  box-shadow: 0 6px 22px rgba(124, 58, 237, 0.75);
  transform: translateY(-1px);
}
.dp-send-fab:disabled {
  opacity: 0.38;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

/* Animations */
.popup-fade-enter-active, .popup-fade-leave-active {
  transition: opacity 0.14s, transform 0.14s;
}
.popup-fade-enter-from, .popup-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-4px);
}
.hint-fade-enter-active, .hint-fade-leave-active { transition: opacity 0.25s; }
.hint-fade-enter-from, .hint-fade-leave-to { opacity: 0; }

.drawing-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.color-toggle { flex-shrink: 0; }

.swatch {
  display: block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
}

.brush-slider { flex: 1; min-width: 80px; }

.slide-up-enter-active, .slide-up-leave-active {
  transition: opacity 160ms, transform 160ms;
}

.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* Composer */
.composer {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  background: var(--c-sidebar);
  border-top: 1px solid var(--c-border);
  flex-shrink: 0;
  gap: 6px;
}

.composer-input { flex: 1; }

.composer-emoji-picker {
  width: 320px;
  height: 360px;
  overflow: hidden;
  border-radius: 10px;
}

/* Empty chat */
.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 24px;
  color: var(--c-muted);
}

.chat-empty h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--c-text);
  margin-bottom: 6px;
}

.chat-empty p { font-size: 0.875rem; }

/* Group dialog */
.group-dlg {
  background: var(--c-card) !important;
  border: 1px solid var(--c-border-md) !important;
}

.dlg-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px 12px;
  border-bottom: 1px solid var(--c-border);
}

.dlg-head h3 { font-size: 1rem; font-weight: 700; color: var(--c-text); }

.dlg-body { padding: 16px 20px; }

.dlg-foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 20px 16px;
  border-top: 1px solid var(--c-border);
}

/* ── Server header info ─────────────────────────────────────────── */
.server-header-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.server-member-count {
  display: flex;
  align-items: center;
  font-size: 0.68rem;
  color: var(--c-muted);
  margin-top: 1px;
}

/* ── DM sections (single scrollable area) ───────────────────────── */
.dm-sections {
  flex: 1;
  overflow-y: auto;
  padding: 4px 8px 16px;
  scrollbar-width: thin;
}

.dm-section-items {
  display: flex;
  flex-direction: column;
  margin-bottom: 4px;
}

/* Compact empty state for sections */
.sidebar-empty-sm {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px 10px;
  text-align: center;
  color: var(--c-muted);
  font-size: 0.72rem;
}

/* Group conversation item extras */
.group-avatar {
  border-radius: 10px !important;
}

.group-member-badge {
  position: absolute;
  bottom: -3px;
  right: -4px;
  background: var(--c-elevated);
  border: 1.5px solid var(--c-sidebar);
  border-radius: 8px;
  font-size: 0.58rem;
  font-weight: 700;
  color: var(--c-muted);
  padding: 0 3px;
  line-height: 13px;
  min-width: 13px;
  text-align: center;
}

.group-preview {
  display: flex;
  align-items: center;
  gap: 2px;
}

/* Mobile hub bar (rail replacement on small screens) */
.mobile-hub-bar {
  display: none;
  align-items: center;
  gap: 6px;
  padding: 8px 10px;
  background: var(--c-sidebar);
  border-bottom: 1px solid var(--c-border);
  overflow-x: auto;
  scrollbar-width: none;
  flex-shrink: 0;
}
.mobile-hub-bar::-webkit-scrollbar { display: none; }

.mhub-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: var(--c-muted);
  cursor: pointer;
  flex-shrink: 0;
  transition: background 150ms, color 150ms;
}
.mhub-btn:hover { background: var(--c-elevated); color: var(--c-text); }
.mhub-btn--active {
  background: var(--c-accent-soft) !important;
  color: #a78bfa !important;
  border-radius: 8px;
}

.mhub-divider {
  width: 1px;
  height: 24px;
  background: var(--c-border-md);
  flex-shrink: 0;
  margin: 0 2px;
}

/* Channel permissions panel */
.ch-perm-panel {
  display: flex;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 12px 10px 36px;
  background: rgba(255,255,255,0.02);
  border-bottom: 1px solid var(--c-border);
}
.ch-perm-label {
  font-size: 0.72rem;
  color: var(--c-muted);
  align-self: center;
  flex-shrink: 0;
  margin-right: 2px;
}
.ch-perm-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
  flex: 1;
}

/* Responsive: collapse rail + sidebar on small screens */
@media (max-width: 959px) {
  .messages-page {
    flex-direction: row;
    position: relative;
    height: calc(100dvh - 72px);
    overflow: hidden;
  }

  .msg-rail { display: none; }

  /* Hub bar: always-visible strip at the top, above the sliding sidebar/chat */
  .mobile-hub-bar {
    display: flex !important;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 54px;
    z-index: 20;
    pointer-events: auto;
    box-sizing: border-box;
  }

  /* Sidebar: below the hub bar, slides left when a conversation is open */
  .msg-sidebar {
    position: absolute;
    top: 54px;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    z-index: 5;
    border-right: none;
    transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--c-sidebar);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    pointer-events: auto;
    will-change: transform;
  }
  .messages-page.has-conv .msg-sidebar {
    transform: translateX(-100%);
    pointer-events: none;
  }

  /* Chat: below the hub bar, slides in when a conversation is open */
  .msg-chat {
    position: absolute;
    top: 54px;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    transform: translateX(100%);
    transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 4;
    pointer-events: none;
    will-change: transform;
  }
  .messages-page.has-conv .msg-chat {
    transform: translateX(0);
    pointer-events: auto;
  }

  /* Larger touch targets */
  .conv-item { padding: 10px 12px; min-height: 56px; }
  .channel-item { padding: 9px 10px 9px 14px; min-height: 44px; }
  .channel-item-name { font-size: 0.9rem; }
  .server-header { padding: 14px 12px 12px; }

  /* Chat header */
  .chat-head { padding: 10px 12px; min-height: 54px; }
  .chat-head-name { font-size: 0.95rem; }

  /* Messages: full-bleed padding on mobile */
  .msg-row { padding: 2px 10px; }
  .msg-stream { padding: 6px 0 8px; }

  /* Members + pinned panels go full-width on mobile */
  .members-panel { width: 100%; border-left: none; position: absolute; inset: 0; z-index: 10; }
  .pinned-panel { width: 100%; }

  /* ── Drawing panel: auto height so toolbar + canvas + colour strip all fit ── */
  .drawing-panel {
    width: 92vw;
    max-width: 560px;
    height: auto;   /* let flex children determine height */
    flex: 0 0 auto;
    align-self: center;
    border-radius: 14px;
    border: 1px solid var(--c-border-md);
    margin: 6px auto;
    box-shadow: 0 4px 24px rgba(0,0,0,0.22);
  }

  /* Show close button so user can dismiss the canvas */
  .dp-tb-btn--close { display: flex; }

  /* Toolbar: single scrollable row, no wrapping */
  .dp-top-bar { overflow-x: auto; overflow-y: visible; scrollbar-width: none; touch-action: pan-x; }
  .dp-top-bar::-webkit-scrollbar { display: none; }
  .dp-tool-inner { flex-wrap: nowrap; min-width: max-content; gap: 3px; padding: 6px 10px; min-height: 48px; }
  .dp-tb-sep { display: block; }
  .dp-tb-spacer { display: block; flex: 1; min-width: 6px; }
  .dp-tb-actions { flex-basis: auto; border-top: none; padding-top: 0; gap: 4px; }
  .dp-tb-btn { width: 38px; height: 38px; border-radius: 10px; touch-action: manipulation; }

  /* Color strip: thumb-friendly */
  .dp-color-strip { touch-action: pan-x; }
  .dp-color-inner { gap: 6px; padding: 7px 12px; min-height: 48px; }
  .dp-fb-preset { width: 26px; height: 26px; margin: 0; touch-action: manipulation; }
  .dp-fb-swatch { width: 28px; height: 28px; }
  .dp-fb-color { width: 36px; height: 36px; border-radius: 10px; touch-action: manipulation; }
  .dp-fb-color-input { touch-action: manipulation; }

  /* Caption: font ≥ 16px prevents iOS zoom */
  .dp-caption-input { font-size: 1rem; padding: 9px 14px; }

  /* Action row: bigger targets, safe-area bottom */
  .dp-action-row { padding: 8px 12px; padding-bottom: max(12px, env(safe-area-inset-bottom)); gap: 8px; }
  .dp-clear-btn { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; }
  .dp-send-fab { flex: 1; padding: 12px 20px; font-size: 0.95rem; font-weight: 700; border-radius: 10px; }

  .server-settings-dlg {
    max-height: calc(100dvh - 12px);
    border-radius: 12px;
  }

  .settings-tabs {
    overflow-x: auto;
    scrollbar-width: none;
  }
  .settings-tabs::-webkit-scrollbar { display: none; }

  .overview-layout {
    flex-direction: column;
    gap: 14px;
  }

  .server-icon-section {
    align-items: center;
    display: flex;
    flex-direction: column;
    width: 100%;
  }

  .server-icon-preview-wrap {
    width: 112px;
    height: 112px;
  }

  .server-icon-actions {
    width: 100%;
    justify-content: stretch;
    gap: 8px;
  }

  .server-icon-actions :deep(.v-btn) {
    flex: 1;
    min-width: 0;
  }

  .settings-body {
    padding: 12px !important;
  }
}

/* ── Small phone: ≤400px ─────────────────────────────────────────────── */
@media (max-width: 400px) {
  /* 4:3 landscape on small phones: full screen width */
  .drawing-panel {
    width:  96vw !important;
    height: auto !important;  /* let content determine height */
    max-width: none !important;
    margin: 4px auto !important;
    border-radius: 10px !important;
  }

  /* Tighter chrome to leave room for messages above the canvas */
  .msg-row { padding: 2px 8px; }
  .chat-head { padding: 8px 10px; min-height: 48px; }
  .chat-head-name { font-size: 0.88rem; }
  .mobile-hub-bar { padding: 6px 8px; gap: 4px; }
  .mhub-btn { width: 34px; height: 34px; }
  .dp-tb-btn { width: 34px !important; height: 34px !important; }
  .dp-tool-inner { padding: 4px 6px !important; }
  .dp-color-inner { padding: 5px 8px !important; }
  .dp-action-row { padding: 6px 8px !important; padding-bottom: max(10px, env(safe-area-inset-bottom)) !important; }
}



/* Chat body layout */
.chat-body {
  display: flex;
  flex: 1;
  overflow: hidden;
  position: relative;
}
.messages-area {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
  position: relative;
}
.msg-main {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

/* Reply reference bar above a message */
.msg-reply-ref {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 2px 0 2px 52px;
  font-size: 0.72rem;
  color: var(--c-text-muted, rgba(255,255,255,0.45));
  cursor: pointer;
  line-height: 1.2;
  position: relative;
}
.msg-reply-ref::before {
  content: '';
  position: absolute;
  left: 26px;
  top: 50%;
  width: 20px;
  height: 1px;
  border-top: 2px solid rgba(255,255,255,0.15);
  border-left: 2px solid rgba(255,255,255,0.15);
  border-radius: 4px 0 0 0;
  transform: translateY(-6px);
}
.msg-reply-ref:hover { color: var(--c-text); }
.msg-reply-ref-author { font-weight: 600; color: var(--c-text); margin-right: 4px; }
.msg-reply-ref-content { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px; }

/* Pinned message highlight */
.msg-row--pinned {
  background: rgba(255, 200, 0, 0.04);
  border-left: 2px solid rgba(255, 200, 0, 0.3);
}

/* Role badge in meta row */
.msg-role-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 5px;
  border-radius: 3px;
  font-size: 0.62rem;
  font-weight: 600;
  border: 1px solid transparent;
  margin-right: 3px;
  vertical-align: middle;
}

/* Edited indicator */
.msg-edited {
  font-size: 0.62rem;
  color: var(--c-text-muted, rgba(255,255,255,0.35));
  margin-left: 4px;
}

/* Pin indicator in meta */
.msg-pin-badge {
  margin-left: 4px;
  opacity: 0.5;
}

/* Inline edit box */
.msg-edit-box {
  margin-top: 2px;
}
.msg-edit-textarea {
  width: 100%;
  background: rgba(0,0,0,0.25);
  border: 1px solid var(--c-border);
  border-radius: 6px;
  color: var(--c-text);
  padding: 6px 10px;
  font-size: 0.875rem;
  font-family: inherit;
  resize: none;
  outline: none;
  box-sizing: border-box;
}
.msg-edit-textarea:focus { border-color: var(--c-accent); }
.msg-edit-hint {
  font-size: 0.68rem;
  color: var(--c-text-muted, rgba(255,255,255,0.4));
  margin-top: 3px;
}
.link-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: var(--c-text-muted, rgba(255,255,255,0.4));
  font-size: 0.68rem;
  text-decoration: underline;
}
.link-btn:hover { color: var(--c-text); }
.link-btn--primary { color: var(--c-accent); }
.link-btn--primary:hover { color: var(--c-accent); opacity: 0.8; }

/* Reactions bar */
.msg-reactions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}
.reaction-chip {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 2px 7px;
  border-radius: 99px;
  font-size: 0.78rem;
  border: 1px solid var(--c-border);
  background: rgba(255,255,255,0.05);
  color: var(--c-text);
  cursor: pointer;
  transition: background 0.12s;
}
.reaction-chip:hover { background: rgba(255,255,255,0.1); }
.reaction-chip--mine {
  background: rgba(88, 101, 242, 0.25);
  border-color: rgba(88, 101, 242, 0.5);
}
.reaction-add-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 22px;
  border-radius: 99px;
  border: 1px dashed var(--c-border);
  background: transparent;
  color: var(--c-text-muted, rgba(255,255,255,0.4));
  cursor: pointer;
  font-size: 0.75rem;
}
.reaction-add-btn:hover { background: rgba(255,255,255,0.08); color: var(--c-text); }

/* Enhanced action buttons */
.msg-action-btn--active {
  color: #fbbf24 !important;
}
.msg-action-btn--danger:hover {
  color: #ef4444 !important;
  background: rgba(239,68,68,0.12) !important;
}

/* Reply compose bar */
.reply-compose-bar {
  display: flex;
  align-items: center;
  padding: 5px 12px;
  background: rgba(255,255,255,0.04);
  border-top: 1px solid var(--c-border);
  font-size: 0.78rem;
  color: var(--c-text-muted, rgba(255,255,255,0.5));
  gap: 4px;
}
.reply-compose-preview {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 200px;
  margin-left: 6px;
  opacity: 0.6;
}
.reply-slide-enter-active, .reply-slide-leave-active { transition: all 0.15s ease; }
.reply-slide-enter-from, .reply-slide-leave-to { opacity: 0; transform: translateY(4px); }

/* Members panel */
.members-panel {
  width: 240px;
  flex-shrink: 0;
  border-left: 1px solid var(--c-border);
  background: var(--c-sidebar, #2b2d31);
  overflow-y: auto;
  padding: 12px 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.members-section { margin-bottom: 12px; }
.members-section-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: var(--c-text-muted, rgba(255,255,255,0.35));
  padding: 4px 6px;
  text-transform: uppercase;
}
.member-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 6px;
  border-radius: 4px;
  cursor: pointer;
}
.member-row:hover { background: rgba(255,255,255,0.06); }
.member-row--offline { opacity: 0.45; }
.member-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.member-online-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #23a55a;
  border: 2px solid var(--c-sidebar, #2b2d31);
}
.member-info { display: flex; flex-direction: column; min-width: 0; }
.member-name {
  font-size: 0.82rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--c-text);
}
.member-roles { display: flex; flex-wrap: wrap; gap: 2px; margin-top: 1px; }
.member-role-tag {
  font-size: 0.58rem;
  font-weight: 600;
  padding: 1px 4px;
  border-radius: 2px;
}
.members-slide-enter-active, .members-slide-leave-active { transition: all 0.18s ease; }
.members-slide-enter-from, .members-slide-leave-to { opacity: 0; transform: translateX(20px); }

/* Pinned messages panel */
.pinned-panel {
  position: absolute;
  top: 0;
  right: 0;
  width: 340px;
  height: 100%;
  background: var(--c-sidebar, #2b2d31);
  border-left: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  z-index: 20;
  box-shadow: -4px 0 16px rgba(0,0,0,0.2);
}
.pinned-panel-head {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  border-bottom: 1px solid var(--c-border);
  flex-shrink: 0;
}
.pinned-panel-body { overflow-y: auto; padding: 8px; flex: 1; }
.pinned-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  border: 1px solid var(--c-border);
  margin-bottom: 6px;
  background: rgba(255,255,255,0.03);
}
.pinned-item:hover { background: rgba(255,255,255,0.07); }
.pinned-item-body { display: flex; flex-direction: column; min-width: 0; }
.pinned-item-author { font-size: 0.75rem; font-weight: 600; color: var(--c-text); }
.pinned-item-content {
  font-size: 0.73rem;
  color: var(--c-text-muted, rgba(255,255,255,0.5));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 250px;
}
.pinned-empty { font-size: 0.78rem; color: var(--c-text-muted, rgba(255,255,255,0.4)); padding: 12px; text-align: center; }
.pinned-slide-enter-active, .pinned-slide-leave-active { transition: all 0.18s ease; }
.pinned-slide-enter-from, .pinned-slide-leave-to { opacity: 0; transform: translateX(20px); }

/* Head button active state */
.head-btn--active { color: var(--c-accent) !important; }

/* Emoji picker */
.emoji-picker-overlay {
  position: fixed;
  z-index: 9999;
}
.emoji-picker-popup {
  background: var(--c-card, #1e1f22);
  border: 1px solid var(--c-border);
  border-radius: 10px;
  padding: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  width: 240px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
}
.emoji-btn {
  background: none;
  border: none;
  font-size: 1.3rem;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 6px;
  line-height: 1;
  transition: background 0.1s;
}
.emoji-btn:hover { background: rgba(255,255,255,0.1); }

</style>
