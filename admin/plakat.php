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
    'weekday_weight'    => 'bold',
    'weekday_visible'   => '1',
    'date_size'         => '2.8',
    'date_weight'       => 'bold',
    'date_visible'      => '1',
    'time_size'         => '2.5',
    'time_weight'       => 'bold',
    'time_visible'      => '1',
    'circle_font'       => 'Arial, Helvetica, sans-serif',
    'circle_align'      => 'center',
    // Layout & Schrift – Veranstaltungsort
    'venue_top'         => '48',
    'venue_right'       => '3',
    'venue_l1_size'     => '1.9',
    'venue_l1_weight'   => 'bold',
    'venue_l1_visible'  => '1',
    'venue_l2_size'     => '1.7',
    'venue_l2_weight'   => 'normal',
    'venue_l2_visible'  => '1',
    'venue_font'        => 'Arial, Helvetica, sans-serif',
    'venue_align'       => 'center',
    // Layout & Schrift – Partner
    'partner_top'       => '13',
    'partner_left'      => '2',
    'partner_name_size' => '1.8',
    'partner_name_weight' => 'bold',
    'partner_name_visible' => '1',
    'partner_sub_size'  => '1.6',
    'partner_sub_weight' => 'normal',
    'partner_sub_visible' => '1',
    'partner_font'      => 'Arial, Helvetica, sans-serif',
    'partner_align'     => 'center',
    // Layout & Schrift – Event-Untertitel
    'eventsub_top'      => '20',
    'eventsub_left'     => '30',
    'eventsub_size'     => '1.7',
    'eventsub_weight'   => 'bold',
    'eventsub_visible'  => '1',
    'eventsub_font'     => 'Arial, Helvetica, sans-serif',
    'eventsub_align'    => 'center',
    // Partner-Logo (eigenständig)
    'partner_logo_top'   => '5',
    'partner_logo_left'  => '2',
    'partner_logo_width' => '20',
    'partner_logo_visible' => '1',
    // Venue-Logo (eigenständig)
    'venue_logo_top'     => '70',
    'venue_logo_right'   => '3',
    'venue_logo_width'   => '15',
    'venue_logo_visible' => '1',
    // Text-Overlay-Breiten
    'circle_width'   => '28',
    'venue_width'    => '36',
    'partner_width'  => '28',
    'eventsub_width' => '38',
];

$saved = read_json('plakat', $defaults);
$data  = array_merge($defaults, $saved);

function collect_image_choices(array $relativeDirs): array {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
    $items = [];

    foreach ($relativeDirs as $dir) {
        $abs = realpath(__DIR__ . '/../' . $dir);
        if (!$abs || !is_dir($abs)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $ext = strtolower((string)pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }

            $full = str_replace('\\', '/', $fileInfo->getPathname());
            $base = str_replace('\\', '/', rtrim($abs, '\\/'));
            $rel = ltrim(substr($full, strlen($base)), '/');
            $items[] = rtrim($dir, '/\\') . '/' . $rel;
        }
    }

    $items = array_values(array_unique($items));
    sort($items, SORT_NATURAL | SORT_FLAG_CASE);
    return $items;
}

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

$imageChoices = collect_image_choices(['assets/img/uploadlogos']);

