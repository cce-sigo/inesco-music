<?php /** @var array $contact */ ?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <strong>INESCO</strong><br>
            Soul · Blues · Gypsy-Jazz · Latin-Rock
        </div>
        <div>
            <?php if (!empty($contact['email'])): ?>
                <a href="mailto:<?= e($contact['email']) ?>"><?= e($contact['email']) ?></a><br>
            <?php endif; ?>
            <?php if (!empty($contact['phone'])): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $contact['phone'])) ?>"><?= e($contact['phone']) ?></a>
            <?php endif; ?>
        </div>
        <div class="socials">
            <?php
            $socialIcons = [
                'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.7c0-.9.3-1.6 1.6-1.6h1.6V4.2C16.4 4.1 15.5 4 14.5 4c-2.2 0-3.7 1.3-3.7 3.8v3H8v3.2h2.8V22h2.7z"/></svg>',
                'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2 0 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.2.1 1.6.1 4.8s0 3.6-.1 4.8c0 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.2.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2 0-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c0-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.3.8-.4.4-.6.8-.8 1.3-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.3.4.4.8.6 1.3.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.3-.8.4-.4.6-.8.8-1.3.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.3-.4-.4-.8-.6-1.3-.8-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 1.8a3.1 3.1 0 1 0 0 6.2 3.1 3.1 0 0 0 0-6.2zm5.1-2.1a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3z"/></svg>',
                'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23 7.2s-.2-1.6-.9-2.3c-.9-.9-1.8-.9-2.3-1C16.4 3.5 12 3.5 12 3.5s-4.4 0-7.8.4c-.5.1-1.4.1-2.3 1C1.2 5.6 1 7.2 1 7.2S.8 9.1.8 11v1.9c0 1.9.2 3.8.2 3.8s.2 1.6.9 2.3c.9.9 2.1.9 2.6 1 1.9.2 8 .3 8 .3s4.4 0 7.8-.4c.5-.1 1.4-.1 2.3-1 .7-.7.9-2.3.9-2.3s.2-1.9.2-3.8V11c0-1.9-.7-3.8-.7-3.8zM9.7 15V8.4l5.7 3.3L9.7 15z"/></svg>',
                'tiktok'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21 8.4a7.4 7.4 0 0 1-4.4-1.4v6.8a5.7 5.7 0 1 1-5.7-5.7c.3 0 .6 0 .9.1v3a2.7 2.7 0 1 0 1.9 2.6V2h2.9a4.6 4.6 0 0 0 4.4 4.4v2z"/></svg>',
                'twitter'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.9 3H22l-7.5 8.6L23 21h-6.8l-5.3-6.9L4.7 21H1.6l8-9.2L1 3h7l4.8 6.3L18.9 3zm-2.4 16.2h1.9L7.6 4.7H5.5l11 14.5z"/></svg>',
                'x'         => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.9 3H22l-7.5 8.6L23 21h-6.8l-5.3-6.9L4.7 21H1.6l8-9.2L1 3h7l4.8 6.3L18.9 3zm-2.4 16.2h1.9L7.6 4.7H5.5l11 14.5z"/></svg>',
                'spotify'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm4.6 14.4c-.2.3-.6.4-.9.2-2.5-1.5-5.6-1.9-9.3-1-.4.1-.7-.2-.8-.5-.1-.4.2-.7.5-.8 4-.9 7.5-.5 10.3 1.2.3.2.4.6.2.9zm1.2-2.7c-.3.4-.7.5-1.1.3-2.8-1.7-7.1-2.2-10.5-1.2-.4.1-.9-.1-1-.6-.1-.4.1-.9.6-1 3.8-1.2 8.6-.6 11.8 1.4.4.2.5.7.2 1.1zm.1-2.8C14.4 8.9 8.5 8.7 5.3 9.7c-.5.2-1.1-.1-1.3-.7-.2-.5.1-1.1.7-1.3 3.7-1.1 10.2-.9 14.2 1.5.5.3.7 1 .4 1.5-.3.4-1 .6-1.4.2z"/></svg>',
                'soundcloud'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M2 14.5a.5.5 0 0 1 1 0V17a.5.5 0 0 1-1 0v-2.5zm2-2a.5.5 0 0 1 1 0V17a.5.5 0 0 1-1 0v-4.5zm2-1a.5.5 0 0 1 1 0V17a.5.5 0 0 1-1 0v-5.5zm2-1a.5.5 0 0 1 1 0V17a.5.5 0 0 1-1 0v-6.5zm2-2a.5.5 0 0 1 1 0V17a.5.5 0 0 1-1 0V8.5zM12 7a.5.5 0 0 1 1 0v10a.5.5 0 0 1-1 0V7zm2.5-.5a4.5 4.5 0 0 1 4.5 4.5h.5a3 3 0 0 1 0 6h-5a.5.5 0 0 1-.5-.5V7a.5.5 0 0 1 .5-.5z"/></svg>',
                'bandcamp'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M2 16l5-10h15l-5 10H2z"/></svg>',
                'apple'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.4 12.6c0-2.6 2.1-3.8 2.2-3.9-1.2-1.7-3-2-3.7-2-1.6-.2-3.1.9-3.9.9-.8 0-2-.9-3.4-.9-1.7 0-3.4 1-4.3 2.6-1.8 3.2-.5 7.9 1.3 10.5.9 1.3 1.9 2.7 3.3 2.6 1.3-.1 1.8-.9 3.4-.9 1.6 0 2 .9 3.4.8 1.4 0 2.3-1.3 3.2-2.6 1-1.5 1.4-2.9 1.4-3 0-.1-2.7-1.1-2.7-4.1zm-2.7-7.6c.7-.9 1.2-2.1 1.1-3.4-1 .1-2.3.7-3 1.6-.6.8-1.3 2.1-1.1 3.3 1.1.1 2.2-.6 3-1.5z"/></svg>',
            ];
            ?>
            <?php foreach (($contact['social'] ?? []) as $name => $href): ?>
                <?php if ($href):
                    $key = strtolower((string)$name);
                    $icon = $socialIcons[$key] ?? '';
                    $label = ucfirst($name);
                ?>
                    <a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>" title="<?= e($label) ?>" class="social-link social-<?= e($key) ?>">
                        <?php if ($icon): ?>
                            <span class="social-icon"><?= $icon ?></span>
                        <?php endif; ?>
                        <span class="social-label"><?= e($label) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="container footer-bottom">
        <small>
            &copy; <?= date('Y') ?> INESCO. Alle Rechte vorbehalten.
            · <a href="<?= e(url('impressum.php')) ?>">Impressum</a>
            · <a href="<?= e(url('datenschutz.php')) ?>">Datenschutz</a>
        </small>
    </div>
</footer>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
