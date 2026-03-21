# 04 Routing, Controller und API-Flaechen

## 4.1 Routing-Dateien

- `routes/web.php`
- `routes/admin.php`
- `routes/api.php`

Diese Dateien werden im Kernel-Boot geladen und registrieren alle Endpunkte.

## 4.2 Frontend-Routen (Ist-Stand)

`routes/web.php`:
- `GET /` -> Home
- `GET /seiten` -> Seitenliste
- `GET /seite/{slug}` -> Einzelseite
- `GET /artikel` -> Artikelliste
- `GET /artikel/{slug}` -> Einzelartikel

Controller: `core/Controllers/FrontendController.php`

## 4.3 Admin-Routen (Ist-Stand)

`routes/admin.php` umfasst unter anderem:
- Auth: Login/Logout
- Dashboard
- Content CRUD
- Benutzerverwaltung
- Rollen/Berechtigungen
- Settings
- Themes
- Modules
- Trash
- Profil

Zentrale Controller-Klasse:
- `core/Controllers/AdminController.php`

## 4.4 API-Routen v1

`routes/api.php`:
- System:
  - `GET /api/v1/system/health`
  - `GET /api/v1/system/info`
  - `GET /api/v1/system/content-types`
  - `GET /api/v1/system/states`
- Content:
  - `GET /api/v1/content/{type}`
  - `GET /api/v1/content/{type}/{id}`
  - `POST /api/v1/content/{type}`
  - `PUT /api/v1/content/{type}/{id}`
  - `DELETE /api/v1/content/{type}/{id}`
- Typen:
  - `GET /api/v1/types`

## 4.5 Middleware

Eingesetzt im API-Routing:
- `CorsMiddleware`
- `ApiAuthMiddleware` (fuer schreibende Endpunkte)

Router baut Middleware als Pipeline um den Route-Handler.

## 4.6 Request-Verarbeitung

`Request` bietet:
- `getQuery`, `getPost`, `input`, `getJsonBody`
- Headerzugriff (`getHeader`)
- JSON-/Ajax-Erkennung (`expectsJson`)
- Route-Parameterzugriff (`getRouteParam`)

## 4.7 API-Response-Formate

Es existieren zwei relevante Response-Stile im Code:

1. `Response::apiSuccess/apiError` (Envelope mit `success`, `data`, `meta`, `errors`)
2. `BaseApiController::success/error` (Envelope mit `success`, `message`, optional `data`)

Im aktuellen API-Controller-Pfad wird vor allem Stil 2 verwendet.

## 4.8 Wichtige Controller-Funktionsgruppen

`AdminController`:
- Auth: `loginForm`, `loginSubmit`, `logout`
- Content: `contentList`, `contentCreate`, `contentStore`, `contentEdit`, `contentUpdate`, `contentDelete`
- Users/Roles/Permissions: vollstaendiger CRUD-Flow
- Settings inkl. Asset-Library-Aktionen
- Theme-/Module-Bereiche inkl. Detailansichten
- Trash-Funktionen

`FrontendController`:
- Home, Listen und Detailseiten fuer `page` und `article`

`ContentApiController`:
- list/show/store/update/destroy/types

`SystemApiController`:
- info/health/contentTypes/states

## 4.9 Fehlerpfade

- Nicht gematchte API-Routen liefern JSON-404.
- Nicht gematchte HTML-Routen rendern Theme-404.

## 4.10 Fazit

Routing und Controller sind klar getrennt organisiert. Der Adminbereich ist breit umgesetzt, API v1 ist funktional fuer Kerninhalte, und Frontend-Routen sind bewusst kompakt gehalten.
