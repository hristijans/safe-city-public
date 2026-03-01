<?php

namespace App\Services;

class TextProcessorService
{
    public function chunk(string $text, int $maxLength): array
    {
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $chunks = [];
        $words = explode(' ', $text);
        $currentChunk = '';

        foreach ($words as $word) {
            $testChunk = empty($currentChunk) ? $word : $currentChunk.' '.$word;

            if (mb_strlen($testChunk) > $maxLength) {
                // Current chunk is full, save it and start new one
                if (! empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }
                $currentChunk = $word;
            } else {
                $currentChunk = $testChunk;
            }
        }

        // Add remaining chunk
        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    public function sanitize(string $text): string
    {
        // Strip any remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace - replace multiple spaces with single space
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize line breaks (convert multiple line breaks to double line break)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove whitespace from beginning and end of each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        // Final trim
        return trim($text);
    }
}
