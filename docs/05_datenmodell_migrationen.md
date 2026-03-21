# 05 Datenmodell und Migrationen

## 5.1 Migrationseintraege im Repository

Migrationen liegen in `system/migrations/`.

Vorhanden:
- `001_create_users_table.php`
- `002_create_content_entries_table.php`
- `003_create_content_versions_table.php`
- `004_create_sessions_table.php`
- `005_seed_admin_user.php`
- `006_create_permissions_table.php`
- `007_create_settings_table.php`
- `007_create_user_roles_table.php`
- `008_create_api_tokens_table.php`

Hinweis:
- Es gibt zwei Migrationen mit Prefix `007_...`.

## 5.2 Kernentitaeten

### users
- Stammdaten fuer Benutzer
- inklusive Legacy-Spalte `role` und locale

### roles / permissions / role_permissions / user_roles
- normalisierte Berechtigungsstruktur
- Rollen koennen mehreren Benutzern zugeordnet werden
- Permissions werden rollenbasiert zugewiesen

### content_entries
- zentrale Tabelle fuer Inhalte
- Feld `data` als JSON
- Status, Version, Locale, Publish-Infos

### content_versions
- Snapshot-artige Versionen pro Inhaltseintrag
- Foreign Key auf `content_entries`

### settings
- gruppierte Schluessel-Wert-Konfigurationen in DB

### sessions
- Session-Persistenzstruktur (DB-Tabelle vorhanden)

### api_tokens
- Token-Storage fuer API-Zugriffe (Hash-basiert)

## 5.3 Content-Datenmodell

`content_entries` bildet den primaren Inhaltsspeicher.

Wichtige Felder:
- `content_type`
- `status`
- `version`
- `locale`
- `data` (JSON)
- `published_at`

Der JSON-Ansatz erlaubt flexible Felder je Content Type.

## 5.4 Versionierungsmodell

`content_versions` speichert:
- `content_id`
- `version`
- `data` (JSON)
- `note`
- `created_by`
- `created_at`

Verwendung ueber `VersionManager`.

## 5.5 DataProvider-Schicht

Abstraktion ueber `DataProviderInterface`.

Implementierungen:
- `MockDataProvider` (seed/in-memory)
- `LiveDataProvider` (SQL)

Diese Schicht kapselt:
- Content CRUD
- User CRUD
- Rollen/Berechtigungen
- Settings
- Dashboard-Statistiken

## 5.6 SQL vs JSON in der Praxis

Chamy nutzt Hybrid-Storage:
- relationale Metadaten in klaren Tabellen
- flexible Inhaltsfelder in JSON

Vorteile:
- geringe Schemahaeufigkeit fuer Content-Felder
- gute Erweiterbarkeit ueber Content Types

Nachteile:
- komplexere SQL-Auswertung auf JSON-Feldern
- Teile der Validierung liegen in Anwendungscode

## 5.7 Datenquelle mock/live

`MockDataProvider`:
- laedt Seeds aus `data/mock/*.php`
- ideal fuer Demo, lokale Entwicklung ohne DB

`LiveDataProvider`:
- echte DB-Operationen
- nutzt Tabellenprefixe konsequent

## 5.8 Bekannte Inkonsistenz

`ApiAuthMiddleware` sucht aktuell nach `api_keys` mit Spalten (`token`, `active`), waehrend Migration `008_create_api_tokens_table.php` eine Tabelle `api_tokens` mit `token_hash` erstellt.

Konsequenz:
- Bearbeitende API-Authentifizierung via Token ist im DB-Pfad inkonsistent und sollte harmonisiert werden.

## 5.9 Migration-CLI

CLI-Entry: `chamy`

Verfuegbare Befehle:
- `php chamy migrate`
- `php chamy migrate:down`

MigrationRunner liegt unter `core/Database/MigrationRunner.php`.

## 5.10 Zusammenfassung

Das persistente Modell ist fuer Content-zentriertes Arbeiten solide: strukturierte Kernentitaeten plus JSON-Content, mit Versionierung und rollenbasierter Rechtebasis.
