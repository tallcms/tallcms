{{-- Timeline Node (circle with number, icon, or date) --}}
<div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-primary/10 flex items-center justify-center ring-4 ring-base-100">
    @if($isNumbered)
        <span class="text-lg sm:text-xl font-bold text-primary">
            {{ $index + 1 }}
        </span>
    @elseif($isValidIcon)
        <x-dynamic-component
            :component="$iconName"
            class="w-6 h-6 text-primary"
        />
    @elseif(!empty($date))
        <span class="text-xs font-semibold text-primary text-center leading-tight px-1 truncate w-full">
            {{ $date }}
        </span>
    @else
        <div class="w-3 h-3 rounded-full bg-primary"></div>
    @endif
</div>
