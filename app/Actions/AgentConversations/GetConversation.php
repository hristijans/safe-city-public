<?php

namespace App\Actions\AgentConversations;

use Illuminate\Support\Facades\DB;

class GetConversation
{
    public function handle(string $conversationId, int $userId): object
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
