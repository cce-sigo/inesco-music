<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Plakat';

$defaults = [
    'weekday'          => 'Freitag',
    'date'             => '10. April',
    'time'             => '19:30 Uhr',
    'venue_line1'      => 'im Cafe Amthof',
    'venue_line2'      => 'Amthofgasse 5 Feldkirchen',
    'venue_logo'       => '',
    'partner_logo'     => '',
    'partner_name'     => '',
    'partner_subtitle' => '',
    'event_subtitle'   => '',
    // Layout & Schrift – Kreis
    'circle_top'        => '33',
    'circle_left'       => '17',
    'weekday_size'      => '2.5',
    'date_size'         => '2.8',
    'time_size'         => '2.5',
    'circle_font'       => 'Arial, Helvetica, sans-serif',
    // Layout & Schrift – Veranstaltungsort
    'venue_top'         => '48',
    'venue_right'       => '3',
    'venue_l1_size'     => '1.9',
    'venue_l2_size'     => '1.7',
    'venue_font'        => 'Arial, Helvetica, sans-serif',
    // Layout & Schrift – Partner
    'partner_top'       => '13',
    'partner_left'      => '2',
    'partner_name_size' => '1.8',
    'partner_sub_size'  => '1.6',
    'partner_font'      => 'Arial, Helvetica, sans-serif',
    // Layout & Schrift – Event-Untertitel
    'eventsub_top'      => '20',
    'eventsub_left'     => '30',
    'eventsub_size'     => '1.7',
    'eventsub_font'     => 'Arial, Helvetica, sans-serif',
    // Partner-Logo (eigenständig)
    'partner_logo_top'   => '5',
    'partner_logo_left'  => '2',
    'partner_logo_width' => '20',
    // Venue-Logo (eigenständig)
    'venue_logo_top'     => '70',
    'venue_logo_right'   => '3',
    'venue_logo_width'   => '15',
];

$saved = read_json('plakat', $defaults);
$data  = array_merge($defaults, $saved);

