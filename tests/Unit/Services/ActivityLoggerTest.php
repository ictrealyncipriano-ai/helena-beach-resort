<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\Cottage;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9.6 — direct unit coverage for the ActivityLogger. It snapshots the
 * authenticated admin's id + name at write time so entries survive later user
 * deletion, and normalizes model/array/null subjects.
 */
class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_action_and_model_subject(): void
    {
        $cottage = Cottage::create([
            'name' => 'Logger Cottage',
            'capacity' => 4,
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
        ]);

        app(ActivityLogger::class)->record('cottage.created', $cottage, 'Cottage created.');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'cottage.created',
            'subject_type' => $cottage->getMorphClass(),
            'subject_id' => $cottage->id,
            'description' => 'Cottage created.',
        ]);
    }

    public function test_record_with_model_snapshots_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Alice Admin', 'role' => 'super_admin']);
        $this->actingAs($user);

        app(ActivityLogger::class)->record('user.updated', $user, 'Updated.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'user_name' => 'Alice Admin',
            'action' => 'user.updated',
        ]);
    }

    public function test_record_without_user_stores_null_actor(): void
    {
        app(ActivityLogger::class)->record('payment.received', null, 'Webhook.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => null,
            'user_name' => null,
            'action' => 'payment.received',
        ]);
    }

    public function test_record_with_array_subject(): void
    {
        app(ActivityLogger::class)->record('inquiry.submitted', ['type' => 'App\\Models\\Inquiry', 'id' => 42], 'Submitted.');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'inquiry.submitted',
            'subject_type' => 'App\\Models\\Inquiry',
            'subject_id' => 42,
        ]);
    }

    public function test_record_persists_properties_json(): void
    {
        app(ActivityLogger::class)->record('inquiry.updated', null, null, ['field' => 'status', 'from' => 'pending', 'to' => 'confirmed']);

        $row = ActivityLog::where('action', 'inquiry.updated')->first();
        $this->assertSame('confirmed', $row->properties['to']);
    }
}
