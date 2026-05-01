<?php
require_once __DIR__ . '/includes/config.php';

$content = read_json('content');
$contact = read_json('contact');

include __DIR__ . '/includes/header.php';
?>

<section class="section section-legal" id="impressum">
    <div class="container legal-content">
        <h1 class="section-title">Impressum</h1>
        <p class="muted">
            Offenlegung gemäß § 5 E-Commerce-Gesetz (ECG), § 14 Unternehmensgesetzbuch (UGB)
            sowie § 25 Mediengesetz (MedienG).
        </p>

        <h2>Medieninhaber, Herausgeber und Diensteanbieter</h2>
        <?php
            $L = $contact['legal'] ?? [];
            $owner   = $L['owner']   ?? '';
            $street  = $L['street']  ?? '';
            $zip     = $L['zip']     ?? '';
            $city    = $L['city']    ?? '';
            $country = $L['country'] ?? 'Österreich';
        ?>
        <p>
            <strong>INESCO – Ines &amp; Sigo</strong><br>
            <?php if ($owner): ?><?= e($owner) ?><br><?php endif; ?>
            <?php if ($street): ?><?= e($street) ?><br><?php endif; ?>
            <?php if ($zip || $city): ?><?= e(trim($zip . ' ' . $city)) ?><?php if ($country): ?>, <?= e($country) ?><?php endif; ?><?php endif; ?>
        </p>

        <h2>Kontakt</h2>
        <p>
            <?php if (!empty($contact['email'])): ?>
                E-Mail: <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><br>
            <?php endif; ?>
            <?php if (!empty($contact['phone'])): ?>
                Telefon: <a href="tel:<?= e(preg_replace('/\s+/', '', $contact['phone'])) ?>"><?= e($contact['phone']) ?></a>
            <?php endif; ?>
        </p>

        <h2>Anwendbare Rechtsvorschriften</h2>
        <p>
            Gewerbeordnung (GewO), E-Commerce-Gesetz (ECG), Mediengesetz (MedienG),
            Unternehmensgesetzbuch (UGB), Datenschutz-Grundverordnung (DSGVO),
            Datenschutzgesetz (DSG) – jeweils in geltender Fassung.<br>
            Einsehbar unter <a href="https://www.ris.bka.gv.at" target="_blank" rel="noopener">www.ris.bka.gv.at</a>.
        </p>

        <h2>Online-Streitbeilegung (OS-Plattform)</h2>
        <p>
            Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
            <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener">https://ec.europa.eu/consumers/odr/</a>.
            Wir sind nicht verpflichtet und nicht bereit, an Streitbeilegungsverfahren vor einer
            Verbraucherschlichtungsstelle teilzunehmen.
        </p>

        <h2>Urheberrecht</h2>
        <p>
            Alle Inhalte dieser Website (Texte, Bilder, Audio- und Videodateien, Layout) sind
            urheberrechtlich geschützt. Eine Verwendung – insbesondere Vervielfältigung,
            Verbreitung oder öffentliche Zugänglichmachung – bedarf der vorherigen
            schriftlichen Zustimmung der Rechteinhaber:innen.
        </p>

        <h2>Haftungsausschluss</h2>
        <p>
            Inhalte dieser Website werden mit größtmöglicher Sorgfalt erstellt. Für die
            Richtigkeit, Vollständigkeit und Aktualität der Inhalte kann jedoch keine Gewähr
            übernommen werden. Für Inhalte externer verlinkter Websites sind ausschließlich
            deren Betreiber:innen verantwortlich.
        </p>

        <p class="muted"><a href="<?= e(url('')) ?>">&larr; Zurück zur Startseite</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
