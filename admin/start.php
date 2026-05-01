<?php
// Leitet auf Dashboard / Login um
require_once __DIR__ . '/../includes/config.php';
if (is_logged_in()) {
    header('Location: ' . url('admin/index.php'));
} else {
    header('Location: ' . url('admin/login.php'));
}
