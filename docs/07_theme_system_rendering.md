# 07 Theme-System und Rendering

## 7.1 Grundmodell

Chamy trennt Admin- und Frontend-Themes:
- `themes/admin/...`
- `themes/frontend/...`

Aktive Theme-IDs kommen initial aus `config/theme.php` bzw. ENV.

## 7.2 Theme-Discovery

`ThemeManager::discoverThemes()` durchsucht:
- `themes/admin/*/theme.json`
- `themes/frontend/*/theme.json`

Jedes gefundene Theme wird mit `_path` und `_area` registriert.

## 7.3 Twig-Initialisierung

Es existieren zwei Environments:
- `getAdminTwig()`
- `getFrontendTwig()`

Eigenschaften:
- Template-Ordner des aktiven Themes
- optional Parent-Template-Pfad (bei `parent` in `theme.json`)
- Cache unter `storage/cache/twig`

## 7.4 Parent-Theme-Unterstuetzung

Ist in Manifest `parent` gesetzt:
- Child-Theme templates zuerst
- Parent-Templates als Fallback

Das gilt fuer Admin und Frontend.

## 7.5 Theme-Management-Funktionen

`ThemeManager` unterstuetzt u. a.:
- Theme aktivieren (`setAdminThemeId`, `setFrontendThemeId`)
- deaktivieren/aktivieren Flag (`toggleThemeDisabled`)
- Child Theme erzeugen (`createChildTheme`)
- Manifest aktualisieren (`updateThemeManifest`)
- Deinstallation in Trash (nicht harte Direktloeschung)
- Wiederherstellung aus Trash

## 7.6 Aktuell vorhandene Themes

Admin:
- `default`
- `cybersec_clone` (Parent `default`)

Frontend:
- `default`

## 7.7 Template-Helfer

Wichtige Twig-Helfer:
- `theme_asset('css/admin.css')`
- `csrf_token()`, `csrf_field()`
- `t('...')`
- `route('...')`

## 7.8 Rendering-Pfade

- Admin-Controller rendern mit `area='admin'`
- Frontend-Controller rendern mit `area='frontend'`

Beispiele:
- `settings.twig`, `dashboard.twig` (Admin)
- `home.twig`, `page.twig`, `article.twig` (Frontend)

## 7.9 Asset-Einbindung

Standard:
- Theme-CSS/JS ueber `theme_asset`

Erweitert:
- Admin kann zusaetzliche Icon-/Font-CSS dynamisch in `base.twig` einbinden (`admin_icon_css`, `admin_font_css`).

## 7.10 Fehlerdarstellung

Bei Twig-Renderfehlern erzeugt `ThemeManager::render()` eine diagnostische HTML-Ausgabe mit:
- Fehlermeldung
- Dateipfad/Zeile
- Codeauszug
- Stack Trace

## 7.11 Grenzen im aktuellen Stand

- Kein vollwertiger visueller Theme-Editor im Core
- Kein umfassendes Runtime-Theme-Scaffolding ausser Child-Theme-Erzeugung

## 7.12 Fazit

Das Theme-System ist funktional und produktiv nutzbar, inklusive Parent-Fallback, Admin-/Frontend-Trennung und betriebspraktischen Funktionen wie Disable/Trash/Restore.
