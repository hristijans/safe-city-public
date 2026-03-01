<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bulletin extends Model
{
    /** @use HasFactory<\Database\Factories\BulletinFactory> */
    use HasFactory;

    protected $fillable = [
        'data',
        'url',
        'parsed_at',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'parsed_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to get only new/unprocessed records
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope to get only processed records
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Mark the record as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public static function urlExists(string $url): bool
    {
        return self::where('url', $url)->exists();
    }

    /**
     * Get the embeddings for this bulletin
     */
    public function embeddings(): HasMany
    {
        return $this->hasMany(BulletinEmbedding::class);
    }

    /**
     * Check if this record has embeddings
     */
    public function hasEmbeddings(): bool
    {
        return $this->embeddings()->exists();
    }
}
