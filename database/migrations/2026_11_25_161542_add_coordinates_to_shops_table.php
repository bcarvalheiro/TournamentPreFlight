<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a shop is (M09, the event page's map and directions). pokedata sends
 * latitude and longitude with every event; M10 fills them. Six decimals is
 * about 10 cm, more than a map pin needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('latitude', 9, 6)->nullable()->after('district');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
