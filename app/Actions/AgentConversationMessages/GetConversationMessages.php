<?php

namespace App\Actions\AgentConversationMessages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetConversationMessages
{
    public function handle(string $conversationId): Collection
    {
        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'usage', 'created_at']);
    }
}
