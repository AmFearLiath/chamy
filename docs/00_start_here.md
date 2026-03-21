# Chamy Dokumentation (Ist-Stand)

Diese Dokumentation beschreibt den aktuell implementierten Zustand von Chamy im Repository `f:\Chamy`.

Ziel dieser Fassung:
- technische Realitaet dokumentieren (kein Wunschbild)
- Architektur, Datenfluesse und Betriebsdetails nachvollziehbar machen
- Entwicklern und Administratoren einen belastbaren Referenzstand geben

## Lesereihenfolge

1. `01_systemueberblick.md`
2. `02_bootstrap_kernel_runtime.md`
3. `03_verzeichnis_konfiguration.md`
4. `04_routing_controller_api.md`
5. `05_datenmodell_migrationen.md`
6. `06_content_types_workflow_versionierung.md`
7. `07_theme_system_rendering.md`
8. `08_modul_system_marketplace.md`
9. `09_admin_settings_asset_library.md`
10. `10_sicherheit_berechtigungen.md`
11. `11_betrieb_tooling_tests.md`

## Dokumentationsprinzipien

- Aussagen sind am Code ausgerichtet, nicht an geplanten Features.
- Wenn ein Bereich nur teilweise umgesetzt ist, wird das explizit genannt.
- Wo sinnvoll, sind konkrete Dateipfade genannt.

## Schnellorientierung

- Front Controller: `public/index.php`
- Bootstrap: `core/Bootstrap.php`
- Kernel: `core/Kernel.php`
- Routing: `routes/web.php`, `routes/admin.php`, `routes/api.php`
- Admin-Oberflaeche: `themes/admin/default/templates/`
- Frontend-Oberflaeche: `themes/frontend/default/templates/`
- Kern-Manager: `core/Managers/`
- Migrationen: `system/migrations/`
- Content-Type-Definitionen: `system/content-types/`

## Stand

- Dokumentationsstand: 15.03.2026
- PHP Zielversion laut Projekt: `^8.2` (siehe `composer.json`)
