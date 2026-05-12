// ============================================================
// transition.js – Page transition animations
// Scale + fade in/out on navigation (300ms cubic-bezier)
// ============================================================
(function(){
    // ── Helpers ────────────────────────────────────────────────
    function onReady(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    // ── Enter animation ────────────────────────────────────────
    function playEnter() {
        var page = document.getElementById('page');
        if (!page) return;
        // Start hidden (CSS .page class), then activate after ~40ms
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                page.classList.add('active');
            });
        });
    }

    // ── Exit animation + navigate ──────────────────────────────
    function playExitAndGo(href) {
        var page = document.getElementById('page');
        if (!page) { window.location.href = href; return; }
        page.classList.add('exit');
        setTimeout(function() {
            window.location.href = href;
        }, 340); // 300ms transition + 40ms buffer
    }

    // ── Determine if a link is internal (same origin) ──────────
    function isInternalLink(link) {
        // Only intercept same-origin navigations
        return link.hostname === window.location.hostname
            && link.protocol === window.location.protocol;
    }

    // ── Boot ───────────────────────────────────────────────────
    onReady(function() {
        playEnter();

        // Intercept all clicks on <a> tags for exit animation
        document.addEventListener('click', function(e) {
            var link = e.target.closest('a');
            if (!link) return;
            // Skip links that open in new tab, download, or have special behavior
            if (link.target === '_blank') return;
            if (link.hasAttribute('download')) return;
            if (link.getAttribute('href') === '#') return;
            // Skip anchor-only links
            if (link.getAttribute('href') && link.getAttribute('href').charAt(0) === '#') return;
            if (!isInternalLink(link)) return;
            // Skip links inside forms or that trigger POST
            if (link.closest('form')) return;

            e.preventDefault();
            playExitAndGo(link.href);
        });
    });
})();