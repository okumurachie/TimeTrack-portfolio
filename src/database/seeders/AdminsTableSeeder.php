<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            ['id' => 1, 'name' => '管理者1', 'email' => 'admin1@coachtech.com', 'password' => 'admin1234'],
            ['id' => 2, 'name' => '管理者2', 'email' => 'admin2@coachtech.com', 'password' => 'admin5678'],
        ];

        foreach ($admins as $admin) {
            Admin::create([
                'id' => $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => Hash::make($admin['password']),
            ]);
        }
    }
}
