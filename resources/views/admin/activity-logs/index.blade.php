@extends('admin.layouts.app')

@section('title', 'Activity Logs')
@section('header', 'Activity Logs')
@section('description', 'Audit trail of admin and guest actions')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Activity Logs</span>
    </nav>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search action or description..." x-data="liveSearchState()" x-model="search" @input.debounce.350ms="goSearch()" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <input type="text" name="user" value="{{ request('user') }}" placeholder="Actor name..." class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                <select name="action" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-1">
                    <input type="date" name="from" value="{{ request('from') }}" aria-label="From" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <span class="text-gray-400">to</span>
                    <input type="date" name="to" value="{{ request('to') }}" aria-label="To" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'user', 'action', 'from', 'to']))
                    <a href="{{ route('admin.activity-logs.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
        </div>

        @if($logs->isEmpty())
            @include('admin.components.empty-state', [
                'title' => 'No activity recorded',
                'message' => 'No audit entries match these filters.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">When</th>
                            <th class="text-left px-5 py-3 font-medium">Action</th>
                            <th class="text-left px-5 py-3 font-medium">Description</th>
                            <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Actor</th>
                            <th class="text-right px-5 py-3 font-medium hidden sm:table-cell">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap dark:text-slate-400">
                                {{ $log->created_at->format('M d, Y g:i A') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs text-gray-600 dark:text-slate-300">{{ $log->action }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-700 max-w-md dark:text-slate-200">
                                {{ Str::limit($log->description ?? '—', 90) }}
                            </td>
                            <td class="px-5 py-3 hidden md:table-cell">
                                <span class="{{ $log->user_name ? 'text-gray-700 dark:text-slate-200' : 'text-gray-400 dark:text-slate-500' }}">{{ $log->actorLabel() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right hidden sm:table-cell">
                                <a href="{{ route('admin.activity-logs.show', $log) }}" class="inline-flex items-center gap-1 p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="View details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
                @include('admin.components.pagination', ['paginator' => $logs])
            </div>
        @endif
    </div>
</div>
@endsection