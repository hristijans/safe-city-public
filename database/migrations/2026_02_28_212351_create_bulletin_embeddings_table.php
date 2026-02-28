<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('bulletin_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('bulletins')->onDelete('cascade');
            $table->integer('chunk_index')->default(0);
            $table->text('chunk_text');
            $table->vector('embedding', 1536); // pgvector column
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for faster lookups
            $table->index('bulletin_id');
            $table->index(['bulletin_id', 'chunk_index']);
        });

        // Create an index for vector similarity search using cosine distance
        DB::statement('CREATE INDEX bulletin_embeddings_embedding_idx ON bulletin_embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulletin_embeddings');
    }
};
