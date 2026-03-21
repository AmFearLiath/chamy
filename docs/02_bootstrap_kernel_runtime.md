# 02 Bootstrap, Kernel und Runtime

## 2.1 Einstiegspunkt

`public/index.php` ist der zentrale Front Controller.

Ablauf:
1. Anfragepfad ermitteln und Dotfile-Zugriffe blockieren.
2. Installationsschutz pruefen (`storage/install.lock`).
3. Bei Bedarf Installer (`public/install.php`) starten.
4. Bei PHP Built-in Server statische Assets direkt ausliefern.
5. Composer-Autoloader laden.
6. `Bootstrap::init(...)` aufrufen.
7. Request capturen und an Kernel dispatchen.

## 2.2 Bootstrap-Sequenz

`core/Bootstrap.php`:
- laedt `vendor/autoload.php`
- laedt `.env` mit Dotenv (falls vorhanden)
- setzt Zeitzone (`Europe/Berlin`) und interne UTF-8-Codierung
- erstellt Kernel und bootet ihn sofort

## 2.3 Kernel-Bootreihenfolge

`core/Kernel.php` bootet in fester Reihenfolge:

1. ErrorHandler
2. Config
3. Cache
4. Language
5. Database
6. DataProvider
7. Event
8. Hook
9. Session
10. Permission
11. Router
12. Asset
13. Module
14. Theme
15. ContentType
16. Content
17. State
18. Version
19. Layout
20. Component
21. Marketplace
22. Trash
23. AssetLibrary

Danach:
- `ManagerRegistry::bootAll()`
- Routenregistrierung aus `routes/*.php`

## 2.4 ManagerRegistry

`core/ManagerRegistry.php` bietet:
- `register(name, manager)`
- `get(name)`
- `boot(name)` / `bootAll()`
- `isBooted(name)`
- `all()`

Eigenschaften:
- keine Mehrfachregistrierung desselben Namens
- Bootstatus pro Manager wird intern verfolgt

## 2.5 Request/Response-Lebenszyklus

1. `Request::capture()` sammelt Method, Path, Query, POST, Header, Body.
2. Router matched Route und setzt Route-Parameter.
3. Middleware-Pipeline wird ausgefuehrt.
4. Controller/Callable liefert `Response` oder Daten.
5. Response wird ueber `send()` ausgeliefert.

## 2.6 Router-Verhalten

`core/Routing/Router.php`:
- HTTP-Methoden: `get`, `post`, `put`, `delete`, `any`
- benannte Routen
- Middleware als Klassenname oder Instanz
- Hook vor Dispatch: `router.before_dispatch`
- API/JSON-Anfragen erhalten bei Nichttreffer JSON-404
- HTML-Anfragen erhalten Theme-404 (`errors/404.twig`)

## 2.7 Twig-Initialisierung zur Laufzeit

`ThemeManager` initialisiert separate Twig-Environments fuer:
- Admin
- Frontend

Jeweils mit:
- Template-Loader fuer aktives Theme
- optionalem Parent-Theme-Fallback
- Twig-Cache unter `storage/cache/twig`

## 2.8 Verfuegbare Twig-Funktionen (Core)

Registriert in `ThemeManager::registerTwigFunctions(...)`:
- `t(...)`
- `hook(...)`
- `asset(...)`
- `theme_asset(...)`
- `active_theme_id()`
- `route(...)`
- `csrf_token()`
- `csrf_field()`
- `config(...)`

## 2.9 Fehlerverhalten

- Templatefehler werden in `ThemeManager::render(...)` abgefangen und als Debug-HTML mit Dateiausschnitt dargestellt.
- Fallback-404 wird im Router auch ohne Theme-Rendering abgesichert.

## 2.10 Zusammenfassung

Die Runtime ist klassisch-serverseitig, aber strukturiert:
- klarer Bootprozess
- zentrale Managersteuerung
- dedizierte Renderpfade fuer Admin/Frontend
- nachvollziehbare Request-Pipeline
