# 10. Globales Papierkorb-System

## Ziel
Alle Loschvorgange (Content, Seiten, Kommentare, User, Rollen, Permissions, Themes, Module und zukunftige Entitaten) werden **immer zuerst** im globalen Papierkorb archiviert.

## Zentrale Komponenten
- `core/Managers/TrashManager.php`
- Kernel-Accessor: `kernel->trash()`
- Persistenz: `storage/trash/trash.json`
- Admin UI: `/admin/trash`

## Datenmodell eines Papierkorb-Eintrags
- `id`: eindeutige ID
- `category`: fachliche Kategorie (z. B. `content`, `users`, `access`, `themes`, `modules`)
- `entity_type`: technischer Typ (z. B. `content_entry`, `user`, `role`, `permission`, `theme`, `module`)
- `entity_key`: fachlicher Schluessel
- `payload`: Snapshot fur Restore
- `deleted_by`, `deleted_at`
- `status`: `trashed` | `restored` | `purged`
- `restored_at`, `purged_at`

## Pflicht-Flow fur jede Delete-Action
1. Berechtigung pruefen (bereichsspezifisch)
2. CSRF pruefen
3. Aktuellen Datensatz laden
4. Snapshot in `TrashManager->add(...)` schreiben
5. Erst danach eigentliche Delete-Operation ausfuhren
6. Audit-Log schreiben

## Restore-Flow
- Restore erfolgt uber `/admin/trash/{id}/restore`
- Bereichsspezifische Berechtigungsprufung nach `entity_type`
- Restore ist typabhangig (z. B. `createContent`, `createUser`, `restoreThemeFromTrash`)

## Purge-Flow
- Endgultiges Entfernen nur fur `system.manage`
- Zweistufige Bestatigung im UI (`data-confirm-double`)

## Integrations-Checkliste fur neue Systeme
- Neue Entitat bekommt `entity_type` + `category`
- Delete-Endpoints nutzen obigen Pflicht-Flow
- Restore-Implementierung in AdminController erganzen
- UI-Aktionen im globalen Papierkorb pruefen
- Audit-Logging hinterlegen

## Hinweis zu Themes
Theme-Uninstall verschiebt den Theme-Ordner nach `storage/trash/themes/...` statt sofortiger physischer Loschung. Restore verschiebt den Ordner zuruck.
