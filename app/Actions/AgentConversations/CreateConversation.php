<?php

namespace App\Actions\AgentConversations;

use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;

class CreateConversation
{
    public function __construct(private readonly ConversationStore $store) {}

    public function handle(int $userId, string $firstMessage): string
    {
        $title = Str::limit($firstMessage, 100, preserveWords: true);

        return $this->store->storeConversation($userId, $title);
    }
}
