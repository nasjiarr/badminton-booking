<?php

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    /**
     * Seed 4 courts with default weekly schedules.
     */
    public function run(): void
    {
        $defaultDescription = 'Lapangan badminton standar dengan lantai karpet vinil berkualitas, pencahayaan LED terang, dan fasilitas lengkap.';

        $courts = [
            [
                'name' => 'Lapangan A',
                'description' => $defaultDescription,
                'price_per_hour' => 40000,
                'image_path' => 'images/courts/court-a-placeholder.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Lapangan B',
                'description' => $defaultDescription,
                'price_per_hour' => 40000,
                'image_path' => 'images/courts/court-b-placeholder.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Lapangan C',
                'description' => $defaultDescription,
                'price_per_hour' => 40000,
                'image_path' => 'images/courts/court-c-placeholder.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Lapangan D',
                'description' => $defaultDescription,
                'price_per_hour' => 40000,
                'image_path' => 'images/courts/court-d-placeholder.jpg',
                'is_active' => true,
            ],
        ];

        foreach ($courts as $courtData) {
            $court = Court::create($courtData);

            // Create default schedule: open every day 06:00 - 22:00
            for ($day = 0; $day <= 6; $day++) {
                CourtSchedule::create([
                    'court_id' => $court->id,
                    'day_of_week' => $day,
                    'open_time' => '06:00',
                    'close_time' => '22:00',
                ]);
            }
        }
    }
}

