<?php
require_once __DIR__ . '/includes/config.php';

$content = read_json('content');
$contact = read_json('contact');

include __DIR__ . '/includes/header.php';
?>

<section class="section section-legal" id="datenschutz">
    <div class="container legal-content">
        <h1 class="section-title">Datenschutzerklärung</h1>
        <p class="muted">
            Information gemäß Art. 13 und 14 Datenschutz-Grundverordnung (DSGVO) sowie
            österreichischem Datenschutzgesetz (DSG).
        </p>

        <h2>1. Verantwortlicher</h2>
        <p>
            Verantwortlich für die Datenverarbeitung auf dieser Website im Sinne des Art. 4 Z 7 DSGVO ist:
        </p>
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
            <?php if ($zip || $city): ?><?= e(trim($zip . ' ' . $city)) ?><?php if ($country): ?>, <?= e($country) ?><?php endif; ?><br><?php endif; ?>
            <?php if (!empty($contact['email'])): ?>
                E-Mail: <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><br>
            <?php endif; ?>
            <?php if (!empty($contact['phone'])): ?>
                Telefon: <a href="tel:<?= e(preg_replace('/\s+/', '', $contact['phone'])) ?>"><?= e($contact['phone']) ?></a>
            <?php endif; ?>
        </p>

        <h2>2. Allgemeines zur Datenverarbeitung</h2>
        <p>
            Wir verarbeiten personenbezogene Daten ausschließlich auf Grundlage der gesetzlichen
            Bestimmungen (DSGVO, DSG, TKG 2003). Personenbezogene Daten sind alle Informationen,
            die sich auf eine identifizierte oder identifizierbare natürliche Person beziehen.
        </p>

        <h2>3. Bereitstellung der Website &amp; Server-Logfiles</h2>
        <p>
            Beim Aufruf dieser Website werden durch den Webserver automatisch technische Daten
            in sogenannten Logfiles gespeichert:
        </p>
        <ul>
            <li>IP-Adresse des aufrufenden Geräts (gekürzt/anonymisiert, sofern technisch möglich)</li>
            <li>Datum und Uhrzeit des Zugriffs</li>
            <li>aufgerufene Seite / Datei sowie HTTP-Statuscode</li>
            <li>übertragene Datenmenge</li>
            <li>verwendeter Browser, Betriebssystem und Referrer-URL</li>
        </ul>
        <p>
            <strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an
            einem stabilen, sicheren Betrieb der Website und an der Abwehr von Angriffen).<br>
            <strong>Speicherdauer:</strong> in der Regel maximal 30 Tage, danach Löschung bzw.
            Anonymisierung. Bei sicherheitsrelevanten Vorfällen längere Aufbewahrung zulässig.
        </p>

        <h2>4. Kontaktformular und E-Mail-Kontakt</h2>
        <p>
            Wenn Sie uns über das Kontaktformular oder per E-Mail kontaktieren, verarbeiten wir
            die von Ihnen angegebenen Daten (insbesondere Name, E-Mail-Adresse und Ihre Nachricht)
            ausschließlich zur Bearbeitung Ihrer Anfrage und – sofern relevant – zur Abwicklung
            einer möglichen Buchung.
        </p>
        <p>
            <strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Anbahnung/Erfüllung eines
            Vertrages) bzw. Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Beantwortung
            von Anfragen).<br>
            <strong>Speicherdauer:</strong> bis zur abschließenden Beantwortung der Anfrage; bei
            vertraglichen oder steuerrelevanten Vorgängen bis zum Ablauf der gesetzlichen
            Aufbewahrungsfristen (in Österreich grundsätzlich 7 Jahre gemäß § 132 BAO).
        </p>

        <h2>5. Cookies und Session</h2>
        <p>
            Diese Website verwendet ausschließlich technisch notwendige Cookies (z.&nbsp;B. ein
            Session-Cookie zur Absicherung von Formular-Übermittlungen mittels CSRF-Token).
            Diese Cookies sind für den Betrieb der Website erforderlich und werden nach
            Beendigung der Browser-Sitzung gelöscht.
        </p>
        <p>
            <strong>Rechtsgrundlage:</strong> § 165 Abs. 3 TKG 2021 sowie Art. 6 Abs. 1 lit. f DSGVO.
            Eine Einwilligung ist für rein technisch notwendige Cookies nicht erforderlich.
        </p>

        <h2>6. Schriftarten von Google Fonts</h2>
        <p>
            Diese Website lädt Schriftarten von Google Fonts (Anbieter: Google Ireland Limited,
            Gordon House, Barrow Street, Dublin 4, Irland). Beim Aufruf der Seite stellt Ihr
            Browser eine Verbindung zu Servern von Google her, wodurch Ihre IP-Adresse an Google
            übermittelt werden kann.
        </p>
        <p>
            <strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an
            einer einheitlichen, ansprechenden Darstellung der Inhalte).<br>
            Weitere Informationen:
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>.
        </p>

        <h2>7. Eingebettete Inhalte (YouTube/Vimeo)</h2>
        <p>
            Diese Website kann Videos einbetten, die auf externen Plattformen (z.&nbsp;B. YouTube,
            Vimeo) gehostet sind. Beim Abspielen eines solchen Videos werden Daten an den
            jeweiligen Anbieter übertragen. Wir haben keinen Einfluss auf Art und Umfang dieser
            Datenverarbeitung.
        </p>
        <ul>
            <li>YouTube – Google Ireland Limited:
                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>
            </li>
            <li>Vimeo – Vimeo.com, Inc.:
                <a href="https://vimeo.com/privacy" target="_blank" rel="noopener">vimeo.com/privacy</a>
            </li>
        </ul>
        <p><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. f DSGVO.</p>

        <h2>8. Social-Media-Verlinkungen</h2>
        <p>
            Auf dieser Website befinden sich gegebenenfalls Verlinkungen zu unseren
            Social-Media-Profilen (z.&nbsp;B. Instagram, Facebook, YouTube). Es handelt sich um
            einfache Hyperlinks – eine Datenübertragung an die Anbieter erfolgt erst, wenn Sie
            den jeweiligen Link aktiv anklicken.
        </p>

        <h2>9. Empfänger und Auftragsverarbeiter</h2>
        <p>
            Personenbezogene Daten werden an folgende Kategorien von Empfängern weitergegeben,
            soweit dies für die jeweilige Verarbeitung erforderlich ist:
        </p>
        <ul>
            <li>Hosting-Provider (Bereitstellung der Website und des E-Mail-Versands)</li>
            <li>Steuerberatung sowie Behörden, soweit gesetzlich vorgeschrieben</li>
        </ul>
        <p>
            Mit Auftragsverarbeitern wurden Verträge gemäß Art. 28 DSGVO abgeschlossen.
            Eine Übermittlung in Drittstaaten (außerhalb EU/EWR) findet nur statt, wenn ein
            Angemessenheitsbeschluss oder geeignete Garantien gemäß Kapitel V DSGVO vorliegen.
        </p>

        <h2>10. Ihre Rechte als betroffene Person</h2>
        <p>Ihnen stehen nach der DSGVO insbesondere folgende Rechte zu:</p>
        <ul>
            <li>Recht auf Auskunft (Art. 15 DSGVO)</li>
            <li>Recht auf Berichtigung (Art. 16 DSGVO)</li>
            <li>Recht auf Löschung (Art. 17 DSGVO)</li>
            <li>Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
            <li>Recht auf Datenübertragbarkeit (Art. 20 DSGVO)</li>
            <li>Widerspruchsrecht (Art. 21 DSGVO)</li>
            <li>Recht auf Widerruf einer Einwilligung (Art. 7 Abs. 3 DSGVO)</li>
        </ul>
        <p>
            Zur Ausübung Ihrer Rechte genügt eine formlose Mitteilung an die oben genannte
            Kontaktadresse.
        </p>

        <h2>11. Beschwerderecht bei der Aufsichtsbehörde</h2>
        <p>
            Sie haben das Recht, sich bei der Datenschutzbehörde über die Verarbeitung Ihrer
            personenbezogenen Daten zu beschweren:
        </p>
        <p>
            <strong>Österreichische Datenschutzbehörde</strong><br>
            Barichgasse 40–42, 1030 Wien<br>
            Telefon: +43 1 52 152-0<br>
            E-Mail: <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a><br>
            Web: <a href="https://www.dsb.gv.at" target="_blank" rel="noopener">www.dsb.gv.at</a>
        </p>

        <h2>12. Datensicherheit</h2>
        <p>
            Diese Website verwendet zum Schutz der Übertragung Ihrer Daten eine
            SSL-/TLS-Verschlüsselung. Wir treffen darüber hinaus angemessene technische und
            organisatorische Maßnahmen, um Ihre Daten gegen unbefugten Zugriff, Verlust oder
            Manipulation zu schützen.
        </p>

        <h2>13. Aktualität dieser Datenschutzerklärung</h2>
        <p>
            Diese Datenschutzerklärung ist aktuell gültig. Durch Weiterentwicklung der Website
            oder geänderte gesetzliche Vorgaben kann eine Anpassung erforderlich werden. Die
            jeweils aktuelle Fassung kann jederzeit auf dieser Seite abgerufen werden.
        </p>
        <p class="muted">Stand: <?= e(date('d.m.Y')) ?></p>

        <p class="muted"><a href="<?= e(url('')) ?>">&larr; Zurück zur Startseite</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
