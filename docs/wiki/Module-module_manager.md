# Modul: ModuleManager (Kern)

Kurz: `core/Managers/ModuleManager.php` verwaltet Discovery, Boot und Aktivierungsstatus von Modulen.

Wichtige Methoden
- `bootActiveModules()` — lädt und bootet aktive Module
- `activate($name)` / `deactivate($name)` — ändert Aktivierungsstatus und persistiert (z. B. `storage/cache/modules-active.json`).

Persistenz
- Aktivierungszustand wird filebasiert in `storage/cache/modules-active.json` gehalten — prüfe Dateirechte.

Tipps für Module Autoren
- Registriere Permissions mittels `$perms->definePermission(...)` *und* sorge für DB‑Seeding, wenn die Admin UI diese Permissions listen soll.
