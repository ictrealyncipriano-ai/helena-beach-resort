<script>
    // Apply light/dark before first paint to avoid a flash.
    (function () {
        var mode = localStorage.getItem('theme') || 'system';
        var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
