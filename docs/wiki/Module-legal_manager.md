# Modul: legal_manager

Kurz: Verwaltung von Datenschutzerklärungen, Impressum, Consent‑Management und Tracking‑Services.

Wichtige Pfade
- `modules/legal_manager/module.php` — Modulentry: Routen, Permissions, DB‑Seeds
- `modules/legal_manager/templates/admin/` — Admin‑TWIG Templates

Permissions
- `legal.view`, `legal.edit`, `legal.services` — sollten in der `permissions` Tabelle vorhanden sein.

CSRF / Security
- Das Modul verwendet das Theme CSRF Field `_csrf_token`. Bei Updates prüfen, dass Server‑Code `getPost('_csrf_token')` liest.

i18n
- Übersetzungen unter `modules/legal_manager/languages/` (de + en). Achte auf fehlende Keys und Fallbacks.

Besonderheiten
- Wenn Sidebar‑Einträge fehlen, prüfe, ob die Permissions in DB vorhanden sind — `definePermission()` allein reicht nicht für Admin UI.
