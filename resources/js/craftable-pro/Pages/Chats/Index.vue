<template>
  <Head :title="$t('craftable-pro', 'Messages')" />

  <div class="flex h-screen overflow-hidden bg-[#1e1f22] text-gray-100">
    <aside class="flex w-72 flex-shrink-0 flex-col border-r border-black/30 bg-[#2b2d31]">
      <div
        class="flex items-center justify-between border-b border-black/30 px-4 py-3 shadow-sm"
      >
        <h2 class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
          {{ $t('craftable-pro', 'Direct Messages') }}
        </h2>
        <button
          v-if="users.length"
          type="button"
          class="rounded p-1 text-gray-400 transition hover:bg-white/10 hover:text-white"
          :title="$t('craftable-pro', 'New chat')"
          @click="onNewChat"
        >
          <PlusIcon class="h-4 w-4" />
        </button>
      </div>

      <div class="px-2 pb-1 pt-2">
        <div class="relative">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500"
          />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('craftable-pro', 'Find a conversation')"
            class="w-full rounded-md bg-[#1e1f22] py-1.5 pl-7 pr-2 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/60"
          />
        </div>
      </div>

      <ul
        v-if="filteredConversations.length"
        class="flex-1 space-y-0.5 overflow-y-auto px-2 py-2"
      >
        <li v-for="conv in filteredConversations" :key="conv.id">
          <button
            type="button"
            class="group flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition"
            :class="
              activeId === conv.id
                ? 'bg-white/10 text-white'
                : 'text-gray-400 hover:bg-white/5 hover:text-gray-200'
            "
            @click="openConversation(conv.id)"
          >
            <div class="relative flex-shrink-0">
              <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-semibold uppercase text-white shadow-md shadow-indigo-900/40"
              >
                {{ initialsFor(conv) }}
              </div>
              <span
                class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-[#2b2d31] bg-emerald-500"
              />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-baseline justify-between gap-2">
                <p class="truncate text-sm font-medium">
                  {{ displayNameFor(conv) }}
                </p>
                <span
                  v-if="conv.last_message"
                  class="flex-shrink-0 text-[10px] text-gray-500"
                >
                  {{ formatTime(conv.last_message.created_at) }}
                </span>
              </div>
              <p class="truncate text-xs text-gray-500">
                {{
                  conv.last_message?.body ??
                  $t('craftable-pro', 'No messages yet')
                }}
              </p>
            </div>
          </button>
        </li>
      </ul>

      <div
        v-else
        class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center"
      >
        <ChatBubbleLeftRightIcon class="h-10 w-10 text-gray-700" />
        <p class="mt-3 text-xs text-gray-500">
          {{
            search
              ? $t('craftable-pro', 'No conversations match.')
              : $t('craftable-pro', 'No conversations yet.')
          }}
        </p>
      </div>
    </aside>

    <section
      v-if="active"
      class="flex min-h-0 flex-1 flex-col overflow-hidden"
    >
      <!-- Click-away layer that closes any open ⋮ message menu. -->
      <div
        v-if="openMenuId !== null"
        class="fixed inset-0 z-10"
        @click="openMenuId = null"
      />
      <!-- Click-away layer that closes the whisper member picker. -->
      <div
        v-if="whisperPickerOpen"
        class="fixed inset-0 z-10"
        @click="whisperPickerOpen = false"
      />
      <header
        class="flex items-center gap-3 border-b border-black/30 px-4 py-3 shadow-sm"
      >
        <div
          class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-[11px] font-semibold uppercase text-white"
        >
          {{ activeInitials }}
        </div>
        <h2 class="flex-1 text-sm font-semibold text-white">{{ activeTitle }}</h2>
        <button
          type="button"
          class="rounded-md px-2 py-1 text-xs text-gray-400 transition hover:bg-red-500/10 hover:text-red-400"
          :title="$t('craftable-pro', 'Leave conversation')"
          @click="leaveConversation"
        >
          {{ $t('craftable-pro', 'Leave') }}
        </button>
      </header>

      <ul
        v-if="threadMessages.length"
        ref="scrollEl"
        class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto px-4 py-4"
      >
        <li
          v-for="m in threadMessages"
          :key="m.id"
          class="group flex items-center gap-1"
          :class="m.user_id === currentUserId ? 'justify-end' : 'justify-start'"
        >
          <!-- ⋮ menu: every message can be replied to; oversight (or a whisper
               recipient) also gets "Reply privately". -->
          <div class="relative order-last flex-shrink-0">
            <button
              type="button"
              class="rounded p-1 text-gray-500 opacity-0 transition hover:bg-white/10 hover:text-white group-hover:opacity-100"
              :class="openMenuId === m.id ? 'opacity-100' : ''"
              :title="$t('craftable-pro', 'More')"
              @click="toggleMenu(m.id)"
            >
              <EllipsisVerticalIcon class="h-4 w-4" />
            </button>
            <div
              v-if="openMenuId === m.id"
              class="absolute z-20 mt-1 w-44 overflow-hidden rounded-md bg-[#2b2d31] shadow-xl shadow-black/60 ring-1 ring-black/40"
              :class="m.user_id === currentUserId ? 'right-0' : 'left-0'"
            >
              <button
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-gray-200 transition hover:bg-white/10"
                @click="startReply(m)"
              >
                <ArrowUturnLeftIcon class="h-3.5 w-3.5 text-gray-400" />
                {{ $t('craftable-pro', 'Reply') }}
              </button>
              <button
                v-if="canReplyPrivately(m)"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-gray-200 transition hover:bg-white/10"
                @click="startPrivateReply(m)"
              >
                <LockClosedIcon class="h-3.5 w-3.5 text-amber-400" />
                {{ $t('craftable-pro', 'Reply privately') }}
              </button>
            </div>
          </div>

          <div
            class="max-w-[70%] rounded-2xl px-3 py-2 text-sm"
            :class="
              m.private_to_id
                ? 'bg-violet-500/15 text-violet-100 ring-1 ring-violet-500/40'
                : m.visibility === 'internal'
                  ? 'bg-amber-500/15 text-amber-100 ring-1 ring-amber-500/40'
                  : m.user_id === currentUserId
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white/10 text-gray-100'
            "
          >
            <!-- Quoted message this is a reply to. -->
            <div
              v-if="m.reply_to"
              class="mb-1 rounded border-l-2 border-white/30 bg-black/20 px-2 py-1 text-[11px] opacity-80"
            >
              <span class="font-semibold">{{ quotedName(m.reply_to) || $t('craftable-pro', 'message') }}</span>
              <span class="opacity-70"> · {{ truncate(m.reply_to.body) }}</span>
            </div>
            <!-- Whisper: visible only to the sender and this one recipient. -->
            <p
              v-if="m.private_to_id"
              class="mb-1 flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-violet-300"
            >
              <LockClosedIcon class="h-3 w-3" />
              {{ $t('craftable-pro', 'Private · only you and') }} {{ whisperOtherName(m) }}
            </p>
            <p
              v-else-if="m.visibility === 'internal'"
              class="mb-1 flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-amber-300"
            >
              <LockClosedIcon class="h-3 w-3" />
              {{ $t('craftable-pro', 'Internal · staff only') }}
            </p>
            <p
              v-if="m.user_id !== currentUserId"
              class="mb-0.5 text-[11px] font-semibold opacity-80"
            >
              {{ senderName(m) }}
            </p>
            <p class="whitespace-pre-wrap break-words">{{ m.body }}</p>
            <p class="mt-1 flex items-center justify-end gap-1 text-[10px] opacity-60">
              {{ formatTime(m.created_at) }}
              <!-- Read receipt on my own messages: single check = sent, double = seen. -->
              <span
                v-if="m.user_id === currentUserId"
                class="inline-flex items-center"
                :class="isSeen(m) ? 'text-sky-300 opacity-100' : ''"
                :title="isSeen(m) ? $t('craftable-pro', 'Seen') : $t('craftable-pro', 'Sent')"
              >
                <CheckIcon class="h-3 w-3" />
                <CheckIcon v-if="isSeen(m)" class="-ml-1.5 h-3 w-3" />
              </span>
            </p>
          </div>
        </li>
      </ul>
      <div
        v-else
        class="flex flex-1 items-center justify-center text-xs text-gray-500"
      >
        {{ $t('craftable-pro', 'No messages yet. Say hi.') }}
      </div>

      <form
        class="flex flex-col gap-2 border-t border-black/30 px-4 py-3"
        @submit.prevent="sendMessage"
      >
        <div
          v-if="replyingTo || whisperTo"
          class="flex items-center gap-2 rounded-md border-l-2 px-3 py-1.5 text-[11px]"
          :class="
            whisperTo
              ? 'border-violet-400/60 bg-violet-500/10 text-violet-100'
              : 'border-indigo-400/60 bg-indigo-500/10 text-indigo-100'
          "
        >
          <LockClosedIcon
            v-if="whisperTo"
            class="h-3.5 w-3.5 flex-shrink-0 text-violet-400"
          />
          <ArrowUturnLeftIcon v-else class="h-3.5 w-3.5 flex-shrink-0 text-indigo-300" />
          <span class="min-w-0 flex-1 truncate">
            <template v-if="whisperTo">
              {{ replyingTo ? $t('craftable-pro', 'Replying privately to') : $t('craftable-pro', 'Whispering to') }}
              <span class="font-semibold">{{ whisperToName }}</span>
            </template>
            <template v-else>
              {{ $t('craftable-pro', 'Replying to') }}
              <span class="font-semibold">{{ senderName(replyingTo) }}</span>
            </template>
            <span v-if="replyingTo" class="opacity-70"> · {{ truncate(replyingTo.body, 60) }}</span>
          </span>
          <button
            type="button"
            class="flex-shrink-0 rounded p-0.5 transition hover:bg-white/10 hover:text-white"
            :title="$t('craftable-pro', 'Cancel')"
            @click="cancelReply"
          >
            <XMarkIcon class="h-3.5 w-3.5" />
          </button>
        </div>

        <div v-if="isStaff" class="flex items-center gap-2">
          <div class="inline-flex rounded-md bg-[#1e1f22] p-0.5 text-xs">
            <button
              type="button"
              class="flex items-center gap-1 rounded px-2.5 py-1 font-medium transition"
              :class="
                composeMode === 'public'
                  ? 'bg-indigo-600 text-white'
                  : 'text-gray-400 hover:text-gray-200'
              "
              @click="composeMode = 'public'"
            >
              <GlobeAltIcon class="h-3.5 w-3.5" />
              {{ $t('craftable-pro', 'Public') }}
            </button>
            <button
              type="button"
              class="flex items-center gap-1 rounded px-2.5 py-1 font-medium transition"
              :class="
                composeMode === 'internal'
                  ? 'bg-amber-500 text-black'
                  : 'text-gray-400 hover:text-gray-200'
              "
              @click="composeMode = 'internal'"
            >
              <LockClosedIcon class="h-3.5 w-3.5" />
              {{ $t('craftable-pro', 'Private') }}
            </button>
          </div>

          <!-- Whisper: pick a member of this conversation to message 1-to-1. -->
          <div v-if="whisperableMembers.length" class="relative">
            <button
              type="button"
              class="flex items-center gap-1 rounded-md bg-[#1e1f22] px-2.5 py-1.5 text-xs font-medium text-violet-300 transition hover:text-violet-200"
              :class="whisperPickerOpen ? 'ring-1 ring-violet-500/50' : ''"
              :title="$t('craftable-pro', 'Whisper to a member')"
              @click="whisperPickerOpen = !whisperPickerOpen"
            >
              <LockClosedIcon class="h-3.5 w-3.5" />
              {{ $t('craftable-pro', 'Whisper') }}
            </button>
            <div
              v-if="whisperPickerOpen"
              class="absolute bottom-full left-0 z-20 mb-1 max-h-60 w-52 overflow-y-auto rounded-md bg-[#2b2d31] py-1 shadow-xl shadow-black/60 ring-1 ring-black/40"
            >
              <button
                v-for="member in whisperableMembers"
                :key="member.id"
                type="button"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-gray-200 transition hover:bg-white/10"
                @click="selectWhisperTarget(member)"
              >
                <LockClosedIcon class="h-3 w-3 text-violet-400" />
                {{ memberName(member) }}
              </button>
            </div>
          </div>

          <span class="text-[11px] text-gray-500">
            {{
              composeMode === 'internal'
                ? $t('craftable-pro', 'Only staff can see this — hidden from the client.')
                : $t('craftable-pro', 'The client can see this message.')
            }}
          </span>
        </div>

        <div class="flex items-end gap-2">
          <textarea
            v-model="messageBody"
            rows="1"
            :placeholder="
              composeMode === 'internal'
                ? $t('craftable-pro', 'Write an internal note…')
                : $t('craftable-pro', 'Type a message')
            "
            class="flex-1 resize-none rounded-md bg-[#1e1f22] px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1"
            :class="
              composeMode === 'internal'
                ? 'ring-1 ring-amber-500/40 focus:ring-amber-500/60'
                : 'focus:ring-indigo-500/60'
            "
            @keydown.enter.exact.prevent="sendMessage"
          />
          <button
            type="submit"
            class="rounded-md px-3 py-2 text-sm font-medium transition disabled:opacity-50"
            :class="
              composeMode === 'internal'
                ? 'bg-amber-500 text-black hover:bg-amber-400'
                : 'bg-indigo-600 text-white hover:bg-indigo-500'
            "
            :disabled="!messageBody.trim()"
          >
            {{ $t('craftable-pro', 'Send') }}
          </button>
        </div>
      </form>
    </section>

    <section
      v-else
      class="relative flex flex-1 items-center justify-center overflow-hidden"
    >
      <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.08),_transparent_60%)]"
      />
      <div class="relative z-10 max-w-md px-8 text-center">
        <div
          class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-xl shadow-indigo-900/40"
        >
          <ChatBubbleLeftRightIcon class="h-10 w-10 text-white" />
        </div>
        <h3 class="mt-6 text-xl font-semibold text-white">
          {{ $t('craftable-pro', 'Your messages') }}
        </h3>
        <p class="mt-2 text-sm text-gray-400">
          {{
            $t(
              'craftable-pro',
              'Pick a conversation from the list, or start a new one.'
            )
          }}
        </p>
        <button
          v-if="users.length"
          type="button"
          class="mt-6 inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-indigo-900/30 transition hover:bg-indigo-500"
          @click="onNewChat"
        >
          <PlusIcon class="h-4 w-4" />
          {{ $t('craftable-pro', 'New chat') }}
        </button>
      </div>
    </section>

    <div
      v-if="pickerOpen"
      class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 px-4 pt-24"
      @click.self="pickerOpen = false"
    >
      <div
        class="w-full max-w-md overflow-hidden rounded-lg bg-[#2b2d31] shadow-2xl shadow-black/60"
      >
        <div class="flex items-center justify-between border-b border-black/40 px-4 py-3">
          <h3 class="text-sm font-semibold text-white">
            {{ $t('craftable-pro', 'Start a new chat') }}
          </h3>
          <button
            type="button"
            class="rounded p-1 text-gray-400 transition hover:bg-white/10 hover:text-white"
            @click="pickerOpen = false"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <div class="px-4 py-3">
          <div class="relative">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500"
            />
            <input
              v-model="userSearch"
              type="text"
              :placeholder="$t('craftable-pro', 'Search users')"
              class="w-full rounded-md bg-[#1e1f22] py-1.5 pl-7 pr-2 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/60"
            />
          </div>
        </div>

        <ul v-if="filteredUsers.length" class="max-h-80 overflow-y-auto px-2 pb-3">
          <li v-for="u in filteredUsers" :key="u.id">
            <button
              type="button"
              class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-gray-300 transition hover:bg-white/5 hover:text-white"
              @click="startChat(u.id)"
            >
              <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-[11px] font-semibold uppercase text-white"
              >
                {{
                  `${u.first_name?.[0] ?? ''}${u.last_name?.[0] ?? ''}`.toUpperCase() || '?'
                }}
              </div>
              <span class="text-sm">
                {{ `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim() || `#${u.id}` }}
              </span>
            </button>
          </li>
        </ul>
        <p v-else class="px-4 pb-4 text-center text-xs text-gray-500">
          {{ $t('craftable-pro', 'No users found.') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  LockClosedIcon,
  GlobeAltIcon,
  EllipsisVerticalIcon,
  ArrowUturnLeftIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  conversations: { type: Array, required: true },
  users: { type: Array, default: () => [] },
  active: { type: Object, default: null },
  oversightRoles: { type: Array, default: () => [] },
})

const page = usePage()
const currentUserId = computed(() => page.props.auth?.user?.id)
const isStaff = computed(() => {
  const roles = page.props.auth?.roles ?? []
  return (
    roles.includes('Administrator') ||
    roles.includes('super-admin') ||
    roles.includes('account-manager')
  )
})
// Oversight roles (super-admin/admin) may whisper any member of a conversation.
const isOversight = computed(() => {
  const roles = page.props.auth?.roles ?? []
  return props.oversightRoles.some((r) => roles.includes(r))
})

// True if the current user may send a private reply to message `m`: oversight
// users can whisper anyone; anyone else can only whisper BACK on a whisper they
// received (reply to its sender).
function canReplyPrivately(m) {
  if (isOversight.value) return true
  return m.private_to_id != null && m.private_to_id === currentUserId.value
}

const search = ref('')
const activeId = computed(() => props.active?.id ?? null)
const messageBody = ref('')
// 'public'  → the client can see the message
// 'internal' → staff-only note (staff ↔ staff, hidden from the client)
const composeMode = ref('public')
// When set, the next message is a reply quoting this message.
const replyingTo = ref(null)
// When set, the next message is a private whisper to this user (id + name) —
// only the two of you can see it.
const whisperTo = ref(null)
// Whether the "Whisper to a member" picker dropdown is open.
const whisperPickerOpen = ref(false)
// Id of the message whose ⋮ menu is currently open (null = none).
const openMenuId = ref(null)

// Members you can whisper to: staff only (never a client), excluding yourself.
const whisperableMembers = computed(() => {
  if (!isStaff.value) return []
  return (props.active?.members ?? []).filter(
    (m) => m.id !== currentUserId.value && m.is_staff
  )
})

// Display name of the current whisper target (for the compose preview).
const whisperToName = computed(() => {
  if (!whisperTo.value) return ''
  return memberName(whisperTo.value)
})

const activeTitle = computed(() => {
  if (!props.active) return ''
  if (props.active.type === 'group' && props.active.name) return props.active.name
  const other =
    props.active.members?.find((m) => m.id !== currentUserId.value) ??
    props.active.members?.[0]
  if (other) return `${other.first_name ?? ''} ${other.last_name ?? ''}`.trim()
  return props.active.name ?? '(unnamed)'
})

const activeInitials = computed(
  () =>
    activeTitle.value
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0]?.toUpperCase() ?? '')
      .join('') || '?'
)

