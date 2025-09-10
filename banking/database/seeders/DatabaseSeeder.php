<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Wallet;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Customer::factory()->count(5)->create()->each(function ($customer) {
            Wallet::create([
                'user_id' => $customer->id,
                'user_type' => Customer::class,
                'balance' => rand(100, 1000),
            ]);
        });

        Company::factory()->count(5)->create()->each(function ($company) {
            Wallet::create([
                'user_id' => $company->id,
                'user_type' => Company::class,
                'balance' => 0,
            ]);
        });
    }
}
