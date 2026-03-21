# 09 Admin Settings und Asset-Library

## 9.1 Settings-Funktionszentrum

Die Seite `/admin/settings` ist in Chamy ein zentraler operativer Bereich.

`AdminController::settingsPage()` liefert u. a.:
- gruppierte Systemeinstellungen
- Icon- und Font-Library-Daten
- bekannte Sources/Source-Templates
- Analyseergebnisse fuer Import/Installationsflows

`AdminController::settingsUpdate()` verarbeitet alle POST-Aktionen.

## 9.2 Berechtigungsmodell in Settings

Unterschieden werden:
- allgemeine Einstellungen (`system.manage`)
- Icon-Manager-Berechtigung
- Font-Manager-Berechtigung

Je nach Aktion wird vorab granular geprueft.

## 9.3 AssetLibraryManager

Pfad: `core/Managers/AssetLibraryManager.php`

Verwaltet Zustand in:
- `storage/assets/libraries.json`

Kernbereiche:
- Icon Sets
- Font Sets
- Icon Sources
- Source Templates

## 9.4 Icon-Management

Vorhandene Funktionen:
- CSS analysieren (`analyzeIconCss`)
- Set von URL installieren (`installIconSetFromUrl`)
- Set aus Template installieren (`installIconSetFromTemplate`)
- Quellen verwalten (add/remove)
- Templates verwalten (add/remove)
- Import/Export
- Sidebar-Icon-Set speichern (`icons.set_sidebar`)

## 9.5 Font-Management

Vorhandene Funktionen:
- CSS analysieren (`analyzeFontCss`)
- Font-Set von URL installieren (`installFontSetFromUrl`)
- Google-Fonts-Installation (`installGoogleFont`)
- Set-Update/Delete/Import

## 9.6 Google Fonts API im Admin

Admin-API-Endpunkte:
- `GET /admin/api/google-fonts/status`
- `POST /admin/api/google-fonts/check`
- `GET /admin/api/google-fonts/search`

Datenquelle:
- API-Key aus `storage/secrets/google_fonts_api_key`
- Katalog-Cache in `storage/assets/google_fonts_catalog_cache.json`

## 9.7 Google-Fonts-Suche

`searchGoogleFonts(...)` unterstuetzt Filter wie:
- Suchbegriff (`q`)
- Stil (`style`)
- Kategorie / Subkategorie
- Pagination

Rueckgabe enthaelt u. a.:
- Trefferliste
- Kategorien/Subkategorien
- Installationsstatus je Familie

## 9.8 Vorschau- und Installationslogik (aktueller Stand)

Im Admin-Template (`themes/admin/default/templates/settings.twig`) wurde die Vorschau auf servergestuetzte Suche und lokale/remote Ladepfade umgestellt.

Zielverhalten:
- bereits installierte Fonts nur lokal laden
- nicht installierte Fonts via HTTP-CSS einbinden

## 9.9 Sprachintegration

Settings-Template nutzt `t('...')` + Fallbacktexte.

Globale Sprachdateien:
- `languages/de.php`
- `languages/en.php`

Neue/ergaenzte Schluessel fuer Asset-UI muessen in beiden Sprachen gepflegt werden, um Fallback-Wildwuchs zu vermeiden.

## 9.10 Typische Betriebsprobleme

- 404 auf `icons.css` / `fonts.css` weist meist auf inkonsistente Asset-Pfade oder veraltete Set-Referenzen hin.
- Browser-Caching kann Settings- und Preview-Tests verfaelschen -> Hard Reload empfohlen.

## 9.11 Fazit

Der Settings-Bereich ist einer der am staerksten ausgebauten Teile von Chamy und kombiniert Systemsettings, Rechtepruefung und praktisches Asset-Library-Management in einer zentralen Admin-Oberflaeche.