include __DIR__ . '/header.php';
?>
<div class="pk-topbar">
    <h1>Plakat</h1>
    <span class="muted">Live-Vorschau – Felder rechts ausfüllen, speichern &amp; als PDF exportieren.</span>
    <div class="pk-topbar-right">
        <label class="pk-zoom-ctrl">
            <span id="pkZoomLabel">100%</span>
            <input type="range" id="pkZoomSlider" min="30" max="200" step="10" value="100">
        </label>
        <button type="submit" class="btn" form="pkForm">Speichern</button>
        <a class="btn" id="pkPrintLink" href="<?= e(url('admin/plakat-print.php')) ?>" target="_blank">Drucken&nbsp;/ PDF</a>
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
            <div class="pk-overlay pk-logo-overlay<?= $data['partner_logo_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkPartnerLogoWrap"
                 style="top:<?= e($data['partner_logo_top']) ?>%;left:<?= e($data['partner_logo_left']) ?>%;width:<?= e($data['partner_logo_width']) ?>%">
                <img class="pk-img-free" id="pkPartnerLogo"
                     src="<?= e($data['partner_logo'] ? url($data['partner_logo']) : '') ?>"
                     alt="Partner Logo"
                     style="<?= $data['partner_logo'] ? '' : 'display:none' ?>">
            </div>

            <!-- Partner-Text -->
            <div class="pk-overlay pk-partner" id="pkPartnerArea"
                  style="top:<?= e($data['partner_top']) ?>%;left:<?= e($data['partner_left']) ?>%;width:<?= e($data['partner_width']) ?>%;font-family:<?= e($data['partner_font']) ?>;text-align:<?= e($data['partner_align']) ?>;align-items:<?= e($data['partner_align'] === 'left' ? 'flex-start' : ($data['partner_align'] === 'right' ? 'flex-end' : 'center')) ?>">
                <div class="pk-partner-text">
                    <span class="pk-partner-name<?= $data['partner_name_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkPartnerName" style="font-size:<?= e($data['partner_name_size']) ?>cqw;font-weight:<?= e($data['partner_name_weight']) ?>"><?= e($data['partner_name']) ?></span>
                    <span class="pk-partner-sub<?= $data['partner_sub_visible'] === '0' ? ' pk-text-hidden' : '' ?>"  id="pkPartnerSub"  style="font-size:<?= e($data['partner_sub_size']) ?>cqw;font-weight:<?= e($data['partner_sub_weight']) ?>"><?= e($data['partner_subtitle']) ?></span>
                </div>
            </div>

            <!-- Event-Untertitel (oben mitte) -->
            <div class="pk-overlay pk-eventsub<?= $data['eventsub_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkEventSub"
                                    style="top:<?= e($data['eventsub_top']) ?>%;left:<?= e($data['eventsub_left']) ?>%;width:<?= e($data['eventsub_width']) ?>%;font-size:<?= e($data['eventsub_size']) ?>cqw;font-weight:<?= e($data['eventsub_weight']) ?>;font-family:<?= e($data['eventsub_font']) ?>;text-align:<?= e($data['eventsub_align']) ?>"><?= e($data['event_subtitle']) ?></div>

            <!-- Weißer Kreis: Datum/Uhrzeit -->
            <div class="pk-overlay pk-circle" id="pkCircle"
                  style="top:<?= e($data['circle_top']) ?>%;left:<?= e($data['circle_left']) ?>%;width:<?= e($data['circle_width']) ?>%;font-family:<?= e($data['circle_font']) ?>;text-align:<?= e($data['circle_align']) ?>;align-items:<?= e($data['circle_align'] === 'left' ? 'flex-start' : ($data['circle_align'] === 'right' ? 'flex-end' : 'center')) ?>">
                                <span class="pk-weekday<?= $data['weekday_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkWeekday" style="font-size:<?= e($data['weekday_size']) ?>cqw;font-weight:<?= e($data['weekday_weight']) ?>"><?= e($data['weekday']) ?></span>
                                <span class="pk-date<?= $data['date_visible'] === '0' ? ' pk-text-hidden' : '' ?>"    id="pkDate"    style="font-size:<?= e($data['date_size']) ?>cqw;font-weight:<?= e($data['date_weight']) ?>"><?= e($data['date']) ?></span>
                                <span class="pk-time<?= $data['time_visible'] === '0' ? ' pk-text-hidden' : '' ?>"    id="pkTime"    style="font-size:<?= e($data['time_size']) ?>cqw;font-weight:<?= e($data['time_weight']) ?>"><?= e($data['time']) ?></span>
            </div>

            <!-- Venue-Info (rechts unter LIVE KONZERT) -->
            <div class="pk-overlay pk-venue" id="pkVenue"
                  style="top:<?= e($data['venue_top']) ?>%;right:<?= e($data['venue_right']) ?>%;width:<?= e($data['venue_width']) ?>%;font-family:<?= e($data['venue_font']) ?>;text-align:<?= e($data['venue_align']) ?>;align-items:<?= e($data['venue_align'] === 'left' ? 'flex-start' : ($data['venue_align'] === 'right' ? 'flex-end' : 'center')) ?>">
                                <span class="pk-venue-l1<?= $data['venue_l1_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkVenueL1" style="font-size:<?= e($data['venue_l1_size']) ?>cqw;font-weight:<?= e($data['venue_l1_weight']) ?>"><?= e($data['venue_line1']) ?></span>
                                <span class="pk-venue-l2<?= $data['venue_l2_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkVenueL2" style="font-size:<?= e($data['venue_l2_size']) ?>cqw;font-weight:<?= e($data['venue_l2_weight']) ?>"><?= e($data['venue_line2']) ?></span>
            </div>

            <!-- Venue-Logo (eigenständig positioniert) -->
            <div class="pk-overlay pk-logo-overlay<?= $data['venue_logo_visible'] === '0' ? ' pk-text-hidden' : '' ?>" id="pkVenueLogoWrap"
                 style="top:<?= e($data['venue_logo_top']) ?>%;right:<?= e($data['venue_logo_right']) ?>%;width:<?= e($data['venue_logo_width']) ?>%">
                <img class="pk-img-free" id="pkVenueLogo"
                     src="<?= e($data['venue_logo'] ? url($data['venue_logo']) : '') ?>"
                     alt="Location Logo"
                     style="<?= $data['venue_logo'] ? '' : 'display:none' ?>">
            </div>
        </div><!-- /pk-canvas -->
        </div><!-- /pkScaler -->

        <div class="pk-text-popup" id="pkTextPopup" hidden>
            <div class="pk-popup-row pk-popup-align" id="pkPopupAlign">
                <button type="button" data-align="left" title="Links">L</button>
                <button type="button" data-align="center" title="Zentriert">C</button>
                <button type="button" data-align="right" title="Rechts">R</button>
            </div>
            <div class="pk-popup-row">
                <label for="pkPopupFont">Schrift</label>
                <select id="pkPopupFont">
                    <option value="Arial, Helvetica, sans-serif">Arial</option>
                    <option value="Impact, sans-serif">Impact</option>
                    <option value="Georgia, serif">Georgia</option>
                    <option value="Verdana, sans-serif">Verdana</option>
                </select>
            </div>
            <div class="pk-popup-row">
                <label for="pkPopupSize">Größe</label>
                <input type="number" id="pkPopupSize" step="0.1" min="0.5" max="12">
            </div>
            <div class="pk-popup-row">
                <label for="pkPopupWeight">Gewicht</label>
                <select id="pkPopupWeight">
                    <option value="normal">Normal</option>
                    <option value="bold">Bold</option>
                </select>
            </div>
            <div class="pk-popup-row pk-popup-check-row">
                <label for="pkPopupVisible">Sichtbar</label>
                <input type="checkbox" id="pkPopupVisible" checked>
            </div>
        </div>
        <div class="pk-text-popup" id="pkLogoPopup" hidden>
            <div class="pk-popup-row">
                <label for="pkLogoPopupPath">Bild</label>
                <select id="pkLogoPopupPath">
                    <option value="">-- Bitte waehlen --</option>
                    <?php foreach ($imageChoices as $imgPath): ?>
                    <option value="<?= e($imgPath) ?>"><?= e($imgPath) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="pk-popup-upload-btn" for="pkLogoPopupFile">Bild hochladen
                <input type="file" id="pkLogoPopupFile" accept="image/*">
            </label>
            <div class="pk-popup-row pk-popup-check-row">
                <label for="pkLogoPopupVisible">Sichtbar</label>
                <input type="checkbox" id="pkLogoPopupVisible" checked>
            </div>
        </div>
        <datalist id="pkImagePathList">
            <?php foreach ($imageChoices as $imgPath): ?>
            <option value="<?= e($imgPath) ?>"></option>
            <?php endforeach; ?>
        </datalist>
    </div>

    <!-- ===== RECHTS: Editor ===== -->
    <aside class="pk-editor" aria-label="Plakat bearbeiten">
        <form method="post" action="<?= e(url('admin/plakat.php')) ?>" id="pkForm">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="circle_align" id="fCircleAlign" value="<?= e($data['circle_align']) ?>">
            <input type="hidden" name="venue_align" id="fVenueAlign" value="<?= e($data['venue_align']) ?>">
            <input type="hidden" name="partner_align" id="fPartnerAlign" value="<?= e($data['partner_align']) ?>">
            <input type="hidden" name="eventsub_align" id="fEventSubAlign" value="<?= e($data['eventsub_align']) ?>">
            <input type="hidden" name="weekday_weight" id="fWeekdayWeight" value="<?= e($data['weekday_weight']) ?>">
            <input type="hidden" name="weekday_visible" id="fWeekdayVisible" value="<?= e($data['weekday_visible']) ?>">
            <input type="hidden" name="date_weight" id="fDateWeight" value="<?= e($data['date_weight']) ?>">
            <input type="hidden" name="date_visible" id="fDateVisible" value="<?= e($data['date_visible']) ?>">
            <input type="hidden" name="time_weight" id="fTimeWeight" value="<?= e($data['time_weight']) ?>">
            <input type="hidden" name="time_visible" id="fTimeVisible" value="<?= e($data['time_visible']) ?>">
            <input type="hidden" name="venue_l1_weight" id="fVenueL1Weight" value="<?= e($data['venue_l1_weight']) ?>">
            <input type="hidden" name="venue_l1_visible" id="fVenueL1Visible" value="<?= e($data['venue_l1_visible']) ?>">
            <input type="hidden" name="venue_l2_weight" id="fVenueL2Weight" value="<?= e($data['venue_l2_weight']) ?>">
            <input type="hidden" name="venue_l2_visible" id="fVenueL2Visible" value="<?= e($data['venue_l2_visible']) ?>">
            <input type="hidden" name="partner_name_weight" id="fPartnerNameWeight" value="<?= e($data['partner_name_weight']) ?>">
            <input type="hidden" name="partner_name_visible" id="fPartnerNameVisible" value="<?= e($data['partner_name_visible']) ?>">
            <input type="hidden" name="partner_sub_weight" id="fPartnerSubWeight" value="<?= e($data['partner_sub_weight']) ?>">
            <input type="hidden" name="partner_sub_visible" id="fPartnerSubVisible" value="<?= e($data['partner_sub_visible']) ?>">
            <input type="hidden" name="eventsub_weight" id="fEventSubWeight" value="<?= e($data['eventsub_weight']) ?>">
            <input type="hidden" name="eventsub_visible" id="fEventSubVisible" value="<?= e($data['eventsub_visible']) ?>">
            <input type="hidden" name="circle_width" id="fCircleWidth" value="<?= e($data['circle_width']) ?>">
            <input type="hidden" name="venue_width" id="fVenueWidth" value="<?= e($data['venue_width']) ?>">
            <input type="hidden" name="partner_width" id="fPartnerWidth" value="<?= e($data['partner_width']) ?>">
            <input type="hidden" name="eventsub_width" id="fEventSubWidth" value="<?= e($data['eventsub_width']) ?>">
            <input type="hidden" name="partner_logo_visible" id="fPartnerLogoVisible" value="<?= e($data['partner_logo_visible']) ?>">
            <input type="hidden" name="venue_logo_visible" id="fVenueLogoVisible" value="<?= e($data['venue_logo_visible']) ?>">
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
                           value="<?= e($data['venue_logo']) ?>" list="pkImagePathList" placeholder="z.B. images/Amthof.png">
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
                           value="<?= e($data['partner_logo']) ?>" list="pkImagePathList" placeholder="z.B. images/Bellantik.png">
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
                <legend>Event-Untertitel</legend>
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
    display: flex;
    flex-wrap: nowrap;
    gap: 24px;
    align-items: start;
}
.pk-preview-wrap {
    flex: 0 0 clamp(320px, 36vw, 520px);
    width: clamp(320px, 36vw, 520px);
    position: relative;
    min-width: 0;
    overflow-x: auto;
    overflow-y: visible;
}
#pkScaler { width: 100%; }

