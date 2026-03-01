<?php

namespace App\Http\Controllers;

use App\Actions\AgentConversationMessages\GetConversationMessages;
use App\Actions\AgentConversations\GetConversation;
use App\Actions\AgentConversations\GetUserConversations;
use App\Ai\Agents\BulletinChatAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly GetUserConversations $getUserConversations,
        private readonly GetConversation $getConversation,
        private readonly GetConversationMessages $getConversationMessages,
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

    public function stream(Request $request, ?string $id = null): StreamedResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $agent = $id
            ? (new BulletinChatAgent)->continue($id, as: $request->user())
            : (new BulletinChatAgent)->forUser($request->user());

        $streamResponse = $agent->stream($validated['message']);

        return response()->stream(function () use ($streamResponse): void {
            foreach ($streamResponse as $event) {
                if (connection_aborted()) {
                    return;
                }

                if ($event instanceof TextDelta) {
                    echo 'data: ' . json_encode(['type' => 'delta', 'text' => $event->delta]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();
                }
            }

            echo 'data: ' . json_encode(['type' => 'done', 'conversationId' => $streamResponse->conversationId]) . "\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }, headers: [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
