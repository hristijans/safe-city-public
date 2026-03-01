<?php

namespace App\Http\Controllers;

use App\Ai\Agents\BulletinChatAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = DB::table('agent_conversations')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'conversation' => null,
            'messages' => [],
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $id)
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        $conversations = DB::table('agent_conversations')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'conversation' => $conversation,
            'messages' => $messages,
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

            // After the loop, RememberConversation middleware's then() callback has fired
            // and conversationId is now populated on the response.
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
