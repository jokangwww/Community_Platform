<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarumtFacultySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('departments')->insertOrIgnore([
            ['name' => 'Faculty of Accountancy, Finance and Business', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Applied Sciences', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Computing and Information Technology', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Built Environment', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Engineering and Technology', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Communication and Creative Industries', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Social Science and Humanities', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Hospitality and Tourism Management', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Business Studies', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Faculty of Pre-University and Professional Studies', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

