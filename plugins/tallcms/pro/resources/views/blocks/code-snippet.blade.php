@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];

    $customProperties = collect([
        '--block-heading-color: ' . ($textPreset['heading'] ?? '#111827'),
        '--block-text-color: ' . ($textPreset['description'] ?? '#4b5563'),
        '--block-primary-color: ' . ($textPreset['link'] ?? '#2563eb'),
    ])->join('; ') . ';';

    $uniqueId = 'code-' . uniqid();

    // Prism language mapping (some languages need different names)
    $prismLanguage = match($language ?? 'javascript') {
        'typescript' => 'typescript',
        'tsx' => 'tsx',
        'jsx' => 'jsx',
        'vue' => 'markup',
        'csharp' => 'csharp',
        'cpp' => 'cpp',
        'docker' => 'docker',
        'nginx' => 'nginx',
        'apache' => 'apacheconf',
        'plaintext' => 'plaintext',
        default => $language ?? 'javascript',
    };

    // Theme CSS file names
    $themeFile = match($theme ?? 'okaidia') {
        'tomorrow' => 'prism-tomorrow',
        'okaidia' => 'prism-okaidia',
        'twilight' => 'prism-twilight',
        'coy' => 'prism-coy',
        'solarizedlight' => 'prism-solarizedlight',
        'dark' => 'prism-dark',
        default => 'prism-okaidia',
    };

    // Max height styles
    $maxHeightStyle = match($max_height ?? 'none') {
        'sm' => 'max-height: 300px; overflow-y: auto;',
        'md' => 'max-height: 400px; overflow-y: auto;',
        'lg' => 'max-height: 500px; overflow-y: auto;',
        'xl' => 'max-height: 600px; overflow-y: auto;',
        default => '',
    };

    // Escape HTML in code
    $escapedCode = e($code ?? '');

    // Prism data attributes
    $dataAttrs = [];
    if ($show_line_numbers ?? true) {
        $dataAttrs[] = 'line-numbers';
    }
    if (!empty($highlight_lines)) {
        $dataAttrs[] = 'data-line="' . e($highlight_lines) . '"';
    }
@endphp

{{-- Prism.js Theme CSS (loaded per-block, last one wins if different themes on same page) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/{{ $themeFile }}.min.css" data-prism-theme="{{ $themeFile }}">

@once
{{-- Prism.js Plugin CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-highlight/prism-line-highlight.min.css">

{{-- Prism.js Core JS --}}
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-highlight/prism-line-highlight.min.js"></script>

<style>
.pro-code-snippet-block pre[class*="language-"] {
    margin: 0;
    border-radius: 0 0 0.5rem 0.5rem;
}
.pro-code-snippet-block pre[class*="language-"].line-numbers {
    padding-left: 3.8em;
}
.pro-code-snippet-block .line-numbers .line-numbers-rows {
    border-right: 1px solid rgba(255,255,255,0.1);
}
.pro-code-snippet-block.wrap-lines pre[class*="language-"],
.pro-code-snippet-block.wrap-lines code[class*="language-"] {
    white-space: pre-wrap;
    word-wrap: break-word;
}
.pro-code-snippet-block .code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #1e1e1e;
    border-radius: 0.5rem 0.5rem 0 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.pro-code-snippet-block .code-header .filename {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #e5e5e5;
    font-size: 0.875rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.pro-code-snippet-block .code-header .language-badge {
    color: #a0a0a0;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.pro-code-snippet-block .copy-button {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 0.375rem;
    color: #e5e5e5;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.15s ease;
}
.pro-code-snippet-block .copy-button:hover {
    background: rgba(255,255,255,0.2);
}
.pro-code-snippet-block .copy-button.copied {
    background: #22c55e;
    color: white;
}
</style>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proCodeSnippet', () => ({
        copied: false,
        copyCode() {
            const codeEl = this.$refs.code;
            if (!codeEl) return;

            navigator.clipboard.writeText(codeEl.textContent).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            });
        },
        init() {
            // Re-highlight on init (for dynamically loaded content)
            this.$nextTick(() => {
                if (typeof Prism !== 'undefined') {
                    Prism.highlightAllUnder(this.$el);
                }
            });
        }
    }));
});
</script>
@endonce

<section
    class="pro-code-snippet-block py-12 sm:py-16 {{ ($wrap_lines ?? false) ? 'wrap-lines' : '' }}"
    style="{{ $customProperties }}"
>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div class="text-center mb-8">
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

        {{-- Code Block --}}
        @if(!empty($code))
            <div
                x-data="proCodeSnippet"
                class="rounded-lg shadow-lg overflow-hidden"
            >
                {{-- Header Bar --}}
                <div class="code-header">
                    <div class="flex items-center gap-3">
                        {{-- Traffic Light Dots --}}
                        <div class="flex gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        </div>

                        @if(!empty($filename))
                            <span class="filename">
                                <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ $filename }}
                            </span>
                        @else
                            <span class="language-badge">{{ strtoupper($language ?? 'code') }}</span>
                        @endif
                    </div>

                    @if($show_copy_button ?? true)
                        <button
                            @click="copyCode"
                            class="copy-button"
                            :class="{ 'copied': copied }"
                        >
                            <template x-if="!copied">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </template>
                            <template x-if="copied">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    @endif
                </div>

                {{-- Code Content --}}
                <div style="{{ $maxHeightStyle }}">
                    <pre class="{{ ($show_line_numbers ?? true) ? 'line-numbers' : '' }}" @if(!empty($highlight_lines)) data-line="{{ $highlight_lines }}" @endif><code x-ref="code" class="language-{{ $prismLanguage }}">{{ $escapedCode }}</code></pre>
                </div>
            </div>
        @else
            {{-- No Code Configured --}}
            <div class="relative w-full bg-gray-800 rounded-lg" style="padding: 3rem;">
                <div class="flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <p class="text-sm">No code configured. Click to edit this block.</p>
                </div>
            </div>
        @endif
    </div>
</section>
