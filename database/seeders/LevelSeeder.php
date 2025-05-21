<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class LevelSeeder extends Seeder
{
    public function run()
{
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    DB::table('levels')->truncate(); // ou ->delete();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    DB::table('levels')->insert([
        ['id' => 1, 'name' => 'اولى باك'],
        ['id' => 2, 'name' => 'الثانية باك'],
        ['id' => 3, 'name' => 'جذع مشترك'],
        ['id' => 4, 'name' => 'الثالثة اعدادي'],
        ['id' => 5, 'name' => 'الثانية اعدادي'],
        ['id' => 6, 'name' => 'الاولى اعدادي'],
        ['id' => 7, 'name' => 'السادس ابتدائي'],
        ['id' => 8, 'name' => 'الخامس ابتدائي'],
        ['id' => 9, 'name' => 'الرابع ابتدائي'],
        ['id' => 10, 'name' => 'الثالث ابتدائي'],
        ['id' => 11, 'name' => 'الثاني ابتدائي'],
        ['id' => 12, 'name' => 'الاول ابتدائي'],
    ]);
}

    /**
     * Run the database seeds.
     */
   }
