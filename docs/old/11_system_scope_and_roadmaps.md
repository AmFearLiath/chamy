# Chamy - Systemgrenzen, Modulstrategie und Theme-Roadmap

## Zweck dieses Dokuments

Dieses Dokument legt fest:

- welche Bereiche fest zum Chamy-Grundsystem gehoeren
- welche Bereiche als Module umgesetzt werden sollten
- in welchem Entwicklungszustand die Bereiche aktuell sind
- welche Roadmap-Schritte als Naechstes noetig sind
- was konkret noch fehlt, um das Theme-System belastbar zu testen

Der Fokus liegt auf klarer Verantwortlichkeit:

- Core/System = Plattformregeln, Sicherheit, Laufzeit, Stabilitaet
- Module = fachliche Features und optionale Erweiterungen
- Themes = reine Darstellung (UI/UX, Layout, Template-Ausgabe)

---

## 1 Entscheidungsregeln: Core vs Modul

Ein Bereich gehoert in den Core, wenn mindestens einer dieser Punkte zutrifft:

- wird von nahezu allen Installationen benoetigt
- ist sicherheitskritisch (Auth, Rechte, Routing, Session, CSRF)
- ist Infrastruktur fuer andere Features (Konfiguration, Hooks, Caching, Datenzugriff)
- muss transaktional und konsistent systemweit funktionieren
- darf nicht optional abschaltbar sein

Ein Bereich sollte als Modul umgesetzt werden, wenn mindestens einer dieser Punkte zutrifft:

- ist fachlich/projektspezifisch
- ist optional und darf installierbar/deinstallierbar sein
- kann ueber oeffentliche Schnittstellen auf Core-Funktionen aufsetzen
- hat eigene Release-Zyklen
- soll im Marketplace verteilt werden

---

## 2 Bereiche, die fest zum Grundsystem gehoeren sollten

### 2.1 Kernel, Bootstrapping, Config, Language, Routing

Beschreibung:

- Systemstart, Manager-Registrierung, Konfigurationsauflosung, Sprachsystem, Request-Dispatch

Ist-Zustand:

- **weit entwickelt**
- Kernel-Initialisierung und Manager-Lifecycle sind vorhanden
- Routing fuer Admin/Web/API ist vorhanden
- Sprache via `LanguageManager` und `t()` ist vorhanden

Offen:

- klarere Trennung und Prioritaetsregel zwischen ENV, Datei-Config und DB-Settings dokumentieren
- konsistente Fehlerseiten fuer Admin und Frontend (404/403/500) finalisieren

### 2.2 Auth, Session, Permissions, Rollen/Benutzer

Beschreibung:

- Login, Session, CSRF, Rollen-/Rechtesystem, Zugriffskontrolle

Ist-Zustand:

- **weit entwickelt**
- Permission-Pruefung in Admin-Controllern vorhanden
- CSRF-Checks vorhanden

Offen:

- zentrale Policy-Matrix pro Bereich dokumentieren (Admin, API, Module)
- Berechtigungsabdeckung fuer neue Modul-Endpunkte als Regressionstest absichern

### 2.3 Datenzugriff, Settings, Content-Grundlagen

Beschreibung:

- DataProvider, Settings-Store, Content CRUD-Grundpfade, State/Version-Basis

Ist-Zustand:

- **fortgeschritten, aber inkonsistent in Details**
- Grundfunktionen vorhanden
- Theme-Settings waren teils gruppeninkonsistent (`theme` vs `appearance`) und wurden kompatibel abgefangen

Offen:

- Settings-Gruppenkonzept final entscheiden und migrieren
- Integritaetsregeln fuer Keys und Datentypen strikt durchsetzen

### 2.4 Theme-Laufzeit im Core (nicht Theme-Inhalt)

Beschreibung:

- Theme-Discovery, Aktivierung, Asset-Aufloesung, Parent/Child-Mechanik, Render-Kontext

Ist-Zustand:

- **fortgeschritten**
- ThemeManager ist vorhanden
- Admin/Frontend Theme-Auswahl und Aktivierung sind vorhanden
- dynamische Theme-Assets ueber aktive Theme-ID sind umgesetzt

Offen:

- automatisierte Integrations-Tests fuer Theme-Umschaltung und Persistenz
- klare API fuer Theme-Capabilities und Fallbacks

