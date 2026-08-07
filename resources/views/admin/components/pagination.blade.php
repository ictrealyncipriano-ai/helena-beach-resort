@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between border-t border-gray-100 pt-4 dark:border-slate-700">
        <div class="text-sm text-gray-500 dark:text-slate-400">
            Showing <span class="font-medium text-gray-700 dark:text-slate-200">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-gray-700 dark:text-slate-200">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-gray-700 dark:text-slate-200">{{ $paginator->total() }}</span> results
        </div>
        <nav class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-gray-300 rounded-lg border border-gray-100 cursor-not-allowed dark:border-slate-700 dark:text-slate-600">&laquo; Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-700 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/40 dark:hover:text-teal-300">&laquo; Prev</a>
            @endif

            {{-- Pages --}}
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="px-3 py-1.5 text-sm font-medium text-white bg-teal-700 rounded-lg">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-700 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/40 dark:hover:text-teal-300">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-700 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/40 dark:hover:text-teal-300">Next &raquo;</a>
            @else
                <span class="px-3 py-1.5 text-sm text-gray-300 rounded-lg border border-gray-100 cursor-not-allowed dark:border-slate-700 dark:text-slate-600">Next &raquo;</span>
            @endif
        </nav>
    </div>
@endif