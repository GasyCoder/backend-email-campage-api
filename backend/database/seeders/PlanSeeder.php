<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['code' => 'free'],
            [
                'name' => 'Free',
                'monthly_credits' => 300,
                'max_recipients_per_campaign' => 10,
                'monthly_recipient_limit' => 200,
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'pro'],
            [
                'name' => 'Pro',
                'monthly_credits' => 2000,
                'max_recipients_per_campaign' => 200,
                'monthly_recipient_limit' => 20000,
            ]
        );
    }
}
