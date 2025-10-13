<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) return;

        $this->command->info('Seeding chat data...');

        $this->call([
            ChatSeeder::class,
            DirectMessagesSeeder::class,
            ChatDemoSeeder::class,
        ]);
    }
}