/* ---------- POST: Speichern ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/plakat.php'));
    }
    $new = [];
    foreach (array_keys($defaults) as $k) {
        $new[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if (write_json('plakat', $new)) {
        flash_set('ok', 'Plakat gespeichert.');
    } else {
        flash_set('err', 'Fehler beim Speichern.');
    }
    redirect(url('admin/plakat.php'));
}

include __DIR__ . '/header.php';
?>
<div class="pk-topbar">
    <h1>Plakat</h1>
    <span class="muted">Live-Vorschau – Felder rechts ausfüllen, speichern &amp; als PDF exportieren.</span>
    <div class="pk-topbar-right">
        <label class="pk-zoom-ctrl">
            <span id="pkZoomLabel">100%</span>
            <input type="range" id="pkZoomSlider" min="30" max="200" step="2" value="100">
        </label>
        <a class="btn" href="<?= e(url('plakat.php')) ?>" target="_blank">Drucken&nbsp;/ PDF</a>
    </div>
</div>

<div class="pk-layout">

    <!-- ===== LINKS: Vorschau ===== -->
    <div class="pk-preview-wrap" id="pkPreviewWrap">

        <!-- Plakat-Canvas -->
        <div id="pkScaler">
        <div class="pk-canvas" id="pkCanvas">
            <img class="pk-bg" src="<?= e(url('images/Plakat-INESCO_neutral.png')) ?>" alt="Plakat Hintergrund">

            <!-- Partner-Logo (eigenständig positioniert) -->
            <div class="pk-overlay" id="pkPartnerLogoWrap"
                 style="top:<?= e($data['partner_logo_top']) ?>%;left:<?= e($data['partner_logo_left']) ?>%;width:<?= e($data['partner_logo_width']) ?>%">
                <img class="pk-img-free" id="pkPartnerLogo"
                     src="<?= e($data['partner_logo'] ? url($data['partner_logo']) : '') ?>"
                     alt="Partner Logo"
                     style="<?= $data['partner_logo'] ? '' : 'display:none' ?>">
            </div>

            <!-- Partner-Text -->
            <div class="pk-overlay pk-partner" id="pkPartnerArea"
                 style="top:<?= e($data['partner_top']) ?>%;left:<?= e($data['partner_left']) ?>%;font-family:<?= e($data['partner_font']) ?>">
                <div class="pk-partner-text">
                    <span class="pk-partner-name" id="pkPartnerName" style="font-size:<?= e($data['partner_name_size']) ?>cqw"><?= e($data['partner_name']) ?></span>
                    <span class="pk-partner-sub"  id="pkPartnerSub"  style="font-size:<?= e($data['partner_sub_size']) ?>cqw"><?= e($data['partner_subtitle']) ?></span>
                </div>
            </div>

            <!-- Event-Untertitel (oben mitte) -->
            <div class="pk-overlay pk-eventsub" id="pkEventSub"
                 style="top:<?= e($data['eventsub_top']) ?>%;left:<?= e($data['eventsub_left']) ?>%;font-size:<?= e($data['eventsub_size']) ?>cqw;font-family:<?= e($data['eventsub_font']) ?>"><?= e($data['event_subtitle']) ?></div>

            <!-- Weißer Kreis: Datum/Uhrzeit -->
            <div class="pk-overlay pk-circle" id="pkCircle"
                 style="top:<?= e($data['circle_top']) ?>%;left:<?= e($data['circle_left']) ?>%;font-family:<?= e($data['circle_font']) ?>">
                <span class="pk-weekday" id="pkWeekday" style="font-size:<?= e($data['weekday_size']) ?>cqw"><?= e($data['weekday']) ?></span>
                <span class="pk-date"    id="pkDate"    style="font-size:<?= e($data['date_size']) ?>cqw"><?= e($data['date']) ?></span>
                <span class="pk-time"    id="pkTime"    style="font-size:<?= e($data['time_size']) ?>cqw"><?= e($data['time']) ?></span>
            </div>

            <!-- Venue-Info (rechts unter LIVE KONZERT) -->
            <div class="pk-overlay pk-venue" id="pkVenue"
                 style="top:<?= e($data['venue_top']) ?>%;right:<?= e($data['venue_right']) ?>%;font-family:<?= e($data['venue_font']) ?>">
                <span class="pk-venue-l1" id="pkVenueL1" style="font-size:<?= e($data['venue_l1_size']) ?>cqw"><?= e($data['venue_line1']) ?></span>
                <span class="pk-venue-l2" id="pkVenueL2" style="font-size:<?= e($data['venue_l2_size']) ?>cqw"><?= e($data['venue_line2']) ?></span>
            </div>

            <!-- Venue-Logo (eigenständig positioniert) -->
            <div class="pk-overlay" id="pkVenueLogoWrap"
                 style="top:<?= e($data['venue_logo_top']) ?>%;right:<?= e($data['venue_logo_right']) ?>%;width:<?= e($data['venue_logo_width']) ?>%">
                <img class="pk-img-free" id="pkVenueLogo"
                     src="<?= e($data['venue_logo'] ? url($data['venue_logo']) : '') ?>"
                     alt="Location Logo"
                     style="<?= $data['venue_logo'] ? '' : 'display:none' ?>">
            </div>
        </div><!-- /pk-canvas -->
        </div><!-- /pkScaler -->
    </div>

    <!-- ===== RECHTS: Editor ===== -->
    <aside class="pk-editor" aria-label="Plakat bearbeiten">
        <form method="post" action="<?= e(url('admin/plakat.php')) ?>" id="pkForm">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <?php $fonts = ['Arial, Helvetica, sans-serif' => 'Arial', 'Impact, sans-serif' => 'Impact', 'Georgia, serif' => 'Georgia', 'Verdana, sans-serif' => 'Verdana']; ?>

            <h2>Bearbeiten</h2>

            <div class="pk-editor-cards">

            <!-- ── Datum & Uhrzeit ── -->
            <fieldset>
                <legend>Datum &amp; Uhrzeit</legend>
                <div class="pk-field-row">
                    <label class="pk-field-main">Wochentag
                        <input type="text" name="weekday" id="fWeekday"
                               value="<?= e($data['weekday']) ?>" maxlength="20" placeholder="z.B. Freitag">
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="weekday_size" id="fWeekdaySize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['weekday_size']) ?>">
                    </label>
                </div>
                <div class="pk-field-row">
                    <label class="pk-field-main">Datum
                        <input type="text" name="date" id="fDate"
                               value="<?= e($data['date']) ?>" maxlength="30" placeholder="z.B. 10. April">
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="date_size" id="fDateSize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['date_size']) ?>">
                    </label>
                </div>
                <div class="pk-field-row">
                    <label class="pk-field-main">Uhrzeit
                        <input type="text" name="time" id="fTime"
                               value="<?= e($data['time']) ?>" maxlength="20" placeholder="z.B. 19:30 Uhr">
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="time_size" id="fTimeSize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['time_size']) ?>">
                    </label>
                </div>
                <label>Schriftart
                    <select name="circle_font" id="fCircleFont">
                        <?php foreach ($fonts as $fv => $fl): ?>
                        <option value="<?= e($fv) ?>"<?= $data['circle_font'] === $fv ? ' selected' : '' ?>><?= e($fl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Position oben (%)
                        <input type="number" name="circle_top" id="fCircleTop"
                               step="0.5" min="0" max="100" value="<?= e($data['circle_top']) ?>">
                    </label>
                    <label class="pk-field-half">Position links (%)
                        <input type="number" name="circle_left" id="fCircleLeft"
                               step="0.5" min="0" max="100" value="<?= e($data['circle_left']) ?>">
                    </label>
                </div>
            </fieldset>

            <!-- ── Veranstaltungsort ── -->
            <fieldset>
                <legend>Veranstaltungsort</legend>
                <div class="pk-field-row">
                    <label class="pk-field-main">Zeile 1
                        <input type="text" name="venue_line1" id="fVenueL1"
                               value="<?= e($data['venue_line1']) ?>" maxlength="60" placeholder="z.B. im Cafe Amthof">
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="venue_l1_size" id="fVenueL1Size"
                               step="0.1" min="0.5" max="12" value="<?= e($data['venue_l1_size']) ?>">
                    </label>
                </div>
                <div class="pk-field-row">
                    <label class="pk-field-main">Zeile 2
                        <input type="text" name="venue_line2" id="fVenueL2"
                               value="<?= e($data['venue_line2']) ?>" maxlength="60" placeholder="z.B. Amthofgasse 5">
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="venue_l2_size" id="fVenueL2Size"
                               step="0.1" min="0.5" max="12" value="<?= e($data['venue_l2_size']) ?>">
                    </label>
                </div>
                <label>Logo
                    <input type="text" name="venue_logo" id="fVenueLogo"
                           value="<?= e($data['venue_logo']) ?>" placeholder="z.B. images/Amthof.png">
                </label>
                <label>Logo hochladen
                    <input type="file" id="fVenueLogoFile" accept="image/*">
                </label>
                <label>Logo-Breite (%)
                    <input type="number" name="venue_logo_width" id="fVenueLogoWidth"
                           step="0.5" min="1" max="100" value="<?= e($data['venue_logo_width']) ?>">
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Logo oben (%)
                        <input type="number" name="venue_logo_top" id="fVenueLogoTop"
                               step="0.5" min="0" max="100" value="<?= e($data['venue_logo_top']) ?>">
                    </label>
                    <label class="pk-field-half">Logo rechts (%)
                        <input type="number" name="venue_logo_right" id="fVenueLogoRight"
                               step="0.5" min="0" max="100" value="<?= e($data['venue_logo_right']) ?>">
                    </label>
                </div>
                <label>Schriftart
                    <select name="venue_font" id="fVenueFont">
                        <?php foreach ($fonts as $fv => $fl): ?>
                        <option value="<?= e($fv) ?>"<?= $data['venue_font'] === $fv ? ' selected' : '' ?>><?= e($fl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Position oben (%)
                        <input type="number" name="venue_top" id="fVenueTop"
                               step="0.5" min="0" max="100" value="<?= e($data['venue_top']) ?>">
                    </label>
                    <label class="pk-field-half">Position rechts (%)
                        <input type="number" name="venue_right" id="fVenueRight"
                               step="0.5" min="0" max="100" value="<?= e($data['venue_right']) ?>">
                    </label>
                </div>
            </fieldset>

            <!-- ── Partner / Sponsor ── -->
            <fieldset>
                <legend>Partner / Sponsor (optional)</legend>
                <label>Logo
                    <input type="text" name="partner_logo" id="fPartnerLogo"
                           value="<?= e($data['partner_logo']) ?>" placeholder="z.B. images/Bellantik.png">
                </label>
                <label>Logo hochladen
                    <input type="file" id="fPartnerLogoFile" accept="image/*">
                </label>
                <label>Logo-Breite (%)
                    <input type="number" name="partner_logo_width" id="fPartnerLogoWidth"
                           step="0.5" min="1" max="100" value="<?= e($data['partner_logo_width']) ?>">
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Logo oben (%)
                        <input type="number" name="partner_logo_top" id="fPartnerLogoTop"
                               step="0.5" min="0" max="100" value="<?= e($data['partner_logo_top']) ?>">
                    </label>
                    <label class="pk-field-half">Logo links (%)
                        <input type="number" name="partner_logo_left" id="fPartnerLogoLeft"
                               step="0.5" min="0" max="100" value="<?= e($data['partner_logo_left']) ?>">
                    </label>
                </div>
                <div class="pk-field-row">
                    <label class="pk-field-main">Name
                        <input type="text" name="partner_name" id="fPartnerName"
                               value="<?= e($data['partner_name']) ?>" maxlength="60" placeholder='z.B. "Barbacoa BBQ"'>
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="partner_name_size" id="fPartnerNameSize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['partner_name_size']) ?>">
                    </label>
                </div>
                <div class="pk-field-row">
                    <label class="pk-field-main">Untertitel
                        <input type="text" name="partner_subtitle" id="fPartnerSub"
                               value="<?= e($data['partner_subtitle']) ?>" maxlength="80" placeholder='z.B. "Grillerei um 16:30"'>
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="partner_sub_size" id="fPartnerSubSize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['partner_sub_size']) ?>">
                    </label>
                </div>
                <label>Schriftart
                    <select name="partner_font" id="fPartnerFont">
                        <?php foreach ($fonts as $fv => $fl): ?>
                        <option value="<?= e($fv) ?>"<?= $data['partner_font'] === $fv ? ' selected' : '' ?>><?= e($fl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Position oben (%)
                        <input type="number" name="partner_top" id="fPartnerTop"
                               step="0.5" min="0" max="100" value="<?= e($data['partner_top']) ?>">
                    </label>
                    <label class="pk-field-half">Position links (%)
                        <input type="number" name="partner_left" id="fPartnerLeft"
                               step="0.5" min="0" max="100" value="<?= e($data['partner_left']) ?>">
                    </label>
                </div>
            </fieldset>

            <!-- ── Event-Untertitel ── -->
            <fieldset>
                <legend>Event-Untertitel (optional)</legend>
                <div class="pk-field-row">
                    <label class="pk-field-main">Text
                        <input type="text" name="event_subtitle" id="fEventSub"
                               value="<?= e($data['event_subtitle']) ?>" maxlength="80" placeholder='z.B. "Frühlingswecken"'>
                    </label>
                    <label class="pk-field-size">Größe
                        <input type="number" name="eventsub_size" id="fEventSubSize"
                               step="0.1" min="0.5" max="12" value="<?= e($data['eventsub_size']) ?>">
                    </label>
                </div>
                <label>Schriftart
                    <select name="eventsub_font" id="fEventSubFont">
                        <?php foreach ($fonts as $fv => $fl): ?>
                        <option value="<?= e($fv) ?>"<?= $data['eventsub_font'] === $fv ? ' selected' : '' ?>><?= e($fl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pk-field-row">
                    <label class="pk-field-half">Position oben (%)
                        <input type="number" name="eventsub_top" id="fEventSubTop"
                               step="0.5" min="0" max="100" value="<?= e($data['eventsub_top']) ?>">
                    </label>
                    <label class="pk-field-half">Position links (%)
                        <input type="number" name="eventsub_left" id="fEventSubLeft"
                               step="0.5" min="0" max="100" value="<?= e($data['eventsub_left']) ?>">
                    </label>
                </div>
            </fieldset>

            </div><!-- /pk-editor-cards -->

            <button type="submit" class="btn">Speichern</button>
        </form>
    </aside>
</div>

<style>
/* ===== Full-width override for plakat page ===== */
.admin-main {
    max-width: none !important;
    width: 100% !important;
    padding-left: 2vw !important;
    padding-right: 2vw !important;
}