function openConversation(id) {
  router.get(
    route('chats.show', id),
    {},
    { preserveScroll: true, preserveState: true }
  )
}

function leaveConversation() {
  if (!props.active) return
  if (!window.confirm('Leave this conversation?')) return
  router.delete(route('chats.leave', props.active.id), {
    preserveScroll: true,
  })
}

function sendMessage() {
  const body = messageBody.value.trim()
  if (!body || !props.active) return
  router.post(
    route('chats.messages.store', props.active.id),
    {
      body,
      visibility: isStaff.value ? composeMode.value : 'public',
      reply_to_id: replyingTo.value?.id ?? null,
      private_to_id: whisperTo.value?.id ?? null,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        messageBody.value = ''
        replyingTo.value = null
        whisperTo.value = null
        whisperPickerOpen.value = false
      },
    }
  )
}

// Reply to a message (from the ⋮ menu). A normal reply keeps the current
// compose mode — it does NOT force the message to be staff-only.
function startReply(message) {
  replyingTo.value = message
  openMenuId.value = null
}

// Whisper directly to a chosen member of the conversation (not tied to a message).
function selectWhisperTarget(member) {
  whisperTo.value = {
    id: member.id,
    first_name: member.first_name ?? '',
    last_name: member.last_name ?? '',
  }
  replyingTo.value = null
  whisperPickerOpen.value = false
}

