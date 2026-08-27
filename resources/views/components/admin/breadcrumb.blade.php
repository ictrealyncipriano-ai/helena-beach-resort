@props(['items' => []])

<nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
    <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
    @foreach($items as $item)
        <span>/</span>
        @if(isset($item['url']))
            <a href="{{ $item['url'] }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">{{ $item['label'] }}</a>
        @else
            <span class="text-gray-700 font-medium dark:text-slate-200">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
