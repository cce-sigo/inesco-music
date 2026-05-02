<?php
require_once __DIR__ . '/includes/config.php';

$content  = read_json('content');
$members  = read_json('members');
$tracks   = read_json('tracks');
$videos   = read_json('videos');
$concerts = sort_concerts(read_json('concerts'));
$impressions = read_json('impressions');
$sponsors = read_json('sponsors');
$contact  = read_json('contact');

// Hero-Bilder vorbereiten (vor Header, damit Preload möglich ist)
$heroMode     = $content['hero']['mode']     ?? 'slideshow'; // slideshow|random|static
$heroInterval = max(2, (int)($content['hero']['interval'] ?? 6));
$heroFallback = 'assets/img/band.png';
$heroImgs     = hero_images($heroMode === 'random');
if ($heroMode === 'random' || $heroMode === 'static') {
    $heroImgs = $heroImgs ? [$heroImgs[0]] : [$heroFallback];
} elseif (empty($heroImgs)) {
    $heroImgs = [$heroFallback];
}
$heroPreload = ltrim($heroImgs[0], '/');

// Medien (Impressionen + Videos) zum Vorab-Laden für flüssige Darstellung
$mediaPrefetch = [];
foreach ($impressions as $im) {
    $t   = $im['type'] ?? '';
    $src = (string)($im['src'] ?? '');
    if ($src === '') continue;
    if ($t === 'image') {
        $mediaPrefetch[] = ['as' => 'image', 'href' => url($src)];
    } elseif ($t === 'video' && (($im['video_kind'] ?? '') === 'upload')) {
        $mediaPrefetch[] = ['as' => 'video', 'href' => url($src)];
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero" id="hero" data-hero-interval="<?= e((string)($heroInterval * 1000)) ?>">
    <div class="hero-slides">
        <?php foreach ($heroImgs as $i => $img): ?>
            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>"
                 style="background-image:url('<?= e(ltrim($img, '/')) ?>');"></div>
        <?php endforeach; ?>
    </div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <span class="hero-logo">
            <img src="<?= e(main_logo_url()) ?>" alt="INESCO">
        </span>
        <h1><?= e($content['hero']['headline'] ?? 'INESCO') ?> <span class="subline"><?= e($content['hero']['subline'] ?? '') ?></span></h1>
        <p class="slogan"><?= e($content['hero']['slogan'] ?? '') ?></p>
        <div class="cta-row">
            <a class="btn btn-primary" href="<?= e($content['hero']['ctaPrimary']['href'] ?? '#kontakt') ?>">
                <?= e($content['hero']['ctaPrimary']['label'] ?? 'Jetzt buchen') ?>
            </a>
            <a class="btn btn-ghost" href="<?= e($content['hero']['ctaSecondary']['href'] ?? '#musik') ?>">
                <?= e($content['hero']['ctaSecondary']['label'] ?? 'Hör rein') ?>
            </a>
        </div>
    </div>
</section>

<?php if (nav_is_visible('bio')): ?>
<!-- BIO -->
<section class="section section-bio" id="bio">
    <div class="container">
        <h2 class="section-title"><?= e($content['bio']['title'] ?? 'Über INESCO') ?></h2>
        <p class="lead"><?= nl2br(e($content['bio']['text'] ?? '')) ?></p>

        <div class="members">
            <?php foreach ($members as $m): ?>
                <article class="member reveal">
                    <div class="member-photo">
                        <img src="<?= e(url($m['image'] ?? 'assets/img/placeholder.jpg')) ?>" alt="<?= e($m['name'] . ' – ' . $m['role'] . ', INESCO') ?>" loading="lazy">
                    </div>
                    <div class="member-text">
                        <h3><?= e($m['name']) ?></h3>
                        <p class="role"><?= e($m['role']) ?></p>
                        <p><?= nl2br(e($m['bio'])) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('musik')): ?>
<!-- MUSIK -->
<section class="section section-music" id="musik">
    <div class="container">
        <h2 class="section-title">Hörproben</h2>
        <?php if (empty($tracks)): ?>
            <p class="muted">Noch keine Tracks online.</p>
        <?php else: ?>
            <div class="tracks">
                <?php foreach ($tracks as $t): ?>
                    <div class="track reveal">
                        <div class="track-body">
                            <h4><?= e($t['title']) ?></h4>
                            <p class="muted"><?= e($t['artist'] ?? 'INESCO') ?></p>
                            <audio controls preload="none" src="<?= e(url($t['file'])) ?>"></audio>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('videos')): ?>
