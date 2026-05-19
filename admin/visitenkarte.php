<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Visitenkarte';

$contact = read_json('contact');
$defaults = [
    'logo'    => 'assets/img/logo.png',
    'name'    => 'INESCO',
    'tagline' => 'Live · Music · Booking',
    'phone'   => $contact['phone'] ?? '+43 664 88 54 83 33',
    'email'   => $contact['email'] ?? 'booking@inesco-music.at',
    'web'     => 'inesco-music.at',
];

include __DIR__ . '/header.php';
?>
<h1>Visitenkarte</h1>
<p class="muted" style="margin-top:0">
    Live-Vorschau – ändere rechts die Inhalte, klicke auf <em>QR aktualisieren</em>
    und drucke die Karte aus oder speichere sie als PDF.
    Änderungen bleiben in diesem Browser gespeichert.
</p>

<div class="vk-layout">
    <!-- ===== LINKS: Karten + Toolbar ===== -->
    <div class="vk-preview">
        <div class="vk-toolbar">
            <button type="button" class="btn" id="vkPrintAll">Beide Seiten als PDF downloaden</button>
            <button type="button" class="btn btn-outline" id="vkJpegFront">Vorderseite als JPEG</button>
            <button type="button" class="btn btn-outline" id="vkJpegBack">Rückseite als JPEG</button>
            <button type="button" class="btn btn-outline" id="vkRefresh">QR aktualisieren</button>
            <button type="button" class="btn btn-outline" id="vkReset">Zurücksetzen</button>
        </div>

        <div class="vk-stage">
            <!-- Vorderseite -->
            <div class="vk-card" id="vkFront">
                <div class="brand">
                    <img id="vkLogo" src="<?= e(url($defaults['logo'])) ?>" alt="Logo">
                    <div>
                        <div class="word" id="vkName"><?= e($defaults['name']) ?></div>
                        <div class="tag"  id="vkTag"><?= e($defaults['tagline']) ?></div>
                    </div>
                </div>
                <div class="info">
                    <div class="row"><span class="lbl">Tel</span><span class="val" id="vkPhone"><?= e($defaults['phone']) ?></span></div>
                    <div class="row"><span class="lbl">Mail</span><span class="val" id="vkEmail"><?= e($defaults['email']) ?></span></div>
                    <div class="row"><span class="lbl">Web</span><span class="val" id="vkWeb"><?= e($defaults['web']) ?></span></div>
                </div>
                <div class="qrwrap"><div id="vkQr"></div></div>
            </div>

            <!-- Rückseite -->
            <div class="vk-card back">
                <div class="word" id="vkBackName"><?= e($defaults['name']) ?></div>
                <div class="tag"  id="vkBackWeb"><?= e($defaults['web']) ?></div>
            </div>
        </div>
    </div>

    <!-- ===== RECHTS: Editor ===== -->
    <aside class="vk-editor" aria-label="Visitenkarte bearbeiten">
        <h2>Bearbeiten</h2>
        <div class="vk-editor-fields">

        <fieldset>
            <legend>Logo</legend>
            <label>Logo-Pfad (relativ zur Website)
                <input type="text" id="fLogo" value="<?= e($defaults['logo']) ?>">
            </label>
            <label>Oder Logo-Datei hochladen (nur Vorschau)
                <input type="file" id="fLogoFile" accept="image/*">
            </label>
            <label>Logo-Größe: <output id="oLogoSize">22</output> mm
                <input type="range" id="fLogoSize" min="10" max="35" step="1" value="22">
            </label>
        </fieldset>

        <fieldset>
            <legend>Texte (Vorderseite)</legend>
            <label>Name
                <input type="text" id="fName" value="<?= e($defaults['name']) ?>" maxlength="40">
            </label>
            <label>Untertitel / Tagline
                <input type="text" id="fTag" value="<?= e($defaults['tagline']) ?>" maxlength="60">
            </label>
            <label>Telefon
                <input type="text" id="fPhone" value="<?= e($defaults['phone']) ?>" maxlength="40">
            </label>
            <label>E-Mail
                <input type="email" id="fEmail" value="<?= e($defaults['email']) ?>" maxlength="120">
            </label>
            <label>Web
                <input type="text" id="fWeb" value="<?= e($defaults['web']) ?>" maxlength="80">
            </label>
        </fieldset>

        <fieldset>
            <legend>QR-Code</legend>
            <label>QR-Inhalt
                <textarea id="fQr" rows="6" placeholder="Leer lassen → vCard aus den Feldern oben."></textarea>
            </label>
            <p class="muted" style="margin:.25rem 0 .5rem">
                Tipp: Leer lassen → es wird automatisch eine <strong>vCard</strong>
                (Name, Tel, Mail, Web) erzeugt.
            </p>
            <label>Fehlerkorrektur
                <select id="fQrLevel">
                    <option value="L">L – niedrig</option>
                    <option value="M" selected>M – mittel</option>
                    <option value="Q">Q – hoch</option>
                    <option value="H">H – sehr hoch</option>
                </select>
            </label>
        </fieldset>

        <fieldset>
            <legend>Rückseite</legend>
            <label>Großer Text
                <input type="text" id="fBackName" value="<?= e($defaults['name']) ?>" maxlength="40">
            </label>
            <label>Kleiner Text
                <input type="text" id="fBackWeb" value="<?= e($defaults['web']) ?>" maxlength="80">
            </label>
        </fieldset>

        <fieldset>
            <legend>Beschnittzugabe (JPEG)</legend>
            <label>Weißer Rand: <output id="oBleed">3</output> mm
                <input type="range" id="fBleed" min="0" max="10" step="0.1" value="3">
            </label>
            <p class="muted" style="margin:.25rem 0 0;font-size:.8rem">
                Weißer Rand um die Karte im JPEG-Export (Beschnittzugabe für den Druck).
            </p>
        </fieldset>

        </div><!-- .vk-editor-fields -->
    </aside>