/* ===== Compact topbar ===== */
.pk-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.pk-topbar h1 {
    margin: 0;
    font-size: 1.2rem;
    flex-shrink: 0;
    line-height: 1;
}
.pk-topbar .muted {
    font-size: .82rem;
    flex: 1;
    min-width: 0;
}
.pk-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    margin-left: auto;
}
.pk-zoom-ctrl {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .82rem;
    white-space: nowrap;
    cursor: default;
    user-select: none;
}
.pk-zoom-ctrl input[type=range] {
    width: 90px;
    cursor: pointer;
    accent-color: #d4a544;
}

/* ===== Layout ===== */
.pk-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(360px, 42%);
    gap: 24px;
    align-items: start;
}
.pk-preview-wrap { min-width: 0; overflow: auto; }
#pkScaler { width: 100%; }

/* Editor: sticky, container for card-grid queries */
.pk-editor {
    position: sticky;
    top: 16px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    background: rgba(0,0,0,.25);
    border: 1px solid rgba(212,168,90,.25);
    border-radius: 8px;
    padding: 14px 16px;
    container-type: inline-size;
    container-name: pk-editor;
}
.pk-editor > form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pk-editor h2 { margin: 0 0 .5rem; font-size: 1.1rem; }

/* Cards: 1 col default, grow with editor width via container queries */
.pk-editor-cards {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    align-items: start;
}
@container pk-editor (min-width: 380px) {
    .pk-editor-cards { grid-template-columns: 1fr 1fr; }
}
@container pk-editor (min-width: 620px) {
    .pk-editor-cards { grid-template-columns: 1fr 1fr 1fr; }
}
@container pk-editor (min-width: 860px) {
    .pk-editor-cards { grid-template-columns: repeat(4, 1fr); }
}

