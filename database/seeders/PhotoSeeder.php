<?php

namespace Database\Seeders;

use App\Models\Cottage;
use App\Models\CottagePhoto;
use App\Models\Gallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PhotoSeeder extends Seeder
{
    public function run(): void
    {
        if (CottagePhoto::count() > 0 && Gallery::count() > 0) {
            return;
        }

        $disk = Storage::disk('public');

        $disk->makeDirectory('cottages');
        $disk->makeDirectory('gallery');

        $cottages = Cottage::orderBy('sort_order')->get();
        $names = [
            'Kubo Aplaya' => ['KH-1', 'KH-2', 'KH-3'],
            'Villa del Mar' => ['VM-1', 'VM-2'],
            'Casa del Sol' => ['CS-1', 'CS-2', 'CS-3'],
            'Bahay Dalampasigan' => ['BD-1', 'BD-2'],
            'Pamilya Pavilion' => ['PP-1', 'PP-2', 'PP-3'],
            'Honeymoon Hideaway' => ['HH-1', 'HH-2'],
        ];

        foreach ($cottages as $cottage) {
            $codes = $names[$cottage->name];
            foreach ($codes as $i => $code) {
                $path = "cottages/{$code}.svg";
                $svg = $this->makeSvg($cottage->name, $i === 0 ? '800x600' : '600x400');
                $disk->put($path, $svg);

                CottagePhoto::create([
                    'cottage_id' => $cottage->id,
                    'photo_path' => $path,
                    'is_primary' => $i === 0,
                    'sort_order' => $i,
                ]);
            }
        }

        $galleryItems = [
            ['title' => 'Sunrise at the Shore', 'category' => 'beach', 'label' => 'Sunrise'],
            ['title' => 'Beachfront View', 'category' => 'beach', 'label' => 'Beach'],
            ['title' => 'Coastal Sunset', 'category' => 'sunset', 'label' => 'Sunset'],
            ['title' => 'Evening Glow', 'category' => 'sunset', 'label' => 'Dusk'],
            ['title' => 'Kubo Aplaya Exterior', 'category' => 'cottages', 'label' => 'Kubo'],
            ['title' => 'Villa del Mar Veranda', 'category' => 'cottages', 'label' => 'Villa'],
            ['title' => 'Seafood Platter', 'category' => 'dining', 'label' => 'Seafood'],
            ['title' => 'Al Fresco Dining', 'category' => 'dining', 'label' => 'Dining'],
            ['title' => 'Beach Volleyball', 'category' => 'activities', 'label' => 'Volleyball'],
            ['title' => 'Kayaking', 'category' => 'activities', 'label' => 'Kayak'],
            ['title' => 'Garden Path', 'category' => 'beach', 'label' => 'Garden'],
            ['title' => 'Night Bonfire', 'category' => 'activities', 'label' => 'Bonfire'],
        ];

        foreach ($galleryItems as $i => $item) {
            $code = 'GL-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
            $path = "gallery/{$code}.svg";
            $svg = $this->makeSvg($item['label'], '800x600');
            $disk->put($path, $svg);

            Gallery::create([
                'title' => $item['title'],
                'photo_path' => $path,
                'category' => $item['category'],
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function makeSvg(string $label, string $dims = '800x600'): string
    {
        [$w, $h] = explode('x', $dims);
        $color1 = '#0d9488';
        $color2 = '#0f766e';
        $escaped = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $fontSize = max(24, min(48, (int)$w / 12));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$color1}"/>
      <stop offset="100%" style="stop-color:{$color2}"/>
    </linearGradient>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#bg)"/>
  <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
        font-family="Arial,sans-serif" font-size="{$fontSize}" font-weight="bold" fill="white">{$escaped}</text>
</svg>
SVG;
    }
}
