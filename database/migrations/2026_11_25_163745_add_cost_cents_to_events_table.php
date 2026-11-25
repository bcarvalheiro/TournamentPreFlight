<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The entry fee in euro cents (M09, the event page's details). pokedata has
 * it as free text on some events ("5€", "6,5", "7 euros"); M10 parses it.
 * Null means unknown, 0 means free. Whole cents avoid rounding errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('cost_cents')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('cost_cents');
        });
    }
};