.pk-editor fieldset {
    border: 1px solid rgba(212,168,90,.3);
    border-radius: 8px;
    padding: 12px 14px;
    background: rgba(0,0,0,.35);
    margin: 0;
}
.pk-editor legend { padding: 0 .35rem; font-weight: 700; color: #f0c878; font-size: .95rem; }
.pk-editor label { display: block; margin: .35rem 0; font-size: .85rem; }
/* pk-field-row: text input + size input side by side */
.pk-field-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 6px;
    align-items: end;
    margin: .35rem 0;
}
.pk-field-row .pk-field-main,
.pk-field-row .pk-field-half { margin: 0; }
.pk-field-size { width: 62px; flex-shrink: 0; }
/* rows with two equal halves (position pairs) */
.pk-field-row:has(.pk-field-half) { grid-template-columns: 1fr 1fr; }

.pk-editor input[type=text], .pk-editor input[type=email],
.pk-editor input[type=number], .pk-editor textarea, .pk-editor select {
    width: 100%; box-sizing: border-box;
    background: rgba(0,0,0,.35); color: #f4ead8;
    border: 1px solid rgba(255,255,255,.15); border-radius: 4px;
    padding: 6px 8px; font: inherit;
}
.pk-editor .btn { align-self: flex-start; }

@media (max-width: 1000px) {
    .pk-layout { grid-template-columns: 1fr; }
    .pk-editor { position: static; max-height: none; }
}

