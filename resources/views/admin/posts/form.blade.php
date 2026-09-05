@extends('admin.layouts.app')

@section('title', $post->exists ? 'Edit Post' : 'Create Post')
@section('header', $post->exists ? 'Edit Post' : 'Create Post')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'News & Posts', 'url' => route('admin.posts.index')], ['label' => $post->exists ? $post->title : 'New']]" />
@endsection

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($post->exists) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-5 dark:bg-slate-800 dark:border-slate-700">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $post->title) }}" required maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" maxlength="255" placeholder="Auto-generated from title"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Excerpt</label>
                <textarea name="excerpt" rows="2" maxlength="1000" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('excerpt', $post->excerpt) }}</textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Short summary shown on the news listing.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Body</label>
                <textarea name="body" rows="12" class="w-full px-3 py-2 text-sm font-mono border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('body', $post->body) }}</textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Basic HTML allowed (&lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;h2&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;a&gt;, &lt;img&gt;).</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Cover Image</label>
                @if($post->cover_image)
                    <div class="mb-2">
                        <img src="{{ Storage::url($post->cover_image) }}" alt="" class="w-full max-w-sm h-40 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                    </div>
                @endif
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900/30 dark:file:text-teal-300 dark:hover:file:bg-teal-900/40">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Publish Date</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Future dates schedule the post. Blank hides it as a draft.</p>
                </div>
                <div class="flex items-end pb-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $post->exists ? $post->is_active : true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-600 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.posts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                {{ $post->exists ? 'Update Post' : 'Create Post' }}
            </button>
        </div>
    </form>
</div>
@endsection
