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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained();
            $table->string('source');
            $table->string('official_guid')->nullable()->unique();
            $table->string('official_url')->nullable()->unique();
            $table->string('display_id')->nullable();
            $table->string('type');
            $table->string('title')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status');
            $table->timestamp('possibly_cancelled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
