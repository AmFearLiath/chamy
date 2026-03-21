# Modul: AssetLibrary / AssetLibraryManager

Kurz: Verwaltung von Fonts, Icons und externen Asset‑Quellen (z. B. Google Fonts).

Wichtige Klassen
- `core/Managers/AssetLibraryManager.php` — zentrale Implementierung: `getGoogleFontCatalog()`, `searchGoogleFonts()`, `installGoogleFont()`.

Google Fonts Integration
- API‑Key wird aus: `storage/secrets/google_fonts_api_key` gelesen.
- Katalogcache: `storage/assets/google_fonts_catalog_cache.json`.
- Endpoint für Admin: `GET /admin/api/google-fonts/status` und `POST /admin/api/google-fonts/check`.

Fehlerbehandlung
- Prüfe Network‑Timeouts beim Abruf von Google Webfonts API; implementiere Retries falls nötig.
