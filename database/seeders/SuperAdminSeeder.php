<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@eca.com',
            'phone'     => '9999999999',
            'password'  => Hash::make('admin@123'),
            'role'      => 'super_admin',
            'is_active' => true
        ]);

        echo "Super Admin created!\n";
        echo "Email   : admin@eca.com\n";
        echo "Password: admin@123\n";
    }
}
