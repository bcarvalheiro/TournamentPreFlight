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
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->timestamp('started_at');
            $table->timestamp('finished_at');
            $table->string('status');
            $table->unsignedInteger('events_created')->default(0);
            $table->unsignedInteger('events_updated')->default(0);
            $table->unsignedInteger('events_flagged')->default(0);
            $table->unsignedInteger('events_unchanged')->default(0);
            $table->text('error')->nullable();
            $table->json('payload_sample')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
