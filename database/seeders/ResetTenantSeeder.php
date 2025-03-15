<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResetTenantSeeder extends Seeder
{
    public function run(): void
    {
        foreach (app('db')->select('SHOW DATABASES') as $database) {
            if (str($database->Database)->startsWith(config('tenancy.database.prefix'))) {
                app('db')->statement("DROP DATABASE $database->Database");
            }

        }
    }
}
