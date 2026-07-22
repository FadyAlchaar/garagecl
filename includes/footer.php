<?php
// ============================================================
// FOOTER – Included at the bottom of every page
// ============================================================
?>
</main> <!-- Close main-content opened in header -->

<!-- ============================================================
     FLOATING SCROLL BUTTONS (Up / Down)
     ============================================================ -->
<div id="scrollButtons" style="position:fixed; bottom:30px; right:30px; z-index:9999; display:flex; flex-direction:column; gap:8px;">
    <button id="scrollUpBtn" 
            style="width:48px; height:48px; border-radius:50%; background:#c0392b; color:#fff; border:none; font-size:24px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.2); transition:transform 0.2s, opacity 0.2s; opacity:0.7;"
            onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)'"
            onmouseout="this.style.opacity='0.7'; this.style.transform='scale(1)'"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            aria-label="Scroll to top">
        ↑
    </button>
    <button id="scrollDownBtn" 
            style="width:48px; height:48px; border-radius:50%; background:#1a1a2e; color:#fff; border:none; font-size:24px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.2); transition:transform 0.2s, opacity 0.2s; opacity:0.7;"
            onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)'"
            onmouseout="this.style.opacity='0.7'; this.style.transform='scale(1)'"
            onclick="window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })"
            aria-label="Scroll to bottom">
        ↓
    </button>
</div>

<!-- ============================================================
     OPTIONAL: Hide/show buttons based on scroll position
     (makes them appear only when needed)
     ============================================================ -->
<script>
// Simple: hide both buttons when at top, show down button when scrolled down
// and show up button when not at top, hide down when at bottom.
(function() {
    const upBtn = document.getElementById('scrollUpBtn');
    const downBtn = document.getElementById('scrollDownBtn');

    function updateButtons() {
        const scrollY = window.scrollY;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        const atTop = scrollY < 20;
        const atBottom = maxScroll - scrollY < 20;

        upBtn.style.display = atTop ? 'none' : 'block';
        downBtn.style.display = atBottom ? 'none' : 'block';
    }

    window.addEventListener('scroll', updateButtons);
    window.addEventListener('resize', updateButtons);
    updateButtons(); // Initial check
})();
</script>

<footer style="text-align:center; padding:20px; color:#888; font-size:0.8rem; border-top:1px solid #eee; margin-top:30px;">
    <span class="ar">نظام فحص السيارات</span> &bull; 
    <span class="en">Car Inspection System</span> &bull; 
    v<?= date('Y') ?>
</footer>

<!-- Close body and html tags opened in header -->
</body>
</html>