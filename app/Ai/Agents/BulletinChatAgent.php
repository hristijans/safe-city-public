<?php

namespace App\Ai\Agents;

use App\Models\BulletinEmbedding;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

class BulletinChatAgent implements Agent, HasTools
{
    use Promptable, RemembersConversations;

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are a helpful assistant for SafeCity, a platform that tracks public safety bulletins.
        You have access to a database of safety bulletins via a search tool.
        When answering questions, always search the bulletins first to ground your response in real data.
        Cite the source URL from the bulletin metadata where relevant.
        If no relevant bulletins are found, say so honestly rather than guessing.
        Be concise and factual.
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(
                model: BulletinEmbedding::class,
                column: 'embedding',
                minSimilarity: 0.5,
                limit: 5,
            )->withDescription('Search the safety bulletin database for information relevant to the user\'s question.'),
        ];
    }
}
