<?php

namespace App\Actions\AgentConversations;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetUserConversations
{
    public function handle(int $userId): Collection
    {
        return DB::table('agent_conversations')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);
    }
}