### 2.5 Modul-Laufzeit im Core (nicht Fachmodule)

Beschreibung:

- Modul-Discovery, Manifest-Validierung, Aktivierung/Deaktivierung, Hook-Integration

Ist-Zustand:

- **fortgeschritten**
- ModuleManager und Admin-Managerseiten vorhanden

Offen:

- robustere Manifest-/Dependency-Validierung
- semantische Version-Constraints systemweit absichern
- Upgrade-/Rollback-Strategie standardisieren

### 2.6 Admin-Grundshell und Systemwerkzeuge

Beschreibung:

- zentrale Admin-Navigation, Flash/Fehleranzeigen, Systemseiten (Settings, Themes, Modules, Trash)

Ist-Zustand:

- **weit entwickelt**

Offen:

- UI-Consistency-Review (Texte, Zustandslabels, Form-Hinweise)
- E2E-Szenarien fuer kritische Admin-Flows

### 2.7 Logging, Audit, Cache, Wartung

Beschreibung:

- technische Betriebsfunktionen fuer Nachvollziehbarkeit und Performance

Ist-Zustand:

- **basis bis fortgeschritten**
- Logging/Audit und Caching vorhanden

Offen:

- strukturierte Audit-Events mit Event-Kategorien standardisieren
- Cache-Invalidierungsregeln pro Bereich dokumentieren

---

## 3 Grundsystem-Roadmap (was noch erledigt werden muss)

### Phase GS-1 (kurzfristig)

- Settings-Quelle vereinheitlichen: finale Entscheidung `theme` als kanonische Gruppe
- Migrationsskript: Altwerte aus `appearance` nach `theme` uebernehmen
- Fehlerseiten pro Area absichern (Admin/Frontend) inkl. Tests
- Uebersetzungs-Regression fuer neue Admin-Bereiche (Module/Themes) automatisieren

### Phase GS-2 (mittelfristig)

- Manifest- und Dependency-Engine fuer Module/Themes haerten
- Integrations-Tests fuer Lifecycle-Aktionen (activate/deactivate/uninstall/restore)
- technische Dokumentation der Core-Schnittstellen (stabile API fuer Module/Themes)

### Phase GS-3 (stabilisierung)

- Rechte-/Policy-Testmatrix komplett abdecken
- Performance-Benchmarks fuer Boot/Render/Hook-Ausfuehrung
- Release-Checklist fuer Core-Regressionen einfuehren

---

## 4 Bereiche, die als Module umgesetzt werden sollten

Hinweis: Reihenfolge nach Prioritaet und Abhaengigkeitswert.

### Prioritaet M0 - Plattform-Abhaengigkeitsmodule (zuerst)

Diese Module werden von vielen spaeteren Fachmodulen benoetigt und sollten zuerst entwickelt werden.

1. `media-library`

- Zweck: zentrale Medienverwaltung, Upload/Metadaten/Transformation
- Warum frueh: Abhaengigkeit fuer Blog, Shop, Pages, Formulare, SEO-Vorschau

2. `notification-center`

- Zweck: E-Mail, Systembenachrichtigungen, ggf. Webhooks
- Warum frueh: benoetigt von Formularen, User-Flows, Freigaben, Monitoring

3. `job-queue-scheduler`

- Zweck: Hintergrundjobs, geplante Tasks, Retry-Logik
- Warum frueh: notwendig fuer Updates, Backups, Indexing, Newsletter

4. `search-index`

- Zweck: Inhaltsindex und Suchabstraktion
- Warum frueh: benoetigt von Site-Search, Marketplace, grossen Content-Bestaenden

5. `backup-restore`

- Zweck: Snapshots, Restore, Export/Import
- Warum frueh: Betriebs- und Sicherheitsgrundlage fuer produktive Nutzung

### Prioritaet M1 - Systemnahe Funktionsmodule

1. `seo-toolkit`
2. `forms-workflow`
3. `analytics-dashboard`
4. `api-auth-tokens`

### Prioritaet M2 - Fach-/Projektmodule

1. `blog-pro`
2. `docs-center`
3. `shop-core`
4. `events-booking`

### Prioritaet M3 - Integrationsmodule

1. `crm-connector`
2. `erp-connector`
3. `payment-gateways`
4. `cdn-connector`

---

