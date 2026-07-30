@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
        <div class="text-sm text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium text-gray-700">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-gray-700">{{ $paginator->total() }}</span> results
        </div>
        <nav class="flex items-center gap-1">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-gray-300 rounded-lg border border-gray-100 cursor-not-allowed">&laquo; Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-600 transition-colors">&laquo; Prev</a>
            @endif

            {{-- Pages --}}
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-3 py-1.5 text-sm font-medium text-white bg-teal-600 rounded-lg">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-600 transition-colors">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-600 rounded-lg border border-gray-200 hover:bg-gray-50 hover:text-teal-600 transition-colors">Next &raquo;</a>
            @else
                <span class="px-3 py-1.5 text-sm text-gray-300 rounded-lg border border-gray-100 cursor-not-allowed">Next &raquo;</span>
            @endif
        </nav>
    </div>
@endif