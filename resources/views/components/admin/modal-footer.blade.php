@props(['closeEvent', 'submitLabel' => 'Save'])

<div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
    <button type="button" @@click="window.dispatchEvent(new CustomEvent('{{ $closeEvent }}'))"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
        Cancel
    </button>
    <button type="submit"
        class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
        {{ $submitLabel }}
    </button>
</div>
