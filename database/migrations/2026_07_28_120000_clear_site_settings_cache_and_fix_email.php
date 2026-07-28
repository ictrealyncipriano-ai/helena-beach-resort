<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::where('key', 'contact_email')
            ->where('value', 'like', '%@example.com')
            ->update(['value' => 'ict.realyncipriano@gmail.com']);

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        SiteSetting::where('key', 'contact_email')
            ->where('value', 'ict.realyncipriano@gmail.com')
            ->update(['value' => 'helenabeachresort@example.com']);

        Cache::forget('settings.all');
    }
};
