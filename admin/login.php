<?php
require_once __DIR__ . '/../includes/config.php';

$users = read_json('users')['users'] ?? [];
$needsSetup = empty($users);
$error = null;

// Setup: ersten Admin anlegen, wenn keine User existieren
if ($needsSetup && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sicherheitstoken ungültig.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $p2 = (string)($_POST['password2'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u))      $error = 'Benutzername ungültig (3–40 Zeichen, A–Z, 0–9, _ . -).';
        elseif (mb_strlen($p) < 8)                             $error = 'Passwort muss mind. 8 Zeichen haben.';
        elseif ($p !== $p2)                                    $error = 'Passwörter stimmen nicht überein.';
        else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            write_json('users', ['users' => [[
                'username' => $u,
                'password' => $hash,
                'created'  => date('c'),
            ]]]);
            flash_set('ok', 'Admin angelegt. Bitte einloggen.');
            redirect(url('admin/login.php'));
        }
    }
}

// Login
if (!$needsSetup && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sicherheitstoken ungültig.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $found = null;
        foreach ($users as $usr) {
            if (hash_equals((string)$usr['username'], $u)) { $found = $usr; break; }
        }
        // Kleine Verzögerung gegen Brute Force
        usleep(300000);
        if ($found && password_verify($p, $found['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $found['username'];
            redirect(url('admin/index.php'));
        } else {
            $error = 'Ungültige Zugangsdaten.';
        }
    }
}

$flash = flash_pop();
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin · INESCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= e(url('admin/style.css')) ?>">
</head>
<body class="login-body">
    <main class="login-card">
        <h1>INESCO Admin</h1>
        <?php foreach ($flash as $f): ?>
            <p class="msg <?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
        <?php endforeach; ?>
        <?php if ($error): ?><p class="msg err"><?= e($error) ?></p><?php endif; ?>

        <?php if ($needsSetup): ?>
            <p class="hint">Erste Einrichtung: Bitte Admin-Konto anlegen.</p>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label>Benutzername<input type="text" name="username" required autofocus></label>
                <label>Passwort<input type="password" name="password" required minlength="8"></label>
                <label>Passwort wiederholen<input type="password" name="password2" required minlength="8"></label>
                <button class="btn-primary" type="submit">Konto anlegen</button>
            </form>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <label>Benutzername<input type="text" name="username" required autofocus></label>
                <label>Passwort<input type="password" name="password" required></label>
                <button class="btn-primary" type="submit">Einloggen</button>
            </form>
        <?php endif; ?>

        <p class="back"><a href="<?= e(url('')) ?>">&larr; Zur Website</a></p>
    </main>
</body>
</html>
