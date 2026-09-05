@extends('admin.layouts.app')

@section('title', $testimonial->exists ? 'Edit Testimonial' : 'Create Testimonial')
@section('header', $testimonial->exists ? 'Edit Testimonial' : 'Create Testimonial')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Testimonials', 'url' => route('admin.testimonials.index')], ['label' => $testimonial->exists ? $testimonial->guest_name : 'New']]" />
@endsection

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($testimonial->exists)
            @method('PUT')
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-5 dark:bg-slate-800 dark:border-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Guest Name <span class="text-red-500">*</span></label>
                    <input type="text" name="guest_name" value="{{ old('guest_name', $testimonial->guest_name) }}" required maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Rating <span class="text-red-500">*</span></label>
                    <select name="rating" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        @foreach(range(1,5) as $r)
                            <option value="{{ $r }}" {{ old('rating', $testimonial->rating) == $r ? 'selected' : '' }}>{{ $r }} Star{{ $r > 1 ? 's' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Content <span class="text-red-500">*</span></label>
                <textarea name="content" rows="4" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('content', $testimonial->content) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Guest Avatar</label>
                @if($testimonial->guest_avatar)
                    <div class="mb-2">
                        <img src="{{ Storage::url($testimonial->guest_avatar) }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-slate-600">
                    </div>
                @endif
                <input type="file" name="guest_avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900/30 dark:file:text-teal-300 dark:hover:file:bg-teal-900/40">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Cottage</label>
                    <select name="cottage_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="">None</option>
                        @foreach($cottages as $id => $name)
                            <option value="{{ $id }}" {{ old('cottage_id', $testimonial->cottage_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div class="flex items-end pb-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $testimonial->exists ? $testimonial->is_active : true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-600 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.testimonials.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                {{ $testimonial->exists ? 'Update Testimonial' : 'Create Testimonial' }}
            </button>
        </div>
    </form>
</div>
@endsection