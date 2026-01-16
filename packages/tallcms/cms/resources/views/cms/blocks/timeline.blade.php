@php
    $isVertical = ($style ?? 'vertical') === 'vertical';
    $isAlternating = ($alternating ?? true) && $isVertical;
    $showConnector = $show_connector ?? true;
    $isNumbered = $numbered ?? false;
    $sectionPadding = ($first_section ?? false) ? 'pb-16' : ($padding ?? 'py-16');
@endphp

<section class="timeline-block {{ $sectionPadding }} {{ $background ?? 'bg-base-100' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="{{ $text_alignment ?? 'text-center' }} mb-12 sm:mb-16">
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
            </div>
        @endif

        {{-- Timeline Items --}}
        @if(!empty($items))
            @if($isVertical)
                {{-- Vertical Timeline --}}
                <div class="relative {{ $isAlternating ? 'max-w-5xl mx-auto' : 'max-w-3xl mx-auto' }}">
                    {{-- Connector Line --}}
                    @if($showConnector)
                        <div class="absolute {{ $isAlternating ? 'left-1/2 -translate-x-1/2' : 'left-6 sm:left-8' }} top-0 bottom-0 w-0.5 bg-base-300"></div>
                    @endif

                    <div class="space-y-8 sm:space-y-12">
                        @foreach($items as $index => $item)
                            @php
                                $isEven = $index % 2 === 0;
                                $iconName = $item['icon'] ?? '';
                                $isValidIcon = !empty($iconName) && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);
                            @endphp

                            <div class="relative {{ $isAlternating ? 'flex items-center' : 'pl-16 sm:pl-20' }}">
                                @if($isAlternating)
                                    {{-- Alternating Layout --}}
                                    {{-- Left side (even items): right-aligned text pointing toward center --}}
                                    <div class="flex-1 {{ $isEven ? 'pr-8 sm:pr-12' : 'order-2 pl-8 sm:pl-12' }}">
                                        @if($isEven)
                                            @include('cms.blocks.partials.timeline-content', ['item' => $item, 'alignRight' => true, 'isNumbered' => $isNumbered])
                                        @endif
                                    </div>

                                    {{-- Center Node --}}
                                    <div class="relative z-10 flex-shrink-0">
                                        @include('cms.blocks.partials.timeline-node', [
                                            'index' => $index,
                                            'isNumbered' => $isNumbered,
                                            'isValidIcon' => $isValidIcon,
                                            'iconName' => $iconName,
                                            'date' => $item['date'] ?? '',
                                        ])
                                    </div>

                                    {{-- Right side (odd items): left-aligned text pointing toward center --}}
                                    <div class="flex-1 {{ $isEven ? 'order-2 pl-8 sm:pl-12' : 'pr-8 sm:pr-12' }}">
                                        @if(!$isEven)
                                            @include('cms.blocks.partials.timeline-content', ['item' => $item, 'alignRight' => false, 'isNumbered' => $isNumbered])
                                        @endif
                                    </div>
                                @else
                                    {{-- Left-aligned Layout --}}
                                    <div class="absolute left-0 z-10">
                                        @include('cms.blocks.partials.timeline-node', [
                                            'index' => $index,
                                            'isNumbered' => $isNumbered,
                                            'isValidIcon' => $isValidIcon,
                                            'iconName' => $iconName,
                                            'date' => $item['date'] ?? '',
                                        ])
                                    </div>

                                    @include('cms.blocks.partials.timeline-content', ['item' => $item, 'alignRight' => false, 'isNumbered' => $isNumbered])
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Horizontal Timeline --}}
                <div class="relative overflow-x-auto pb-4">
                    <div class="flex items-start min-w-max px-4">
                        @foreach($items as $index => $item)
                            @php
                                $iconName = $item['icon'] ?? '';
                                $isValidIcon = !empty($iconName) && preg_match('/^heroicon-[oms]-[\w-]+$/', $iconName);
                                $isLast = $loop->last;
                            @endphp

                            <div class="relative flex flex-col items-center {{ !$isLast ? 'mr-8 sm:mr-16' : '' }}" style="min-width: 200px; max-width: 280px;">
                                {{-- Node --}}
                                @include('cms.blocks.partials.timeline-node', [
                                    'index' => $index,
                                    'isNumbered' => $isNumbered,
                                    'isValidIcon' => $isValidIcon,
                                    'iconName' => $iconName,
                                    'date' => $item['date'] ?? '',
                                ])

                                {{-- Connector --}}
                                @if($showConnector && !$isLast)
                                    <div class="absolute top-6 left-full w-8 sm:w-16 h-0.5 bg-base-300"></div>
                                @endif

                                {{-- Content --}}
                                <div class="mt-4 text-center">
                                    @if(!empty($item['title']))
                                        <h3 class="font-semibold text-base sm:text-lg text-base-content">
                                            {{ $item['title'] }}
                                        </h3>
                                    @endif

                                    @if(!empty($item['description']))
                                        <p class="mt-2 text-sm leading-relaxed text-base-content/70">
                                            {{ $item['description'] }}
                                        </p>
                                    @endif

                                    @if(!empty($item['image']))
                                        <img
                                            src="{{ Storage::disk(cms_media_disk())->url($item['image']) }}"
                                            alt="{{ $item['title'] ?? '' }}"
                                            class="mt-4 rounded-lg w-full h-32 object-cover"
                                        >
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>
