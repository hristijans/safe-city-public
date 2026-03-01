<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    conversations: Array,
    conversation: Object,
    messages: Array,
});
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
            <div class="flex-1 flex flex-col bg-gray-50">
                <!-- Empty state -->
                <div v-if="!conversation" class="flex-1 flex items-center justify-center">
                    <p class="text-gray-400 text-sm">Start a new chat to get going.</p>
                </div>

                <!-- Conversation messages -->
                <div v-else class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-xl px-4 py-2 rounded-lg text-sm whitespace-pre-wrap"
                            :class="msg.role === 'user'
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-800 border border-gray-200'"
                        >
                            {{ msg.content }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
