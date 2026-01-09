@php
    $textPreset = function_exists('theme_text_presets') ? theme_text_presets()['primary'] ?? [] : [];
    $selectedTheme = $theme ?? 'okaidia';
    $isPreview = $is_preview ?? false;

    // Theme configurations
    $themes = [
        'okaidia' => ['bg' => '#272822', 'text' => '#f8f8f2', 'lineNum' => '#6e7066', 'header' => '#1e1e1e'],
        'tomorrow' => ['bg' => '#2d2d2d', 'text' => '#cccccc', 'lineNum' => '#666666', 'header' => '#1a1a1a'],
        'twilight' => ['bg' => '#141414', 'text' => '#f7f7f7', 'lineNum' => '#4a4a4a', 'header' => '#0a0a0a'],
        'dark' => ['bg' => '#1d1f21', 'text' => '#c5c8c6', 'lineNum' => '#5c5c5c', 'header' => '#141516'],
        'coy' => ['bg' => '#fdfdfd', 'text' => '#333333', 'lineNum' => '#999999', 'header' => '#f0f0f0'],
        'solarizedlight' => ['bg' => '#fdf6e3', 'text' => '#657b83', 'lineNum' => '#93a1a1', 'header' => '#eee8d5'],
    ];
    $currentTheme = $themes[$selectedTheme] ?? $themes['okaidia'];
    $isDarkTheme = !in_array($selectedTheme, ['coy', 'solarizedlight']);

    // Prism language mapping
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

    // Max height styles
    $maxHeightStyle = match($max_height ?? 'none') {
        'sm' => 'max-height: 300px; overflow-y: auto;',
        'md' => 'max-height: 400px; overflow-y: auto;',
        'lg' => 'max-height: 500px; overflow-y: auto;',
        'xl' => 'max-height: 600px; overflow-y: auto;',
        default => '',
    };

    // Prepare code lines for preview (with line numbers)
    $codeContent = $code ?? '';
    $escapedCode = e($codeContent);
    $codeLines = explode("\n", $codeContent);
    $lineCount = count($codeLines);
@endphp

@if(!$isPreview)
{{-- Frontend: Full Prism.js experience --}}
@once
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.css">
@php
$themeCssFile = match($selectedTheme) {
    'tomorrow' => 'prism-tomorrow',
    'twilight' => 'prism-twilight',
    'coy' => 'prism-coy',
    'solarizedlight' => 'prism-solarizedlight',
    'dark' => 'prism-dark',
    default => 'prism-okaidia',
};
@endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/{{ $themeCssFile }}.min.css">
<style>
.pro-code-snippet-block pre[class*="language-"] {
    margin: 0;
    border-radius: 0 0 0.5rem 0.5rem;
}
.pro-code-snippet-block .code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem 0.5rem 0 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.pro-code-snippet-block .copy-button {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.15s ease;
}
.pro-code-snippet-block .copy-button:hover {
    background: rgba(255,255,255,0.2);
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
        }
    }));
});
</script>
@endonce
@endif

<section style="padding: 3rem 0;">
    <div style="max-width: 56rem; margin: 0 auto; padding: 0 1rem;">
        {{-- Section Header --}}
        @if(!empty($heading) || !empty($subheading))
            <div style="text-align: center; margin-bottom: 2rem;">
                @if(!empty($heading))
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: {{ $textPreset['heading'] ?? '#111827' }};">
                        {{ $heading }}
                    </h2>
                @endif
                @if(!empty($subheading))
                    <p style="margin-top: 0.75rem; font-size: 1.125rem; color: {{ $textPreset['description'] ?? '#4b5563' }};">
                        {{ $subheading }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Code Block --}}
        @if(!empty($code))
            <div style="border-radius: 0.5rem; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);" @if(!$isPreview) x-data="proCodeSnippet()" @endif>
                {{-- Header Bar --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: {{ $currentTheme['header'] }}; border-bottom: 1px solid rgba({{ $isDarkTheme ? '255,255,255' : '0,0,0' }},0.1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        {{-- Traffic Light Dots --}}
                        <div style="display: flex; gap: 0.375rem;">
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 50%; background: #ef4444;"></span>
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 50%; background: #eab308;"></span>
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 50%; background: #22c55e;"></span>
                        </div>

                        @if(!empty($filename))
                            <span style="display: flex; align-items: center; gap: 0.5rem; color: {{ $isDarkTheme ? '#e5e5e5' : '#333' }}; font-size: 0.875rem; font-family: ui-monospace, monospace;">
                                <svg style="width: 1rem; height: 1rem; opacity: 0.6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ $filename }}
                            </span>
                        @else
                            <span style="color: {{ $isDarkTheme ? '#a0a0a0' : '#666' }}; font-size: 0.75rem; text-transform: uppercase;">
                                {{ strtoupper($language ?? 'code') }}
                            </span>
                        @endif
                    </div>

                    @if($show_copy_button ?? true)
                        @if($isPreview)
                            <span style="display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: rgba({{ $isDarkTheme ? '255,255,255' : '0,0,0' }},0.1); border-radius: 0.375rem; color: {{ $isDarkTheme ? '#e5e5e5' : '#333' }}; font-size: 0.75rem;">
                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copy
                            </span>
                        @else
                            <button @click="copyCode" style="display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: rgba(255,255,255,0.1); border: none; border-radius: 0.375rem; color: #e5e5e5; font-size: 0.75rem; cursor: pointer;">
                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                            </button>
                        @endif
                    @endif
                </div>

                {{-- Code Content --}}
                @if($isPreview)
                    {{-- Preview: Pure inline styles, manual line numbers --}}
                    <div style="background: {{ $currentTheme['bg'] }}; {{ $maxHeightStyle }} overflow-x: auto;">
                        <div style="display: flex; min-width: max-content;">
                            @if($show_line_numbers ?? true)
                                <div style="padding: 1rem 0; text-align: right; user-select: none; border-right: 1px solid rgba({{ $isDarkTheme ? '255,255,255' : '0,0,0' }},0.1); background: rgba({{ $isDarkTheme ? '0,0,0' : '0,0,0' }},0.1);">
                                    @for($i = 1; $i <= $lineCount; $i++)
                                        <div style="padding: 0 0.75rem; font-family: ui-monospace, monospace; font-size: 0.875rem; line-height: 1.5; color: {{ $currentTheme['lineNum'] }};">{{ $i }}</div>
                                    @endfor
                                </div>
                            @endif
                            <pre style="margin: 0; padding: 1rem; flex: 1; overflow-x: auto; background: transparent;"><code style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.875rem; line-height: 1.5; color: {{ $currentTheme['text'] }}; white-space: pre; display: block;">{{ $escapedCode }}</code></pre>
                        </div>
                    </div>
                @else
                    {{-- Frontend: Full Prism.js --}}
                    <div class="pro-code-snippet-block" style="{{ $maxHeightStyle }}">
                        <pre class="{{ ($show_line_numbers ?? true) ? 'line-numbers' : '' }}" @if(!empty($highlight_lines)) data-line="{{ $highlight_lines }}" @endif><code x-ref="code" class="language-{{ $prismLanguage }}">{{ $escapedCode }}</code></pre>
                    </div>
                @endif
            </div>
        @else
            {{-- No Code Configured --}}
            <div style="background: #1f2937; border-radius: 0.5rem; padding: 3rem; text-align: center;">
                <svg style="width: 4rem; height: 4rem; margin: 0 auto 1rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                <p style="color: #9ca3af; font-size: 0.875rem;">No code configured. Click to edit this block.</p>
            </div>
        @endif
    </div>
</section>
