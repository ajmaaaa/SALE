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
        $this->call(RoleSeeder::class);

        if (! config('app.demo_mode') || ! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call([
            DosenAccountSeeder::class,
            AcademicDemoSeeder::class,
            ObeExampleSeeder::class,
            DemoLearningContentSeeder::class,
            StudentScoreExampleSeeder::class,
            RpsSimulationSeeder::class,
        ]);

        // Register further domain seeders here as more persistent SALE
        // data (rubrics, ...) is introduced.
    }
}
