<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No follows and no thresholds (decisions.md, 25 Nov): only going players get
 * emails, so M04's event_follows table goes. down() recreates it as M04 did.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('event_follows');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('event_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('notify_at_count');
            $table->boolean('notify_on_drop')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }
};
