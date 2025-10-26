<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['id' => 1, 'name' => '西 伶奈', 'email' => 'reina.n@coachtech.com', 'password' => 'abcd1234'],
            ['id' => 2, 'name' => '山田 太郎', 'email' => 'taro.y@coachtech.com', 'password' => 'abcd5678'],
            ['id' => 3, 'name' => '増田 一世', 'email' => 'issei.m@coachtech.com', 'password' => 'dcba1234'],
            ['id' => 4, 'name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com', 'password' => 'dcba5678'],
            ['id' => 5, 'name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com', 'password' => 'abcd4321'],
            ['id' => 6, 'name' => '中西 教夫', 'email' => 'norio.n@coachtech.com', 'password' => 'abcd8765'],
        ];

        foreach ($users as $user) {
            User::create([
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make($user['password']),
            ]);
        }
    }
}
