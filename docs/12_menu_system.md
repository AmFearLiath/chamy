# 12 Menü-System (MenuManager)

## 12.1 Überblick

Der **MenuManager** ist ein Core-Manager (`getName() = 'menu'`), der sämtliche Navigationsstrukturen in Chamy CMS zentral verwaltet. Dazu gehören:

- Admin-Sidebar, Admin-Topbar, Frontend-Hauptnavigation, Footer, Account-Menü
- Dynamische Registrierung durch Module und Themes
- Drag & Drop-basierte Sortierung im Admin
- Manuelle Overrides die Sync-Zyklen überleben
- Mehrsprachige Labels (DE/EN)
- Berechtigungs- und Sichtbarkeitssteuerung
- Vollständiges Audit-Log

Zugriff über Kernel: `$kernel->menus()`

## 12.2 Datenmodell

Sechs Tabellen (Migration 009):

| Tabelle | Zweck |
|---|---|
| `menu_locations` | Hauptorte (admin-sidebar, frontend-main, …) |
| `menu_categories` | Gruppen innerhalb einer Location (main, content, extensions, system) |
| `menu_items` | Die eigentlichen Menüeinträge |
| `menu_item_translations` | Labels und Tooltips pro Locale |
| `menu_category_translations` | Kategorielabels pro Locale |
| `menu_audit_log` | Protokoll aller Änderungen |

### Kernfelder menu_items

- `key` – Eindeutiger Schlüssel z.B. `admin.dashboard` oder `module.legal_manager.privacy`
- `source` – Herkunft: `core`, `module`, `theme`, `manual`, `import`
- `source_ref` – Referenz auf Modul-/Theme-ID
- `target_type` – `route`, `url`, `separator`, `heading`, `container`, `action`, `anchor`
- `target_value` – Die Ziel-URL oder Route
- `is_manual` – 1 wenn manuell modifiziert (schützt vor Sync-Überschreibung)
- `is_hidden` – 1 wenn Admin-explizit versteckt
- `override_fields` – JSON-Array der manuell geänderten Felder
- `parent_id` – Für verschachtelte Menü-Items
- `permission` – Erforderliche Berechtigung
- `visibility_rule` – `all`, `authenticated`, `guest`, `permission`, `role`

## 12.3 Boot-Reihenfolge

```
initModule → initTheme → … → initAssetLibrary → initMenu → bootAll
```

`boot()` ruft `syncRegistrations()` auf, das alle gepufferten Registrierungen aus Modulen/Themes in die DB persistiert.

## 12.4 Location CRUD

```php
$menus = $kernel->menus();

// Alle Locations
$locations = $menus->getLocations();

// Erstellen
$id = $menus->createLocation('frontend-sidebar', 'Frontend Sidebar', 'Beschreibung', 10);

// Aktualisieren
$menus->updateLocation($id, ['label' => 'Neuer Name', 'is_active' => 0]);

// Löschen
$menus->deleteLocation($id);

// Nach Key suchen
$loc = $menus->getLocationByKey('admin-sidebar');
```

## 12.5 Category CRUD

```php
// Erstellen mit Übersetzungen
$catId = $menus->createCategory($locationId, 'content', [
    'de' => 'Inhalte',
    'en' => 'Content',
], 'file', 10);

// Aktualisieren inkl. Labels
$menus->updateCategory($catId, [
    'icon'   => 'folder',
    'labels' => ['de' => 'Neue Inhalte', 'en' => 'New Content'],
]);

// Löschen
$menus->deleteCategory($catId);
```

## 12.6 Item CRUD