/* Editor: sticky, scrollable */
.pk-editor {
    flex: 1 1 0;
    min-width: 300px;
    position: sticky;
    top: 16px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    overflow-x: hidden;
    background: rgba(0,0,0,.25);
    border: 1px solid rgba(212,168,90,.25);
    border-radius: 8px;
    padding: 14px 16px;
    container-type: inline-size;
    container-name: pk-editor;
    box-sizing: border-box;
}
.pk-editor > form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pk-editor h2 { margin: 0 0 .5rem; font-size: 1.1rem; }

/* Cards: 2 → 3 columns based on editor width */
.pk-editor-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
    gap: 12px;
    align-items: start;
}

.pk-editor fieldset {
    border: 1px solid rgba(212,168,90,.3);
    border-radius: 8px;
    padding: 12px 14px;
    background: rgba(0,0,0,.35);
    margin: 0;
    min-width: 0;
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
    .pk-layout { flex-direction: column; }
    .pk-preview-wrap {
        flex-basis: auto;
        width: min(100%, 520px);
    }
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

/* Inline text editing on poster */
.pk-editable-text {
    cursor: pointer;
    user-select: none;
}
.pk-editable-text.is-editing {
    cursor: text;
    user-select: text;
}
.pk-editable-text.is-editing:focus {
    outline: 1px dashed rgba(255,255,255,.8);
    outline-offset: 2px;
    background: rgba(0,0,0,.28);
}

/* Drag handles for direct positioning */
.pk-draggable { position: absolute; }
.pk-drag-handle {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    width: 22px;
    height: 10px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.85);
    background: rgba(0,0,0,.6);
    cursor: move;
    opacity: 0;
    transition: opacity .15s ease;
}
.pk-draggable.pk-active .pk-drag-handle,
.pk-draggable:hover .pk-drag-handle {
    opacity: 1;
}

/* Resize handle – bottom-right corner of text boxes */
.pk-resize-handle {
    position: absolute;
    bottom: -7px;
    right: -7px;
    width: 14px;
    height: 14px;
    background: rgba(212,165,68,.9);
    border: 1px solid rgba(255,255,255,.85);
    border-radius: 3px;
    cursor: se-resize;
    opacity: 0;
    transition: opacity .15s ease;
    z-index: 5;
}
.pk-resize-handle-left {
    right: auto;
    left: -7px;
    cursor: sw-resize;
}
.pk-draggable.pk-active .pk-resize-handle,
.pk-draggable:hover .pk-resize-handle {
    opacity: 1;
}

