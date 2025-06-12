<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,      // 管理者ユーザー
            UserSeeder::class,           // 一般ユーザー
            AttendanceSeeder::class,     // 勤怠データ
            AttendanceCorrectionRequestSeeder::class, // 修正申請データ
        ]);
    }
}