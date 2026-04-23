{{--
    daisyUI theme boot script.

    Applies the server-rendered data-theme attribute on first paint, then
    overrides it with the visitor's saved preference if one exists. When the
    admin changes the default preset, visitors' overrides are cleared the next
    time they visit.

    Namespaced under tallcms-* to avoid colliding with Filament's admin
    light/dark toggle, which writes localStorage.theme on the same origin.
    Without namespacing, visiting /admin leaks 'light'/'dark' into the
    daisyUI preset, overriding whatever coffee/dracula/etc the admin had chosen.

    Themes call @tallcmsDaisyUIBoot in <head> right after the opening <html>
    data-theme attribute. The boot script must run synchronously before first
    paint to prevent a flash of wrong theme.
--}}
<script>
    (function() {
        var serverDefault = document.documentElement.getAttribute('data-default-theme');
        var storedDefault = localStorage.getItem('tallcms-theme-default');
        if (storedDefault !== serverDefault) {
            localStorage.removeItem('tallcms-theme');
            localStorage.setItem('tallcms-theme-default', serverDefault);
        }
        var savedTheme = localStorage.getItem('tallcms-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    })();
</script>
