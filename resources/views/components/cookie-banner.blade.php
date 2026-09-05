@php
    $ga4Id = $analytics['ga4_id'];
    $consentRequired = $analytics['consent_required'];
@endphp
@if($ga4Id && $consentRequired)
<div
    x-data="cookieConsent()"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    x-on:keydown.escape.window="decline()"
    class="fixed z-50 bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:bottom-6 sm:max-w-md"
    role="region"
    aria-labelledby="cookie-banner-title"
>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200/80 dark:border-slate-700 p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center text-teal-700 dark:text-teal-300">
                <x-icons name="info" class="w-5 h-5" />
            </div>
            <div>
                <h2 id="cookie-banner-title" class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                    We value your privacy
                </h2>
                <p class="text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
                    We use cookies to measure how visitors use our site and improve your experience.
                    <a href="{{ route('privacy') }}" class="text-teal-700 dark:text-teal-300 underline hover:no-underline">Learn more</a>
                </p>
            </div>
        </div>
        <div class="mt-4 flex flex-col sm:flex-row gap-2">
            <button type="button" @click="accept()"
                class="flex-1 px-4 py-2.5 bg-teal-700 text-white text-sm font-semibold rounded-lg hover:bg-teal-800 transition-colors">
                Accept
            </button>
            <button type="button" @click="decline()"
                class="flex-1 px-4 py-2.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                Decline
            </button>
        </div>
    </div>
</div>
@endif
