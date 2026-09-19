<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * DomPDF renders with screen as its default media type, so @media screen
 * rules apply in the PDF while @media print is ignored. The invoice view
 * therefore gates all screen-only markup/CSS on an explicit $isPdf flag
 * (set by InvoiceController@download) instead of relying on CSS. These
 * tests pin that isolation from both sides.
 */
class InvoicePdfScreenIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function confirmedInquiry(): Inquiry
    {
        Cottage::create([
            'name' => 'Isolation Villa',
            'description' => 'PDF screen isolation',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'is_available' => true,
        ]);

        $this->post('/book', [
            'name' => 'Isolation Guest',
            'email' => 'isolation@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::where('name', 'Isolation Villa')->first()->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-11',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'isolation@example.com')->latest('id')->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function renderInvoice(Inquiry $inquiry, bool $isPdf): string
    {
        $data = [
            'inquiry' => $inquiry,
            'items' => [[
                'desc' => 'Overnight — Nov 10, 2026',
                'qty' => 1,
                'rate' => '2000.00',
                'total' => '2000.00',
            ]],
            'subtotal' => '2000.00',
        ];

        if ($isPdf) {
            $data['isPdf'] = true;
        }

        return view('pages.invoice', $data)->render();
    }

    public function test_pdf_view_contains_no_screen_only_output(): void
    {
        $inquiry = $this->confirmedInquiry();

        $html = $this->renderInvoice($inquiry, true);

        // NB: base CSS keeps a permanent `.action-bar { display: none; }`
        // rule (required so print/PDF hide it); assert on the markup div.
        $this->assertStringNotContainsString('<div class="action-bar"', $html);
        $this->assertStringNotContainsString('Download PDF', $html);
        $this->assertStringNotContainsString('Back to Booking', $html);
        // NB: CSS comments mentioning "@media screen" are emitted verbatim;
        // assert on actual rule openings instead.
        $this->assertStringNotContainsString('@media screen {', $html);
        $this->assertStringNotContainsString('@media screen and', $html);
        $this->assertStringNotContainsString('eef2f5', $html);

        // The invoice itself still renders in the PDF.
        $this->assertStringContainsString('INV-'.$inquiry->reference_code, $html);
        $this->assertStringContainsString('Total Due', $html);
    }

    public function test_html_view_keeps_action_bar_and_screen_styles(): void
    {
        $inquiry = $this->confirmedInquiry();

        $html = $this->renderInvoice($inquiry, false);

        $this->assertStringContainsString('<div class="action-bar"', $html);
        $this->assertStringContainsString('Download PDF', $html);
        $this->assertStringContainsString('@media screen {', $html);
    }
}
