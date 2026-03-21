# 06 Content Types, States und Versionierung

## 6.1 Content-Type-System (Implementierung)

Der `ContentTypeManager` laedt beim Boot alle PHP-Definitionen aus:
- `system/content-types/*.php`

Registrierte Typen im Ist-Stand:
- `page`
- `article`
- `documentation`
- `media_entry`
- `snippet`

Module koennen weitere Typen beisteuern ueber:
- `modules/<id>/content-types/*.php`

## 6.2 Aufbau einer Content-Type-Definition

Jede Definition ist ein PHP-Array mit Feldern wie:
- `id`, `label`, `description`, `group`
- `is_translatable`, `is_revisionable`, `is_publicly_queryable`
- `fields` (assoziatives Feldschema)

## 6.3 Feldtypen im aktuellen Bestand

Bereits verwendet (je nach Typ):
- `text`, `textarea`, `richtext`
- `slug`
- `select`, `multiselect`
- `media`, `user-reference`
- `datetime`, `number`

## 6.4 State-System

`StateManager` registriert standardmaessig:
- `draft`
- `review`
- `published` (public)
- `archived`
- `deleted`

Erlaubte Transitionen sind im Manager kodiert, z. B.:
- `draft -> review|published|deleted`
- `review -> published|draft|deleted`
- `published -> archived|draft`

## 6.5 Versionierung

`VersionManager` speichert Versionen in `content_versions`.

Kernfunktionen:
- `createVersion(contentId, data, userId, note)`
- `getVersions(contentId)`
- `getVersion(contentId, version)`
- `getLatestVersion(contentId)`

## 6.6 Content CRUD und Lifecycle

Primar ueber `ContentManager`:
- create
- findById/findByUuid
- update
- delete (soft delete via status)
- listByType
- count

Statusverwaltung wird in Content- und Admin-Flows genutzt.

## 6.7 API-Sicht auf Inhalte

`ContentApiController` kapselt:
- Listen mit Pagination
- Detailansicht
- create/update/delete
- Typenliste

Die API arbeitet auf dem DataProvider und validiert zunaechst den Content-Type.

## 6.8 Frontend-Ausleitung

`FrontendController` nutzt publizierte Inhalte fuer:
- Seitenlisten/Details
- Artikellisten/Details

Slug-Aufloesung geschieht ueber den DataProvider.

## 6.9 Redaktionsrealitaet im aktuellen Stand

Positiv:
- strukturierte Typdefinitionen vorhanden
- Versionierungstabellen und Manager vorhanden
- State-Set klar definiert

Einschraenkungen:
- kein voll ausgebautes Workflow-/Review-Kommentarsystem im Core
- keine dedizierte UI fuer Transition-Graph-Management

## 6.10 Fazit

Der Inhaltskern ist fuer klassische CMS-Workflows gut vorbereitet: strukturierte Typen, Zustandsmodell, Versionierung und API-Anbindung sind technisch vorhanden und einsetzbar.