## 5 Modul-Roadmap (mit Abhaengigkeiten)

### Phase MOD-1 (Foundation)

- `media-library`
- `notification-center`
- `job-queue-scheduler`
- technische Standards: Modul-SDK, Manifest-Validator, Dependency-Resolver

### Phase MOD-2 (Core Features)

- `search-index`
- `backup-restore`
- `forms-workflow`
- `seo-toolkit`

### Phase MOD-3 (Business Layer)

- `analytics-dashboard`
- projektbezogene Fachmodule (Blog/Docs/Shop)

### Phase MOD-4 (Integrationen)

- externe Connectoren und Payments
- Monitoring/Alerting fuer Modulgesundheit

Akzeptanzkriterien pro Modul:

- Manifest gueltig
- Rechte sauber definiert
- keine Theme-Regelverletzung
- i18n vollstaendig (de/en)
- Install/Update/Uninstall/Restore getestet

---

## 6 Roadmap: Theme-System fertigstellen und testbar machen

### 6.1 Was bereits vorhanden ist

- ThemeManager mit Admin/Frontend-Trennung
- Parent/Child-Mechanik
- Theme-Aktivierung im Admin
- dynamische Asset-Aufloesung ueber aktives Theme
- Theme-Marketplace- und Managerseiten

### 6.2 Was noch fehlt, um das Theme-System fertigzustellen

1. Kanonische Persistenz

- Settings-Gruppe final auf `theme` standardisieren
- Migrationspfad fuer Altinstallationen (`appearance`)

2. Vollstaendige Fehleransichten je Area

- `errors/404.twig`, `errors/403.twig`, `errors/500.twig` in Admin und Frontend pruefen
- fallback-sicheres Rendering validieren

3. Theme-Capabilities und Konventionen

- Pflichtdateien und optionale Dateien verbindlich definieren
- Child-Theme-Override-Regeln dokumentieren (Templates, Assets, Translation)

4. Hook- und Datenvertrag fuer Themes

- welche Kontextdaten Themes immer bekommen
- welche Hook-Ausgaben in welchen Slots erwartet werden

5. i18n-Qualitaet

- alle Theme- und Modulseiten ohne rohe Key-Ausgabe
- DE/EN Schluesselgleichheit pruefbar machen

6. Sicherheits- und Robustheitsregeln

- Path Traversal Schutz fuer Theme-Dateien weiter haerten
- Uninstall/Restore Edge-Cases automatisiert testen

### 6.3 Test-Roadmap fuer Theme-System

#### T-1 Automatisierte Integrations-Tests

- Theme aktivieren (Admin) -> Admin rendert mit neuem Theme
- Theme aktivieren (Frontend) -> Frontend rendert mit neuem Theme
- Persistenz nach Neustart/Boot bleibt erhalten
- Parent/Child-Fallback funktioniert

#### T-2 Render- und Asset-Tests

- CSS/JS laden aus aktivem Theme, nicht aus `default`
- Missing-Asset-Verhalten definiert und getestet
- Area-spezifische 404-Seiten rendern im richtigen Theme

#### T-3 Security-Tests

- illegale Theme-ID/Path ablehnen
- uninstall aktives Theme blockieren
- restore nur in erlaubte Theme-Pfade

#### T-4 UI/Manuelle QA

- Theme-Wechsel-Flows im Admin komplett durchklicken
- Dark/Light je Theme pruefen
- Responsive Smoke-Test in zentralen Seiten

#### T-5 Regression-Suite in CI

- `php -l` auf Core/Controller/Manager
- Twig-Lint/Render-Smoke fuer Haupttemplates
- Snapshot-Tests fuer kritische Seiten (Admin-Dashboard, Frontend-Home, Fehlerseiten)

---

## 7 Kurzfazit

Feste Systemverantwortung und modulare Erweiterungen sind in Chamy bereits gut angelegt, aber nicht in allen Bereichen final konsolidiert.

Die wichtigsten naechsten Schritte sind:

- Settings-Konsolidierung fuer Themes
- harte Lifecycle- und Integrations-Tests
- Priorisierung der Abhaengigkeitsmodule (M0) vor Fachmodulen
- Theme-Testmatrix in CI aufnehmen

Damit wird Chamy als Plattform stabiler und gleichzeitig schneller erweiterbar.
