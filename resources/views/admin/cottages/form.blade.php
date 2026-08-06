@extends('admin.layouts.app')

@section('title', $cottage->exists ? 'Edit Cottage' : 'Create Cottage')
@section('header', $cottage->exists ? 'Edit Cottage' : 'Create Cottage')
@section('description', $cottage->exists ? 'Update cottage details, amenities, photos, and availability.' : 'Add a new cottage to your resort.')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.cottages.index') }}" class="hover:text-teal-600 transition-colors dark:hover:text-teal-300">Cottages</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">{{ $cottage->exists ? $cottage->name : 'New' }}</span>
    </nav>
@endsection

@section('content')
<form method="POST" action="{{ $cottage->exists ? route('admin.cottages.update', $cottage) : route('admin.cottages.store') }}" enctype="multipart/form-data" class="space-y-6" x-data="cottageForm()">
    @csrf
    @if($cottage->exists)
        @method('PUT')
    @endif

    {{-- Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Details</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $cottage->name) }}" required maxlength="255"
                        x-on:blur="if(!slugManuallyChanged) { slug = $el.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') }"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('name') border-red-300 dark:border-red-500 @enderror">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" value="{{ old('slug', $cottage->slug) }}" required maxlength="255"
                        x-model="slug"
                        x-on:input="slugManuallyChanged = true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('slug') border-red-300 dark:border-red-500 @enderror">
                    @error('slug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-5">
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Description</label>
                <textarea name="description" rows="4" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('description', $cottage->description) }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mt-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Capacity (Max Pax)</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $cottage->capacity) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Day Tour Rate (₱)</label>
                    <input type="number" step="0.01" name="rate_daytour" value="{{ old('rate_daytour', $cottage->rate_daytour) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Overnight Rate (₱)</label>
                    <input type="number" step="0.01" name="rate_overnight" value="{{ old('rate_overnight', $cottage->rate_overnight) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $cottage->sort_order ?? 0) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div class="flex items-end pb-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_available" value="1" class="sr-only peer" {{ old('is_available', $cottage->exists ? $cottage->is_available : true) ? 'checked' : '' }}>
                        <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-600 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                        <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Available</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Amenities --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Amenities</h2>
            <button type="button" @@click="addAmenity()" class="text-sm font-medium text-teal-600 hover:text-teal-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add
            </button>
        </div>
        <div class="p-5 space-y-3" id="amenities-container">
            <template x-for="(amenity, i) in amenities" :key="i">
                <div class="flex items-center gap-3">
                    <input type="text" :name="'amenities['+i+'][name]'" x-model="amenity.name" placeholder="Amenity name" class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <select :name="'amenities['+i+'][icon]'" x-model="amenity.icon" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="">Icon</option>
                        <option value="snowflake">Aircon</option>
                        <option value="device-tv">TV</option>
                        <option value="wifi">WiFi</option>
                        <option value="cooking-pot">Kitchen</option>
                        <option value="toilet">CR</option>
                        <option value="car">Parking</option>
                        <option value="microphone">Karaoke</option>
                        <option value="campfire">Grill</option>
                    </select>
                    <button type="button" @@click="amenities.splice(i, 1)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-500 dark:hover:text-red-400 dark:hover:bg-red-500/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                </div>
            </template>
            <template x-if="amenities.length === 0">
                <p class="text-sm text-gray-400 dark:text-slate-500 text-center py-4">No amenities added yet. Click "Add" to add one.</p>
            </template>
        </div>
    </div>

    {{-- Photos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Photos</h2>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" id="photos-container">
                <template x-for="(photo, i) in existingPhotos" :key="i">
                    <div class="relative group aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200 dark:bg-slate-700/40 dark:border-slate-600">
                        <img :src="photo.url" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button type="button" @@click="removeExistingPhoto(i)" class="p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <template x-if="photo.is_primary">
                            <span class="absolute top-1 left-1 px-1.5 py-0.5 text-xs font-medium bg-teal-500 text-white rounded">Primary</span>
                        </template>
                    </div>
                </template>
                <template x-for="(file, i) in newPhotos" :key="'new-'+i">
                    <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200 dark:bg-slate-700/40 dark:border-slate-600">
                        <img :src="file.url" class="w-full h-full object-cover">
                        <button type="button" @@click="newPhotos.splice(i, 1)" class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
                <label class="aspect-[4/3] rounded-lg border-2 border-dashed border-gray-200 hover:border-teal-400 bg-gray-50 hover:bg-teal-50/50 flex flex-col items-center justify-center cursor-pointer transition-colors dark:border-slate-600 dark:bg-slate-700/40 dark:hover:border-teal-500 dark:hover:bg-teal-900/20">
                    <svg class="w-8 h-8 text-gray-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                    <span class="text-xs text-gray-400 mt-1 dark:text-slate-500">Upload</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @@change="handlePhotoUpload($event)">
                </label>
            </div>
            <input type="hidden" name="delete_photos" x-model="deletedPhotoIds">
        </div>
    </div>

    {{-- Blocked Dates --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between dark:border-slate-700">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Blocked Dates</h2>
            <button type="button" @@click="addDateBlock()" class="text-sm font-medium text-teal-600 hover:text-teal-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add
            </button>
        </div>
        <div class="p-5 space-y-3">
            <template x-for="(block, i) in dateBlocks" :key="i">
                <div class="flex items-center gap-3">
                    <input type="date" :name="'date_blocks['+i+'][date]'" x-model="block.date" class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <input type="text" :name="'date_blocks['+i+'][reason]'" x-model="block.reason" placeholder="Reason (optional)" class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <button type="button" @@click="dateBlocks.splice(i, 1)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-500 dark:hover:text-red-400 dark:hover:bg-red-500/10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                </div>
            </template>
            <template x-if="dateBlocks.length === 0">
                <p class="text-sm text-gray-400 dark:text-slate-500 text-center py-4">No blocked dates. Click "Add" to block a date.</p>
            </template>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.cottages.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
        <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
            {{ $cottage->exists ? 'Update Cottage' : 'Create Cottage' }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function cottageForm() {
    return {
        slug: '{{ old('slug', $cottage->slug ?? '') }}',
        slugManuallyChanged: {{ $cottage->exists ? 'true' : 'false' }},
        amenities: @js(old('amenities', $cottage->exists ? $cottage->amenities->toArray() : [])),
        dateBlocks: @js(old('date_blocks', $cottage->exists ? $cottage->dateBlocks->map(fn($b) => ['date' => $b->date?->format('Y-m-d'), 'reason' => $b->reason])->toArray() : [])),
        existingPhotos: @js($cottage->exists ? $cottage->photos->map(fn($p) => ['id' => $p->id, 'url' => Storage::url($p->photo_path), 'is_primary' => $p->is_primary])->toArray() : []),
        newPhotos: [],
        deletedPhotoIds: '',
        addAmenity() { this.amenities.push({ name: '', icon: '' }) },
        addDateBlock() { this.dateBlocks.push({ date: '', reason: '' }) },
        handlePhotoUpload(event) {
            const files = event.target.files;
            for (const file of files) {
                this.newPhotos.push({ file, url: URL.createObjectURL(file) });
            }
            event.target.value = '';
        },
        removeExistingPhoto(i) {
            const ids = this.deletedPhotoIds ? this.deletedPhotoIds.split(',') : [];
            ids.push(String(this.existingPhotos[i].id));
            this.deletedPhotoIds = ids.join(',');
            this.existingPhotos.splice(i, 1);
        }
    }
}
</script>
@endpush