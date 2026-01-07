{{-- Timeline Node (circle with number, icon, or date) --}}
<div class="timeline-node w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center ring-4 ring-white dark:ring-gray-900">
    @if($isNumbered)
        <span class="text-lg sm:text-xl font-bold text-primary-600 dark:text-primary-400">
            {{ $index + 1 }}
        </span>
    @elseif($isValidIcon)
        <x-dynamic-component
            :component="$iconName"
            class="w-6 h-6 text-primary-600 dark:text-primary-400"
        />
    @elseif(!empty($date))
        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 text-center leading-tight px-1 truncate w-full">
            {{ $date }}
        </span>
    @else
        <div class="w-3 h-3 rounded-full bg-primary-600 dark:bg-primary-400"></div>
    @endif
</div>
