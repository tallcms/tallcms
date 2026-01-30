@php
    $columnsClass = match((string) ($columns ?? '3')) {
        '2' => 'sm:grid-cols-2 max-w-4xl mx-auto',
        '3' => 'sm:grid-cols-2 lg:grid-cols-3',
        '4' => 'sm:grid-cols-2 md:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');

    // Platform display labels for accessibility
    $platformLabels = [
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter / X',
        'github' => 'GitHub',
        'email' => 'Email',
        'website' => 'Website',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
    ];

    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-500';
    $animationStagger = $animation_stagger ?? false;
    $staggerDelay = (int) ($animation_stagger_delay ?? 100);
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="team-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-7xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <x-tallcms::animation-wrapper
                :animation="$animationType"
                :duration="$animationDuration"
                :use-parent="true"
                class="{{ $text_alignment ?? 'text-center' }} mb-12 sm:mb-16"
            >
                @if(!empty($heading))
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-4 text-lg sm:text-xl text-base-content/70 max-w-3xl {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'mx-auto' : '' }}">
                        {{ $subheading }}
                    </p>
                @endif
            </x-tallcms::animation-wrapper>
        @endif

        {{-- Team Members --}}
        @if(!empty($members))
            <div class="grid gap-8 {{ $columnsClass }}">
                @foreach($members as $index => $member)
                    @php
                        $itemDelay = $animationStagger ? ($staggerDelay * ($index + 1)) : 0;
                    @endphp
                    <x-tallcms::animation-wrapper
                        :animation="$animationType"
                        :duration="$animationDuration"
                        :use-parent="true"
                        :delay="$itemDelay"
                        class="{{ $card_style ?? 'card bg-base-200 shadow-lg' }} {{ $text_alignment ?? 'text-center' }} min-w-0"
                    >
                        <div class="card-body">
                            {{-- Photo --}}
                            <div class="mb-4 {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'flex justify-center' : '' }}">
                                @if(!empty($member['photo']))
                                    <div class="avatar">
                                        <div class="w-24 sm:w-32 {{ $image_style ?? 'rounded-full' }}">
                                            <img
                                                src="{{ Storage::disk(cms_media_disk())->url($member['photo']) }}"
                                                alt="{{ $member['name'] ?? '' }}"
                                                loading="lazy"
                                            >
                                        </div>
                                    </div>
                                @else
                                    <div class="avatar placeholder">
                                        <div class="w-24 sm:w-32 {{ $image_style ?? 'rounded-full' }} bg-primary/10 text-primary">
                                            <span class="text-3xl sm:text-4xl font-semibold">
                                                {{ substr($member['name'] ?? 'T', 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Name & Role --}}
                            <h3 class="font-semibold text-lg text-base-content">
                                {{ $member['name'] }}
                            </h3>
                            <p class="text-sm mt-1 text-base-content/70">
                                {{ $member['role'] ?? '' }}
                            </p>

                            {{-- Bio --}}
                            @if(($show_bio ?? true) && !empty($member['bio']))
                                <p class="text-sm mt-3 leading-relaxed text-base-content/70">
                                    {{ $member['bio'] }}
                                </p>
                            @endif

                            {{-- Social Links --}}
                            @if(($show_social ?? true) && !empty($member['social_links']))
                                <div class="flex items-center gap-3 mt-4 {{ ($text_alignment ?? 'text-center') === 'text-center' ? 'justify-center' : '' }}">
                                    @foreach($member['social_links'] as $social)
                                        <a
                                            href="{{ e($social['url']) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-base-content/50 hover:text-primary transition-colors"
                                            aria-label="{{ $platformLabels[$social['platform']] ?? ucfirst($social['platform']) }}"
                                        >
                                            @switch($social['platform'])
                                                @case('linkedin')
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                                    </svg>
                                                    @break
                                                @case('twitter')
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                                    </svg>
                                                    @break
                                                @case('github')
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                                                    </svg>
                                                    @break
                                                @case('email')
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    @break
                                                @case('website')
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                    </svg>
                                                    @break
                                                @case('instagram')
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    @break
                                                @case('facebook')
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/>
                                                    </svg>
                                                    @break
                                            @endswitch
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-tallcms::animation-wrapper>
                @endforeach
            </div>
        @endif
    </div>
</x-tallcms::animation-wrapper>
