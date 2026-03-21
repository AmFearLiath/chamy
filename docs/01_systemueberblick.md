# 01 Systemueberblick

## 1.1 Produktbild

Chamy ist ein modulares CMS mit klarer Trennung zwischen:
- Kernlogik (Core)
- Erweiterungen (Module)
- Darstellung (Themes)
- strukturierten Inhalten (Content + Content Types)

Das System ist als klassische serverseitige PHP-Anwendung aufgebaut (kein SPA-Kern), mit Twig fuer Rendering und einem eigenen Routing-/Manager-Ansatz.

## 1.2 Kernbausteine im Ueberblick

- `Kernel` orchestriert Initialisierung und Laufzeit.
- `ManagerRegistry` haelt alle zentralen Manager und bootet sie.
- `Router` verarbeitet Web-, Admin- und API-Anfragen.
- `DataProvider` abstrahiert Datenzugriff (`mock` vs `live`).
- `ThemeManager` rendert Admin und Frontend mit Twig.
- `ModuleManager` entdeckt und bootet Module aus `modules/`.

## 1.3 Implementierter Fokus

Aktuell stark ausgebaut:
- Adminbereich (Content, Nutzer, Rollen, Rechte, Settings, Themes, Module, Trash)
- Content-CRUD inkl. Status und Versionen
- API v1 Basisendpunkte
- Asset-Library im Admin (Icon-/Font-Management inkl. Google Fonts)

Teilweise umgesetzt / MVP:
- Marketplace-Manager als lokale/MVP-Basis
- Layout-/Component-Manager ohne umfangreiche Admin-Editoroberflaeche
- API-Authentifizierung mit Inkonsistenz zur Token-Tabelle (Details in Kapitel Sicherheit)

## 1.4 Laufzeitmodi fuer Daten

Chamy kann mit zwei Datenquellen arbeiten:

- `DATA_SOURCE=mock`
  - In-Memory/Seed-basierter Betrieb ueber `core/Data/MockDataProvider.php`
  - praktisch fuer Demo und schnelle Entwicklung

- `DATA_SOURCE=live`
  - MySQL/MariaDB ueber `core/Data/LiveDataProvider.php`
  - produktionsnahes Verhalten

## 1.5 Benutzerrollen (funktional)

Im System sind mindestens diese Rollen vorgesehen:
- `admin`
- `editor`
- `viewer`

Die finale Rechtezuweisung erfolgt ueber Permissions und Role-Permission-Mapping.

## 1.6 HTTP-Flaechen

- Frontend: `GET /`, `/seiten`, `/seite/{slug}`, `/artikel`, `/artikel/{slug}`
- Admin: `GET/POST /admin/...` mit Login- und Rechtepruefungen
- API: `GET/POST/PUT/DELETE /api/v1/...`

## 1.7 Technologiestack (Repository-Stand)

- PHP `^8.2`
- Twig `^3.8`
- vlucas/phpdotenv `^5.6`
- PDO (DB-Zugriff)
- PHPUnit im Dev-Stack (Teststruktur vorhanden, aktuell kaum/keine Testdateien)

## 1.8 Wichtige Designentscheidung

Chamy kombiniert klassische CMS-Funktionen mit einer Manager-zentrierten Kernarchitektur.

Konsequenz:
- Feature-Erweiterungen sind meist ueber Manager + Controller + Theme-Templates nachvollziehbar.
- Viele Betriebsregeln sind zentral im AdminController und in den Managern konzentriert.
