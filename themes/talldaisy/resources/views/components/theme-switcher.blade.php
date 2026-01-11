@props(['class' => ''])

<div class="dropdown dropdown-end {{ $class }}">
    <div tabindex="0" role="button" class="btn btn-ghost btn-sm gap-1">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
        </svg>
        <span class="hidden sm:inline">Theme</span>
        <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
    <ul tabindex="0" class="dropdown-content menu bg-base-200 rounded-box z-50 w-52 p-2 shadow-2xl max-h-96 overflow-y-auto" id="theme-list">
        @foreach(daisyui_presets() as $preset)
            <li>
                <button type="button"
                        class="btn btn-sm btn-block btn-ghost justify-start theme-btn"
                        data-theme-value="{{ $preset }}">
                    {{ ucfirst($preset) }}
                </button>
            </li>
        @endforeach
    </ul>
</div>

<script>
    // Theme switcher - explicit control without relying on theme-controller class
    (function() {
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            // Update active button styling
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.toggle('btn-active', btn.dataset.themeValue === theme);
            });
        }

        // Initialize: mark current theme as active
        const savedTheme = localStorage.getItem('theme') ||
                          document.documentElement.getAttribute('data-theme') ||
                          'light';
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.toggle('btn-active', btn.dataset.themeValue === savedTheme);
            btn.addEventListener('click', function() {
                setTheme(this.dataset.themeValue);
            });
        });
    })();
</script>
