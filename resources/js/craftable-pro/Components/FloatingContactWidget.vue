<template>
  <Teleport to="body">
    <template v-if="isClient">
      <button
        type="button"
        class="fixed bottom-6 right-6 z-[9999] flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-2xl shadow-indigo-900/50 transition hover:scale-105 hover:bg-indigo-500"
        :title="$t('craftable-pro', 'Contact support')"
        @click="openModal"
      >
        <ChatBubbleLeftRightIcon class="h-6 w-6" />
      </button>

      <div
        v-if="open"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 px-4"
        @click.self="open = false"
      >
        <div class="flex h-[32rem] w-full max-w-md flex-col overflow-hidden rounded-lg bg-[#2b2d31] shadow-2xl shadow-black/60">
          <header class="flex items-center gap-3 border-b border-black/40 px-4 py-3">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-xs font-semibold uppercase text-white"
            >
              {{ supportInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-white">{{ supportName }}</p>
              <p class="text-[10px] text-gray-500">Account Manager</p>
            </div>
            <button
              type="button"
              class="rounded p-1 text-gray-400 transition hover:bg-white/10 hover:text-white"
              @click="open = false"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </header>

          <ul
            ref="scrollEl"
            class="flex flex-1 flex-col gap-2 overflow-y-auto px-4 py-4"
          >
            <li v-if="loading" class="text-center text-xs text-gray-500">Loading…</li>
            <li v-else-if="!conversationId" class="text-center text-xs text-gray-500">
              Support is unavailable right now.
            </li>
            <template v-else>
              <li class="rounded-md bg-white/5 px-3 py-2 text-center text-[11px] text-gray-400">
                You are talking with Account Manager
                <span class="font-semibold text-gray-200">{{ supportName }}</span>
              </li>
              <li v-if="!messages.length" class="text-center text-xs text-gray-500">
                Say hi to start the conversation.
              </li>
              <li
                v-for="m in messages"
                :key="m.id"
                class="flex"
                :class="m.user_id === currentUserId ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[75%] rounded-2xl px-3 py-2 text-sm"
                  :class="m.user_id === currentUserId ? 'bg-indigo-600 text-white' : 'bg-white/10 text-gray-100'"
                >
                  <p class="whitespace-pre-wrap break-words">{{ m.body }}</p>
                  <!-- Read receipt on my own messages: single check = sent, double = seen. -->
                  <p
                    v-if="m.user_id === currentUserId"
                    class="mt-1 flex justify-end"
                  >
                    <span
                      class="inline-flex items-center text-white/60"
                      :class="isSeen(m) ? 'text-sky-300' : ''"
                      :title="isSeen(m) ? 'Seen' : 'Sent'"
                    >
                      <CheckIcon class="h-3 w-3" />
                      <CheckIcon v-if="isSeen(m)" class="-ml-1.5 h-3 w-3" />
                    </span>
                  </p>
                </div>
              </li>
            </template>
          </ul>

          <form
            v-if="conversationId"
            class="flex items-end gap-2 border-t border-black/40 px-4 py-3"
            @submit.prevent="send"
          >
            <textarea
              v-model="body"
              rows="1"
              placeholder="Type a message"
              class="flex-1 resize-none rounded-md bg-[#1e1f22] px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/60"
              @keydown.enter.exact.prevent="send"
            />
            <button
              type="submit"
              class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:opacity-50"
              :disabled="!body.trim() || sending"
            >
              Send
            </button>
          </form>
        </div>
      </div>
    </template>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { ChatBubbleLeftRightIcon, XMarkIcon, CheckIcon } from '@heroicons/vue/24/outline'

const page = usePage()

const open = ref(false)
const loading = ref(false)
const sending = ref(false)
const conversationId = ref(null)
const supportUser = ref(null)
const messages = ref([])
const body = ref('')
const currentUserId = ref(null)
const scrollEl = ref(null)

const isClient = computed(() =>
  (page.props.auth?.roles ?? []).includes('client')
)

const supportName = computed(() =>
  supportUser.value
    ? `${supportUser.value.first_name ?? ''} ${supportUser.value.last_name ?? ''}`.trim()
    : 'Support'
)

const supportInitials = computed(() => {
  if (!supportUser.value) return '?'
  return (
    `${supportUser.value.first_name?.[0] ?? ''}${supportUser.value.last_name?.[0] ?? ''}`.toUpperCase() ||
    '?'
  )
})

// One of my messages is "seen" once the account-manager read past it.
function isSeen(m) {
  if (m.user_id !== currentUserId.value) return false
  const read = supportUser.value?.last_read_at
  if (!read || !m.created_at) return false
  return new Date(read).getTime() >= new Date(m.created_at).getTime()
}

async function openModal() {
  open.value = true
  if (!conversationId.value) {
    await load()
  } else {
    await load() // refresh on each open to pick up replies
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get(route('chats.support'))
    conversationId.value = data.conversation_id
    supportUser.value = data.support_user
    messages.value = data.messages
    currentUserId.value = data.current_user_id
  } finally {
    loading.value = false
  }
  // Wait until loading is false and the message list has actually rendered
  // (it lives in a v-else block) before measuring scrollHeight.
  await nextTick()
  scrollToBottom()
}

async function send() {
  const text = body.value.trim()
  if (!text || !conversationId.value || sending.value) return
  sending.value = true
  try {
    const { data } = await axios.post(
      route('chats.messages.store', conversationId.value),
      { body: text }
    )
    body.value = ''

    // Optimistically append the sent message so the client sees their own bubble.
    // The broadcast uses ->toOthers() so the sender never receives it back.
    const sent = data?.message ?? {
      id: `local-${Date.now()}`,
      body: text,
      user_id: currentUserId.value,
      created_at: new Date().toISOString(),
    }
    if (!messages.value.some((m) => m.id === sent.id)) {
      messages.value.push(sent)
      await nextTick()
      scrollToBottom()
    }
  } finally {
    sending.value = false
  }
}

function scrollToBottom() {
  if (scrollEl.value) {
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  }
}

let personalChannel = null

// I listen on my own personal channel; the support conversation's messages and
// read receipts arrive here. Filter to my current conversation before applying.
function subscribePersonal() {
  if (personalChannel) return
  if (!window.Echo || !currentUserId.value) {
    if (!window.Echo) console.warn('[widget] Echo not available — realtime disabled')
    return
  }
  personalChannel = `whisper.${currentUserId.value}`
  console.log('[widget] subscribing to private-' + personalChannel)
  window.Echo.private(personalChannel)
    .listen('.message.sent', async (e) => {
      if (e.conversation_id !== conversationId.value) return
      if (messages.value.some((m) => m.id === e.id)) return
      messages.value.push({
        id: e.id,
        body: e.body,
        user_id: e.user_id,
        created_at: e.created_at,
      })
      await nextTick()
      scrollToBottom()
    })
    .listen('.conversation.read', (e) => {
      if (e.conversation_id !== conversationId.value) return
      // The account-manager read the conversation → refresh my "Seen" marks.
      if (supportUser.value && e.user_id === supportUser.value.id) {
        supportUser.value = { ...supportUser.value, last_read_at: e.last_read_at }
      }
    })
    .error((err) => console.error('[widget] channel error', err))
}

function unsubscribePersonal() {
  if (personalChannel && window.Echo) {
    window.Echo.leave(`private-${personalChannel}`)
  }
  personalChannel = null
}

watch(currentUserId, () => subscribePersonal())

// Keep the thread pinned to the latest message whenever the count changes
// (load, optimistic send, or realtime push) while the widget is open.
watch(
  () => messages.value.length,
  () => {
    if (open.value) nextTick(scrollToBottom)
  },
  { flush: 'post' }
)

onBeforeUnmount(unsubscribePersonal)
</script>
