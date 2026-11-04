{{-- Applies the saved theme before first paint. First visit only: follow the device setting. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('skillbridge-theme') || localStorage.getItem('sb-landing-theme');
            if (t !== 'dark' && t !== 'light') t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    })();
</script>