</div>

<style>
    /* ===== Layout ===== */
    .vk-layout   { display: grid; grid-template-columns: 1fr; gap: 24px; align-items: start; }
    .vk-preview  { min-width: 0; }
    .vk-toolbar  { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .vk-stage    { display: flex; flex-wrap: wrap; gap: 24px; }

    .vk-editor   {
        background: rgba(0,0,0,.25);
        border: 1px solid rgba(212,168,90,.25);
        border-radius: 8px;
        padding: 14px 16px;
    }
    .vk-editor h2 { margin: 0 0 .75rem; font-size: 1.1rem; }
    .vk-editor-fields {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
        align-items: start;
    }
    .vk-editor fieldset { border: 1px solid rgba(255,255,255,.12); border-radius: 6px; margin: 0; padding: 10px 12px; }
    .vk-editor legend   { padding: 0 .35rem; font-weight: 700; color: #f0c878; }
    .vk-editor label    { display: block; margin: .35rem 0; font-size: .9rem; }
    .vk-editor input[type=text], .vk-editor input[type=email],
    .vk-editor textarea, .vk-editor select {
        width: 100%; box-sizing: border-box;
        background: rgba(0,0,0,.35); color: #f4ead8;
        border: 1px solid rgba(255,255,255,.15); border-radius: 4px;
        padding: 6px 8px; font: inherit;
    }
    .vk-editor input[type=range] { width: 100%; }

    /* ===== Karte ===== */
    .vk-card {
        --bg:#1a0a0f; --bg2:#2a0d14; --gold:#d4a85a; --gold-2:#f0c878;
        --text:#f4ead8; --muted:#c8b893;
        width: 85mm; height: 55mm;
        background: radial-gradient(ellipse at top left, var(--bg2) 0%, var(--bg) 75%);
        border: 1px solid rgba(212,168,90,.35);
        border-radius: 4mm;
        box-shadow: 0 6px 24px rgba(0,0,0,.45);
        color: var(--text);
        position: relative; overflow: hidden;
        padding: 4mm 5mm;
        display: grid;
        grid-template-columns: 1fr 22mm;
        grid-template-rows: auto 1fr auto;
        gap: 2mm 4mm;
        font-family: "Trebuchet MS","Segoe UI",system-ui,sans-serif;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
    }
    .vk-card::before {
        content:""; position:absolute; inset:2mm;
        border:.3mm solid rgba(212,168,90,.45); border-radius:2mm; pointer-events:none;
    }
    .vk-card .brand { grid-column: 1/3; grid-row: 1/2; display:flex; align-items:center; gap:3mm; }
    .vk-card .brand img {
        height: 22mm; width: 22mm; object-fit: contain;
        filter: drop-shadow(0 0 1mm rgba(0,0,0,.5));
    }
    .vk-card .brand .word { font-family:"Georgia","Trajan Pro",serif; font-size:9mm; letter-spacing:.25em; color:var(--gold-2); line-height:1; }
    .vk-card .brand .tag  { font-size:2.2mm; letter-spacing:.35em; color:var(--muted); text-transform:uppercase; margin-top:1mm; }

    .vk-card .info { grid-column:1/2; grid-row:2/4; align-self:end; font-size:2.7mm; line-height:1.45; }
    .vk-card .info .row { display:flex; gap:1.5mm; align-items:baseline; }
    .vk-card .info .lbl { color:var(--gold); min-width:10mm; font-weight:700; letter-spacing:.05em; text-transform:uppercase; font-size:2.2mm; }
    .vk-card .info .val { color:var(--text); }

    .vk-card .qrwrap { grid-column:2/3; grid-row:2/4; align-self:end; justify-self:end;
        background:#fff; padding:1.2mm; border-radius:1.5mm; box-shadow:0 0 0 .3mm rgba(212,168,90,.6); }
    .vk-card .qrwrap > div, .vk-card .qrwrap canvas, .vk-card .qrwrap img {
        display:block; width:20mm !important; height:20mm !important;
    }

    .vk-card.back { display:flex; flex-direction:column; align-items:center; justify-content:center;
        padding: 3mm; /* override base padding so wide letter-spaced text has room */
        text-align:center;
        background: radial-gradient(circle at center, rgba(212,168,90,.18) 0%, transparent 60%),
                    radial-gradient(ellipse at top left, var(--bg2) 0%, var(--bg) 75%); }
    .vk-card.back .word { font-family:"Georgia",serif; font-size:14mm; letter-spacing:.35em;
        padding-left:.35em; /* compensate for trailing letter-spacing so text is visually centred */
        color:var(--gold-2); text-shadow:0 0 3mm rgba(212,168,90,.35); }
    .vk-card.back .tag  { position:absolute; bottom:4mm; left:0; right:0; text-align:center;
        font-size:2.2mm; letter-spacing:.4em; color:var(--muted); text-transform:uppercase; }

    @page { size: A4; margin: 15mm; }
    @media print {
        html, body, .admin-main {
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .admin-header, .admin-footer, .vk-toolbar, .vk-editor,
        h1, h1 + p { display: none !important; }
        .vk-layout { display:block; }
        .vk-stage  { gap: 8mm; }
        .vk-card,
        .vk-card.back {
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }
        html[data-vk-print="front"] .vk-card.back {
            display: none !important;
        }
        html[data-vk-print="back"] #vkFront {
            display: none !important;
        }
    }
</style>

<script src="<?= e(url('assets/js/vendor/qrcode.min.js')) ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    var BASE = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;
    var STORAGE_KEY = 'inesco_vk_v1';
    var DEFAULTS = <?= json_encode([
        'logo'      => $defaults['logo'],
        'logoSize'  => 22,
        'name'      => $defaults['name'],
        'tag'       => $defaults['tagline'],
        'phone'     => $defaults['phone'],
        'email'     => $defaults['email'],
        'web'       => $defaults['web'],
        'qrText'    => '',
        'qrLevel'   => 'M',
        'backName'  => $defaults['name'],
        'backWeb'   => $defaults['web'],
        'bleed'     => 3,
    ], JSON_UNESCAPED_UNICODE) ?>;

    var $ = function (id) { return document.getElementById(id); };
    var f = {
        logo:$('fLogo'), logoSize:$('fLogoSize'),
        name:$('fName'), tag:$('fTag'),
        phone:$('fPhone'), email:$('fEmail'), web:$('fWeb'),
        qrText:$('fQr'), qrLevel:$('fQrLevel'),
        backName:$('fBackName'), backWeb:$('fBackWeb'),
        bleed:$('fBleed')
    };

    function load() {
        try { var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return Object.assign({}, DEFAULTS, JSON.parse(raw));
        } catch (e) {}
        return Object.assign({}, DEFAULTS);
    }
    function save(s) { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(s)); } catch (e) {} }

    function readState() {
        return {
            logo: f.logo.value.trim(),
            logoSize: parseInt(f.logoSize.value, 10) || 22,
            name: f.name.value, tag: f.tag.value,
            phone: f.phone.value, email: f.email.value, web: f.web.value,
            qrText: f.qrText.value, qrLevel: f.qrLevel.value,
            backName: f.backName.value, backWeb: f.backWeb.value,
            bleed: parseFloat(f.bleed.value) || 0
        };
    }
    function applyToFields(s) {
        f.logo.value=s.logo; f.logoSize.value=s.logoSize;
        f.name.value=s.name; f.tag.value=s.tag;
        f.phone.value=s.phone; f.email.value=s.email; f.web.value=s.web;
        f.qrText.value=s.qrText; f.qrLevel.value=s.qrLevel;
        f.backName.value=s.backName; f.backWeb.value=s.backWeb;
        f.bleed.value = (s.bleed !== undefined ? s.bleed : 3);
        $('oLogoSize').textContent = s.logoSize;
        $('oBleed').textContent = f.bleed.value;
    }
    function logoSrc(p) {
        if (!p) return BASE + 'assets/img/logo.png';
        if (/^(https?:|data:|\/)/.test(p)) return p;
        return BASE + p.replace(/^\.?\//, '');
    }
    function applyToCard(s) {
        $('vkLogo').src = logoSrc(s.logo);
        $('vkLogo').style.height = s.logoSize + 'mm';
        $('vkLogo').style.width  = s.logoSize + 'mm';
        $('vkName').textContent      = s.name;
        $('vkTag').textContent       = s.tag;
        $('vkPhone').textContent     = formatPhone(s.phone);
        $('vkEmail').textContent     = s.email;
        $('vkWeb').textContent       = s.web;
        $('vkBackName').textContent  = s.backName;
        $('vkBackWeb').textContent   = s.backWeb;
        $('oLogoSize').textContent   = s.logoSize;
    }
    function formatPhone(p) {
        // Behalte führendes +, entferne alles andere außer Ziffern
        var raw = (p || '').trim();
        var plus = raw.charAt(0) === '+' ? '+' : '';
        var d = raw.replace(/\D/g, '');
        if (!d) return raw;
        // Gruppierung: +CC CCC CC CC CC CC  (CC=Ländercode 2, dann 3, dann 2er-Gruppen)
        var parts = [];
        if (plus) {
            parts.push(d.slice(0, 2));            // Ländercode
            parts.push(d.slice(2, 5));            // Vorwahl
            var rest = d.slice(5);
            for (var i = 0; i < rest.length; i += 2) parts.push(rest.substr(i, 2));
        } else {
            // ohne +: einfach 2er-Gruppen
            for (var j = 0; j < d.length; j += 2) parts.push(d.substr(j, 2));
        }
        return (plus + parts.filter(Boolean).join(' ')).trim();
    }

    function buildVCard(s) {
        var telPretty = formatPhone(s.phone);                 // mit Leerzeichen, schöne Anzeige
        var telDial   = (s.phone || '').replace(/[^\d+]/g, ''); // ohne Leerzeichen, zum Anrufen
        var url = s.web && !/^https?:\/\//i.test(s.web) ? 'https://' + s.web : s.web;
        // Nur Nachname:  N:Familienname;Vorname;Mittel;Anrede;Suffix
        // Kein ORG, kein FN → viele Apps zeigen Kontakt sauber als reinen Nachnamen.
        return [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' + s.name + ';;;;',
            'FN:' + s.name,
            'TEL;TYPE=CELL,VOICE:' + telPretty,
            'item1.TEL;TYPE=VOICE:' + telDial,
            'EMAIL;TYPE=INTERNET:' + s.email,
            'URL:' + url,
            'END:VCARD'
        ].join('\r\n');
    }
    function levelConst(lv) { return QRCode.CorrectLevel[lv] || QRCode.CorrectLevel.M; }
    function renderQr(s) {
        var box = $('vkQr'); box.innerHTML = '';
        var text = (s.qrText && s.qrText.trim()) ? s.qrText : buildVCard(s);
        new QRCode(box, {
            text: text, width: 240, height: 240,
            colorDark:'#1a0a0f', colorLight:'#ffffff',
            correctLevel: levelConst(s.qrLevel)
        });
    }
    function renderAll(s) { applyToCard(s); renderQr(s); }

    function buildFilename(side) {
        var d = new Date();
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        var suffix = side === 'front' ? 'Vorderseite' : (side === 'back' ? 'Rueckseite' : 'Beide');
        return 'INESCO_Visitenkarte_' + suffix + '_' + yyyy + '-' + mm + '-' + dd + '.pdf';
    }

    function waitForImages(root) {
        var imgs = root.querySelectorAll('img');
        var jobs = [];
        for (var i = 0; i < imgs.length; i++) {
            (function (img) {
                if (img.complete && img.naturalWidth > 0) return;
                jobs.push(new Promise(function (resolve) {
                    img.addEventListener('load', resolve, { once: true });
                    img.addEventListener('error', resolve, { once: true });
                }));
            })(imgs[i]);
        }
        return Promise.all(jobs);
    }

    function nextPaint() {
        return new Promise(function (resolve) {
            requestAnimationFrame(function () {
                requestAnimationFrame(resolve);
            });
        });
    }

    var EXPORT_DPI = 300;
    var CARD_W_MM = 85;
    var CARD_H_MM = 55;
    var CARD_W_PX_300 = Math.round(CARD_W_MM * EXPORT_DPI / 25.4);
    var CARD_H_PX_300 = Math.round(CARD_H_MM * EXPORT_DPI / 25.4);

    async function captureCard(el) {
        await waitForImages(el);
        return await html2canvas(el, {
            // Capture at a higher source resolution before scaling to exact output size.
            scale: 4,
            useCORS: true,
            allowTaint: true,
            backgroundColor: null,
            logging: false
        });
    }

    async function renderCardAt300Dpi(el, bleedMm) {
        var source = await captureCard(el);
        var bleedPx = Math.max(0, Math.round((bleedMm || 0) * EXPORT_DPI / 25.4));
        var out = document.createElement('canvas');
        out.width = CARD_W_PX_300 + bleedPx * 2;
        out.height = CARD_H_PX_300 + bleedPx * 2;
        var ctx = out.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, out.width, out.height);
        ctx.drawImage(source, bleedPx, bleedPx, CARD_W_PX_300, CARD_H_PX_300);
        return out;
    }

    function buildJpegFilename(side) {
        var d = new Date();
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        var suffix = side === 'front' ? 'Vorderseite' : 'Rueckseite';
        return 'INESCO_Visitenkarte_' + suffix + '_' + yyyy + '-' + mm + '-' + dd + '.jpg';
    }

    async function downloadJpeg(side) {
        if (typeof html2canvas === 'undefined') {
            alert('html2canvas konnte nicht geladen werden. Bitte Seite neu laden.');
            return;
        }
        state = readState();
        renderAll(state);
        save(state);
        await nextPaint();

        var el = side === 'front' ? $('vkFront') : document.querySelector('.vk-card.back');
        if (!el) { alert('Karte konnte nicht gefunden werden.'); return; }

        // Temporarily strip the CSS border so the card edge is clean for print
        var prevBorder       = el.style.border;
        var prevBorderRadius = el.style.borderRadius;
        var prevBoxShadow    = el.style.boxShadow;
        el.style.border       = 'none';
        el.style.borderRadius = '0';
        el.style.boxShadow    = 'none';
        await nextPaint();

        try {
            var out = await renderCardAt300Dpi(el, state.bleed || 0);

            var link = document.createElement('a');
            link.download = buildJpegFilename(side);
            link.href = out.toDataURL('image/jpeg', 0.95);
            link.click();
        } catch (e) {
            console.error(e);
            alert('JPEG-Download fehlgeschlagen. Bitte erneut versuchen.');
        } finally {
            el.style.border       = prevBorder;
            el.style.borderRadius = prevBorderRadius;
            el.style.boxShadow    = prevBoxShadow;
        }
    }

    async function downloadPdf(side) {
        if (typeof html2canvas === 'undefined' || !window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF-Bibliothek konnte nicht geladen werden. Bitte Seite neu laden.');
            return;
        }

        state = readState();
        renderAll(state);
        save(state);
        await nextPaint();

        var front = $('vkFront');
        var back = document.querySelector('.vk-card.back');
        if (!front || !back) {
            alert('Karte konnte nicht gefunden werden.');
            return;
        }

        try {
            var jsPDF = window.jspdf.jsPDF;
            var pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            var cardW = CARD_W_MM;
            var cardH = CARD_H_MM;
            var topY = 15;
            var singleX = 15;
            var gap = 8;
            var bothX = (210 - (cardW * 2 + gap)) / 2;

            if (side === 'front') {
                var frontCanvas = await renderCardAt300Dpi(front, 0);
                pdf.addImage(frontCanvas.toDataURL('image/png'), 'PNG', singleX, topY, cardW, cardH);
            } else if (side === 'back') {
                var backCanvas = await renderCardAt300Dpi(back, 0);
                pdf.addImage(backCanvas.toDataURL('image/png'), 'PNG', singleX, topY, cardW, cardH);
            } else {
                var frontBoth = await renderCardAt300Dpi(front, 0);
                var backBoth = await renderCardAt300Dpi(back, 0);
                pdf.addImage(frontBoth.toDataURL('image/png'), 'PNG', bothX, topY, cardW, cardH);
                pdf.addImage(backBoth.toDataURL('image/png'), 'PNG', bothX + cardW + gap, topY, cardW, cardH);
            }

            pdf.save(buildFilename(side));
        } catch (e) {
            alert('PDF-Download fehlgeschlagen. Bitte erneut versuchen.');
        }
    }

    // Init
    var state = load();
    applyToFields(state);
    renderAll(state);

    // Live updates: bei jeder Änderung Karte UND QR neu rendern
    Object.keys(f).forEach(function (k) {
        var ev = (f[k].tagName === 'SELECT') ? 'change' : 'input';
        f[k].addEventListener(ev, function () {
            state = readState();
            applyToCard(state);
            renderQr(state);
            save(state);
            if (k === 'bleed') $('oBleed').textContent = state.bleed;
        });
    });

    $('vkRefresh').addEventListener('click', function () {
        state = readState(); renderAll(state); save(state);
    });
    $('vkReset').addEventListener('click', function () {
        if (!confirm('Alle Felder auf Standardwerte zurücksetzen?')) return;
        state = Object.assign({}, DEFAULTS);
        applyToFields(state); renderAll(state); save(state);
    });

    $('vkPrintAll').addEventListener('click', function () {
        downloadPdf('both');
    });
    $('vkJpegFront').addEventListener('click', function () {
        downloadJpeg('front');
    });
    $('vkJpegBack').addEventListener('click', function () {
        downloadJpeg('back');
    });

    // Lokales Logo-Upload → DataURL
    $('fLogoFile').addEventListener('change', function (e) {
        var file = e.target.files && e.target.files[0]; if (!file) return;
        var r = new FileReader();
        r.onload = function () {
            f.logo.value = r.result;
            state = readState(); applyToCard(state); save(state);
        };
        r.readAsDataURL(file);
    });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
