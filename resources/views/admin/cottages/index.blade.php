@extends('admin.layouts.app')

@php
$showEditModal = $errors->hasAny(['name', 'slug', 'description', 'capacity', 'rate_daytour', 'rate_overnight', 'peak_start', 'peak_end', 'peak_rate_daytour', 'peak_rate_overnight', 'sort_order', 'is_available']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($cottagesData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Cottages')
@section('header', 'Cottages')
@section('description', 'Manage your resort cottages')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Cottages']]" />
@endsection

@section('content')
<div x-data="cottageModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 flex-1 max-w-lg">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search cottages..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="availability" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Availability</option>
                    <option value="available" {{ request('availability') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="unavailable" {{ request('availability') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'availability']))
                    <a href="{{ route('admin.cottages.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-800 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Cottage
            </button>
        </div>

        @if($cottages->isEmpty())
            <x-admin.empty-state
                title="No cottages yet"
                message="Create your first cottage to start accepting bookings."
                actionClick="openCreate()"
                actionLabel="Add Cottage"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Name</th>
                            <th class="text-center px-5 py-3 font-medium">Max Pax</th>
                            <th class="text-right px-5 py-3 font-medium">Day Tour</th>
                            <th class="text-right px-5 py-3 font-medium">Overnight</th>
                            <th class="text-center px-5 py-3 font-medium">Available</th>
                            <th class="text-center px-5 py-3 font-medium">Bookings</th>
                            <th class="text-center px-5 py-3 font-medium">Sort</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($cottages as $cottage)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if($cottage->primaryPhoto)
                                        <img src="{{ Storage::url($cottage->primaryPhoto->photo_path) }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/40 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-teal-500 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819"/></svg>
                                        </div>
                                    @endif
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $cottage->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600 dark:text-slate-300">{{ $cottage->capacity }}</td>
                            <td class="px-5 py-3 text-right text-gray-600 dark:text-slate-300">{{ formatPrice($cottage->rate_daytour) }}</td>
                            <td class="px-5 py-3 text-right text-gray-600 dark:text-slate-300">{{ formatPrice($cottage->rate_overnight) }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($cottage->is_available)
                                    <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-red-400 mx-auto dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">@include('components.admin.badge', ['type' => 'primary', 'slot' => $cottage->inquiries_count])</td>
                            <td class="px-5 py-3 text-center text-gray-500 dark:text-slate-400">{{ $cottage->sort_order }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openEdit({{ $cottage->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete"
                                        @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.cottages.destroy', $cottage) }}', method: 'DELETE' })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
                <x-admin.pagination :paginator="$cottages" />
            </div>
        @endif
    </div>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="cottage-form" size="xl">
        <form method="POST" :action="formAction" enctype="multipart/form-data" @@submit="attachPhotos($event)">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.cottages._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-cottage-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Cottage' : 'Create Cottage'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

<x-admin.confirm-dialog name="delete" title="Delete Cottage?" message="Are you sure you want to delete this cottage? This action cannot be undone." />
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.$store('confirmDelete', {
        open: false,
        url: '',
        method: 'POST',
    });
    document.addEventListener('open-confirm-delete', (e) => {
        Alpine.store('confirmDelete', { open: true, url: e.detail.url, method: e.detail.method || 'DELETE' });
    });
});

function cottageModal() {
    return {
        cottages: @js($cottagesData),
        isEditing: false,
        editingId: null,
        slugManuallyChanged: false,
        form: {
            name: '',
            slug: '',
            description: '',
            capacity: '',
            rate_daytour: '',
            rate_overnight: '',
            peak_start: '',
            peak_end: '',
            peak_rate_daytour: '',
            peak_rate_overnight: '',
            sort_order: 0,
            is_available: true,
        },
        amenities: [],
        dateBlocks: [],
        existingPhotos: [],
        newPhotos: [],
        deletedPhotoIds: '',
        formAction: '',
        formMethod: 'PUT',

        addAmenity() {
            this.amenities.push({ name: '', icon: '' });
        },

        addDateBlock() {
            this.dateBlocks.push({ date: '', reason: '' });
        },

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
        },

        attachPhotos(event) {
            if (!this.newPhotos.length) return;
            const dt = new DataTransfer();
            this.newPhotos.forEach(p => dt.items.add(p.file));
            const input = event.target.querySelector('input[name="photos[]"]');
            if (input) input.files = dt.files;
        },

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.slugManuallyChanged = false;
            this.form = {
                name: '',
                slug: '',
                description: '',
                capacity: '',
                rate_daytour: '',
                rate_overnight: '',
                peak_start: '',
                peak_end: '',
                peak_rate_daytour: '',
                peak_rate_overnight: '',
                sort_order: 0,
                is_available: true,
            };
            this.amenities = [];
            this.dateBlocks = [];
            this.existingPhotos = [];
            this.newPhotos = [];
            this.deletedPhotoIds = '';
            this.formAction = '{{ route('admin.cottages.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('open-modal-cottage-form', { detail: { title: 'Add Cottage' } }));
        },

        openEdit(id) {
            const cottage = this.cottages.find(c => c.id === id);
            if (!cottage) return;
            this.isEditing = true;
            this.editingId = cottage.id;
            this.slugManuallyChanged = true;
            this.form = {
                name: cottage.name || '',
                slug: cottage.slug || '',
                description: cottage.description || '',
                capacity: cottage.capacity ?? '',
                rate_daytour: cottage.rate_daytour ?? '',
                rate_overnight: cottage.rate_overnight ?? '',
                peak_start: cottage.peak_start || '',
                peak_end: cottage.peak_end || '',
                peak_rate_daytour: cottage.peak_rate_daytour ?? '',
                peak_rate_overnight: cottage.peak_rate_overnight ?? '',
                sort_order: cottage.sort_order ?? 0,
                is_available: cottage.is_available !== false,
            };
            this.amenities = (cottage.amenities || []).map(a => ({ name: a.name || '', icon: a.icon || '' }));
            this.dateBlocks = (cottage.date_blocks || []).map(b => ({ date: b.date || '', reason: b.reason || '' }));
            this.existingPhotos = (cottage.photos || []).map(p => ({ id: p.id, url: p.url, is_primary: !!p.is_primary }));
            this.newPhotos = [];
            this.deletedPhotoIds = '';
            this.formAction = '/admin/cottages/' + cottage.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('open-modal-cottage-form', { detail: { title: 'Edit Cottage: ' + cottage.name } }));
        },

        init() {
            const showModal = @js($showEditModal);
            const editingId = @js($editingData ? $editingData['id'] : 0);

            if (showModal) {
                if (editingId) {
                    this.openEdit(Number(editingId));
                } else {
                    this.openCreate();
                }

                const oldName = @js(old('name', ''));
                const oldSlug = @js(old('slug', ''));
                const oldDescription = @js(old('description', ''));
                const oldCapacity = @js(old('capacity', ''));
                const oldDayTour = @js(old('rate_daytour', ''));
                const oldOvernight = @js(old('rate_overnight', ''));
                const oldPeakStart = @js(old('peak_start', ''));
                const oldPeakEnd = @js(old('peak_end', ''));
                const oldPeakDayTour = @js(old('peak_rate_daytour', ''));
                const oldPeakOvernight = @js(old('peak_rate_overnight', ''));
                const oldSort = @js(old('sort_order', ''));
                const oldAvailable = @js(old('is_available'));
                const oldAmenities = @js(old('amenities', []));
                const oldDateBlocks = @js(old('date_blocks', []));

                this.$nextTick(() => {
                    if (oldName) this.form.name = oldName;
                    if (oldSlug) this.form.slug = oldSlug;
                    if (oldDescription) this.form.description = oldDescription;
                    if (oldCapacity !== null && oldCapacity !== '') this.form.capacity = oldCapacity;
                    if (oldDayTour !== null && oldDayTour !== '') this.form.rate_daytour = oldDayTour;
                    if (oldOvernight !== null && oldOvernight !== '') this.form.rate_overnight = oldOvernight;
                    if (oldPeakStart) this.form.peak_start = oldPeakStart;
                    if (oldPeakEnd) this.form.peak_end = oldPeakEnd;
                    if (oldPeakDayTour !== null && oldPeakDayTour !== '') this.form.peak_rate_daytour = oldPeakDayTour;
                    if (oldPeakOvernight !== null && oldPeakOvernight !== '') this.form.peak_rate_overnight = oldPeakOvernight;
                    if (oldSort !== null && oldSort !== '') this.form.sort_order = oldSort;
                    if (oldAvailable !== null) this.form.is_available = true;
                    if (oldAmenities && oldAmenities.length) this.amenities = oldAmenities;
                    if (oldDateBlocks && oldDateBlocks.length) this.dateBlocks = oldDateBlocks;
                });
            }
        },
    };
}
</script>
@endpush
