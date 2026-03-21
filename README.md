# Chamy

Modulares, thema‑getriebenes Content‑Management‑System. Diese Datei enthält einen kompakten Quickstart, Entwicklerhinweise und Links zu weiterführender Dokumentation.

Kurzübersicht
------------
- Modularer Kern mit Manager‑Registry, Hook‑System und klarer Trennung zwischen `core`, `modules` und `themes`.
- Themes liefern alle Präsentationsschichten (Twig‑Templates, Assets, Übersetzungen).
- Module erweitern die Funktionalität über klar definierte Hooks, Permissions und APIs.

Schnellstart (lokal)
--------------------
Voraussetzungen
- PHP 8.0+ (oder passend zu Ihrem Stack)
- Composer
- Node.js + pnpm (für Frontend/Assets)
- MySQL/MariaDB (oder ein kompatibles SQL DBMS)

Kurze Befehle

```bash
git clone <repo-url> chamy
cd chamy
composer install
pnpm install
copy .env.example .env      # Windows
# cp .env.example .env     # Linux / macOS
```

DB einrichten

```bash
php chamy migrate
```

Entwicklungsserver

```bash
php -S localhost:8080 -t public public/index.php
pnpm run dev
```

Tests

```bash
vendor/bin/phpunit --configuration phpunit.xml
powershell -NoProfile -ExecutionPolicy Bypass -File "scripts\e2e-tests.ps1"  # E2E (Windows)
```

Konfiguration & Secrets
-----------------------
- Kopieren Sie `.env.example` nach `.env` und passen Sie DB/Env‑Werte an.
- Secrets lokal/auf Server: `storage/secrets/` (dieser Ordner ist im Git ausgeschlossen).

Dokumentation & Wiki
--------------------
- Projektdokumentation liegt im `docs/`-Ordner.
- GitHub‑Wiki lokal vorbereiten unter `docs/wiki/` und per Script mit `scripts/push_wiki.ps1` veröffentlichen.

Entwicklerhinweise
-------------------
- Coding Standards: PSR‑12, `declare(strict_types=1)`, Type Hints.
- Static Analysis empfohlen: PHPStan oder Psalm (CI: höhere Level).
- Pre-commit Hooks: Linting, Tests und CS fixer empfohlen.
- Module: `modules/<id>/` (mit `module.php`, `migrations/`, `languages/`, `templates/`).

CI & Release
------------
- Geplante Schritte: GitHub Actions für Tests, PHPStan, CodeQL und Asset‑Builds.
- Dependabot empfohlen für Composer und pnpm.

Lizenz & Mitwirken
-------------------
- Siehe `LICENSE` im Repo für die Projektlizenz.
- CONTRIBUTING.md erklärt die Projektrichtlinie; derzeit ist veröffentlicht, wie externe Beiträge gehandhabt werden.

Support / Kontakt
-----------------
- Öffnen Sie Issues im Repository für Fehlerberichte und Feature‑Requests.

Weiterführende Links
-------------------
- Dokumentation: `docs/`
- Wiki (generiert): `docs/wiki/` (siehe `scripts/push_wiki.ps1`)

---
Kurz‑README: für tiefergehende Details siehe `docs/` und die Wiki‑Seiten.