/* ===== Plakat-Canvas ===== */
.pk-canvas {
    position: relative;
    display: block;
    width: 100%;
    max-width: 100%;
    font-family: Arial, sans-serif;
    container-type: inline-size;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.pk-bg {
    display: block;
    width: 100%;
    height: auto;
}

/* Alle Overlays absolut zum Canvas */
.pk-overlay { position: absolute; }

/* --- Partner-Bereich: oben links --- */
.pk-partner {
    top: 13%; left: 2%;
    width: 28%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2%;
    text-align: center;
}
.pk-partner-logo {
    max-width: 80%;
    max-height: 12vw;
    object-fit: contain;
}
.pk-img-free {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}
.pk-partner-text { display: flex; flex-direction: column; align-items: center; gap: 1%; }
.pk-partner-name {
    font-weight: 700;
    font-size: 1.8cqw;
    color: #1a1a1a;
    line-height: 1.2;
    white-space: pre-wrap;
    text-align: center;
}
.pk-partner-sub {
    font-size: 1.6cqw;
    color: #1a1a1a;
    white-space: pre-wrap;
    text-align: center;
}

/* --- Event-Untertitel: oben mitte --- */
.pk-eventsub {
    top: 20%;
    left: 30%;
    width: 38%;
    text-align: center;
    font-size: 1.7cqw;
    font-weight: 600;
    color: #1a1a1a;
}

/* --- Weißer Kreis: Datum/Zeit --- */
.pk-circle {
    top: 33%;
    left: 17%;
    width: 28%;
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #1a1a1a;
}
.pk-weekday { font-size: 2.5cqw; font-weight: 700; line-height: 1.3; }
.pk-date    { font-size: 2.8cqw; font-weight: 900; line-height: 1.2; }
.pk-time    { font-size: 2.5cqw; font-weight: 700; line-height: 1.3; }

/* --- Venue-Info: rechts --- */
.pk-venue {
    top: 48%;
    right: 3%;
    width: 36%;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 2%;
}
.pk-venue-l1 {
    display: block;
    font-size: 1.9cqw;
    color: #fff;
    font-weight: 600;
    line-height: 1.3;
}
.pk-venue-l2 {
    display: block;
    font-size: 1.7cqw;
    color: #fff;
    line-height: 1.3;
}
.pk-venue-logo {
    margin-top: 2%;
    max-width: 45%;
    max-height: 10vw;
    object-fit: contain;
}

/* cqw fallback for older browsers */
@supports not (font-size: 1cqw) {
    .pk-canvas { container-type: inline-size; }
    .pk-partner-name, .pk-partner-sub, .pk-eventsub,
    .pk-weekday, .pk-date, .pk-time,
    .pk-venue-l1, .pk-venue-l2 { font-size: 1.6%; }
    .pk-weekday { font-size: 2%; }
    .pk-date    { font-size: 2.2%; }
}

/* Print */
@page { size: A3 portrait; margin: 0; }
@media print {
    html, body, .admin-main { background: #fff !important; }
    .admin-header, .admin-footer, .pk-topbar, .pk-editor { display: none !important; }
    .pk-layout { display: block; }
    .pk-preview-wrap { overflow: visible; }
    #pkScaler { width: 100% !important; }
    .pk-bg { max-width: 100%; width: 100%; }
}
</style>

<script>
(function () {
    var BASE = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;

    function imgSrc(p) {
        if (!p) return '';
        if (/^(https?:|data:|\/)/.test(p)) return p;
        return BASE + p.replace(/^\.?\//, '');
    }

    function setImg(el, src) {
        if (!src) { el.style.display = 'none'; el.src = ''; return; }
        el.src = src;
        el.style.display = '';
    }

    var map = [
        { field: 'fWeekday',   el: 'pkWeekday',   attr: null },
        { field: 'fDate',      el: 'pkDate',       attr: null },
        { field: 'fTime',      el: 'pkTime',       attr: null },
        { field: 'fVenueL1',   el: 'pkVenueL1',    attr: null },
        { field: 'fVenueL2',   el: 'pkVenueL2',    attr: null },
        { field: 'fPartnerName', el: 'pkPartnerName', attr: null },
        { field: 'fPartnerSub',  el: 'pkPartnerSub',  attr: null },
        { field: 'fEventSub',    el: 'pkEventSub',    attr: null },
        { field: 'fVenueLogo',   el: 'pkVenueLogo',   attr: 'img' },
        { field: 'fPartnerLogo', el: 'pkPartnerLogo', attr: 'img' },
    ];

    map.forEach(function (m) {
        var f = document.getElementById(m.field);
        var e = document.getElementById(m.el);
        if (!f || !e) return;
        f.addEventListener('input', function () {
            if (m.attr === 'img') {
                setImg(e, imgSrc(f.value));
            } else {
                e.textContent = f.value;
            }
        });
    });

    /* Position, font-size & font-family live updates */
    var styleMap = [
        { field: 'fCircleTop',    el: 'pkCircle',  prop: 'top',        unit: '%'   },
        { field: 'fCircleLeft',   el: 'pkCircle',  prop: 'left',       unit: '%'   },
        { field: 'fCircleFont',   el: 'pkCircle',  prop: 'fontFamily', unit: ''    },
        { field: 'fWeekdaySize',  el: 'pkWeekday', prop: 'fontSize',   unit: 'cqw' },
        { field: 'fDateSize',     el: 'pkDate',    prop: 'fontSize',   unit: 'cqw' },
        { field: 'fTimeSize',     el: 'pkTime',    prop: 'fontSize',   unit: 'cqw' },
        { field: 'fVenueTop',     el: 'pkVenue',   prop: 'top',        unit: '%'   },
        { field: 'fVenueRight',   el: 'pkVenue',   prop: 'right',      unit: '%'   },
        { field: 'fVenueFont',    el: 'pkVenue',   prop: 'fontFamily', unit: ''    },
        { field: 'fVenueL1Size',     el: 'pkVenueL1',     prop: 'fontSize',   unit: 'cqw' },
        { field: 'fVenueL2Size',     el: 'pkVenueL2',     prop: 'fontSize',   unit: 'cqw' },
        { field: 'fPartnerTop',      el: 'pkPartnerArea', prop: 'top',        unit: '%'   },
        { field: 'fPartnerLeft',     el: 'pkPartnerArea', prop: 'left',       unit: '%'   },
        { field: 'fPartnerFont',     el: 'pkPartnerArea', prop: 'fontFamily', unit: ''    },
        { field: 'fPartnerNameSize', el: 'pkPartnerName', prop: 'fontSize',   unit: 'cqw' },
        { field: 'fPartnerSubSize',  el: 'pkPartnerSub',  prop: 'fontSize',   unit: 'cqw' },
        { field: 'fEventSubTop',      el: 'pkEventSub',       prop: 'top',        unit: '%'   },
        { field: 'fEventSubLeft',     el: 'pkEventSub',       prop: 'left',       unit: '%'   },
        { field: 'fEventSubSize',     el: 'pkEventSub',       prop: 'fontSize',   unit: 'cqw' },
        { field: 'fEventSubFont',     el: 'pkEventSub',       prop: 'fontFamily', unit: ''    },
        { field: 'fPartnerLogoTop',   el: 'pkPartnerLogoWrap', prop: 'top',   unit: '%' },
        { field: 'fPartnerLogoLeft',  el: 'pkPartnerLogoWrap', prop: 'left',  unit: '%' },
        { field: 'fPartnerLogoWidth', el: 'pkPartnerLogoWrap', prop: 'width', unit: '%' },
        { field: 'fVenueLogoTop',     el: 'pkVenueLogoWrap',   prop: 'top',   unit: '%' },
        { field: 'fVenueLogoRight',   el: 'pkVenueLogoWrap',   prop: 'right', unit: '%' },
        { field: 'fVenueLogoWidth',   el: 'pkVenueLogoWrap',   prop: 'width', unit: '%' },
    ];
    styleMap.forEach(function (m) {
        var f = document.getElementById(m.field);
        var e = document.getElementById(m.el);
        if (!f || !e) return;
        ['input', 'change'].forEach(function (evt) {
            f.addEventListener(evt, function () {
                e.style[m.prop] = f.value + m.unit;
            });
        });
    });

    /* File-Upload → DataURL Vorschau */
    function bindUpload(fileId, pathId, imgElId) {
        var fi = document.getElementById(fileId);
        var pi = document.getElementById(pathId);
        var img = document.getElementById(imgElId);
        if (!fi) return;
        fi.addEventListener('change', function (ev) {
            var file = ev.target.files && ev.target.files[0];
            if (!file) return;
            var r = new FileReader();
            r.onload = function () {
                if (pi) pi.value = r.result;
                setImg(img, r.result);
            };
            r.readAsDataURL(file);
        });
    }
    bindUpload('fVenueLogoFile',   'fVenueLogo',   'pkVenueLogo');
    bindUpload('fPartnerLogoFile', 'fPartnerLogo', 'pkPartnerLogo');

    /* Zoom slider */
    var zoomSlider = document.getElementById('pkZoomSlider');
    var zoomLabel  = document.getElementById('pkZoomLabel');
    var scaler     = document.getElementById('pkScaler');
    if (zoomSlider && scaler) {
        zoomSlider.addEventListener('input', function () {
            scaler.style.width = zoomSlider.value + '%';
            if (zoomLabel) zoomLabel.textContent = zoomSlider.value + '%';
        });
    }

    /* cqw polyfill: use container-type on canvas */
    var canvas = document.getElementById('pkCanvas');
    if (canvas && CSS && CSS.supports && !CSS.supports('font-size','1cqw')) {
        canvas.style.containerType = 'inline-size';
    }
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
