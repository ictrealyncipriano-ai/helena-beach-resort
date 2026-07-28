<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::where('key', 'contact_email')
            ->where('value', 'helenabeachresort@example.com')
            ->update(['value' => 'ict.realyncipriano@gmail.com']);
    }

    public function down(): void
    {
        SiteSetting::where('key', 'contact_email')
            ->where('value', 'ict.realyncipriano@gmail.com')
            ->update(['value' => 'helenabeachresort@example.com']);
    }
};