// Reply privately to a message: the next message becomes a whisper visible only
// to you and that message's author. Quotes the message too. The recipient is the
// OTHER party — if it's a whisper I received, that's its sender.
function startPrivateReply(message) {
  replyingTo.value = message
  whisperTo.value = {
    id: message.user_id,
    first_name: message.sender?.first_name ?? '',
    last_name: message.sender?.last_name ?? '',
  }
  openMenuId.value = null
}

function cancelReply() {
  replyingTo.value = null
  whisperTo.value = null
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

const filteredConversations = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return props.conversations
  return props.conversations.filter(
    (c) =>
      displayNameFor(c).toLowerCase().includes(q) ||
      (c.last_message?.body ?? '').toLowerCase().includes(q)
  )
})

function otherMember(conv) {
  if (!conv.members?.length) return null
  if (conv.type === 'private') {
    return (
      conv.members.find((m) => m.id !== currentUserId.value) ?? conv.members[0]
    )
  }
  return null
}

function displayNameFor(conv) {
  if (conv.type === 'group' && conv.name) return conv.name
  const other = otherMember(conv)
  if (other) return `${other.first_name ?? ''} ${other.last_name ?? ''}`.trim()
  return conv.name ?? '(unnamed)'
}

function initialsFor(conv) {
  const name = displayNameFor(conv)
  return (
    name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0]?.toUpperCase() ?? '')
      .join('') || '?'
  )
}

