# INESCO – Bandwebsite

Moderne, responsive PHP-Website für die Band **INESCO**
(Soul · Blues · Gypsy-Jazz · Latin-Rock).
Inhalte werden in JSON-Dateien gespeichert (keine Datenbank nötig).

## Features

- Landing-Page mit Hero, Bio, Mitgliedern, Audio-Player, Videos, Konzerten und Kontaktformular
- Verantwortliches, mobiles Design (Bordeaux / Gold / Schwarz)
- Sanfte Scroll- & Hover-Animationen, reduzierte Bewegung wird respektiert
- Kontaktformular mit serverseitiger Validierung, CSRF-Schutz, Honeypot, Rate-Limit, PHP-`mail()`
- Geschützter Admin-Bereich (`/admin`) mit Session-Login, Passwort-Hashing
- CRUD für: Texte, Mitglieder, Audio-Tracks, Videos (YouTube/Vimeo/Upload), Konzerte, Kontakt
- Gästebuch mit optionaler WhatsApp-Benachrichtigung bei neuen Einträgen
- Datei-Uploads (Bilder, Audio, Video) mit Whitelist & Größenbeschränkung
- JSON-Dateien per `.htaccess` vor direktem Zugriff geschützt
- SEO-Basics, OG-Tags, Caching & Kompression in `.htaccess`

## Ordnerstruktur

```
inesco-music/
├── index.php              # Landing-Page
├── .htaccess              # Security / Caching / Kompression
├── api/
│   └── contact.php        # Kontaktformular-Endpoint
├── assets/
│   ├── css/style.css      # Frontend-CSS
│   ├── js/main.js         # Frontend-JS
│   ├── img/               # Bilder (Logo, Bandfotos, Uploads)
│   ├── audio/             # MP3 / OGG / M4A (per Admin hochladbar)
│   └── video/             # MP4 / WebM (per Admin hochladbar)
├── data/                  # JSON-Inhalte (vor Webzugriff geschützt)
│   ├── content.json
│   ├── members.json
│   ├── tracks.json
│   ├── videos.json
│   ├── concerts.json
│   ├── contact.json
│   └── users.json
├── includes/
│   ├── config.php         # Bootstrap, Sessions, Pfade
│   ├── helpers.php        # JSON I/O, CSRF, Uploads, Auth
│   ├── mailer.php         # mail()-Wrapper
│   ├── header.php
│   └── footer.php
└── admin/                 # Admin-Backend
    ├── index.php          # Dashboard
    ├── login.php          # Login + Erst-Setup
    ├── logout.php
    ├── auth.php           # require_login()
    ├── content.php
    ├── members.php
    ├── tracks.php
    ├── videos.php
    ├── concerts.php
      ├── contact.php
    ├── header.php
    ├── footer.php
    └── style.css
```

## Installation

1. **Dateien** in `htdocs/inesco-music/` ablegen (oder eigenen Ordner).
2. **PHP 7.4+** mit aktiviertem `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires` empfohlen.
3. **Schreibrechte** für die Ordner `data/` und `assets/` (img, audio, video) sicherstellen.
4. Im Browser öffnen: `http://localhost/inesco-music/`
5. **Admin-Erst-Setup:** `http://localhost/inesco-music/admin/`
   → Beim ersten Aufruf legen Sie Benutzername + Passwort an (mind. 8 Zeichen).
6. Bilder ablegen (oder über Admin hochladen):
   - `assets/img/logo.png`  – Bandlogo
   - `assets/img/band.jpg`  – Hero-Hintergrund (Bandfoto)
   - `assets/img/ines.jpg`  – Profilbild Ines
   - `assets/img/sigo.jpg`  – Profilbild Sigo
7. **E-Mail:** Funktion `mail()` muss verfügbar sein. Empfänger im Admin unter „Kontakt“ einstellen.
   Für Produktion empfohlen: SMTP via PHPMailer (in `includes/mailer.php` austauschen).
8. **WhatsApp für Gästebuch:** Optional `INESCO_WHATSAPP_API_KEY=...` in `.env` setzen.
   Danach im Admin unter „Gästebuch“ eine Zielnummer im internationalen Format hinterlegen.

## Sicherheit

- Passwörter mit `password_hash()` (BCRYPT) gespeichert.
- Sessions: HttpOnly + SameSite=Lax + `session_regenerate_id` nach Login.
- CSRF-Token in allen POST-Formularen.
- JSON-Verzeichnis durch `.htaccess` (`Require all denied`) geschützt.
- Datei-Uploads: Endungs-Whitelist, sichere Umbenennung, Größenlimit.
- Mail-Header: Schutz vor Header-Injection.
- Kontaktformular: Honeypot + Rate-Limit (20 Sek/Session).

## Anpassen

- **Farben/Typografie:** `assets/css/style.css` (`:root`-Variablen).
- **Standard-Tracks/Videos:** über Admin-Backend pflegen.
- **Mehrsprachigkeit / DSGVO-Hinweis:** ggf. ergänzen.

Viel Spaß mit INESCO! 🎶
