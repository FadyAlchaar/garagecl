// ============================================================
// GARAGE INSPECTION SYSTEM — SHARED JS
// ============================================================

// ---- HAMBURGER MENU ----------------------------------------
function toggleNav() {
    const menu = document.getElementById('navMenu');
    const btn  = document.getElementById('navHamburger');
    if (!menu || !btn) return;
    const isOpen = menu.classList.toggle('open');
    btn.classList.toggle('open', isOpen);
    btn.setAttribute('aria-expanded', isOpen);
}

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('navMenu');
    const btn  = document.getElementById('navHamburger');
    if (!menu || !btn) return;
    if (!menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove('open');
        btn.classList.remove('open');
    }
});

// Close menu on nav link click (mobile)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.navbar-menu a').forEach(link => {
        link.addEventListener('click', () => {
            const menu = document.getElementById('navMenu');
            const btn  = document.getElementById('navHamburger');
            if (menu) menu.classList.remove('open');
            if (btn)  btn.classList.remove('open');
        });
    });
});

// ---- ACTIVE NAV LINK ---------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.navbar-menu a');
    links.forEach(link => {
        const href = link.getAttribute('href') || '';
        if (href && window.location.href.includes(href.split('?')[0])) {
            link.classList.add('active');
        }
    });
});

// ---- AUTO-HIDE ALERTS --------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert:not(#report-alert)').forEach(a => {
        setTimeout(() => {
            a.style.transition = 'opacity .5s';
            a.style.opacity = '0';
            setTimeout(() => a.remove(), 500);
        }, 5000);
    });
});

// ---- SMOOTH SCROLL TO TOP ON STEP CHANGE ------------------
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ---- SWIPE SUPPORT FOR WIZARD (touch devices) -------------
let touchStartX = 0;
let touchStartY = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
}, { passive: true });

document.addEventListener('touchend', function(e) {
    if (!window.currentStep && window.currentStep !== 0) return;
    const dx = e.changedTouches[0].clientX - touchStartX;
    const dy = e.changedTouches[0].clientY - touchStartY;

    // Only horizontal swipes (more horizontal than vertical)
    if (Math.abs(dx) < 60 || Math.abs(dy) > Math.abs(dx)) return;

    // Don't swipe on inputs or selects
    const tag = e.target.tagName.toLowerCase();
    if (['input','select','textarea'].includes(tag)) return;

    // RTL: swipe right = previous, swipe left = next
    if (document.dir === 'rtl') {
        if (dx > 60 && typeof changeStep === 'function') changeStep(-1); // right = prev
        if (dx < -60 && typeof changeStep === 'function') changeStep(1); // left  = next
    } else {
        if (dx < -60 && typeof changeStep === 'function') changeStep(1);
        if (dx > 60  && typeof changeStep === 'function') changeStep(-1);
    }
}, { passive: true });

// ---- KEYBOARD SHORTCUTS ------------------------------------
document.addEventListener('keydown', function(e) {
    // Ctrl+S = Save draft
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (typeof saveReport === 'function') saveReport(false);
    }
    // Ctrl+P = Print PDF (if on report view page)
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        const pdfLink = document.querySelector('a[href*="pdf/generate"]');
        if (pdfLink) {
            e.preventDefault();
            window.open(pdfLink.href, '_blank');
        }
    }
    // Escape = close any open popup
    if (e.key === 'Escape') {
        document.querySelectorAll('.popup,.pup').forEach(p => p.style.display = 'none');
        const navMenu = document.getElementById('navMenu');
        if (navMenu) navMenu.classList.remove('open');
    }
});

// ---- CONFIRM DELETE ----------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'هل أنت متأكد؟ / Are you sure?')) {
                e.preventDefault();
            }
        });
    });
});

// ---- TABLE RESPONSIVE LABELS (phone card view) -------------
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.reports-table').forEach(table => {
        const headers = Array.from(table.querySelectorAll('thead th'))
                             .map(th => th.textContent.trim());
        table.querySelectorAll('tbody tr').forEach(row => {
            Array.from(row.querySelectorAll('td')).forEach((td, i) => {
                if (headers[i]) td.setAttribute('data-label', headers[i]);
            });
        });
    });
});

// ---- RIPPLE EFFECT ON BUTTONS ------------------------------
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const ripple = document.createElement('span');
    const rect   = btn.getBoundingClientRect();
    const size   = Math.max(rect.width, rect.height);
    ripple.style.cssText = `
        position:absolute;width:${size}px;height:${size}px;
        border-radius:50%;background:rgba(255,255,255,.3);
        top:${e.clientY - rect.top - size/2}px;
        left:${e.clientX - rect.left - size/2}px;
        transform:scale(0);animation:ripple .4s linear;pointer-events:none
    `;
    if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 400);
});

// Add ripple animation
const style = document.createElement('style');
style.textContent = '@keyframes ripple{to{transform:scale(2.5);opacity:0}}';
document.head.appendChild(style);
