<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay on (no WithoutModelEvents): the Event and PlayerProfile
     * saving hooks set ends_at and is_child and must run on every path.
     */
    public function run(): void
    {
        $this->call([
            DemoSeeder::class,
        ]);
    }
}
