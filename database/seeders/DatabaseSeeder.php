<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createAccount();
        $this->call(AccountSeeder::class);
    }


    protected function createAccount(): void
    {
        \App\Models\Account::updateOrCreate(['id' => 1], [
            "name" => "member",
            "email" => "member@app.com",
            "password" => Hash::make("password"),
        ]);
    }
}