<!-- VIDEOS -->
<section class="section section-video" id="videos">
    <div class="container">
        <h2 class="section-title">Videos</h2>
        <?php if (empty($videos)): ?>
            <p class="muted">Noch keine Videos online.</p>
        <?php else: ?>
            <div class="videos">
                <?php foreach ($videos as $v): ?>
                    <div class="video reveal">
                        <div class="video-frame">
                            <?php if (($v['type'] ?? '') === 'youtube' || ($v['type'] ?? '') === 'vimeo'):
                                $vsrc = $v['src'];
                                if ($v['type'] === 'youtube' && strpos($vsrc, 'enablejsapi=') === false) {
                                    $vsrc .= (strpos($vsrc, '?') === false ? '?' : '&') . 'enablejsapi=1';
                                }
                                if ($v['type'] === 'vimeo' && strpos($vsrc, 'api=') === false) {
                                    $vsrc .= (strpos($vsrc, '?') === false ? '?' : '&') . 'api=1';
                                }
                            ?>
                                <iframe src="<?= e($vsrc) ?>" title="<?= e($v['title']) ?>"
                                        frameborder="0" loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            <?php else: ?>
                                <video controls preload="none" src="<?= e(url($v['src'])) ?>"></video>
                            <?php endif; ?>
                        </div>
                        <h4><?= e($v['title']) ?></h4>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('impressionen')): ?>
<!-- IMPRESSIONEN -->
<section class="section section-impressions" id="impressionen">
    <div class="container">
        <h2 class="section-title">Impressionen</h2>
        <?php if (empty($impressions)): ?>
            <p class="muted">Bald gibt es hier Eindrücke von unseren Live-Auftritten.</p>
        <?php else: ?>
            <div class="impressions">
                <?php foreach ($impressions as $idx => $im):
                    $type = $im['type'] ?? 'text';
                    $cap  = trim((string)($im['caption'] ?? ''));
                    $eager = $idx < 3;
                ?>
                    <article class="impression impression-<?= e($type) ?> reveal">
                        <?php if ($type === 'image' && !empty($im['src'])): ?>
                            <div class="impression-media">
                                <img src="<?= e(url($im['src'])) ?>" alt="<?= e($cap) ?>"
                                     loading="<?= $eager ? 'eager' : 'lazy' ?>" decoding="async"
                                     <?= $eager ? 'fetchpriority="high"' : '' ?>>
                            </div>
                            <?php if ($cap !== ''): ?><p class="impression-caption"><?= e($cap) ?></p><?php endif; ?>
                        <?php elseif ($type === 'video' && !empty($im['src'])): ?>
                            <div class="impression-media video-frame">
                                <?php $kind = $im['video_kind'] ?? 'upload'; ?>
                                <?php if ($kind === 'youtube' || $kind === 'vimeo'):
                                    $imSrc = $im['src'];
                                    if ($kind === 'youtube' && strpos($imSrc, 'enablejsapi=') === false) {
                                        $imSrc .= (strpos($imSrc, '?') === false ? '?' : '&') . 'enablejsapi=1';
                                    }
                                    if ($kind === 'vimeo' && strpos($imSrc, 'api=') === false) {
                                        $imSrc .= (strpos($imSrc, '?') === false ? '?' : '&') . 'api=1';
                                    }
                                ?>
                                    <iframe src="<?= e($imSrc) ?>" title="<?= e($cap) ?>"
                                            frameborder="0" loading="lazy"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen></iframe>
                                <?php else: ?>
                                    <video controls preload="metadata" playsinline
                                           src="<?= e(url($im['src'])) ?>#t=0.1"></video>
                                <?php endif; ?>
                            </div>
                            <?php if ($cap !== ''): ?><p class="impression-caption"><?= e($cap) ?></p><?php endif; ?>
                        <?php else: ?>
                            <blockquote class="impression-text">
                                <p><?= nl2br(e((string)($im['text'] ?? ''))) ?></p>
                                <?php if ($cap !== ''): ?><cite>— <?= e($cap) ?></cite><?php endif; ?>
                            </blockquote>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('konzerte')): ?>
<!-- KONZERTE -->
<section class="section section-concerts" id="konzerte">
    <div class="container">
        <h2 class="section-title">Konzerte</h2>
        <?php if (empty($concerts)): ?>
            <p class="muted">Aktuell keine Termine angekündigt – wir melden uns bald wieder.</p>
        <?php else: ?>
            <ul class="concert-list">
                <?php foreach ($concerts as $c): ?>
                    <li class="concert reveal <?= ($c['date'] < date('Y-m-d')) ? 'past' : '' ?>">
                        <div class="concert-date">
                            <span class="day"><?= e(date('d', strtotime($c['date']))) ?></span>
                            <span class="month"><?= e(strtoupper(strftime_safe($c['date']))) ?></span>
                            <span class="year"><?= e(date('Y', strtotime($c['date']))) ?></span>
                        </div>
                        <div class="concert-info">
                            <h4><?= e($c['venue']) ?> · <?= e($c['city']) ?></h4>
                            <p class="muted"><?= e($c['time'] ?? '') ?> Uhr</p>
                            <p><?= e($c['description']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('kontakt')): ?>
