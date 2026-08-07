@extends('layouts.app')

@section('title', 'Booking Confirmation')
@section('description', 'Your booking inquiry has been received.')

@section('content')
<section class="relative pt-32 pb-20 overflow-hidden bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800">
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-[30rem] h-[30rem] bg-cyan-400/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-16 h-16 bg-green-400/20 rounded-2xl flex items-center justify-center mx-auto mb-6 backdrop-blur-sm animate-pulse-soft">
            <x-icons name="check" class="w-8 h-8 text-green-300" />
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-4 font-heading">Inquiry Received!</h1>
        <p class="text-teal-100/90 text-lg sm:text-xl max-w-2xl mx-auto">Thank you for your interest in Helena Beach Resort. Your request is held for <strong class="text-white">48 hours</strong> — we'll email you to confirm availability, and payment is only required after your booking is confirmed.</p>
    </div>
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <path d="M0 30C240 50 480 50 720 35C960 20 1200 20 1440 35V60H0V30Z" fill="white" fill-opacity="0.1"/>
            <path d="M0 40C240 55 480 55 720 45C960 35 1200 35 1440 45V60H0V40Z" fill="white"/>
        </svg>
    </div>
</section>

<section class="py-20 bg-white dark:bg-slate-800">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-8 reveal">
            <div class="text-center mb-8 pb-8 border-b border-gray-100 dark:border-slate-700">
                <p class="text-sm text-gray-500 dark:text-slate-400 mb-2">Reference Number</p>
                <p class="text-3xl font-bold text-teal-700 dark:text-teal-300 font-mono tracking-wider">{{ $inquiry->reference_code }}</p>
            </div>

            <div class="space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Submitted Details</h2>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Name</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Email</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->email }}</p>
                    </div>
                    @if($inquiry->phone)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Phone</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->phone }}</p>
                    </div>
                    @endif
                    @if($inquiry->cottage)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Cottage</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->cottage->name }}</p>
                    </div>
                    @endif
                </div>

                @if($inquiry->booking_type)
                <div class="pt-4 border-t border-gray-100 dark:border-slate-700 text-sm flex items-center gap-2">
                    <x-icons name="{{ $inquiry->booking_type === 'day_tour' ? 'sun' : 'moon' }}" class="w-4 h-4 text-gray-500 dark:text-slate-400" />
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Booking Type</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}</p>
                    </div>
                </div>
                @endif

                @if($inquiry->check_in || $inquiry->check_out)
                <div class="grid grid-cols-2 gap-4 text-sm pt-4 border-t border-gray-100 dark:border-slate-700">
                    @if($inquiry->check_in)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">
                            <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Check-in
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->check_in->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->check_out)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">
                            <x-icons name="calendar" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Check-out
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->check_out->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if($inquiry->pax)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">
                            <x-icons name="users" class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-500 dark:text-slate-400" />
                            Guests
                        </p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->pax }}</p>
                    </div>
                    @endif
                    @if($inquiry->total_amount)
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Total</p>
                        <p class="font-medium text-teal-700 dark:text-teal-300">₱{{ number_format($inquiry->total_amount) }}</p>
                    </div>
                    @endif
                </div>
                @endif

                @if($inquiry->message)
                <div class="pt-4 border-t border-gray-100 dark:border-slate-700 text-sm">
                    <p class="text-gray-500 dark:text-slate-400 mb-1">Message</p>
                    <p class="text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-slate-800/50 rounded-xl p-4">{{ $inquiry->message }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100 dark:border-slate-700 text-sm">
                    <p class="text-gray-500 dark:text-slate-400">Status</p>
                    <p class="inline-flex items-center gap-1.5 mt-1 px-3 py-1 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 text-xs font-semibold rounded-full ring-1 ring-yellow-200 dark:ring-yellow-800">
                        <x-icons name="clock" class="w-3 h-3" />
                        Pending
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 rounded-xl text-sm text-teal-700 dark:text-teal-300 flex items-start gap-3 reveal">
            <x-icons name="info" class="w-5 h-5 shrink-0 mt-0.5" />
            <div>
                <strong>We've emailed these details to {{ $inquiry->email }}.</strong> Keep your reference code safe — you can use it later to
                <a href="{{ route('booking.portal.lookup') }}" class="underline font-medium hover:text-teal-800 dark:hover:text-teal-200">view your booking</a>.
            </div>
        </div>

        <div class="mt-6 text-center reveal">
            <a href="{{ route('booking.portal.lookup') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-700 text-white font-medium rounded-xl hover:bg-teal-700 transition-all shadow-sm hover:shadow-md">
                <x-icons name="search" class="w-4 h-4" />
                View My Booking
            </a>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">Use your reference code <span class="font-mono font-semibold text-teal-700 dark:text-teal-300">{{ $inquiry->reference_code }}</span> and email to look it up anytime.</p>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-sm text-teal-700 dark:text-teal-300 hover:text-teal-700 dark:hover:text-teal-300 inline-flex items-center gap-1">
                <x-icons name="arrow-left" class="w-3 h-3" />
                Back to Home
            </a>
        </div>
    </div>
</section>
@endsection
