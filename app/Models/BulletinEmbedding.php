<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletinEmbedding extends Model
{
    /** @use HasFactory<\Database\Factories\BulletinEmbeddingFactory> */
    use HasFactory;

    protected $fillable = [
        'bulletin_id', 'content', 'embedding', 'chunk_index', 'chunk_text', 'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class);
    }

    public function scopeNearestTo($query, array $embedding, int $limit = 5)
    {
        return $query
            ->selectRaw('*, embedding <=> ? as distance', [json_encode($embedding)])
            ->orderByRaw('embedding <=> ?', [json_encode($embedding)])
            ->limit($limit);
    }
}
