<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CottageUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('cloudflare');
    }

    public function test_store_rejects_non_image_photo_upload(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Test Cottage',
                'slug' => 'test-cottage',
                'photos' => [UploadedFile::fake()->create('malware.pdf', 100)],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('cottages', 0);
        Storage::disk('cloudflare')->assertDirectoryEmpty('cottages');
    }

    public function test_store_rejects_oversized_photo_upload(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Test Cottage',
                'slug' => 'test-cottage',
                'photos' => [UploadedFile::fake()->image('big.jpg')->size(6000)],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('cottages', 0);
    }

    public function test_store_accepts_valid_image_upload(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.cottages.store'), [
                'name' => 'Test Cottage',
                'slug' => 'test-cottage',
                'photos' => [UploadedFile::fake()->image('photo.jpg')],
            ])
            ->assertRedirect(route('admin.cottages.index'));

        $this->assertDatabaseHas('cottages', ['slug' => 'test-cottage']);
        $this->assertDatabaseCount('cottage_photos', 1);
        $this->assertNotEmpty(Storage::disk('cloudflare')->files('cottages'));
    }

    public function test_update_rejects_non_image_photo_upload(): void
    {
        $cottage = Cottage::create(['name' => 'Existing', 'slug' => 'existing']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.cottages.update', $cottage), [
                'name' => 'Existing',
                'slug' => 'existing',
                'photos' => [UploadedFile::fake()->create('evil.txt', 100)],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('cottage_photos', 0);
    }

    public function test_update_delete_photos_ignores_other_cottages_photos(): void
    {
        $cottageA = Cottage::create(['name' => 'A', 'slug' => 'a']);
        $cottageB = Cottage::create(['name' => 'B', 'slug' => 'b']);

        Storage::disk('cloudflare')->put('cottages/b.jpg', 'bytes');
        $photoB = $cottageB->photos()->create([
            'photo_path' => 'cottages/b.jpg',
            'is_primary' => false,
            'sort_order' => 0,
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.cottages.update', $cottageA), [
                'name' => 'A',
                'slug' => 'a',
                'delete_photos' => (string) $photoB->id,
            ])
            ->assertRedirect(route('admin.cottages.index'));

        // The other cottage's photo must survive.
        $this->assertDatabaseHas('cottage_photos', ['id' => $photoB->id]);
        Storage::disk('cloudflare')->assertExists('cottages/b.jpg');
    }

    public function test_update_delete_photos_rejects_non_numeric_ids(): void
    {
        $cottage = Cottage::create(['name' => 'A', 'slug' => 'a']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.cottages.update', $cottage), [
                'name' => 'A',
                'slug' => 'a',
                'delete_photos' => '1,2;drop table users--',
            ])
            ->assertSessionHasErrors('delete_photos');
    }
}
