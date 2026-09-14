<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DosenAccountSeeder::class,
            AcademicDemoSeeder::class,
            ObeExampleSeeder::class,
            StudentScoreExampleSeeder::class,
        ]);

        // Register further domain seeders here as more persistent SALE
        // data (rubrics, ...) is introduced.
    }
}