```php
// Erstellen
$itemId = $menus->createItem([
    'category_id'  => $catId,
    'key'          => 'admin.settings.general',
    'source'       => 'core',
    'target_type'  => 'route',
    'target_value' => '/admin/settings',
    'icon'         => 'settings',
    'permission'   => 'system.manage',
    'sort_order'   => 10,
], [
    'de' => ['label' => 'Allgemein', 'tooltip' => 'Allgemeine Einstellungen'],
    'en' => ['label' => 'General',   'tooltip' => 'General Settings'],
]);

// Aktualisieren (markiert Item automatisch als is_manual=1)
$menus->updateItem($itemId, ['icon' => 'star']);

// Translations aktualisieren
$menus->updateItem($itemId, [], ['de' => ['label' => 'Neues Label']]);

// Ausblenden / Einblenden
$menus->hideItem($itemId);  // is_hidden=1, bleibt bei Sync erhalten
$menus->showItem($itemId);

// Löschen
$menus->deleteItem($itemId);
```

## 12.7 Drag & Drop / Reorder

```php
$menus->reorder([
    42 => ['sort_order' => 0, 'parent_id' => null],
    43 => ['sort_order' => 10, 'parent_id' => 42],
    44 => ['sort_order' => 20, 'parent_id' => null, 'category_id' => 5],
]);
```

Frontend sendet JSON-POST an `/admin/menus/api/reorder` mit CSRF-Header.

## 12.8 Tree Resolution

```php
$tree = $menus->resolveTree('admin-sidebar', $currentUser, '/admin/pages');
// Returns: ['categories' => [...], 'location' => [...]]
```

Jede Kategorie enthält verschachtelte `items` mit `children`-Array. Aktive Items werden via `is_current` und `is_open` markiert.

In Twig:
```twig
{% set sidebar_tree = menu_tree('admin-sidebar') %}
{% include '_partials/sidebar_nav.twig' with { sidebar_tree: sidebar_tree } %}
```

## 12.9 Modul-Integration

### registerModuleNav (empfohlen)

```php
// In module.php:
$kernel->menus()->registerModuleNav(
    'legal_manager',               // Modul-ID
    'module.legal_manager',        // Parent-Key
    [
        [
            'key'          => 'module.legal_manager.dashboard',
            'target_type'  => 'route',
            'target_value' => '/admin/legal',
            'labels'       => ['de' => 'Dashboard', 'en' => 'Dashboard'],
            'icon'         => 'layout-dashboard',
            'sort_order'   => 0,
        ],
        // weitere Untereinträge ...
    ],
    ['de' => 'Rechtstexte', 'en' => 'Legal'],  // Parent-Labels
    'scale',                                      // Parent-Icon
    'legal.manage'                                // Permission
);
```

Items landen automatisch in der Kategorie **Erweiterungen** der Location **admin-sidebar**.

### register (allgemein)

```php
$kernel->menus()->register('module', 'my_module', [
    [
        'key'          => 'module.my.page',
        'location'     => 'frontend-main',
        'category'     => 'main',
        'target_type'  => 'route',
        'target_value' => '/my-page',
        'labels'       => ['de' => 'Meine Seite', 'en' => 'My Page'],
    ],
]);
```

## 12.10 Override-Mechanismus

**Prinzip: Manuelle Änderungen überschreiben niemals automatische Sync-Daten.**

Wenn ein Admin ein Item im UI ändert:
1. `is_manual` wird auf `1` gesetzt
2. Die geänderten Felder werden in `override_fields` (JSON-Array) gespeichert
3. Bei der nächsten `syncRegistrations()` werden nur **nicht-überschriebene** Felder aktualisiert

Wenn ein Admin ein Item ausblendet (`hideItem`):
1. `is_hidden` wird `1`, `is_manual` wird `1`
2. `syncRegistrations()` überspringt dieses Item komplett

## 12.11 Sichtbarkeitsregeln

| Rule | Verhalten |
|---|---|
| `all` | Immer sichtbar |
| `authenticated` | Nur für eingeloggte Benutzer |
| `guest` | Nur für nicht-eingeloggte Benutzer |
| `permission` | Prüft `permission`-Feld gegen Benutzerrechte |
| `role` | Prüft `visibility_value` gegen Benutzerrolle |

