<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Trois comptes de démonstration, un par rôle.
     * Mot de passe commun : « password » (à changer en production).
     */
    public function run(): void
    {
        $accounts = [
            ['Administrateur', 'admin@cheztraore.mr', Role::Admin],
            ['Gérant', 'gerant@cheztraore.mr', Role::Gerant],
            ['Caissier', 'caissier@cheztraore.mr', Role::Caissier],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