function senderName(m) {
  if (m.sender) {
    const name = `${m.sender.first_name ?? ''} ${m.sender.last_name ?? ''}`.trim()
    if (name) return name
  }
  return `#${m.user_id}`
}

// A member's display name, falling back to "#id" when they have no name set.
function memberName(member) {
  const name = `${member.first_name ?? ''} ${member.last_name ?? ''}`.trim()
  return name || `#${member.id}`
}

function quotedName(reply) {
  if (!reply?.sender) return ''
  return `${reply.sender.first_name ?? ''} ${reply.sender.last_name ?? ''}`.trim()
}

// The OTHER party of a whisper from the current user's point of view: the
// recipient if I sent it, otherwise the sender.
function whisperOtherName(m) {
  if (m.user_id === currentUserId.value) {
    if (!m.recipient) return ''
    return `${m.recipient.first_name ?? ''} ${m.recipient.last_name ?? ''}`.trim()
  }
  return senderName(m)
}

function truncate(text, len = 80) {
  if (!text) return ''
  return text.length > len ? `${text.slice(0, len)}…` : text
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = dayjs(dateStr)
  const diffHours = dayjs().diff(d, 'hour')
  if (diffHours < 24) return d.format('HH:mm')
  if (diffHours < 24 * 7) return d.format('ddd')
  return d.format('MMM D')
}

