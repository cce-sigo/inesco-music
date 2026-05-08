<?php
require_once __DIR__ . '/includes/config.php';

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
$d     = array_merge($defaults, $saved);
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>INESCO – Konzertplakat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 100%; height: 100%; background: #111; }

        .pk-canvas {
            position: relative;
            display: block;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            font-family: Arial, Helvetica, sans-serif;
            container-type: inline-size;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pk-bg { display: block; width: 100%; height: auto; }

        .pk-overlay { position: absolute; }

        /* --- Partner oben links --- */
        .pk-partner {
            top: 13%; left: 2%;
            width: 28%;
            display: flex; flex-direction: column;
            align-items: center; gap: 1cqw;
            text-align: center;
        }
        .pk-partner-logo { max-width: 80%; max-height: 10cqw; object-fit: contain; }
        .pk-partner-text { display: flex; flex-direction: column; align-items: center; gap: .5cqw; }
        .pk-partner-name { font-weight: 700; font-size: 1.8cqw; color: #1a1a1a; line-height: 1.2; }
        .pk-partner-sub  { font-size: 1.6cqw; color: #1a1a1a; line-height: 1.2; }

        /* --- Event-Untertitel oben mitte --- */
        .pk-eventsub {
            top: 20%; left: 30%; width: 38%;
            text-align: center;
            font-size: 1.7cqw; font-weight: 600; color: #1a1a1a;
        }

        /* --- Weißer Kreis Datum/Zeit --- */
        .pk-circle {
            top: 33%; left: 17%; width: 28%;
            aspect-ratio: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; color: #1a1a1a;
        }
        .pk-weekday { font-size: 2.5cqw; font-weight: 700; line-height: 1.3; }
        .pk-date    { font-size: 2.8cqw; font-weight: 900; line-height: 1.2; }
        .pk-time    { font-size: 2.5cqw; font-weight: 700; line-height: 1.3; }

        /* --- Venue rechts --- */
        .pk-venue {
            top: 48%; right: 3%; width: 36%;
            display: flex; flex-direction: column;
            align-items: center; text-align: center; gap: 1cqw;
        }
        .pk-venue-l1 { display: block; font-size: 1.9cqw; color: #fff; font-weight: 600; line-height: 1.3; }
        .pk-venue-l2 { display: block; font-size: 1.7cqw; color: #fff; line-height: 1.3; }
        .pk-venue-logo { margin-top: 1cqw; max-width: 45%; max-height: 8cqw; object-fit: contain; }

        /* Print – single page, canvas fills exactly the page */
        @page { size: A3 portrait; margin: 0; }
        @media print {
            html, body {
                width: 100%; height: 100%;
                margin: 0; padding: 0;
                background: white !important;
                overflow: hidden;
            }
            .no-print { display: none !important; }
            .pk-canvas {
                display: block;
                position: fixed;
                inset: 0;
                width: 100% !important;
                max-width: none !important;
                height: 100% !important;
                margin: 0;
                container-type: inline-size;
            }
            /* Stretch bg image to fill the page so overlay % positions stay aligned */
            .pk-bg {
                position: absolute;
                inset: 0;
                width: 100% !important;
                height: 100% !important;
                object-fit: fill;
            }
        }

        .no-print {
            display: flex; justify-content: center; gap: 12px;
            padding: 16px; flex-wrap: wrap;
        }
        .no-print a, .no-print button {
            background: #d4a85a; color: #1a0a0f;
            border: none; border-radius: 6px;
            padding: 10px 22px; font: bold 14px Arial, sans-serif;
            cursor: pointer; text-decoration: none;
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Drucken / Als PDF speichern</button>
    <a href="<?= e(url('admin/plakat.php')) ?>">← Zurück zum Editor</a>
</div>

<div class="pk-canvas">
    <img class="pk-bg" src="<?= e(url('images/Plakat-INESCO_neutral.png')) ?>" alt="Plakat">

    <?php if ($d['partner_logo'] && $d['partner_logo_visible'] !== '0'): ?>
    <div class="pk-overlay" style="top:<?= e($d['partner_logo_top']) ?>%;left:<?= e($d['partner_logo_left']) ?>%;width:<?= e($d['partner_logo_width']) ?>%">
        <img style="display:block;width:100%;height:auto;object-fit:contain"
             src="<?= e(url($d['partner_logo'])) ?>" alt="<?= e($d['partner_name']) ?>">
    </div>
    <?php endif; ?>
    <?php if (($d['partner_name'] && $d['partner_name_visible'] !== '0') || ($d['partner_subtitle'] && $d['partner_sub_visible'] !== '0')): ?>
    <div class="pk-overlay pk-partner"
         style="top:<?= e($d['partner_top']) ?>%;left:<?= e($d['partner_left']) ?>%;width:<?= e($d['partner_width']) ?>%;font-family:<?= e($d['partner_font']) ?>;text-align:<?= e($d['partner_align']) ?>;align-items:<?= e($d['partner_align'] === 'left' ? 'flex-start' : ($d['partner_align'] === 'right' ? 'flex-end' : 'center')) ?>">
        <div class="pk-partner-text">
            <?php if ($d['partner_name'] && $d['partner_name_visible'] !== '0'): ?>
                <span class="pk-partner-name" style="font-size:<?= e($d['partner_name_size']) ?>cqw;font-weight:<?= e($d['partner_name_weight']) ?>"><?= e($d['partner_name']) ?></span>
            <?php endif; ?>
            <?php if ($d['partner_subtitle'] && $d['partner_sub_visible'] !== '0'): ?>
                <span class="pk-partner-sub" style="font-size:<?= e($d['partner_sub_size']) ?>cqw;font-weight:<?= e($d['partner_sub_weight']) ?>"><?= e($d['partner_subtitle']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($d['event_subtitle'] && $d['eventsub_visible'] !== '0'): ?>
    <div class="pk-overlay pk-eventsub"
         style="top:<?= e($d['eventsub_top']) ?>%;left:<?= e($d['eventsub_left']) ?>%;width:<?= e($d['eventsub_width']) ?>%;font-size:<?= e($d['eventsub_size']) ?>cqw;font-weight:<?= e($d['eventsub_weight']) ?>;font-family:<?= e($d['eventsub_font']) ?>;text-align:<?= e($d['eventsub_align']) ?>"><?= e($d['event_subtitle']) ?></div>
    <?php endif; ?>

    <div class="pk-overlay pk-circle"
         style="top:<?= e($d['circle_top']) ?>%;left:<?= e($d['circle_left']) ?>%;width:<?= e($d['circle_width']) ?>%;font-family:<?= e($d['circle_font']) ?>;text-align:<?= e($d['circle_align']) ?>;align-items:<?= e($d['circle_align'] === 'left' ? 'flex-start' : ($d['circle_align'] === 'right' ? 'flex-end' : 'center')) ?>">
        <?php if ($d['weekday_visible'] !== '0'): ?>
        <span class="pk-weekday" style="font-size:<?= e($d['weekday_size']) ?>cqw;font-weight:<?= e($d['weekday_weight']) ?>"><?= e($d['weekday']) ?></span>
        <?php endif; ?>
        <?php if ($d['date_visible'] !== '0'): ?>
        <span class="pk-date"    style="font-size:<?= e($d['date_size']) ?>cqw;font-weight:<?= e($d['date_weight']) ?>"><?= e($d['date']) ?></span>
        <?php endif; ?>
        <?php if ($d['time_visible'] !== '0'): ?>
        <span class="pk-time"    style="font-size:<?= e($d['time_size']) ?>cqw;font-weight:<?= e($d['time_weight']) ?>"><?= e($d['time']) ?></span>
        <?php endif; ?>
    </div>

    <div class="pk-overlay pk-venue"
         style="top:<?= e($d['venue_top']) ?>%;right:<?= e($d['venue_right']) ?>%;width:<?= e($d['venue_width']) ?>%;font-family:<?= e($d['venue_font']) ?>;text-align:<?= e($d['venue_align']) ?>;align-items:<?= e($d['venue_align'] === 'left' ? 'flex-start' : ($d['venue_align'] === 'right' ? 'flex-end' : 'center')) ?>">
        <?php if ($d['venue_l1_visible'] !== '0'): ?>
        <span class="pk-venue-l1" style="font-size:<?= e($d['venue_l1_size']) ?>cqw;font-weight:<?= e($d['venue_l1_weight']) ?>"><?= e($d['venue_line1']) ?></span>
        <?php endif; ?>
        <?php if ($d['venue_l2_visible'] !== '0'): ?>
        <span class="pk-venue-l2" style="font-size:<?= e($d['venue_l2_size']) ?>cqw;font-weight:<?= e($d['venue_l2_weight']) ?>"><?= e($d['venue_line2']) ?></span>
        <?php endif; ?>
    </div>
    <?php if ($d['venue_logo'] && $d['venue_logo_visible'] !== '0'): ?>
    <div class="pk-overlay" style="top:<?= e($d['venue_logo_top']) ?>%;right:<?= e($d['venue_logo_right']) ?>%;width:<?= e($d['venue_logo_width']) ?>%">
        <img style="display:block;width:100%;height:auto;object-fit:contain"
             src="<?= e(url($d['venue_logo'])) ?>" alt="<?= e($d['venue_line1']) ?>">
    </div>
    <?php endif; ?>
</div>

</body>
</html>
