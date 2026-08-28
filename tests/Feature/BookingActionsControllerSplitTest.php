<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BookingActionsController;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 8.9 — the admin confirm/cancel actions were extracted from
 * InquiryController into the focused BookingActionsController. These tests
 * guard the route-to-controller mapping and the end-to-end transitions.
 */
class BookingActionsControllerSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function cottage(): Cottage
    {
        return Cottage::first();
    }

    private function pendingBooking(string $reference): Inquiry
    {
        return Inquiry::create([
            'reference_code' => $reference,
            'name' => 'Guest',
            'email' => strtolower($reference).'@example.com',
            'source' => Inquiry::SOURCE_WEBSITE,
            'status' => Inquiry::STATUS_PENDING,
            'cottage_id' => $this->cottage()->id,
            'check_in' => '2026-09-15',
            'check_out' => '2026-09-17',
        ]);
    }

    public function test_confirm_route_resolves_to_booking_actions_controller(): void
    {
        $route = app(Router::class)->getRoutes()->getByName('admin.inquiries.confirm');
        $uses = (string) $route->getAction('uses');

        $this->assertStringStartsWith(BookingActionsController::class.'@', $uses);
        $this->assertSame('confirm', $route->getActionMethod());
    }

    public function test_cancel_route_resolves_to_booking_actions_controller(): void
    {
        $route = app(Router::class)->getRoutes()->getByName('admin.inquiries.cancel');
        $uses = (string) $route->getAction('uses');

        $this->assertStringStartsWith(BookingActionsController::class.'@', $uses);
        $this->assertSame('cancel', $route->getActionMethod());
    }

    public function test_confirm_promotes_booking_and_books_blocks(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'conf@example.com', 'total_stays' => 0]);
        $inquiry = $this->pendingBooking('HB-CONF');
        $inquiry->guest()->associate($guest)->save();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame(Inquiry::STATUS_CONFIRMED, $inquiry->status);
        $this->assertSame('Booked: HB-CONF', CottageDateBlock::where('inquiry_id', $inquiry->id)->first()->reason);
        $this->assertSame(1, $guest->fresh()->total_stays);
    }

    public function test_confirm_rejects_non_pending(): void
    {
        $inquiry = $this->pendingBooking('HB-NONPEND');
        $inquiry->update(['status' => Inquiry::STATUS_CONFIRMED]);

        $this->actingAs($this->admin())
            ->from(route('admin.inquiries.show', $inquiry))
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('error');

        $this->assertSame(Inquiry::STATUS_CONFIRMED, $inquiry->fresh()->status);
    }

    public function test_cancel_pending_sets_cancelled_and_releases_blocks(): void
    {
        $inquiry = $this->pendingBooking('HB-CANC');
        $inquiry->reserveBlocks();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.cancel', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->status);
        $this->assertCount(0, CottageDateBlock::where('inquiry_id', $inquiry->id)->get());
    }

    public function test_cancel_pending_does_not_decrement_guest_stay(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'stayx@example.com', 'total_stays' => 1]);
        $inquiry = $this->pendingBooking('HB-KEEPSTAY');
        $inquiry->guest()->associate($guest)->save();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.cancel', $inquiry));

        // Pending cancellations must never subtract a confirmed stay.
        $this->assertSame(1, $guest->fresh()->total_stays);
    }

    public function test_cancel_rejects_non_pending(): void
    {
        $inquiry = $this->pendingBooking('HB-CANCNP');
        $inquiry->update(['status' => Inquiry::STATUS_CANCELLED]);

        $this->actingAs($this->admin())
            ->from(route('admin.inquiries.show', $inquiry))
            ->post(route('admin.inquiries.cancel', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('error');

        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->fresh()->status);
    }
}
