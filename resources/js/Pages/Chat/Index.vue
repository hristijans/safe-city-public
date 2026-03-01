<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const props = defineProps({
    conversations: Array,
    conversation: Object,
    messages: Array,
});

// Local message list — seeded from server props, then appended as we stream
const localMessages = ref(
    (props.messages ?? []).map(m => ({ role: m.role, content: m.content }))
);

const input = ref('');
const isStreaming = ref(false);
const messagesEl = ref(null);

function scrollToBottom() {
    nextTick(() => {
        if (messagesEl.value) {
            messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
        }
    });
}

async function send() {
    const text = input.value.trim();
    if (!text || isStreaming.value) return;

    input.value = '';
    isStreaming.value = true;

    // Optimistically add the user message
    localMessages.value.push({ role: 'user', content: text });
    // Placeholder for the streaming assistant message
    localMessages.value.push({ role: 'assistant', content: '' });
    scrollToBottom();

    const assistantIndex = localMessages.value.length - 1;
    const conversationId = props.conversation?.id ?? null;
    const url = conversationId
        ? route('chat.stream', conversationId)
        : route('chat.stream.new');

    try {
        const xsrfToken = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        );

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken,
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({ message: text }),
        });

        if (!response.ok) {
            localMessages.value[assistantIndex].content = 'Something went wrong. Please try again.';
            return;
        }

        // Conversation ID is known before streaming starts
        const newConversationId = response.headers.get('X-Conversation-Id');
        if (newConversationId && !conversationId) {
            router.replace(route('chat.show', newConversationId));
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop(); // keep incomplete last line

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const payload = line.slice(6).trim();
                if (payload === '[DONE]') break;

                try {
                    const event = JSON.parse(payload);
                    if (event.type === 'text-delta') {
                        localMessages.value[assistantIndex].content += event.delta;
                        scrollToBottom();
                    }
                } catch {
                    // ignore malformed events
                }
            }
        }
    } catch {
        localMessages.value[assistantIndex].content = 'Connection error. Please try again.';
    } finally {
        isStreaming.value = false;
        scrollToBottom();
    }
}

function onKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}
</script>

<template>
    <AppLayout title="Chat">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chat
            </h2>
        </template>

        <div class="flex h-[calc(100vh-8rem)]">
            <!-- Sidebar -->
            <div class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
                <div class="p-4 border-b border-gray-200">
                    <Link
                        :href="route('chat.index')"
                        class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
                    >
                        New Chat
                    </Link>
                </div>

                <nav class="flex-1 overflow-y-auto p-2 space-y-1">
                    <Link
                        v-for="conv in conversations"
                        :key="conv.id"
                        :href="route('chat.show', conv.id)"
                        class="block px-3 py-2 rounded-lg text-sm truncate hover:bg-gray-100"
                        :class="conversation?.id === conv.id ? 'bg-gray-100 font-medium text-gray-900' : 'text-gray-600'"
                    >
                        {{ conv.title }}
                    </Link>

                    <p v-if="!conversations.length" class="px-3 py-2 text-xs text-gray-400">
                        No conversations yet.
                    </p>
                </nav>
            </div>

            <!-- Main area -->
            <div class="flex-1 flex flex-col bg-gray-50 min-w-0">
                <!-- Empty state -->
                <div v-if="!conversation && !localMessages.length" class="flex-1 flex items-center justify-center">
                    <p class="text-gray-400 text-sm">Ask a question about safety bulletins to get started.</p>
                </div>

                <!-- Messages -->
                <div v-else ref="messagesEl" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div
                        v-for="(msg, i) in localMessages"
                        :key="i"
                        class="flex"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-xl px-4 py-2 rounded-lg text-sm whitespace-pre-wrap"
                            :class="msg.role === 'user'
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-800 border border-gray-200'"
                        >
                            <span v-if="msg.role === 'assistant' && !msg.content && isStreaming" class="animate-pulse text-gray-400">
                                Thinking…
                            </span>
                            <span v-else>{{ msg.content }}</span>
                        </div>
                    </div>
                </div>

                <!-- Input bar -->
                <div class="border-t border-gray-200 bg-white p-4">
                    <div class="flex gap-3 items-end">
                        <textarea
                            v-model="input"
                            rows="1"
                            placeholder="Ask about safety bulletins…"
                            class="flex-1 resize-none rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                            :disabled="isStreaming"
                            @keydown="onKeydown"
                        />
                        <button
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isStreaming || !input.trim()"
                            @click="send"
                        >
                            Send
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Enter to send · Shift+Enter for newline</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
