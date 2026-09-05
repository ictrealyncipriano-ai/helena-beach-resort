@extends('admin.layouts.app')

@section('title', 'Activity Log')
@section('header', 'Activity Details')
@section('description', 'Audit trail entry details')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Activity Logs', 'url' => route('admin.activity-logs.index')], ['label' => '#' . $log->id]]" />
@endsection

@section('content')
<div class="space-y-6 max-w-3xl">
    <a href="{{ route('admin.activity-logs.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-teal-700 transition-colors dark:text-slate-400 dark:hover:text-teal-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
        Back to activity logs
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-slate-700">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-heading text-lg font-semibold text-gray-900 dark:text-white">{{ $log->actionLabel() }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $log->description ?? '—' }}</p>
                </div>
                <span class="font-mono text-xs text-gray-500 whitespace-nowrap dark:text-slate-400">{{ $log->created_at->format('M d, Y g:i A') }}</span>
            </div>
        </div>

        <dl class="divide-y divide-gray-50 dark:divide-slate-700/50">
            <div class="flex justify-between gap-4 px-5 sm:px-6 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Actor</dt>
                <dd class="text-sm text-gray-900 dark:text-white">{{ $log->actorLabel() }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-5 sm:px-6 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Action key</dt>
                <dd class="font-mono text-sm text-gray-900 dark:text-white">{{ $log->action }}</dd>
            </div>
            <div class="flex justify-between gap-4 px-5 sm:px-6 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Subject</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    @if($log->subject_type)
                        {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                    @else
                        <span class="text-gray-400 dark:text-slate-500">None</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between gap-4 px-5 sm:px-6 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Recorded</dt>
                <dd class="text-sm text-gray-900 dark:text-white">{{ $log->created_at->format('Y-m-d H:i:s') }} (UTC)</dd>
            </div>
        </dl>
    </div>

    @if($log->properties)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Properties</h3>
        </div>
        <dl class="divide-y divide-gray-50 dark:divide-slate-700/50">
            @foreach($log->properties as $key => $value)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 px-5 sm:px-6 py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">{{ $key }}</dt>
                    <dd class="text-sm text-gray-900 break-all sm:col-span-2 dark:text-white">
                        @if(is_array($value))
                            <pre class="whitespace-pre-wrap text-xs text-gray-700 dark:text-slate-300">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @else
                            {{ $value === null ? '—' : $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
    @endif
</div>
@endsection