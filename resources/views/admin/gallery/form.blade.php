@extends('admin.layouts.app')

@section('title', $gallery->exists ? 'Edit Gallery Image' : 'Add Gallery Image')
@section('header', $gallery->exists ? 'Edit Gallery Image' : 'Add Gallery Image')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.gallery.index') }}" class="hover:text-teal-600 transition-colors">Gallery</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">{{ $gallery->exists ? ($gallery->title ?? 'Edit') : 'New' }}</span>
    </nav>
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $gallery->exists ? route('admin.gallery.update', $gallery) : route('admin.gallery.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($gallery->exists) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title', $gallery->title) }}" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Image {{ $gallery->exists ? '' : '*' }}</label>
                @if($gallery->photo_path)
                    <div class="mb-2">
                        <img src="{{ Storage::url($gallery->photo_path) }}" alt="" class="w-32 h-24 rounded-lg object-cover border border-gray-200">
                    </div>
                @endif
                <input type="file" name="photo_path" accept="image/jpeg,image/png,image/webp" {{ $gallery->exists ? '' : 'required' }} class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                        <option value="">None</option>
                        <option value="Resort" {{ old('category', $gallery->category) === 'Resort' ? 'selected' : '' }}>Resort</option>
                        <option value="Beach" {{ old('category', $gallery->category) === 'Beach' ? 'selected' : '' }}>Beach</option>
                        <option value="Food" {{ old('category', $gallery->category) === 'Food' ? 'selected' : '' }}>Food</option>
                        <option value="Events" {{ old('category', $gallery->category) === 'Events' ? 'selected' : '' }}>Events</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $gallery->sort_order ?? 0) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400">
                </div>
                <div class="flex items-end pb-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $gallery->exists ? $gallery->is_active : true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600"></div>
                        <span class="ms-2 text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.gallery.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                {{ $gallery->exists ? 'Update Image' : 'Upload Image' }}
            </button>
        </div>
    </form>
</div>
@endsection