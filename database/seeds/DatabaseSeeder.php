<?php

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
        // $this->call(UserSeeder::class);
        factory(\App\User::class, 4)->create();
        factory(\App\User::class, 4)->create([
            'email_verified_at' => null,
            'created_at' => now()->subDays(7)
        ]);
        $this->call(CategorySeeder::class);
        $this->call(ProductSeeder::class);
    }
}
