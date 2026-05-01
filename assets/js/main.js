// INESCO – Frontend JS
(function () {
    'use strict';

    // Mobile Nav Toggle
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
            nav.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }));
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
})();
