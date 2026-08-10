<?php

namespace Tests\Feature;

use App\Models\Cottage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageAvailabilityWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_renders_availability_widget(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Check Availability')
            ->assertSee('widget-cottage', false)
            ->assertSee('availabilityWidget', false)
            ->assertSee(json_encode(route('availability.check')), false);
    }

    public function test_home_page_lists_every_available_cottage_in_the_widget(): void
    {
        $expected = Cottage::where('is_available', true)
            ->orderBy('sort_order')
            ->get('name');

        $response = $this->get('/')->assertOk();

        foreach ($expected as $cottage) {
            $response->assertSee($cottage->name);
        }
    }

    public function test_widget_is_hidden_when_no_cottages_are_available(): void
    {
        Cottage::query()->update(['is_available' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Check Availability');
    }
}
