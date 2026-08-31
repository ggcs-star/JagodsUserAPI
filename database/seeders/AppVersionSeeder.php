<?php

namespace Database\Seeders;

use App\Models\AppVersion;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        AppVersion::create([
            'device_type' => 0,
            'version' => '1.0.5',
            'minimum_supported_version' => '1.0.4',
            'platform' => 'jagods_ios',
            'whats_new' => 'Performance Improved',
            'app_storage_url' => 'https://apps.apple.com/in/app/jagods/id6749576808',
            'is_active' => true,
            'released_at' => now(),
        ]);

        AppVersion::create([
            'device_type' => 1,
            'version' => '1.0.24',
            'minimum_supported_version' => '1.0.23',
            'platform' => 'jagods_android',
            'whats_new' => 'Performance Improved',
            'app_storage_url' => 'https://play.google.com/store/apps/details?id=com.jagods.customer',
            'is_active' => true,
            'released_at' => now(),
        ]);
    }
}