<!-- KONTAKT -->
<section class="section section-contact" id="kontakt">
    <div class="container contact-grid">
        <div>
            <h2 class="section-title">Kontakt &amp; Buchung</h2>
            <p>Sie möchten INESCO für Ihre Veranstaltung buchen? Schreiben Sie uns – wir melden uns zeitnah zurück.</p>
            <ul class="contact-info">
                <?php if (!empty($contact['email'])): ?>
                    <li>✉ <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a></li>
                <?php endif; ?>
                <?php if (!empty($contact['phone'])): ?>
                    <li>☎ <a href="tel:<?= e(preg_replace('/\s+/', '', $contact['phone'])) ?>"><?= e($contact['phone']) ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
        <form class="contact-form" id="contactForm" action="<?= e(url('api/contact.php')) ?>" method="post" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="ts" value="<?= time() ?>">
            <div class="hp" aria-hidden="true">
                <label>Website (bitte freilassen)
                    <input type="text" name="website" tabindex="-1" autocomplete="off" value="">
                </label>
                <label>URL
                    <input type="text" name="hp_url" tabindex="-1" autocomplete="off" value="">
                </label>
            </div>
            <label>Name
                <input type="text" name="name" required maxlength="100">
            </label>
            <label>E-Mail
                <input type="email" name="email" required maxlength="150">
            </label>
            <label>Nachricht
                <textarea name="message" rows="6" required maxlength="3000"></textarea>
            </label>
            <button type="submit" class="btn btn-primary">Nachricht senden</button>
            <p class="form-status" role="status" aria-live="polite"></p>
        </form>
    </div>
</section>
<?php endif; ?>

<?php if (nav_is_visible('partner')): ?>
<!-- FREUNDE & SPONSOREN -->
<section class="section section-partner" id="partner">
    <div class="container">
        <h2 class="section-title">Freunde &amp; Sponsoren</h2>

        <?php
        $partner_freunde   = array_values(array_filter($sponsors, fn($s) => ($s['group'] ?? '') === 'freunde'   && ($s['visible'] ?? true)));
        $partner_sponsoren = array_values(array_filter($sponsors, fn($s) => ($s['group'] ?? '') === 'sponsoren' && ($s['visible'] ?? true)));
        ?>

        <?php if (!empty($partner_freunde)): ?>
        <h3 class="partner-group-title">Freunde</h3>
        <div class="partner-grid partner-grid--friends">
            <?php foreach ($partner_freunde as $sp): ?>
                <article class="partner-card partner-card--friend reveal">
                    <?php if (!empty($sp['logo'])): ?>
                        <div class="partner-logo">
                            <img src="<?= e(url($sp['logo'])) ?>" alt="<?= e($sp['name']) ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="partner-info">
                        <?php if (!empty($sp['url'])): ?>
                            <a class="partner-name" href="<?= e($sp['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= e($sp['name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="partner-name"><?= e($sp['name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sp['text'])): ?>
                            <p class="partner-text"><?= nl2br(e($sp['text'])) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($partner_sponsoren)): ?>
        <h3 class="partner-group-title">Sponsoren</h3>
        <div class="partner-grid partner-grid--sponsors">
            <?php foreach ($partner_sponsoren as $sp): ?>
                <article class="partner-card partner-card--sponsor reveal">
                    <?php if (!empty($sp['logo'])): ?>
                        <div class="partner-logo">
                            <?php if (!empty($sp['url'])): ?>
                                <a href="<?= e($sp['url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?= e(url($sp['logo'])) ?>" alt="<?= e($sp['name']) ?>" loading="lazy">
                                </a>
                            <?php else: ?>
                                <img src="<?= e(url($sp['logo'])) ?>" alt="<?= e($sp['name']) ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="partner-info">
                        <?php if (!empty($sp['url'])): ?>
                            <a class="partner-name" href="<?= e($sp['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?= e($sp['name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="partner-name"><?= e($sp['name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sp['text'])): ?>
                            <p class="partner-text"><?= nl2br(e($sp['text'])) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($partner_freunde) && empty($partner_sponsoren)): ?>
            <p class="muted">Bald stellen wir hier unsere Partner vor.</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';

// Lokale Hilfsfunktion für Monats-Kürzel
function strftime_safe(string $iso): string {
    $m = (int)date('n', strtotime($iso));
    $names = [1=>'Jan',2=>'Feb',3=>'Mär',4=>'Apr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Dez'];
    return $names[$m] ?? '';
}
