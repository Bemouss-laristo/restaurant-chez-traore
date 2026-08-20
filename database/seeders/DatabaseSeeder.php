<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,   // comptes admin / gérant / caissier
            MenuSeeder::class,   // menu réel de Chez Traoré
            StockSeeder::class,  // ingrédients de démonstration
        ]);
    }
}
