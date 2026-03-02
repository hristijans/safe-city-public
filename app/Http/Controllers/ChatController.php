<?php

namespace App\Http\Controllers;

use App\Actions\AgentConversationMessages\GetConversationMessages;
use App\Actions\AgentConversations\CreateConversation;
use App\Actions\AgentConversations\GetConversation;
use App\Actions\AgentConversations\GetUserConversations;
use App\Ai\Agents\BulletinChatAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Responses\StreamableAgentResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly GetUserConversations $getUserConversations,
        private readonly GetConversation $getConversation,
        private readonly GetConversationMessages $getConversationMessages,
        private readonly CreateConversation $createConversation,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Chat/Index', [
            'conversations' => $this->getUserConversations->handle($request->user()->id),
            'conversation' => null,
            'messages' => [],
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        return Inertia::render('Chat/Index', [
            'conversations' => $this->getUserConversations->handle($request->user()->id),
            'conversation' => $this->getConversation->handle($id, $request->user()->id),
            'messages' => $this->getConversationMessages->handle($id),
        ]);
    }

    public function lastUsage(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $row = \Illuminate\Support\Facades\DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->where('role', 'assistant')
            ->orderBy('created_at', 'desc')
            ->value('usage');

        return response()->json(json_decode($row ?? '{}', true));
    }

    public function stream(Request $request, ?string $id = null): StreamableAgentResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $user = $request->user();
        $conversationId = $id ?? $this->createConversation->handle($user->id, $validated['message']);

        header('X-Conversation-Id: ' . $conversationId);

        return (new BulletinChatAgent)
            ->continue($conversationId, as: $user)
            ->stream($validated['message'])
            ->usingVercelDataProtocol();
    }
}
