@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    $tableClasses = collect([
        'w-full',
        ($bordered ?? true) ? 'border border-gray-200 dark:border-gray-700' : '',
    ])->filter()->join(' ');

    $headerCellClasses = collect([
        'px-4 py-3 text-sm font-semibold',
        ($bordered ?? true) ? 'border-b border-gray-200 dark:border-gray-700' : '',
    ])->filter()->join(' ');

    $bodyCellClasses = collect([
        'px-4 py-3 text-sm',
        ($bordered ?? true) ? 'border-b border-gray-200 dark:border-gray-700' : '',
    ])->filter()->join(' ');
@endphp

<section
    class="pro-table-block py-12 sm:py-16"
    style="{{ $customProperties }}"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-10">
                @if(!empty($heading))
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight" style="color: var(--block-heading-color);">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p class="mt-3 text-lg max-w-2xl mx-auto" style="color: var(--block-text-color);">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Table --}}
        @if(!empty($headers) && !empty($rows))
            <div class="{{ ($responsive ?? true) ? 'overflow-x-auto' : '' }} rounded-lg {{ ($bordered ?? true) ? 'border border-gray-200 dark:border-gray-700' : '' }}">
                <table class="{{ $tableClasses }}">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach($headers as $header)
                                <th
                                    scope="col"
                                    class="{{ $headerCellClasses }} text-{{ $header['align'] ?? 'left' }}"
                                    style="color: var(--block-heading-color);"
                                >
                                    {{ $header['label'] ?? '' }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rows as $rowIndex => $row)
                            <tr class="
                                {{ ($striped ?? true) && $rowIndex % 2 === 1 ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}
                                {{ ($hover ?? true) ? 'hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors' : '' }}
                                {{ ($row['highlight'] ?? false) ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}
                            ">
                                @foreach($row['cells'] ?? [] as $cellIndex => $cell)
                                    <td
                                        class="{{ $bodyCellClasses }} text-{{ $headers[$cellIndex]['align'] ?? 'left' }}"
                                        style="color: var(--block-text-color);"
                                    >
                                        {{ $cell['value'] ?? '' }}
                                    </td>
                                @endforeach
                                {{-- Fill empty cells if row has fewer cells than headers --}}
                                @for($i = count($row['cells'] ?? []); $i < count($headers); $i++)
                                    <td class="{{ $bodyCellClasses }}" style="color: var(--block-text-color);">
                                        &nbsp;
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <p>No table data configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