## 12.12 Audit-Log

Jede Aktion wird protokolliert:

```php
$logs = $menus->getAuditLog(50);          // Letzte 50 Einträge
$logs = $menus->getAuditLog(20, $itemId); // Für ein bestimmtes Item
```

Erfasste Aktionen: `created`, `updated`, `deleted`, `reordered`, `hidden`, `restored`, `imported`

## 12.13 Admin-Routen

| Route | Controller-Methode | Zweck |
|---|---|---|
| `GET /admin/menus` | `index` | Übersicht aller Locations |
| `GET /admin/menus/location/{id}` | `locationDetail` | Tree-Editor mit D&D |
| `GET /admin/menus/location/create` | `locationCreate` | Location-Formular |
| `POST /admin/menus/location/store` | `locationStore` | Location speichern |
| `GET /admin/menus/location/{id}/edit` | `locationEdit` | Location bearbeiten |
| `POST /admin/menus/location/{id}/update` | `locationUpdate` | Location aktualisieren |
| `POST /admin/menus/location/{id}/delete` | `locationDelete` | Location löschen |
| `GET /admin/menus/category/create` | `categoryCreate` | Kategorie-Formular |
| `POST /admin/menus/category/store` | `categoryStore` | Kategorie speichern |
| `GET /admin/menus/category/{id}/edit` | `categoryEdit` | Kategorie bearbeiten |
| `POST /admin/menus/category/{id}/update` | `categoryUpdate` | Kategorie aktualisieren |
| `POST /admin/menus/category/{id}/delete` | `categoryDelete` | Kategorie löschen |
| `GET /admin/menus/item/create` | `itemCreate` | Item-Formular |
| `POST /admin/menus/item/store` | `itemStore` | Item speichern |
| `GET /admin/menus/item/{id}/edit` | `itemEdit` | Item bearbeiten |
| `POST /admin/menus/item/{id}/update` | `itemUpdate` | Item aktualisieren |
| `POST /admin/menus/item/{id}/delete` | `itemDelete` | Item löschen |
| `POST /admin/menus/item/{id}/toggle` | `itemToggleVisibility` | Ein-/Ausblenden |
| `POST /admin/menus/api/reorder` | `apiReorder` | D&D-Reihenfolge (JSON) |
| `GET /admin/menus/api/tree/{key}` | `apiTree` | Tree als JSON |
| `GET /admin/menus/audit` | `auditLog` | Audit-Log Seite |

## 12.14 Templates

| Template | Zweck |
|---|---|
| `menus/index.twig` | Locations-Übersicht mit Karten |
| `menus/location.twig` | Tree-Editor mit Drag & Drop |
| `menus/location_form.twig` | Location erstellen/bearbeiten |
| `menus/category_form.twig` | Kategorie erstellen/bearbeiten |
| `menus/item_form.twig` | Item erstellen/bearbeiten (Zwei-Spalten-Layout) |
| `menus/audit.twig` | Audit-Log Tabelle |
| `_partials/sidebar_nav.twig` | Dynamische Sidebar-Navigation |

## 12.15 Seed-Daten (Migration 010)

Vordefinierte Admin-Sidebar-Struktur:

**Kategorien:** main (Sort 0), content (Sort 10), extensions (Sort 50), system (Sort 90)

**Items:** Dashboard, Seiten, Beiträge, Medien, Inhaltsblöcke, Editor, Benutzer, Rollen, Berechtigungen, Module, Einstellungen, Menüs

## 12.16 Tests

```
tests/Unit/Managers/MenuManagerTest.php
```

28 Tests mit 72 Assertions, die alle CRUD-Operationen, Tree-Resolution, Registration/Sync, Override-Mechanismus, Imports und Audit-Log abdecken. Verwendet SQLite In-Memory-Datenbank.