const pickerOpen = ref(false)
const userSearch = ref('')

const filteredUsers = computed(() => {
  const q = userSearch.value.trim().toLowerCase()
  if (!q) return props.users
  return props.users.filter((u) =>
    `${u.first_name ?? ''} ${u.last_name ?? ''}`.toLowerCase().includes(q)
  )
})

function onNewChat() {
  userSearch.value = ''
  pickerOpen.value = true
}

function startChat(userId) {
  router.post(
    route('chats.store'),
    { user_id: userId },
    {
      preserveScroll: true,
      onFinish: () => {
        pickerOpen.value = false
      },
    }
  )
}

const threadMessages = ref([])
const scrollEl = ref(null)

function scrollToBottom() {
  if (scrollEl.value) {
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  }
}

watch(
  () => props.active?.messages,
  (msgs) => { threadMessages.value = msgs ? [...msgs] : [] },
  { immediate: true, deep: false }
)

// Reactive copy of members so read-receipt updates re-render the "Seen" marks.
const threadMembers = ref([])
watch(
  () => props.active?.members,
  (members) => { threadMembers.value = members ? [...members] : [] },
  { immediate: true, deep: false }
)

// The most recent time any OTHER member read this conversation.
const othersLastReadAt = computed(() => {
  const times = threadMembers.value
    .filter((m) => m.id !== currentUserId.value && m.last_read_at)
    .map((m) => new Date(m.last_read_at).getTime())
  return times.length ? Math.max(...times) : 0
})

