<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Dashboard';

$content  = read_json('content');
$members  = read_json('members');
$tracks   = read_json('tracks');
$videos   = read_json('videos');
$concerts = read_json('concerts');

include __DIR__ . '/header.php';
?>
<h1>Willkommen, <?= e($_SESSION['admin_user']) ?></h1>
<p>Verwalten Sie hier alle Inhalte der INESCO-Website.</p>

<div class="cards">
    <a class="card" href="content.php"><h3>Texte</h3><p>Hero, Bio &amp; Site-Info</p></a>
    <a class="card" href="members.php"><h3>Mitglieder</h3><p><?= count($members) ?> Profile</p></a>
    <a class="card" href="tracks.php"><h3>Audio</h3><p><?= count($tracks) ?> Tracks</p></a>
    <a class="card" href="videos.php"><h3>Videos</h3><p><?= count($videos) ?> Videos</p></a>
    <a class="card" href="concerts.php"><h3>Konzerte</h3><p><?= count($concerts) ?> Termine</p></a>
    <a class="card" href="contact.php"><h3>Kontakt</h3><p>E-Mail, Telefon, Social</p></a>
</div>

<?php include __DIR__ . '/footer.php'; ?>
