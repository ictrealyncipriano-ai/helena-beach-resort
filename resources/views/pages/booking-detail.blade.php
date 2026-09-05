@extends('layouts.app')

@section('title', 'My Booking')
@section('description', 'View your booking details at Helena Beach Resort.')
@section('robots', 'noindex, nofollow')

@section('content')
@section('og_title', 'My Booking — ' . $inquiry->reference_code)

<nav class="pt-24 pb-0 bg-white dark:bg-slate-800" aria-label="Breadcrumb">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-sm text-gray-500 dark:text-slate-400">
        <a href="{{ route('home') }}" class="hover:text-teal-700 dark:hover:text-teal-300 transition-colors">Home</a>
        <span class="mx-2">›</span>
        <span class="text-gray-600 dark:text-slate-300">My Booking</span>
    </div>
</nav>

<x-hero title="My Booking">
    <p class="text-teal-100/90 text-lg">Reference: <span class="font-mono font-semibold">{{ $inquiry->reference_code }}</span></p>
</x-hero>

<section class="py-20 bg-white dark:bg-slate-800">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ showCancelModal: false, _cancelPreviousFocus: null }" @keydown.escape.window="if (showCancelModal) { showCancelModal = false; if (_cancelPreviousFocus) { _cancelPreviousFocus.focus(); _cancelPreviousFocus = null; } }">
        @if(request('result') === 'success')
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2 reveal">
            <x-icons name="check" class="w-5 h-5 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold">Payment successful — we're confirming your booking.</p>
                <p class="text-xs mt-0.5 opacity-90">The payment confirmation may take a few seconds to appear on this page.</p>
            </div>
        </div>
        @endif

        @if(request('result') === 'cancelled')
        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-300 flex items-start gap-2 reveal">
            <x-icons name="clock" class="w-5 h-5 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold">Payment was cancelled — no charge was made.</p>
                <p class="text-xs mt-0.5 opacity-90">You can try again whenever you're ready.</p>
            </div>
        </div>
        @endif

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-center gap-2 reveal">
            <x-icons name="check" class="w-5 h-5 shrink-0" />
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 flex items-center gap-2 reveal">
            <x-icons name="x" class="w-5 h-5 shrink-0" />
            {{ session('error') }}
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2 reveal">
            <x-icons name="clock" class="w-5 h-5 shrink-0" />
            {{ session('warning') }}
        </div>
        @endif

        @if($inquiry->payment_failed_at && ! $inquiry->isPaid())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 flex items-start gap-2 reveal">
            <x-icons name="x" class="w-5 h-5 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold">Your last payment attempt failed.</p>
                <p class="text-xs mt-0.5 opacity-90">You can retry the payment below or contact the resort for help.</p>
            </div>
        </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-8 reveal">
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mb-2">Status</p>
                    @php
                        $statusColors = ['pending' => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200', 'confirmed' => 'bg-green-50 text-green-700 ring-1 ring-green-200', 'cancelled' => 'bg-red-50 text-red-700 ring-1 ring-red-200'];
                        $statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
                        $statusIcons = ['pending' => 'clock', 'confirmed' => 'check', 'cancelled' => 'x'];
                    @endphp
                    <span id="booking-status-badge" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$inquiry->status] ?? 'bg-gray-50 text-gray-700' }}">
                        <x-icons name="{{ $statusIcons[$inquiry->status] ?? 'info' }}" class="w-3 h-3" />
                        {{ $statusLabels[$inquiry->status] ?? ucfirst($inquiry->status) }}
                    </span>
                    @if($inquiry->isPaid())
                    <span class="ml-2 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                        <x-icons name="check" class="w-3 h-3" />
                        Paid
                    </span>
                    @elseif($inquiry->isDepositPaid())
                    <span class="ml-2 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-teal-50 text-teal-700 ring-1 ring-teal-200">
                        <x-icons name="check" class="w-3 h-3" />
                        Deposit Paid
                    </span>
                    @endif
                </div>
                @if($inquiry->total_amount)
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-slate-400 mb-1">Total</p>
                    <p class="text-2xl font-bold text-teal-700 dark:text-teal-300">{{ formatPrice($inquiry->total_amount) }}</p>
                    @if(! $inquiry->isPaid() && $inquiry->hasDeposit())
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Deposit due: {{ formatPrice($inquiry->amountDueNow()) }} · Balance: {{ formatPrice($inquiry->balanceDue()) }}</p>
                    @endif
                    @if($inquiry->isPaid() && $inquiry->payment_method)
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">via {{ $inquiry->paymentMethodLabel() }} · {{ ($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at)?->format('M d, Y') }}</p>
                    @endif
                </div>
                @endif
            </div>

            <div class="space-y-5 text-sm">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Booking Details</h2>

                <div class="grid grid-cols-2 gap-4">
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
                <div class="pt-4 border-t border-gray-100 dark:border-slate-700 flex items-center gap-2">
                    <x-icons name="{{ $inquiry->booking_type === 'day_tour' ? 'sun' : 'moon' }}" class="w-4 h-4 text-gray-500 dark:text-slate-400" />
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Booking Type</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}</p>
                    </div>
                </div>
                @endif

                @if($inquiry->check_in || $inquiry->check_out)
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-slate-700">
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
                </div>
                @endif

                @if($inquiry->message)
                <div class="pt-4 border-t border-gray-100 dark:border-slate-700">
                    <p class="text-gray-500 dark:text-slate-400 mb-1">Message</p>
                    <p class="text-gray-700 dark:text-slate-200 bg-gray-50 dark:bg-slate-800/50 rounded-xl p-4">{{ $inquiry->message }}</p>
                </div>
                @endif

                <div class="pt-4 border-t border-gray-100 dark:border-slate-700">
                    <p class="text-gray-500 dark:text-slate-400">Submitted</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $inquiry->created_at->format('M d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-6 reveal">
            @if($inquiry->status === 'confirmed' && ! $inquiry->isPaid() && $inquiry->total_amount)            <form method="POST" action="{{ route('payment.pay', $inquiry) }}" class="flex-1" id="pay-now-form">
                @csrf
                <button type="submit"
                    class="w-full text-center px-6 py-3 bg-teal-700 text-white font-medium rounded-xl hover:bg-teal-800 transition-all inline-flex items-center justify-center gap-2">
                    <x-icons name="qr-code" class="w-4 h-4" />
                    @if($inquiry->hasDeposit() && ! $inquiry->isDepositPaid())
                        Pay Deposit — {{ formatPrice($inquiry->amountDueNow()) }}
                    @else
                        Pay Balance — {{ formatPrice($inquiry->balanceDue()) }}
                    @endif
                </button>
            </form>
            @endif

            @if($inquiry->status === 'confirmed')
            <a href="{{ route('invoice.show', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white dark:bg-slate-800 text-teal-700 dark:text-teal-300 font-medium rounded-xl border border-teal-200 dark:border-slate-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-all hover:shadow-sm inline-flex items-center justify-center gap-2">
                <x-icons name="photo" class="w-4 h-4" />
                View Invoice
            </a>
            <a href="{{ route('invoice.download', $inquiry) }}"
                class="flex-1 text-center px-6 py-3 bg-white dark:bg-slate-800 text-teal-700 dark:text-teal-300 font-medium rounded-xl border border-teal-200 dark:border-slate-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-all hover:shadow-sm inline-flex items-center justify-center gap-2">
                <x-icons name="download" class="w-4 h-4" />
                Download Invoice PDF
            </a>
            @endif

            <div>
                @if($canModify)
                <a href="{{ route('booking.portal.modify', $inquiry) }}"
                    class="w-full px-6 py-3 bg-white dark:bg-slate-800 text-teal-700 dark:text-teal-300 font-medium rounded-xl border border-teal-200 dark:border-slate-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 transition-all inline-flex items-center justify-center gap-2">
                    <x-icons name="calendar" class="w-4 h-4" />
                    Modify Booking
                </a>
                @elseif($modifyBlockReason)
                <div class="w-full px-6 py-3 bg-gray-50 dark:bg-slate-800/50 text-gray-500 dark:text-slate-400 rounded-xl border border-gray-200 dark:border-slate-700 text-sm flex items-center gap-2">
                    <x-icons name="info" class="w-4 h-4 shrink-0 text-gray-500 dark:text-slate-400" />
                    {{ $modifyBlockReason }}
                </div>
                @endif

                @if($canCancel)
                <button type="button" @click="_cancelPreviousFocus = $el; showCancelModal = true"
                    class="w-full px-6 py-3 bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 font-medium rounded-xl border border-red-200 dark:border-slate-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all inline-flex items-center justify-center gap-2">
                    <x-icons name="x" class="w-4 h-4" />
                    Cancel Booking
                </button>
                @elseif($cancelBlockReason)
                <div class="w-full px-6 py-3 bg-gray-50 dark:bg-slate-800/50 text-gray-500 dark:text-slate-400 rounded-xl border border-gray-200 dark:border-slate-700 text-sm flex items-center gap-2">
                    <x-icons name="info" class="w-4 h-4 shrink-0 text-gray-500 dark:text-slate-400" />
                    {{ $cancelBlockReason }}
                </div>
                @endif
            </div>
        </div>

        <div class="mt-6 p-4 bg-gray-50 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-700 rounded-xl text-sm text-gray-600 dark:text-slate-300 reveal">
            <div class="flex items-start gap-2">
                <x-icons name="info" class="w-4 h-4 shrink-0 mt-0.5 text-teal-700 dark:text-teal-300" />
                <div class="space-y-1">
                    <p><strong>Cancellation &amp; refunds:</strong> bookings can be cancelled for free up to 24 hours before check-in (and while the request is still pending or confirmed). Paid bookings cancelled before that cutoff are refunded in full automatically.</p>
                    <p class="text-xs">Questions? See our <a href="{{ route('faq') }}" class="underline text-teal-700 dark:text-teal-300 hover:text-teal-700">FAQ</a> or <a href="{{ route('contact') }}" class="underline text-teal-700 dark:text-teal-300 hover:text-teal-700">contact the resort</a>.</p>
                </div>
            </div>
        </div>

        @if($canReview)
        <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-8 reveal" x-data="{ rating: 5 }">
            <div class="flex items-center gap-2 mb-1">
                <x-icons name="star" class="w-5 h-5 text-amber-400" />
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Share Your Experience</h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Thanks for staying with us! Your review helps other guests — it will be published after a quick review by the resort.</p>

            @if($inquiry->testimonials()->exists())
            <div class="flex items-start gap-2 p-4 bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 rounded-xl text-sm text-teal-700 dark:text-teal-300">
                <x-icons name="check" class="w-4 h-4 shrink-0 mt-0.5" />
                <p>You've already submitted a review for this booking. Thank you!</p>
            </div>
            @else
            <form method="POST" action="{{ route('booking.portal.review', $inquiry) }}" class="space-y-5">
                @csrf
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300" id="rating-label">Your Rating</span>
                    <div class="flex items-center gap-1" role="radiogroup" aria-labelledby="rating-label">
                        @foreach(range(1, 5) as $star)
                        <label :class="{ 'text-amber-400': rating >= {{ $star }}, 'text-gray-300': rating < {{ $star }} }" class="cursor-pointer focus-within:ring-2 focus-within:ring-teal-600 rounded-lg p-0.5 transition-colors">
                            <input type="radio" name="rating" value="{{ $star }}" @change="rating = {{ $star }}" :checked="rating === {{ $star }}" class="sr-only" aria-label="{{ $star }} star{{ $star > 1 ? 's' : '' }}">
                            <x-icons name="star" class="w-8 h-8" />
                        </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label for="review-content" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Your Review</label>
                    <textarea name="content" id="review-content" rows="4" required maxlength="2000" placeholder="What did you enjoy about your stay?"
                        @error('content') aria-invalid="true" aria-describedby="review-content-error" @enderror
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('content') border-red-300 dark:border-red-500 @enderror"></textarea>
                    @error('content') <p id="review-content-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-teal-700 text-white font-medium rounded-xl hover:bg-teal-800 transition-all inline-flex items-center gap-2">
                    <x-icons name="star" class="w-4 h-4" />
                    Submit Review
                </button>
            </form>
            @endif
        </div>
        @endif

        @if($canSubmitPaymentProof || $inquiry->hasPendingPaymentProof() || $inquiry->hasApprovedPaymentProof())
        <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-8 reveal">
            <div class="flex items-center gap-2 mb-1">
                <x-icons name="photo" class="w-5 h-5 text-teal-700 dark:text-teal-300" />
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pay by Bank Transfer or GCash</h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Prefer to pay manually? Upload a photo of your payment receipt and the resort will confirm it.</p>

            @if($inquiry->hasPendingPaymentProof())
            <div class="flex items-start gap-2 p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-300">
                <x-icons name="clock" class="w-4 h-4 shrink-0 mt-0.5" />
                <div>
                    <p class="font-semibold">Your payment proof is being reviewed.</p>
                    <p class="text-xs mt-0.5 opacity-90">Submitted {{ $inquiry->payment_proof_submitted_at?->format('M d, Y \a\t h:i A') }}. The resort will confirm your payment shortly.</p>
                </div>
            </div>
            @elseif($inquiry->hasApprovedPaymentProof())
            <div class="flex items-start gap-2 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-300">
                <x-icons name="check" class="w-4 h-4 shrink-0 mt-0.5" />
                <p>Your payment proof was approved. Thank you!</p>
            </div>
            @else
            <form method="POST" action="{{ route('booking.portal.proof', $inquiry) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div>
                    <label for="payment-proof" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Payment Receipt</label>
                    <input type="file" name="payment_proof" id="payment-proof" accept="image/jpeg,image/png,image/webp" required
                        @error('payment_proof') aria-invalid="true" aria-describedby="payment-proof-error" @enderror
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('payment_proof') border-red-300 dark:border-red-500 @enderror">
                    @error('payment_proof') <p id="payment-proof-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Accepted formats: JPG, PNG, WebP. Max 5MB.</p>
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-teal-700 text-white font-medium rounded-xl hover:bg-teal-800 transition-all inline-flex items-center gap-2">
                    <x-icons name="photo" class="w-4 h-4" />
                    Upload Payment Proof
                </button>
            </form>
            @endif
        </div>
        @endif

        @if($canCancel)
        <div x-cloak x-show="showCancelModal"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50"
            @click.self="showCancelModal = false"></div>

        <div x-cloak x-show="showCancelModal" role="dialog" aria-modal="true" aria-labelledby="cancel-booking-title"
            x-trap.noscroll="showCancelModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-xl w-full max-w-md p-8">
                <div class="text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                        <x-icons name="x" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <h3 id="cancel-booking-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Cancel Booking?</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">This will cancel your booking and it cannot be undone.</p>
                </div>
                <div class="flex items-center justify-center gap-3">
                    <button type="button" @click="showCancelModal = false; if (_cancelPreviousFocus) { _cancelPreviousFocus.focus(); _cancelPreviousFocus = null; }"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        Keep Booking
                    </button>
                    <form method="POST" action="{{ route('booking.portal.cancel', $inquiry) }}">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors">
                            Cancel Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-sm text-teal-700 dark:text-teal-300 hover:text-teal-700 dark:hover:text-teal-300 inline-flex items-center gap-1">
                <x-icons name="arrow-left" class="w-3 h-3" />
                Back to Home
            </a>
        </div>
    </div>
</section>

@push('scripts')
@if(request('result') === 'success' && ! $inquiry->isPaid())
<script>
    // Poll the session-gated status endpoint while the PayMongo webhook is
    // catching up, then update the page in place once paid_at is set.
    (function () {
        var statusUrl = @json(route('booking.portal.status', $inquiry));
        if (!statusUrl) return;
        var attempts = 0;
        var maxAttempts = 10; // ~30s at 3s intervals
        var timer = setInterval(function () {
            attempts++;
            fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.paid) {
                        clearInterval(timer);
                        var payForm = document.getElementById('pay-now-form');
                        if (payForm) payForm.remove();
                        var badge = document.getElementById('booking-status-badge');
                        if (badge) {
                            badge.textContent = 'Paid';
                            badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(timer);
                    }
                })
                .catch(function () {
                    if (attempts >= maxAttempts) clearInterval(timer);
                });
        }, 3000);
    })();
</script>
@endif
@endpush
@endsection
