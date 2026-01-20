<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            [ 'username' => 'ltanadmin' ],
            [
                'username' => 'ltanadmin',
                'name' => 'ltanadmin',
                'email' => 'ltanadmin',
                'password' => bcrypt('scryhold'),
                'role' => 'admin',
            ]
        );
    }
}
