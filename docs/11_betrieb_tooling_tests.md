# 11 Betrieb, Tooling und Tests

## 11.1 Entwicklungsstart

Typischer lokaler Startpfad:
1. Abhaengigkeiten installieren (`composer install`)
2. `.env` konfigurieren
3. Migrationen ausfuehren (`php chamy migrate`)
4. Installer/Lock-Status pruefen
5. PHP-Server starten (`php -S localhost:8080 -t public`)

## 11.2 Installationslogik

`public/install.php`:
- gefuehrter Setup-Prozess
- prueft vorhandene `.env` oder `install.lock`
- bietet Force-Reinstall fuer Entwicklungsfaelle

## 11.3 CLI und Skripte

Wichtige Betriebs- und Hilfsskripte unter `scripts/`:
- `twig_lint_and_smoke.php` (Template-Smoketest)
- `check_db.php`, `inspect_*` (Diagnose)
- `import_*` (Daten-/Rollenimport)
- `dev_service.php` + `dev_service.ps1` (lokaler Servicewrapper)

## 11.4 Lint/Smoke in der Praxis

Haefig verwendetes Muster:
- `php -l <datei>` fuer Syntax
- `php scripts/twig_lint_and_smoke.php` fuer Render-Smoke

Diese Checks sind besonders relevant bei Template- und Controller-Aenderungen.

## 11.5 Dev Service

`scripts/dev_service.php` kann lokalen PHP-Server starten/ueberwachen und schreibt Status in:
- `scripts/terminal_status.json`
- `scripts/terminal.log`

## 11.6 Tests

- PHPUnit ist als Dev-Dependency vorhanden.
- Verzeichnisstruktur `tests/Integration` und `tests/Unit` ist vorhanden.
- Aktueller Testbestand im Repository ist begrenzt/leer.

## 11.7 Betrieb mit Mock vs Live

Mock (`DATA_SOURCE=mock`):
- schnell, reproduzierbar, DB-unabhaengig

Live (`DATA_SOURCE=live`):
- echte Persistenz
- benoetigt konsistente DB-Struktur und Migrationen

## 11.8 Wartungsrelevante Dateien

- `storage/assets/libraries.json`
- `storage/trash/trash.json`
- `storage/secrets/google_fonts_api_key`
- `storage/install.lock`

## 11.9 Monitoring/Health

API-Endpunkt:
- `GET /api/v1/system/health`

Prueft derzeit u. a.:
- DB-Erreichbarkeit
- Schreibbarkeit von Cache/Logs

## 11.10 Empfohlene Betriebsroutine

1. Vor Deploy: Syntax + Twig-Smoke
2. Nach Deploy: Health-Check-Endpunkt pruefen
3. Settings/Asset-Flows im Admin kurz smoke-testen
4. Backup/Versionierung fuer `storage/`-kritische Dateien sicherstellen

## 11.11 Fazit

Chamy ist betrieblich pragmatisch aufgebaut: klare Startlogik, brauchbare Diagnose-Skripte und reproduzierbare Entwicklungswege. Die Testinfrastruktur ist vorbereitet, aber funktionale Testabdeckung sollte weiter ausgebaut werden.
