// INESCO – Frontend JS
(function () {
    'use strict';

    // ---- DSGVO-Fassade: YouTube/Vimeo erst auf Klick laden ----
    document.querySelectorAll('.video-facade').forEach(function (facade) {
        facade.addEventListener('click', function () {
            const src   = facade.dataset.src;
            const title = facade.dataset.title || '';
            if (!src) return;
            const iframe = document.createElement('iframe');
            // append autoplay so the video starts immediately after facade click
            const autoSrc = src + (src.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
            iframe.src         = autoSrc;
            iframe.title       = title;
            iframe.frameBorder = '0';
            iframe.allow       = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            iframe.style.cssText   = 'position:absolute;inset:0;width:100%;height:100%;border:0;';
            facade.parentNode.replaceChild(iframe, facade);
        });
    });

    // Mobile Nav Toggle
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');
    if (toggle && nav) {
        const header = document.querySelector('.site-header');
        const headerOffset = () => {
            if (!header) return 0;
            return Math.ceil(header.getBoundingClientRect().height) + 10;
        };

        const normalizePath = (path) => {
            if (!path) return '/';
            let p = path;
            if (p.length > 1 && p.endsWith('/')) p = p.slice(0, -1);
            return p;
        };

        const resolveHash = (href) => {
            if (!href) return null;
            if (href.startsWith('#')) return href;
            try {
                const u = new URL(href, window.location.href);
                if (normalizePath(u.pathname) !== normalizePath(window.location.pathname)) return null;
                return u.hash || null;
            } catch (e) {
                return null;
            }
        };

        const scrollToHash = (hash, updateHistory = true) => {
            if (!hash || hash.length < 2) return false;
            const id = decodeURIComponent(hash.slice(1));
            const target = document.getElementById(id);
            if (!target) return false;

            const scrollNow = (behavior) => {
                const isFixed = window.getComputedStyle(target).position === 'fixed';
                const y = isFixed ? 0 : target.getBoundingClientRect().top + window.scrollY - headerOffset();
                window.scrollTo({ top: Math.max(0, y), behavior });
            };

            scrollNow('smooth');
            if (updateHistory) history.replaceState(null, '', hash);
            return true;
        };

        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        const handleHashLink = (a, e) => {
            const hash = resolveHash(a.getAttribute('href'));
            if (!hash) return;
            e.preventDefault();
            scrollToHash(hash);
        };

        nav.querySelectorAll('a').forEach(a => a.addEventListener('click', (e) => {
            nav.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            handleHashLink(a, e);
        }));

        // Brand/logo link (outside .main-nav)
        const brand = document.querySelector('.brand');
        if (brand) {
            brand.addEventListener('click', (e) => handleHashLink(brand, e));
        }

        if (window.location.hash) {
            // Correct initial anchor position once layout and fixed header are painted.
            setTimeout(() => { scrollToHash(window.location.hash, false); }, 0);
        }

        // Dynamic overflow detection: switch to hamburger whenever nav items don't fit.
        const headerInner = header ? header.querySelector('.header-inner') : null;
        const brandEl = document.querySelector('.brand');
        const navUl = nav.querySelector('ul');
        const navToggle = header ? header.querySelector('.nav-toggle') : null;

        const checkNavFit = () => {
            if (!header || !headerInner || !nav || !navUl) return;

            // Measure nav's natural (unwrapped) width via inline style override.
            // We force it off-screen / invisible so there's no flash.
            const hadOverflow = header.classList.contains('nav-overflow');
            const prevStyle = nav.getAttribute('style') || '';
            const prevUlDir = navUl.style.flexDirection;
            const prevUlWrap = navUl.style.flexWrap;
            const prevToggleDisplay = navToggle ? navToggle.style.display : '';

            // width:max-content makes the nav shrink-wrap to content,
            // so getBoundingClientRect().width is the exact natural content width.
            if (hadOverflow) header.classList.remove('nav-overflow');
            if (navToggle) navToggle.style.display = 'none';
            nav.style.cssText = 'position:fixed;top:-9999px;left:0;width:max-content;visibility:hidden;max-height:none;overflow:visible;pointer-events:none;';
            navUl.style.flexDirection = 'row';
            navUl.style.flexWrap = 'nowrap';
            navUl.offsetHeight; // force reflow

            const navNaturalWidth = navUl.getBoundingClientRect().width;
            const brandWidth = brandEl ? brandEl.offsetWidth : 0;
            const available = headerInner.clientWidth - brandWidth;

            nav.style.cssText = prevStyle;
            navUl.style.flexDirection = prevUlDir;
            navUl.style.flexWrap = prevUlWrap;
            if (navToggle) navToggle.style.display = prevToggleDisplay;

            const overflows = navNaturalWidth > available - 4; // 4px tolerance
            header.classList.toggle('nav-overflow', overflows);

            if (!overflows && nav.classList.contains('open')) {
                nav.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        };

        if (typeof ResizeObserver !== 'undefined' && headerInner) {
            new ResizeObserver(checkNavFit).observe(headerInner);
        }
        window.addEventListener('resize', checkNavFit);
        checkNavFit();
    }

    // Reveal on scroll
    const reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && reveals.length) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.15 });
        reveals.forEach(el => io.observe(el));
    } else {
        reveals.forEach(el => el.classList.add('in'));
    }

    // Media-Steuerung: nur ein Audio/Video gleichzeitig (inkl. YouTube/Vimeo iFrames)
    const mediaEls     = document.querySelectorAll('audio, video');
    const videoIframes = Array.from(document.querySelectorAll('.video-frame iframe'));

    const isYouTube = (src) => /youtube\.com|youtu\.be/.test(src || '');
    const isVimeo   = (src) => /vimeo\.com/.test(src || '');

    const pauseIframe = (frame) => {
        const src = frame.getAttribute('src') || '';
        try {
            if (isYouTube(src)) {
                frame.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
            } else if (isVimeo(src)) {
                frame.contentWindow.postMessage(JSON.stringify({ method: 'pause' }), '*');
            } else {
                frame.src = src; // Fallback
            }
        } catch (e) {
            frame.src = src;
        }
    };

    const stopOthers = (current) => {
        mediaEls.forEach(m => { if (m !== current && !m.paused) m.pause(); });
        videoIframes.forEach(f => { if (f !== current) pauseIframe(f); });
    };

    // Native HTML5 Media
    mediaEls.forEach(m => {
        m.addEventListener('play', () => stopOthers(m));
    });

    // Vimeo: "play"-Event abonnieren, sobald Player bereit ist
    videoIframes.forEach(f => {
        const src = f.getAttribute('src') || '';
        if (isVimeo(src)) {
            const send = (obj) => {
                try { f.contentWindow.postMessage(JSON.stringify(obj), '*'); } catch (e) {}
            };
            // Vimeo erwartet, dass wir uns nach dem ready-Event registrieren
            send({ method: 'addEventListener', value: 'play' });
        }
    });

    // Globale Message-Listener für YouTube & Vimeo
    window.addEventListener('message', (ev) => {
        const data = ev.data;
        let parsed = data;
        if (typeof data === 'string') {
            try { parsed = JSON.parse(data); } catch (e) { return; }
        }
        if (!parsed || typeof parsed !== 'object') return;

        // Quelle (iframe) identifizieren
        const source = videoIframes.find(f => f.contentWindow === ev.source);
        if (!source) return;

        // YouTube: { event: "infoDelivery"|"onStateChange", info: {...} | 1 }
        // Status 1 = playing
        if (parsed.event === 'onStateChange' && parsed.info === 1) {
            stopOthers(source);
        }
        if (parsed.event === 'infoDelivery' && parsed.info && parsed.info.playerState === 1) {
            stopOthers(source);
        }

        // Vimeo: { event: "play", ... }
        if (parsed.event === 'play') {
            stopOthers(source);
        }

        // Vimeo "ready" → addEventListener nachreichen
        if (parsed.event === 'ready' && isVimeo(source.getAttribute('src') || '')) {
            try {
                source.contentWindow.postMessage(JSON.stringify({ method: 'addEventListener', value: 'play' }), '*');
            } catch (e) {}
        }
    });

    // YouTube braucht eine Anfrage nach "listening", damit es Status-Events sendet
    videoIframes.forEach(f => {
        const src = f.getAttribute('src') || '';
        if (isYouTube(src)) {
            const trigger = () => {
                try {
                    f.contentWindow.postMessage('{"event":"listening","id":1,"channel":"widget"}', '*');
                } catch (e) {}
            };
            f.addEventListener('load', trigger);
            // Falls iframe schon geladen ist
            setTimeout(trigger, 500);
        }
    });

    // Hero-Slideshow (Crossfade)
    const heroEl = document.querySelector('.hero');
    const slides = heroEl ? heroEl.querySelectorAll('.hero-slide') : [];
    if (heroEl && slides.length > 1) {
        const interval = parseInt(heroEl.dataset.heroInterval, 10) || 6000;
        let idx = 0;
        setInterval(() => {
            slides[idx].classList.remove('is-active');
            idx = (idx + 1) % slides.length;
            slides[idx].classList.add('is-active');
        }, interval);
    }

    // Kontaktformular per Fetch
    const form = document.getElementById('contactForm');
    if (form) {
        const status = form.querySelector('.form-status');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            status.className = 'form-status';
            status.textContent = 'Wird gesendet …';

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    status.classList.add('ok');
                    status.textContent = data.message || 'Vielen Dank! Wir melden uns bald.';
                    form.reset();
                } else {
                    status.classList.add('err');
                    status.textContent = data.error || 'Senden fehlgeschlagen.';
                }
            } catch (err) {
                status.classList.add('err');
                status.textContent = 'Netzwerkfehler. Bitte später erneut versuchen.';
            }
        });
    }

    const refreshGuestbookSection = async (message) => {
        const currentSection = document.getElementById('gaestebuch');
        if (!currentSection) return;

        const res = await fetch(window.location.pathname + window.location.search, {
            headers: { 'X-Requested-With': 'fetch' }
        });
        if (!res.ok) throw new Error('refresh_failed');

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextSection = doc.getElementById('gaestebuch');
        if (!nextSection) throw new Error('section_missing');

        currentSection.replaceWith(nextSection);
        nextSection.querySelectorAll('.reveal').forEach(el => el.classList.add('in'));

        const nextForm = nextSection.querySelector('#gbForm');
        if (nextForm) {
            const nextStatus = nextForm.querySelector('.form-status');
            if (nextStatus) {
                nextStatus.className = 'form-status ok';
                nextStatus.textContent = message;
            }
            bindGuestbookForm(nextForm);
        }
    };

    const bindGuestbookForm = (guestbookForm) => {
        if (!guestbookForm || guestbookForm.dataset.bound === '1') return;
        guestbookForm.dataset.bound = '1';

        const gbStatus = guestbookForm.querySelector('.form-status');
        const submitButton = guestbookForm.querySelector('button[type="submit"]');
        let isSubmitting = false;
        guestbookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (isSubmitting) return;

            isSubmitting = true;
            if (submitButton) submitButton.disabled = true;
            gbStatus.className = 'form-status';
            gbStatus.textContent = 'Wird gesendet …';

            try {
                const res = await fetch(guestbookForm.action, {
                    method: 'POST',
                    body: new FormData(guestbookForm),
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    const successMessage = data.message || 'Danke! Dein Eintrag wird nach Prüfung sichtbar.';
                    guestbookForm.reset();
                    await refreshGuestbookSection(successMessage);
                } else {
                    gbStatus.classList.add('err');
                    if (res.status === 429 && Number.isInteger(data.retry_after) && data.retry_after > 0) {
                        gbStatus.textContent = 'Bitte noch ' + data.retry_after + ' Sekunden warten.';
                    } else {
                        gbStatus.textContent = data.error || 'Senden fehlgeschlagen.';
                    }
                }
            } catch (err) {
                gbStatus.classList.add('err');
                gbStatus.textContent = 'Netzwerkfehler. Bitte später erneut versuchen.';
            } finally {
                isSubmitting = false;
                if (submitButton) submitButton.disabled = false;
            }
        });
    };

    // Gästebuch-Formular per Fetch
    bindGuestbookForm(document.getElementById('gbForm'));
})();
