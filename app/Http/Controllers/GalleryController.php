<?php

namespace App\Http\Controllers;

use App\Models\Gallery;

class GalleryController extends Controller
{
    public function index()
    {
        $categories = Gallery::whereRaw('is_active IS TRUE')
            ->select('category')
            ->distinct()
            ->pluck('category');

        $galleries = Gallery::whereRaw('is_active IS TRUE')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('pages.gallery.index', compact('galleries', 'categories'));
    }
}
