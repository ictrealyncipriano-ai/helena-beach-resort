<section {{ $attributes->merge(['class' => 'relative pt-32 pb-20 overflow-hidden']) }}>
    <div class="absolute inset-0 bg-gradient-to-br from-teal-600 via-teal-700 to-cyan-800">
        @if($image ?? false)
        <img src="{{ $image }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover opacity-20">
        <div class="absolute inset-0 bg-gradient-to-br from-teal-600/80 via-teal-700/80 to-cyan-800/80"></div>
        @endif
    </div>
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-[30rem] h-[30rem] bg-cyan-400/10 rounded-full blur-3xl"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        @if($badge ?? false)
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-teal-100 text-sm font-medium mb-6 backdrop-blur-sm reveal">
            {{ $badge }}
        </div>
        @endif
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-4 font-heading reveal">{{ $title }}</h1>
        @if($subtitle ?? false)
        <p class="text-teal-100/90 text-lg sm:text-xl max-w-2xl mx-auto reveal reveal-delay-1">{{ $subtitle }}</p>
        @endif
        @if($slot ?? false)
        <div class="mt-8 reveal reveal-delay-2">{{ $slot }}</div>
        @endif
    </div>
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <path d="M0 20C240 60 480 60 720 40C960 20 1200 20 1440 40V60H0V20Z" fill="white" fill-opacity="0.08"/>
            <path d="M0 30C240 50 480 50 720 35C960 20 1200 20 1440 35V60H0V30Z" fill="white" fill-opacity="0.15"/>
            <path d="M0 40C240 55 480 55 720 45C960 35 1200 35 1440 45V60H0V40Z" fill="white"/>
        </svg>
    </div>
</section>
