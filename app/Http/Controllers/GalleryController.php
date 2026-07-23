<?php

namespace App\Http\Controllers;

use App\Models\Gallery;

class GalleryController extends Controller
{
    public function index()
    {
        $categories = Gallery::where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category');

        $galleries = Gallery::where('is_active', true)
            ->orderBy('sort_order')
            ->paginate(20);

        return view('pages.gallery.index', compact('galleries', 'categories'));
    }
}
