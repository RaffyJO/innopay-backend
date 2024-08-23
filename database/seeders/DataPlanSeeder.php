<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DataPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('data_plans')->insert([
            [
                'name' => '10 GB',
                'price' => 50000,
                'operator_card_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '20 GB',
                'price' => 90000,
                'operator_card_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '35 GB',
                'price' => 170000,
                'operator_card_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '50 GB',
                'price' => 230000,
                'operator_card_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '10 GB',
                'price' => 50000,
                'operator_card_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '20 GB',
                'price' => 90000,
                'operator_card_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '35 GB',
                'price' => 170000,
                'operator_card_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '50 GB',
                'price' => 230000,
                'operator_card_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '10 GB',
                'price' => 50000,
                'operator_card_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '20 GB',
                'price' => 90000,
                'operator_card_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '35 GB',
                'price' => 170000,
                'operator_card_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '50 GB',
                'price' => 230000,
                'operator_card_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
