<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 一般ユーザーのダミーデータを作成
        $users = [
            [
                'name' => '田中太郎',
                'email' => 'tanaka@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '佐藤花子',
                'email' => 'sato@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '鈴木一郎',
                'email' => 'suzuki@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '高橋美咲',
                'email' => 'takahashi@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '渡辺健太',
                'email' => 'watanabe@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '伊藤舞',
                'email' => 'ito@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '山田隆',
                'email' => 'yamada@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ],
            [
                'name' => '中村優子',
                'email' => 'nakamura@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}