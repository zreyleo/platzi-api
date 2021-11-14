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
        factory(\App\User::class, 10)->create();
        $this->call(CategorySeeder::class);
        $this->call(ProductSeeder::class);
    }
}
