<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = User::updateOrCreate([
            'name' => 'Mohamed Saeed',
            'email' => 'm7md.sa3ed@hotmail.com',
            'password' => bcrypt(123456),
        ]);
    }
}
