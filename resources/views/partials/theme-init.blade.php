{{-- Applies the saved theme before first paint. With no saved choice, the system opens in light mode. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('skillbridge-theme');
            if (t !== 'dark' && t !== 'light') t = 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    })();
</script>
