# 03 Verzeichnisstruktur und Konfiguration

## 3.1 Top-Level-Struktur

Wesentliche Projektordner:

- `core/` Kernklassen (Kernel, Manager, Routing, HTTP, Controller, Data)
- `public/` Front Controller, Installer, oeffentliche Assets
- `routes/` Route-Definitionen fuer Web/Admin/API
- `system/` Migrationen und systemweite Content-Type-Definitionen
- `themes/` Admin- und Frontend-Themes
- `modules/` installierte Module
- `languages/` globale Sprachdateien (`de.php`, `en.php`)
- `config/` PHP-Konfigurationsdateien
- `storage/` Cache, Trash, Assets, Secrets, Install-Lock
- `data/mock/` Seed-Daten fuer Mock-Betrieb
- `scripts/` Entwickler- und Diagnose-Skripte

## 3.2 Theme-Verzeichnisse

- Admin-Themes: `themes/admin/<theme-id>/`
- Frontend-Themes: `themes/frontend/<theme-id>/`

Beispiele im Ist-Stand:
- `themes/admin/default`
- `themes/admin/cybersec_clone` (Parent: `default`)
- `themes/frontend/default`

## 3.3 Modulverzeichnis

Jedes Modul liegt unter `modules/<id>/` und hat mindestens ein `manifest.json`.

Beispielmodul:
- `modules/contact_form/`
  - `manifest.json`
  - `module.php`
  - `languages/`
  - `migrations/`

## 3.4 Konfigurationsquellen

1. `.env`
2. `config/*.php`
3. Runtime-Zugriff ueber `ConfigManager`

Wichtige Schluessel aus `.env.example`:
- App: `APP_*`
- DB: `DB_*`
- Session: `SESSION_*`
- Cache: `CACHE_*`
- API: `API_*`
- Theme: `ADMIN_THEME`, `FRONTEND_THEME`

## 3.5 Relevante Config-Dateien

- `config/app.php`
  - Name, Umgebung, Debug, URL, Locale
- `config/database.php`
  - DB-Verbindung inkl. Prefix
- `config/api.php`
  - CORS, API Prefix, Rate-Limit Konfiguration
- `config/theme.php`
  - aktive Theme-IDs (default)

## 3.6 Installationsartefakte

- `storage/install.lock` sperrt erneute Installation.
- `public/install.php` bietet gefuehrte Erstinstallation und optionalen Force-Modus.

## 3.7 Datenquellenumschaltung

`DataProviderFactory` entscheidet anhand `DATA_SOURCE`:
- `live` -> `LiveDataProvider`
- sonst -> `MockDataProvider`

Konsequenz:
- gleiches Interface, unterschiedliche Persistenzstrategie
- Controller/Manager koennen provider-agnostisch arbeiten

## 3.8 Storage-relevante Pfade

- Cache: `storage/cache/`
- Twig-Cache: `storage/cache/twig/`
- Asset-Library-State: `storage/assets/libraries.json`
- Google-Fonts-Katalog-Cache: `storage/assets/google_fonts_catalog_cache.json`
- Secrets (z. B. Google Fonts API Key): `storage/secrets/`
- Papierkorb: `storage/trash/`

## 3.9 Operative Hinweise

- Das DB-Prefix ist zentral fuer alle Tabellen (`DB_PREFIX`, default `chamy_`).
- Bei Built-in Server (`php -S`) werden statische Dateien in `public/index.php` direkt ausgeliefert.
- Einige Assets koennen sowohl aus Theme-Pfaden als auch aus lokal installierten Library-Pfaden kommen (siehe Settings/Asset-Library Kapitel).
