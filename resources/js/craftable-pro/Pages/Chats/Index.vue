<template>
  <Head :title="$t('craftable-pro', 'Messages')" />

  <div class="flex h-[100dvh] flex-1 overflow-hidden bg-[#1e1f22] text-gray-100">
    <aside class="flex w-72 flex-shrink-0 flex-col border-r border-black/30 bg-[#2b2d31]">
      <div
        class="flex items-center justify-between border-b border-black/30 px-4 py-3 shadow-sm"
      >
        <h2 class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
          {{ $t('craftable-pro', 'Direct Messages') }}
        </h2>
        <button
          v-if="!isClient"
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
          class="flex"
          :class="m.user_id === currentUserId ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[70%] rounded-2xl px-3 py-2 text-sm"
            :class="
              m.user_id === currentUserId
                ? 'bg-indigo-600 text-white'
                : 'bg-white/10 text-gray-100'
            "
          >
            <p class="whitespace-pre-wrap break-words">{{ m.body }}</p>
            <p class="mt-1 text-right text-[10px] opacity-60">
              {{ formatTime(m.created_at) }}
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
        class="flex items-end gap-2 border-t border-black/30 px-4 py-3"
        @submit.prevent="sendMessage"
      >
        <textarea
          v-model="messageBody"
          rows="1"
          :placeholder="$t('craftable-pro', 'Type a message')"
          class="flex-1 resize-none rounded-md bg-[#1e1f22] px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/60"
          @keydown.enter.exact.prevent="sendMessage"
        />
        <button
          type="submit"
          class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:opacity-50"
          :disabled="!messageBody.trim()"
        >
          {{ $t('craftable-pro', 'Send') }}
        </button>
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
          v-if="!isClient"
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
} from '@heroicons/vue/24/outline'

const props = defineProps({
  conversations: { type: Array, required: true },
  users: { type: Array, default: () => [] },
  active: { type: Object, default: null },
})

const page = usePage()
const currentUserId = computed(() => page.props.auth?.user?.id)
const isClient = computed(() => (page.props.auth?.roles ?? []).includes('client'))

const search = ref('')
const activeId = computed(() => props.active?.id ?? null)
const messageBody = ref('')

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
    { body },
    {
      preserveScroll: true,
      onSuccess: () => {
        messageBody.value = ''
      },
    }
  )
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

watch(
  () => threadMessages.value.length,
  () => nextTick(scrollToBottom),
  { flush: 'post' }
)

watch(activeId, () => nextTick(scrollToBottom))

let subscribedChannel = null

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
      if (threadMessages.value.some((m) => m.id === e.id)) return
      threadMessages.value.push({
        id: e.id,
        body: e.body,
        user_id: e.user_id,
        created_at: e.created_at,
        sender: e.sender,
      })
    })
    .error((err) => console.error('[chat] channel error', err?.status, err?.type, err))
}

function unsubscribe() {
  if (subscribedChannel && window.Echo) {
    window.Echo.leave(`private-${subscribedChannel}`)
  }
  subscribedChannel = null
}

watch(activeId, (id) => subscribe(id), { immediate: true })
onBeforeUnmount(unsubscribe)
</script>
