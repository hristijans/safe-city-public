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
        Schema::create('scraper_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('scraper_settings')->insert([
            [
                'key' => 'mvr_last_scraped_page',
                'value' => '0',
                'description' => 'Last successfully scraped pagination page for MVR bulletins',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'mvr_total_pages',
                'value' => '0',
                'description' => 'Total number of pagination pages detected',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_settings');
    }
};