// One of my messages is "seen" once another member read past the time I sent it.
function isSeen(m) {
  if (m.user_id !== currentUserId.value || !m.created_at) return false
  return othersLastReadAt.value >= new Date(m.created_at).getTime()
}

watch(
  () => threadMessages.value.length,
  () => nextTick(scrollToBottom),
  { flush: 'post' }
)

watch(activeId, () => nextTick(scrollToBottom))

let subscribedChannel = null
let internalChannel = null
let whisperChannel = null

function pushMessage(e) {
  if (threadMessages.value.some((m) => m.id === e.id)) return
  threadMessages.value.push({
    id: e.id,
    body: e.body,
    user_id: e.user_id,
    visibility: e.visibility ?? 'public',
    reply_to_id: e.reply_to_id ?? null,
    reply_to: e.reply_to ?? null,
    private_to_id: e.private_to_id ?? null,
    recipient: e.recipient ?? null,
    created_at: e.created_at,
    sender: e.sender,
  })
}

function subscribe(id) {
  unsubscribe()
  if (!id) return
  if (!window.Echo) {
    console.warn('[chat] Echo not available — realtime disabled')
    return
  }
  subscribedChannel = `conversation.${id}`
  console.log('[chat] subscribing to private-' + subscribedChannel)
  window.Echo.private(subscribedChannel)
    .listen('.message.sent', (e) => {
      console.log('[chat] received message.sent', e)
      pushMessage(e)
    })
    .listen('.conversation.read', (e) => {
      // Another member read the conversation → update their last_read_at so my
      // "Seen" marks refresh live.
      const member = threadMembers.value.find((m) => m.id === e.user_id)
      if (member) member.last_read_at = e.last_read_at
    })
    .error((err) => console.error('[chat] channel error', err?.status, err?.type, err))

  // Staff also listen on the internal channel for staff-only notes. Clients
  // are rejected by the channel authorization, so they never reach this.
  if (isStaff.value) {
    internalChannel = `conversation.${id}.internal`
    window.Echo.private(internalChannel)
      .listen('.message.sent', (e) => {
        console.log('[chat] received internal message.sent', e)
        pushMessage(e)
      })
      .error((err) => console.error('[chat] internal channel error', err?.status, err?.type, err))
  }

  // Personal whisper channel — private replies addressed to me arrive here, not
  // on the conversation channel. Only append the ones for the open conversation.
  if (currentUserId.value) {
    whisperChannel = `whisper.${currentUserId.value}`
    window.Echo.private(whisperChannel)
      .listen('.message.sent', (e) => {
        console.log('[chat] received whisper.sent', e)
        if (e.conversation_id === id) pushMessage(e)
      })
      .error((err) => console.error('[chat] whisper channel error', err?.status, err?.type, err))
  }
}

function unsubscribe() {
  if (subscribedChannel && window.Echo) {
    window.Echo.leave(`private-${subscribedChannel}`)
  }
  if (internalChannel && window.Echo) {
    window.Echo.leave(`private-${internalChannel}`)
  }
  if (whisperChannel && window.Echo) {
    window.Echo.leave(`private-${whisperChannel}`)
  }
  subscribedChannel = null
  internalChannel = null
  whisperChannel = null
}

watch(activeId, (id) => {
  composeMode.value = 'public'
  replyingTo.value = null
  whisperTo.value = null
  whisperPickerOpen.value = false
  openMenuId.value = null
  subscribe(id)
}, { immediate: true })
onBeforeUnmount(unsubscribe)
</script>