/* Floating text tools popup */
.pk-text-popup {
    position: absolute;
    z-index: 30;
    min-width: 180px;
    background: rgba(8,8,12,.95);
    border: 1px solid rgba(212,168,90,.55);
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(0,0,0,.38);
    padding: 8px;
}
.pk-popup-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 8px;
    align-items: center;
    margin: 6px 0;
}
.pk-popup-row label {
    font-size: .78rem;
    color: #f0c878;
}
.pk-popup-row select,
.pk-popup-row input {
    width: 100%;
    box-sizing: border-box;
    background: rgba(0,0,0,.42);
    color: #f4ead8;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 4px;
    padding: 4px 6px;
    font-size: .82rem;
}
.pk-popup-check-row {
    grid-template-columns: 1fr auto;
}
.pk-popup-check-row input[type=checkbox] {
    width: 16px;
    height: 16px;
    accent-color: #d4a544;
    justify-self: end;
}
.pk-popup-align {
    display: flex;
    gap: 6px;
    margin: 0 0 6px;
}
.pk-popup-align button {
    flex: 1;
    background: rgba(255,255,255,.06);
    color: #f4ead8;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 4px;
    padding: 4px 6px;
    font-size: .78rem;
    cursor: pointer;
}
.pk-popup-align button.is-active {
    border-color: #d4a544;
    background: rgba(212,165,68,.2);
    color: #ffd98f;
}
.pk-text-hidden {
    opacity: .25;
}
.pk-popup-upload-btn {
    display: block;
    margin: 6px 0;
    padding: 5px 8px;
    background: rgba(212,165,68,.1);
    border: 1px solid rgba(212,165,68,.35);
    border-radius: 4px;
    color: #f0c878;
    font-size: .78rem;
    cursor: pointer;
    text-align: center;
}
.pk-popup-upload-btn input[type=file] {
    display: none;
}
.pk-logo-overlay {
    cursor: default;
}

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
    white-space: pre-wrap;
    word-break: break-word;
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
    white-space: pre-wrap;
    word-break: break-word;
}
.pk-venue-l2 {
    display: block;
    font-size: 1.7cqw;
    color: #fff;
    line-height: 1.3;
    white-space: pre-wrap;
    word-break: break-word;
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
@page { size: A4 portrait; margin: 0; }
@media print {
    html, body, .admin-main { background: #fff !important; }
    .admin-header, .admin-footer, .pk-topbar, .pk-editor { display: none !important; }
    .pk-layout { display: block; }
    .pk-preview-wrap { overflow: visible; }
    #pkScaler { width: 100% !important; }
    .pk-bg { max-width: 100%; width: 100%; }
    .pk-text-hidden { display: none !important; }
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

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function roundToStep(v, step) {
        var s = parseFloat(step);
        if (!s || s <= 0) return Math.round(v * 10) / 10;
        return Math.round(v / s) * s;
    }

    function formatFieldValue(field, value) {
        var step = field ? field.getAttribute('step') : null;
        var rounded = roundToStep(value, step || '0.1');
        if (!field || !step) return String(roundToStep(rounded, '0.1'));

        var stepStr = String(step);
        var decimals = 0;
        if (stepStr.indexOf('.') >= 0) {
            decimals = stepStr.split('.')[1].length;
        }
        return rounded.toFixed(decimals);
    }

    function dispatchInput(el) {
        if (!el) return;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function applyAreaAlign(targetEl, align) {
        if (!targetEl) return;
        targetEl.style.textAlign = align;
        if (targetEl.classList.contains('pk-circle') || targetEl.classList.contains('pk-venue') || targetEl.classList.contains('pk-partner')) {
            targetEl.style.alignItems = align === 'left' ? 'flex-start' : (align === 'right' ? 'flex-end' : 'center');
        }
    }

    function applyTextVisibility(targetEl, isVisible) {
        if (!targetEl) return;
        targetEl.classList.toggle('pk-text-hidden', !isVisible);
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

    function setActiveOverlay(overlay) {
        document.querySelectorAll('.pk-draggable.pk-active').forEach(function (el) {
            el.classList.remove('pk-active');
        });
        if (overlay) overlay.classList.add('pk-active');
    }

    var textPopup = document.getElementById('pkTextPopup');
    var popupAlign = document.getElementById('pkPopupAlign');
    var popupFont = document.getElementById('pkPopupFont');
    var popupSize = document.getElementById('pkPopupSize');
    var popupWeight = document.getElementById('pkPopupWeight');
    var popupVisible = document.getElementById('pkPopupVisible');
    var printLink = document.getElementById('pkPrintLink');
    var formEl = document.getElementById('pkForm');
    var imagePathList = document.getElementById('pkImagePathList');
    var printAfterSaveStorageKey = 'pk-open-print-after-save';
    var navAfterSaveStorageKey = 'pk-open-nav-after-save';
    var activeTextCfg = null;
    var activeTextEl = null;

    var textToolMap = {
        pkWeekday: { alignFieldId: 'fCircleAlign',  alignTargetId: 'pkCircle',      fontFieldId: 'fCircleFont',   sizeFieldId: 'fWeekdaySize',     weightFieldId: 'fWeekdayWeight',    visibleFieldId: 'fWeekdayVisible' },
        pkDate:    { alignFieldId: 'fCircleAlign',  alignTargetId: 'pkCircle',      fontFieldId: 'fCircleFont',   sizeFieldId: 'fDateSize',        weightFieldId: 'fDateWeight',       visibleFieldId: 'fDateVisible' },
        pkTime:    { alignFieldId: 'fCircleAlign',  alignTargetId: 'pkCircle',      fontFieldId: 'fCircleFont',   sizeFieldId: 'fTimeSize',        weightFieldId: 'fTimeWeight',       visibleFieldId: 'fTimeVisible' },
        pkVenueL1: { alignFieldId: 'fVenueAlign',   alignTargetId: 'pkVenue',       fontFieldId: 'fVenueFont',    sizeFieldId: 'fVenueL1Size',     weightFieldId: 'fVenueL1Weight',    visibleFieldId: 'fVenueL1Visible' },
        pkVenueL2: { alignFieldId: 'fVenueAlign',   alignTargetId: 'pkVenue',       fontFieldId: 'fVenueFont',    sizeFieldId: 'fVenueL2Size',     weightFieldId: 'fVenueL2Weight',    visibleFieldId: 'fVenueL2Visible' },
        pkPartnerName: { alignFieldId: 'fPartnerAlign', alignTargetId: 'pkPartnerArea', fontFieldId: 'fPartnerFont', sizeFieldId: 'fPartnerNameSize', weightFieldId: 'fPartnerNameWeight', visibleFieldId: 'fPartnerNameVisible' },
        pkPartnerSub:  { alignFieldId: 'fPartnerAlign', alignTargetId: 'pkPartnerArea', fontFieldId: 'fPartnerFont', sizeFieldId: 'fPartnerSubSize',  weightFieldId: 'fPartnerSubWeight',  visibleFieldId: 'fPartnerSubVisible' },
        pkEventSub:    { alignFieldId: 'fEventSubAlign', alignTargetId: 'pkEventSub', fontFieldId: 'fEventSubFont', sizeFieldId: 'fEventSubSize',    weightFieldId: 'fEventSubWeight',   visibleFieldId: 'fEventSubVisible' }
    };

    function setActiveAlignButton(align) {
        if (!popupAlign) return;
        popupAlign.querySelectorAll('button[data-align]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-align') === align);
        });
    }

    function positionTextPopup() {
        if (!textPopup || textPopup.hidden || !activeTextEl || !previewWrap) return;
        var wrapRect = previewWrap.getBoundingClientRect();
        var textRect = activeTextEl.getBoundingClientRect();
        var left = (textRect.left - wrapRect.left) + (textRect.width / 2);
        var top = textRect.top - wrapRect.top - textPopup.offsetHeight - 10;
        if (top < 6) {
            top = textRect.bottom - wrapRect.top + 10;
        }
        left = clamp(left - (textPopup.offsetWidth / 2), 6, Math.max(6, previewWrap.clientWidth - textPopup.offsetWidth - 6));
        top = clamp(top, 6, Math.max(6, previewWrap.clientHeight - textPopup.offsetHeight - 6));
        textPopup.style.left = left + 'px';
        textPopup.style.top = top + 'px';
    }

    function showTextPopup(el) {
        if (!textPopup || !el) return;
        var cfg = textToolMap[el.id];
        if (!cfg) return;
        var alignField = document.getElementById(cfg.alignFieldId);
        var fontField = document.getElementById(cfg.fontFieldId);
        var sizeField = document.getElementById(cfg.sizeFieldId);
        var weightField = document.getElementById(cfg.weightFieldId);
        var visibleField = document.getElementById(cfg.visibleFieldId);
        if (!alignField || !fontField || !sizeField || !weightField || !visibleField) return;

        activeTextCfg = cfg;
        activeTextEl = el;
        popupFont.value = fontField.value;
        popupSize.value = sizeField.value;
        if (popupWeight) popupWeight.value = weightField.value || 'normal';
        if (popupVisible) popupVisible.checked = visibleField.value !== '0';
        setActiveAlignButton(alignField.value || 'center');
        textPopup.hidden = false;
        positionTextPopup();
    }

    function hideTextPopup() {
        if (!textPopup) return;
        textPopup.hidden = true;
        activeTextCfg = null;
        activeTextEl = null;
    }

    if (popupAlign) {
        popupAlign.addEventListener('click', function (ev) {
            var btn = ev.target.closest('button[data-align]');
            if (!btn || !activeTextCfg) return;
            var alignValue = btn.getAttribute('data-align') || 'center';
            var alignField = document.getElementById(activeTextCfg.alignFieldId);
            var alignTarget = document.getElementById(activeTextCfg.alignTargetId);
            if (!alignField || !alignTarget) return;
            alignField.value = alignValue;
            setActiveAlignButton(alignValue);
            applyAreaAlign(alignTarget, alignValue);
        });
    }
    if (popupFont) {
        popupFont.addEventListener('change', function () {
            if (!activeTextCfg) return;
            var fontField = document.getElementById(activeTextCfg.fontFieldId);
            if (!fontField) return;
            fontField.value = popupFont.value;
            dispatchInput(fontField);
        });
    }
    if (popupSize) {
        popupSize.addEventListener('input', function () {
            if (!activeTextCfg) return;
            var sizeField = document.getElementById(activeTextCfg.sizeFieldId);
            if (!sizeField) return;
            sizeField.value = popupSize.value;
            dispatchInput(sizeField);
        });
    }
    if (popupWeight) {
        popupWeight.addEventListener('change', function () {
            if (!activeTextCfg) return;
            var weightField = document.getElementById(activeTextCfg.weightFieldId);
            if (!weightField) return;
            weightField.value = popupWeight.value;
            dispatchInput(weightField);
        });
    }
    if (popupVisible) {
        popupVisible.addEventListener('change', function () {
            if (!activeTextCfg || !activeTextEl) return;
            var visibleField = document.getElementById(activeTextCfg.visibleFieldId);
            if (!visibleField) return;
            visibleField.value = popupVisible.checked ? '1' : '0';
            applyTextVisibility(activeTextEl, popupVisible.checked);
        });
    }

    if (previewWrap) {
        previewWrap.addEventListener('scroll', positionTextPopup);
    }
    window.addEventListener('resize', positionTextPopup);
    document.addEventListener('mousedown', function (ev) {
        if (textPopup && !textPopup.hidden) {
            var insidePopup = textPopup.contains(ev.target);
            var textEl = ev.target.closest('.pk-editable-text');
            if (!insidePopup && !textEl) hideTextPopup();
        }
        if (logoPopup && !logoPopup.hidden) {
            var insideLogoPopup = logoPopup.contains(ev.target);
            var logoEl = ev.target.closest('.pk-logo-overlay');
            if (!insideLogoPopup && !logoEl) hideLogoPopup();
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key !== 'Escape') return;
        hideTextPopup();
        hideLogoPopup();
    });

    /* Logo overlay popup (double-click) */
    var logoPopup = document.getElementById('pkLogoPopup');
    var logoPopupPath = document.getElementById('pkLogoPopupPath');
    var logoPopupFile = document.getElementById('pkLogoPopupFile');
    var logoPopupVisible = document.getElementById('pkLogoPopupVisible');
    var activeLogoCfg = null;
    var activeLogoEl = null;

    var logoToolMap = {
        pkPartnerLogoWrap: { pathFieldId: 'fPartnerLogo', imgElId: 'pkPartnerLogo', visibleFieldId: 'fPartnerLogoVisible' },
        pkVenueLogoWrap:   { pathFieldId: 'fVenueLogo',   imgElId: 'pkVenueLogo',   visibleFieldId: 'fVenueLogoVisible' }
    };

    function positionLogoPopup() {
        if (!logoPopup || logoPopup.hidden || !activeLogoEl || !previewWrap) return;
        var wrapRect = previewWrap.getBoundingClientRect();
        var elRect = activeLogoEl.getBoundingClientRect();
        var left = (elRect.left - wrapRect.left) + (elRect.width / 2);
        var top = elRect.top - wrapRect.top - logoPopup.offsetHeight - 10;
        if (top < 6) top = elRect.bottom - wrapRect.top + 10;
        left = clamp(left - (logoPopup.offsetWidth / 2), 6, Math.max(6, previewWrap.clientWidth - logoPopup.offsetWidth - 6));
        top = clamp(top, 6, Math.max(6, previewWrap.clientHeight - logoPopup.offsetHeight - 6));
        logoPopup.style.left = left + 'px';
        logoPopup.style.top = top + 'px';
    }

    function showLogoPopup(el) {
        if (!logoPopup || !el) return;
        var cfg = logoToolMap[el.id];
        if (!cfg) return;
        var pathField = document.getElementById(cfg.pathFieldId);
        var visibleField = document.getElementById(cfg.visibleFieldId);
        if (!pathField || !visibleField) return;
        activeLogoCfg = cfg;
        activeLogoEl = el;
        if (logoPopupPath) logoPopupPath.value = pathField.value.startsWith('data:') ? '' : pathField.value;
        if (logoPopupVisible) logoPopupVisible.checked = visibleField.value !== '0';
        logoPopup.hidden = false;
        positionLogoPopup();
    }

    function hideLogoPopup() {
        if (!logoPopup) return;
        logoPopup.hidden = true;
        activeLogoCfg = null;
        activeLogoEl = null;
    }

    function closeEditPopups() {
        hideTextPopup();
        hideLogoPopup();
    }

    function restoreLastSavedState() {
        window.location.reload();
    }

    function ensureImagePathOption(path) {
        if (!path) return;
        var normalized = String(path);

        function hasOptionValue(container) {
            if (!container) return false;
            var opts = container.querySelectorAll('option');
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === normalized) return true;
            }
            return false;
        }

        if (imagePathList && !hasOptionValue(imagePathList)) {
            var dlOpt = document.createElement('option');
            dlOpt.value = normalized;
            imagePathList.appendChild(dlOpt);
        }

        if (logoPopupPath && logoPopupPath.tagName === 'SELECT' && !hasOptionValue(logoPopupPath)) {
            var selectOpt = document.createElement('option');
            selectOpt.value = normalized;
            selectOpt.textContent = normalized;
            logoPopupPath.appendChild(selectOpt);
        }
    }

    function uploadLogoFile(file, onDone) {
        if (!file || !formEl) return;
        var csrfField = formEl.querySelector('input[name="csrf"]');
        var payload = new FormData();
        payload.append('image', file);
        payload.append('csrf', csrfField ? csrfField.value : '');

        fetch('<?= e(url('admin/plakat_upload.php')) ?>', {
            method: 'POST',
            body: payload,
            credentials: 'same-origin'
        })
        .then(function (resp) {
            if (!resp.ok) throw new Error('Upload fehlgeschlagen');
            return resp.json();
        })
        .then(function (data) {
            if (!data || !data.ok || !data.path) {
                throw new Error((data && data.error) ? data.error : 'Upload fehlgeschlagen');
            }
            ensureImagePathOption(data.path);
            if (typeof onDone === 'function') onDone(data.path);
        })
        .catch(function (err) {
            window.alert(err && err.message ? err.message : 'Upload fehlgeschlagen');
        });
    }

    function getFormSnapshot() {
        if (!formEl) return '';
        var fd = new FormData(formEl);
        var pairs = [];
        fd.forEach(function (val, key) {
            pairs.push(key + '=' + String(val));
        });
        pairs.sort();
        return pairs.join('&');
    }

    var initialFormSnapshot = getFormSnapshot();

    function isFormDirty() {
        return getFormSnapshot() !== initialFormSnapshot;
    }

    if (printLink) {
        printLink.addEventListener('click', function (ev) {
            if (!isFormDirty()) return;
            ev.preventDefault();
            closeEditPopups();
            var shouldSave = window.confirm('Es gibt ungespeicherte Aenderungen. Vor dem Drucken/PDF zuerst speichern?');
            if (!shouldSave) {
                restoreLastSavedState();
                return;
            }
            if (!formEl) return;
            try {
                window.sessionStorage.setItem(printAfterSaveStorageKey, printLink.href);
            } catch (err) {
                /* Ignore unavailable sessionStorage */
            }
            formEl.submit();
        });
    }

    var menuLinks = document.querySelectorAll('.admin-header a[href]');
    menuLinks.forEach(function (link) {
        link.addEventListener('click', function (ev) {
            if (!isFormDirty()) return;
            if (ev.defaultPrevented) return;
            if (ev.button !== 0) return;
            if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;

            var href = link.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#') return;

            var targetUrl;
            try {
                targetUrl = new URL(link.href, window.location.href);
            } catch (err) {
                return;
            }
            if (targetUrl.href === window.location.href) return;

            ev.preventDefault();
            closeEditPopups();
            var shouldSave = window.confirm('Es gibt ungespeicherte Aenderungen. Vor dem Wechsel zuerst speichern?');
            if (!shouldSave) {
                restoreLastSavedState();
                return;
            }
            if (!formEl) return;

            try {
                window.sessionStorage.setItem(navAfterSaveStorageKey, targetUrl.href);
            } catch (err) {
                /* Ignore unavailable sessionStorage */
            }
            formEl.submit();
        });
    });

    try {
        var printAfterSaveUrl = window.sessionStorage.getItem(printAfterSaveStorageKey);
        if (printAfterSaveUrl) {
            window.sessionStorage.removeItem(printAfterSaveStorageKey);
            window.open(printAfterSaveUrl, '_blank', 'noopener');
        }
    } catch (err) {
        /* Ignore unavailable sessionStorage */
    }

    try {
        var navAfterSaveUrl = window.sessionStorage.getItem(navAfterSaveStorageKey);
        if (navAfterSaveUrl) {
            window.sessionStorage.removeItem(navAfterSaveStorageKey);
            window.location.assign(navAfterSaveUrl);
        }
    } catch (err) {
        /* Ignore unavailable sessionStorage */
    }

    if (logoPopupPath) {
        logoPopupPath.addEventListener('change', function () {
            if (!activeLogoCfg) return;
            var pathField = document.getElementById(activeLogoCfg.pathFieldId);
            var imgEl = document.getElementById(activeLogoCfg.imgElId);
            if (!pathField || !imgEl) return;
            pathField.value = logoPopupPath.value;
            setImg(imgEl, imgSrc(logoPopupPath.value));
        });
    }
    if (logoPopupFile) {
        logoPopupFile.addEventListener('change', function (ev) {
            var file = ev.target.files && ev.target.files[0];
            if (!file || !activeLogoCfg) return;
            var pathField = document.getElementById(activeLogoCfg.pathFieldId);
            var imgEl = document.getElementById(activeLogoCfg.imgElId);
            if (!pathField || !imgEl) return;
            uploadLogoFile(file, function (storedPath) {
                pathField.value = storedPath;
                if (logoPopupPath) logoPopupPath.value = storedPath;
                setImg(imgEl, imgSrc(storedPath));
            });
            logoPopupFile.value = '';
        });
    }
    if (logoPopupVisible) {
        logoPopupVisible.addEventListener('change', function () {
            if (!activeLogoCfg || !activeLogoEl) return;
            var visibleField = document.getElementById(activeLogoCfg.visibleFieldId);
            if (!visibleField) return;
            visibleField.value = logoPopupVisible.checked ? '1' : '0';
            applyTextVisibility(activeLogoEl, logoPopupVisible.checked);
        });
    }
    window.addEventListener('resize', positionLogoPopup);

    /* Inline text editing directly on poster (double-click to edit, Esc cancels) */
    function bindInlineText(fieldId, elementId) {
        var field = document.getElementById(fieldId);
        var el = document.getElementById(elementId);
        if (!field || !el) return;
        var originalText = field.value;

        el.setAttribute('contenteditable', 'false');
        el.setAttribute('spellcheck', 'false');
        el.setAttribute('tabindex', '0');
        el.classList.add('pk-editable-text');

        function stopEdit(commit) {
            if (el.getAttribute('contenteditable') !== 'true') return;
            if (commit) {
                field.value = el.textContent;
            } else {
                el.textContent = originalText;
                field.value = originalText;
            }
            el.setAttribute('contenteditable', 'false');
            el.classList.remove('is-editing');
        }

        el.addEventListener('click', function () {
            setActiveOverlay(el.closest('.pk-draggable'));
            showTextPopup(el);
        });

        el.addEventListener('dblclick', function (ev) {
            ev.preventDefault();
            setActiveOverlay(el.closest('.pk-draggable'));
            showTextPopup(el);
            originalText = field.value;
            el.setAttribute('contenteditable', 'true');
            el.classList.add('is-editing');
            el.focus();
            var sel = window.getSelection();
            if (!sel) return;
            var range = document.createRange();
            range.selectNodeContents(el);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
        });

        el.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                stopEdit(true);
                el.blur();
            }
            if (ev.key === 'Escape') {
                ev.preventDefault();
                stopEdit(false);
                hideTextPopup();
                el.blur();
            }
        });

        el.addEventListener('input', function () {
            if (el.getAttribute('contenteditable') === 'true') {
                field.value = el.textContent;
            }
        });

        el.addEventListener('blur', function () {
            stopEdit(true);
        });
    }

    [
        ['fWeekday', 'pkWeekday'],
        ['fDate', 'pkDate'],
        ['fTime', 'pkTime'],
        ['fVenueL1', 'pkVenueL1'],
        ['fVenueL2', 'pkVenueL2'],
        ['fPartnerName', 'pkPartnerName'],
        ['fPartnerSub', 'pkPartnerSub'],
        ['fEventSub', 'pkEventSub']
    ].forEach(function (pair) {
        bindInlineText(pair[0], pair[1]);
    });

    /* Drag text areas directly on poster and sync position inputs */
    function bindDragOverlay(cfg) {
        var overlay = document.getElementById(cfg.overlayId);
        var topField = document.getElementById(cfg.topFieldId);
        var leftField = cfg.leftFieldId ? document.getElementById(cfg.leftFieldId) : null;
        var rightField = cfg.rightFieldId ? document.getElementById(cfg.rightFieldId) : null;
        var canvas = document.getElementById('pkCanvas');
        if (!overlay || !topField || (!leftField && !rightField) || !canvas) return;

        overlay.classList.add('pk-draggable');
        var handle = document.createElement('span');
        handle.className = 'pk-drag-handle';
        handle.title = 'Ziehen';
        overlay.appendChild(handle);

        overlay.addEventListener('mousedown', function () {
            setActiveOverlay(overlay);
        });

        handle.addEventListener('mousedown', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            setActiveOverlay(overlay);

            var canvasRect = canvas.getBoundingClientRect();
            var overlayRect = overlay.getBoundingClientRect();
            var widthPct = (overlayRect.width / canvasRect.width) * 100;
            var heightPct = (overlayRect.height / canvasRect.height) * 100;
            var startX = ev.clientX;
            var startY = ev.clientY;
            var startTop = parseFloat(topField.value) || 0;
            var startLeft;

            if (leftField) {
                startLeft = parseFloat(leftField.value) || 0;
            } else {
                var startRight = parseFloat(rightField.value) || 0;
                startLeft = 100 - startRight - widthPct;
            }

            function onMove(moveEv) {
                var dxPct = ((moveEv.clientX - startX) / canvasRect.width) * 100;
                var dyPct = ((moveEv.clientY - startY) / canvasRect.height) * 100;
                var nextTop = clamp(startTop + dyPct, 0, 100 - heightPct);
                var nextLeft = clamp(startLeft + dxPct, 0, 100 - widthPct);

                topField.value = formatFieldValue(topField, nextTop);
                overlay.style.top = topField.value + '%';

                if (leftField) {
                    leftField.value = formatFieldValue(leftField, nextLeft);
                    overlay.style.left = leftField.value + '%';
                } else {
                    var nextRight = clamp(100 - nextLeft - widthPct, 0, 100);
                    rightField.value = formatFieldValue(rightField, nextRight);
                    overlay.style.right = rightField.value + '%';
                }
            }

            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    [
        { overlayId: 'pkCircle',         topFieldId: 'fCircleTop',      leftFieldId: 'fCircleLeft' },
        { overlayId: 'pkVenue',          topFieldId: 'fVenueTop',       rightFieldId: 'fVenueRight' },
        { overlayId: 'pkPartnerArea',    topFieldId: 'fPartnerTop',     leftFieldId: 'fPartnerLeft' },
        { overlayId: 'pkEventSub',       topFieldId: 'fEventSubTop',    leftFieldId: 'fEventSubLeft' },
        { overlayId: 'pkPartnerLogoWrap', topFieldId: 'fPartnerLogoTop', leftFieldId: 'fPartnerLogoLeft' },
        { overlayId: 'pkVenueLogoWrap',  topFieldId: 'fVenueLogoTop',   rightFieldId: 'fVenueLogoRight' }
    ].forEach(bindDragOverlay);

    /* Resize text overlay width by dragging corner handle */
    function bindResizeOverlay(cfg) {
        var overlay = document.getElementById(cfg.overlayId);
        var widthField = document.getElementById(cfg.widthFieldId);
        var canvas = document.getElementById('pkCanvas');
        if (!overlay || !widthField || !canvas) return;

        var handle = document.createElement('span');
        handle.className = cfg.rightAnchored
            ? 'pk-resize-handle pk-resize-handle-left'
            : 'pk-resize-handle';
        handle.title = 'Breite ändern';
        overlay.appendChild(handle);

        handle.addEventListener('mousedown', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            setActiveOverlay(overlay);

            var canvasRect = canvas.getBoundingClientRect();
            var startX = ev.clientX;
            var startWidth = parseFloat(widthField.value) || 28;

            function onMove(moveEv) {
                var dxPct = ((moveEv.clientX - startX) / canvasRect.width) * 100;
                /* Right-anchored overlays grow leftward, so invert direction */
                var nextWidth = clamp(startWidth + (cfg.rightAnchored ? -dxPct : dxPct), 5, 95);
                widthField.value = formatFieldValue(widthField, nextWidth);
                overlay.style.width = widthField.value + '%';
            }

            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            }

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    [
        { overlayId: 'pkCircle',          widthFieldId: 'fCircleWidth' },
        { overlayId: 'pkVenue',           widthFieldId: 'fVenueWidth',        rightAnchored: true },
        { overlayId: 'pkPartnerArea',     widthFieldId: 'fPartnerWidth' },
        { overlayId: 'pkEventSub',        widthFieldId: 'fEventSubWidth' },
        { overlayId: 'pkPartnerLogoWrap', widthFieldId: 'fPartnerLogoWidth' },
        { overlayId: 'pkVenueLogoWrap',   widthFieldId: 'fVenueLogoWidth',    rightAnchored: true }
    ].forEach(bindResizeOverlay);

    /* Logo overlays: double-click to open edit popup */
    ['pkPartnerLogoWrap', 'pkVenueLogoWrap'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('dblclick', function (ev) {
            ev.preventDefault();
            showLogoPopup(el);
        });
    });

    /* Position, font-size & font-family live updates */
    var styleMap = [
        { field: 'fCircleTop',    el: 'pkCircle',  prop: 'top',        unit: '%'   },
        { field: 'fCircleLeft',   el: 'pkCircle',  prop: 'left',       unit: '%'   },
        { field: 'fCircleFont',   el: 'pkCircle',  prop: 'fontFamily', unit: ''    },
        { field: 'fWeekdaySize',  el: 'pkWeekday', prop: 'fontSize',   unit: 'cqw' },
        { field: 'fWeekdayWeight', el: 'pkWeekday', prop: 'fontWeight', unit: '' },
        { field: 'fDateSize',     el: 'pkDate',    prop: 'fontSize',   unit: 'cqw' },
        { field: 'fDateWeight',   el: 'pkDate',    prop: 'fontWeight', unit: '' },
        { field: 'fTimeSize',     el: 'pkTime',    prop: 'fontSize',   unit: 'cqw' },
        { field: 'fTimeWeight',   el: 'pkTime',    prop: 'fontWeight', unit: '' },
        { field: 'fVenueTop',     el: 'pkVenue',   prop: 'top',        unit: '%'   },
        { field: 'fVenueRight',   el: 'pkVenue',   prop: 'right',      unit: '%'   },
        { field: 'fVenueFont',    el: 'pkVenue',   prop: 'fontFamily', unit: ''    },
        { field: 'fVenueL1Size',     el: 'pkVenueL1',     prop: 'fontSize',   unit: 'cqw' },
        { field: 'fVenueL1Weight',   el: 'pkVenueL1',     prop: 'fontWeight', unit: '' },
        { field: 'fVenueL2Size',     el: 'pkVenueL2',     prop: 'fontSize',   unit: 'cqw' },
        { field: 'fVenueL2Weight',   el: 'pkVenueL2',     prop: 'fontWeight', unit: '' },
        { field: 'fPartnerTop',      el: 'pkPartnerArea', prop: 'top',        unit: '%'   },
        { field: 'fPartnerLeft',     el: 'pkPartnerArea', prop: 'left',       unit: '%'   },
        { field: 'fPartnerFont',     el: 'pkPartnerArea', prop: 'fontFamily', unit: ''    },
        { field: 'fPartnerNameSize', el: 'pkPartnerName', prop: 'fontSize',   unit: 'cqw' },
        { field: 'fPartnerNameWeight', el: 'pkPartnerName', prop: 'fontWeight', unit: '' },
        { field: 'fPartnerSubSize',  el: 'pkPartnerSub',  prop: 'fontSize',   unit: 'cqw' },
        { field: 'fPartnerSubWeight', el: 'pkPartnerSub',  prop: 'fontWeight', unit: '' },
        { field: 'fEventSubTop',      el: 'pkEventSub',       prop: 'top',        unit: '%'   },
        { field: 'fEventSubLeft',     el: 'pkEventSub',       prop: 'left',       unit: '%'   },
        { field: 'fEventSubSize',     el: 'pkEventSub',       prop: 'fontSize',   unit: 'cqw' },
        { field: 'fEventSubWeight',   el: 'pkEventSub',       prop: 'fontWeight', unit: '' },
        { field: 'fEventSubFont',     el: 'pkEventSub',       prop: 'fontFamily', unit: ''    },
        { field: 'fPartnerLogoTop',   el: 'pkPartnerLogoWrap', prop: 'top',   unit: '%' },
        { field: 'fPartnerLogoLeft',  el: 'pkPartnerLogoWrap', prop: 'left',  unit: '%' },
        { field: 'fPartnerLogoWidth', el: 'pkPartnerLogoWrap', prop: 'width', unit: '%' },
        { field: 'fVenueLogoTop',     el: 'pkVenueLogoWrap',   prop: 'top',   unit: '%' },
        { field: 'fVenueLogoRight',   el: 'pkVenueLogoWrap',   prop: 'right', unit: '%' },
        { field: 'fVenueLogoWidth',   el: 'pkVenueLogoWrap',   prop: 'width', unit: '%' },
        { field: 'fCircleWidth',      el: 'pkCircle',      prop: 'width', unit: '%' },
        { field: 'fVenueWidth',       el: 'pkVenue',       prop: 'width', unit: '%' },
        { field: 'fPartnerWidth',     el: 'pkPartnerArea', prop: 'width', unit: '%' },
        { field: 'fEventSubWidth',    el: 'pkEventSub',    prop: 'width', unit: '%' },
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

    [
        { fieldId: 'fCircleAlign', targetId: 'pkCircle' },
        { fieldId: 'fVenueAlign', targetId: 'pkVenue' },
        { fieldId: 'fPartnerAlign', targetId: 'pkPartnerArea' },
        { fieldId: 'fEventSubAlign', targetId: 'pkEventSub' }
    ].forEach(function (cfg) {
        var f = document.getElementById(cfg.fieldId);
        var t = document.getElementById(cfg.targetId);
        if (!f || !t) return;
        applyAreaAlign(t, f.value || 'center');
        ['input', 'change'].forEach(function (evt) {
            f.addEventListener(evt, function () {
                applyAreaAlign(t, f.value || 'center');
            });
        });
    });

    [
        { fieldId: 'fWeekdayVisible', targetId: 'pkWeekday' },
        { fieldId: 'fDateVisible', targetId: 'pkDate' },
        { fieldId: 'fTimeVisible', targetId: 'pkTime' },
        { fieldId: 'fVenueL1Visible', targetId: 'pkVenueL1' },
        { fieldId: 'fVenueL2Visible', targetId: 'pkVenueL2' },
        { fieldId: 'fPartnerNameVisible', targetId: 'pkPartnerName' },
        { fieldId: 'fPartnerSubVisible', targetId: 'pkPartnerSub' },
        { fieldId: 'fEventSubVisible', targetId: 'pkEventSub' }
    ].forEach(function (cfg) {
        var f = document.getElementById(cfg.fieldId);
        var t = document.getElementById(cfg.targetId);
        if (!f || !t) return;
        applyTextVisibility(t, f.value !== '0');
        ['input', 'change'].forEach(function (evt) {
            f.addEventListener(evt, function () {
                applyTextVisibility(t, f.value !== '0');
            });
        });
    });

    /* Logo visibility init */
    [
        { fieldId: 'fPartnerLogoVisible', targetId: 'pkPartnerLogoWrap' },
        { fieldId: 'fVenueLogoVisible',   targetId: 'pkVenueLogoWrap' }
    ].forEach(function (cfg) {
        var f = document.getElementById(cfg.fieldId);
        var t = document.getElementById(cfg.targetId);
        if (!f || !t) return;
        applyTextVisibility(t, f.value !== '0');
        ['input', 'change'].forEach(function (evt) {
            f.addEventListener(evt, function () {
                applyTextVisibility(t, f.value !== '0');
            });
        });
    });

    /* File-Upload → serverseitig speichern */
    function bindUpload(fileId, pathId, imgElId) {
        var fi = document.getElementById(fileId);
        var pi = document.getElementById(pathId);
        var img = document.getElementById(imgElId);
        if (!fi) return;
        fi.addEventListener('change', function (ev) {
            var file = ev.target.files && ev.target.files[0];
            if (!file) return;
            uploadLogoFile(file, function (storedPath) {
                if (pi) pi.value = storedPath;
                setImg(img, imgSrc(storedPath));
                if (logoPopupPath && !logoPopup.hidden) {
                    logoPopupPath.value = storedPath;
                }
            });
            fi.value = '';
        });
    }
    bindUpload('fVenueLogoFile',   'fVenueLogo',   'pkVenueLogo');
    bindUpload('fPartnerLogoFile', 'fPartnerLogo', 'pkPartnerLogo');

    /* Zoom slider + Shift+wheel zoom */
    var zoomSlider = document.getElementById('pkZoomSlider');
    var zoomLabel  = document.getElementById('pkZoomLabel');
    var layout     = document.querySelector('.pk-layout');
    var previewWrap = document.getElementById('pkPreviewWrap');
    var scaler     = document.getElementById('pkScaler');
    var zoomStorageKey = 'pk-plakat-zoom';
    var zoomMin = zoomSlider ? (parseFloat(zoomSlider.min) || 30) : 30;
    var zoomMax = zoomSlider ? (parseFloat(zoomSlider.max) || 200) : 200;
    var zoomStep = zoomSlider ? (parseFloat(zoomSlider.step) || 1) : 1;

    function normalizeZoom(v) {
        var n = clamp(v, zoomMin, zoomMax);
        if (zoomStep > 0) {
            n = Math.round(n / zoomStep) * zoomStep;
        }
        return clamp(n, zoomMin, zoomMax);
    }

    function setZoom(v) {
        if (!zoomSlider) return;
        zoomSlider.value = String(normalizeZoom(v));
        try {
            window.localStorage.setItem(zoomStorageKey, zoomSlider.value);
        } catch (err) {
            /* Ignore unavailable localStorage */
        }
        updateZoomLayout();
    }

    function getBasePreviewWidth() {
        if (!previewWrap) return 0;
        var prevBasis = previewWrap.style.flexBasis;
        var prevWidth = previewWrap.style.width;
        previewWrap.style.flexBasis = '';
        previewWrap.style.width = '';
        var width = previewWrap.getBoundingClientRect().width || 0;
        previewWrap.style.flexBasis = prevBasis;
        previewWrap.style.width = prevWidth;
        return width;
    }

    function updateZoomLayout() {
        if (!zoomSlider || !scaler) return;

        var zoom = parseFloat(zoomSlider.value) || 100;
        if (zoomLabel) zoomLabel.textContent = zoom + '%';

        if (!layout || !previewWrap || window.matchMedia('(max-width: 1000px)').matches) {
            if (previewWrap) {
                previewWrap.style.flexBasis = '';
                previewWrap.style.width = '';
            }
            scaler.style.width = zoom + '%';
            return;
        }

        var basePreviewWidth = getBasePreviewWidth();
        if (!basePreviewWidth) {
            scaler.style.width = zoom + '%';
            return;
        }

        var layoutWidth = layout.getBoundingClientRect().width || 0;
        var layoutStyle = window.getComputedStyle(layout);
        var gap = parseFloat(layoutStyle.columnGap || layoutStyle.gap || '24') || 24;
        var editorMinWidth = 300;
        var maxPreviewWidth = Math.max(basePreviewWidth, layoutWidth - gap - editorMinWidth);
        var desiredPreviewWidth = basePreviewWidth * (zoom / 100);
        var previewWidth = Math.min(desiredPreviewWidth, maxPreviewWidth);
        var internalZoom = previewWidth > 0 ? (desiredPreviewWidth / previewWidth) * 100 : zoom;

        previewWrap.style.flexBasis = previewWidth + 'px';
        previewWrap.style.width = previewWidth + 'px';
        scaler.style.width = internalZoom + '%';
    }

    if (zoomSlider && scaler) {
        zoomSlider.addEventListener('input', function () {
            try {
                window.localStorage.setItem(zoomStorageKey, zoomSlider.value);
            } catch (err) {
                /* Ignore unavailable localStorage */
            }
            updateZoomLayout();
        });
        window.addEventListener('resize', updateZoomLayout);

        previewWrap.addEventListener('wheel', function (ev) {
            if (!ev.shiftKey) return;
            ev.preventDefault();

            var currentZoom = parseFloat(zoomSlider.value) || 100;
            var zoomFactor = Math.exp(-ev.deltaY * 0.0016);
            var nextZoom = normalizeZoom(currentZoom * zoomFactor);
            if (nextZoom === currentZoom) return;

            var rect = previewWrap.getBoundingClientRect();
            var pointerX = ev.clientX - rect.left;
            var oldScrollWidth = previewWrap.scrollWidth || 1;
            var relativeX = (previewWrap.scrollLeft + pointerX) / oldScrollWidth;

            setZoom(nextZoom);

            window.requestAnimationFrame(function () {
                var newScrollWidth = previewWrap.scrollWidth || 1;
                var newScrollLeft = relativeX * newScrollWidth - pointerX;
                var maxScrollLeft = Math.max(0, newScrollWidth - previewWrap.clientWidth);
                previewWrap.scrollLeft = clamp(newScrollLeft, 0, maxScrollLeft);
            });
        }, { passive: false });

        try {
            var savedZoom = window.localStorage.getItem(zoomStorageKey);
            if (savedZoom !== null && savedZoom !== '') {
                zoomSlider.value = String(normalizeZoom(parseFloat(savedZoom)));
            }
        } catch (err) {
            /* Ignore unavailable localStorage */
        }

        updateZoomLayout();
    }

    /* cqw polyfill: use container-type on canvas */
    var canvas = document.getElementById('pkCanvas');
    if (canvas && CSS && CSS.supports && !CSS.supports('font-size','1cqw')) {
        canvas.style.containerType = 'inline-size';
    }
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
