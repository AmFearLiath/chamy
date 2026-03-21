# Admin Guide

Diese Seite richtet sich an Betreiber und Admins. Sie beschreibt zentrale Einstellungen, Modulverwaltung, Rollen, Berechtigungen und den Asset‑/Font‑Manager.

Module verwalten
- Admin → Einstellungen → Module: Module aktivieren/deaktivieren.
- Aktivierungsstatus wird persistiert; bei Problemen prüfe `storage/cache/modules-active.json`.

Rollen & Berechtigungen
- Berechtigungen werden in der DB gespeichert (`permissions` Tabelle).
- Neue Module sollten ihre `definePermission()` Aufrufe seeden und bei Bedarf die DB‑Einträge erzeugen.

Asset Library / Google Fonts
- API‑Key: `storage/secrets/google_fonts_api_key` (nicht im Repo). Siehe Settings → Font‑Manager.
- Katalog wird gecached in `storage/assets/google_fonts_catalog_cache.json`.

Backup & Restore
- Backup `storage/backups/` regelmäßig erstellen.
- Teste Wiederherstellung in einer Staging‑Umgebung.

Wartungstipps
- Logs: `storage/logs/` — rotieren und überwachen.
- Cronjobs: Setze regelmäßige Wartungsskripte für Cache/Cleanup.
