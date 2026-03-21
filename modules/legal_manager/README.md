# Legal Manager – Modul für Chamy CMS

**Version:** 1.0.0  
**Kompatibilität:** Chamy CMS ≥ 0.4.0

---

## Übersicht

Das **Legal Manager**-Modul stellt eine vollständige, DSGVO-konforme Verwaltung von Rechtstexten bereit. Es verwaltet Datenschutzerklärung, Impressum, Einwilligungs-Kategorien, externe Dienste und bietet einen Audit-Scanner sowie anonymisierte Statistiken.

### Hauptfunktionen

- **Stammdaten-Verwaltung** – Zentrale Pflege von Firmen-, Kontakt-, Handelsregister- und Datenschutzbeauftragten-Daten
- **Datenschutzerklärung** – Blockbasierter Editor mit Versionierung & Veröffentlichungsfunktion
- **Impressum** – Blockbasierter Editor mit Versionierung & Veröffentlichungsfunktion
- **Einwilligungs-Kategorien** – Verwaltung von Consent-Kategorien (essentiell, Analyse, Marketing …)
- **Externe Dienste** – Registrierung und Kategorisierung aller eingesetzten Drittanbieter-Dienste
- **Audit-Scanner** – Erkennt externe Ressourcen in Templates, CSS, JS und prüft gegen deklarierte Dienste
- **Statistiken** – Datenschutzkonformes Tracking von Seitenaufrufen mit IP-/UA-Hashing
- **Frontend-Seiten** – Automatische Darstellung von Datenschutz- und Impressum-Seiten

---

## Installation

1. Verzeichnis `modules/legal_manager/` ins Chamy CMS kopieren
2. Im Admin → Module → Legal Manager aktivieren
3. Datenbank-Migrationen ausführen: `php chamy migrate`
4. Standard-Blöcke werden bei Erstaufruf automatisch angelegt (konfigurierbar)

## Deinstallation

Das Modul kann über den ModuleManager deaktiviert werden. Die Datenbank-Tabellen bleiben bestehen und können bei Bedarf manuell entfernt werden.

---

## Datenbank-Tabellen

| Tabelle | Beschreibung |
|---|---|
| `chamy_legal_base_data` | Schlüssel/Wert-Paare für Stammdaten |
| `chamy_legal_documents` | Versionierte Dokument-Snapshots |
| `chamy_legal_document_blocks` | Inhaltsblöcke pro Dokument/Locale |
| `chamy_legal_services` | Externe Dienste (Google Analytics etc.) |
| `chamy_legal_consent_categories` | Consent-Kategorien |
| `chamy_legal_audit_results` | Audit-Scan-Ergebnisse |
| `chamy_legal_stats` | Anonymisierte Zugriffs-Statistiken |

---

## Berechtigungen

| Permission | Beschreibung |
|---|---|
| `legal.view` | Modul-Seiten anzeigen |
| `legal.manage` | Inhalte bearbeiten (Stammdaten, Blöcke, Dienste) |
| `legal.publish` | Dokumente veröffentlichen (neue Version erstellen) |
| `legal.audit.view` | Audit-Ergebnisse ansehen |
| `legal.audit.run` | Audit-Scan starten |
| `legal.stats.view` | Statistiken einsehen |
| `legal.settings` | Moduleinstellungen ändern |

---

## Konfiguration

Die Datei `config.json` enthält die Standardwerte:

| Option | Standard | Beschreibung |
|---|---|---|
| `privacy_slug` | `datenschutz` | URL-Slug der Datenschutzseite |
| `imprint_slug` | `impressum` | URL-Slug der Impressumsseite |
| `default_locale` | `de` | Standard-Sprache |
| `frontend_page_enabled` | `true` | Frontend-Seiten aktiv |
| `auto_create_default_blocks` | `true` | Standard-Blöcke bei Erstaufruf anlegen |
| `stats_enabled` | `true` | Statistik-Erfassung aktiv |
| `stats_anonymize_ip` | `true` | IP-Adressen hashen |
| `consent_management_enabled` | `false` | Consent-Verwaltung aktivieren |
| `audit_on_publish` | `false` | Audit bei Veröffentlichung starten |

---

## Architektur

```
modules/legal_manager/
├── manifest.json            # Modul-Manifest (Hooks, Permissions, Metadaten)
├── config.json              # Laufzeit-Konfiguration
├── module.php               # Einstiegspunkt (Routen, Hooks, Permissions)
├── languages/
│   ├── de.php               # Deutsche i18n-Schlüssel (~250)
│   └── en.php               # Englische i18n-Schlüssel
├── migrations/
│   ├── 001_create_legal_base_data.php
│   ├── 002_create_legal_documents.php
│   ├── 003_create_legal_services.php
│   ├── 004_create_legal_consent_categories.php
│   ├── 005_create_legal_audit_results.php
│   └── 006_create_legal_stats.php
├── src/
│   ├── LegalService.php          # Datenzugriff + Business-Logik
│   ├── LegalAuditService.php     # Audit-Scanner
│   └── LegalDocumentBuilder.php  # HTML-Rendering der Rechtsdokumente
├── templates/
│   ├── admin/legal/              # 11 Admin-Templates
│   └── frontend/legal/           # 2 Frontend-Templates
└── assets/css/
    ├── legal-manager-admin.css
    └── legal-manager-frontend.css
```

### Service-Klassen

- **LegalService** – CRUD für Stammdaten, Blöcke, Dokumente, Dienste, Consent-Kategorien, Statistiken
- **LegalAuditService** – Scannt Dateien nach externen Ressourcen und vergleicht mit deklarierten Diensten
- **LegalDocumentBuilder** – Baut aus aktiven Blöcken vollständige HTML-Dokumente, ersetzt Platzhalter mit Stammdaten

---

## Hooks

### Registrierte Hooks

| Hook | Priorität | Beschreibung |
|---|---|---|
| `admin.sidebar.modules` | 50 | Rendert den Legal-Manager-Navigationsbaum |
| `admin.head` | 50 | Injiziert Admin-CSS auf `/admin/legal/*`-Seiten |

### Erweiterbare Hooks (für andere Module)

| Hook | Payload | Beschreibung |
|---|---|---|
| `legal.register_blocks` | – | Erlaubt anderen Modulen, Blöcke zu registrieren |
| `legal.register_services` | – | Erlaubt anderen Modulen, Dienste zu deklarieren |

---

## Template-Override

Admin-Templates können vom Theme überschrieben werden, indem eine gleichnamige Datei im Theme-Verzeichnis liegt:

```
themes/admin/<theme>/templates/legal/dashboard.twig
```

Die Module-Templates dienen als Fallback. Die Auflösungs-Reihenfolge:

1. `themes/admin/<theme>/templates/`
2. `modules/legal_manager/templates/admin/`

---

## Admin-Routen

| Methode | Route | Beschreibung |
|---|---|---|
| GET | `/admin/legal` | Dashboard |
| GET/POST | `/admin/legal/base-data` | Stammdaten |
| GET | `/admin/legal/privacy` | Datenschutz-Übersicht |
| GET/POST | `/admin/legal/privacy/block/{id?}` | Block bearbeiten/anlegen |
| POST | `/admin/legal/privacy/block/delete/{id}` | Block löschen |
| POST | `/admin/legal/privacy/reorder` | Blöcke neu sortieren |
| POST | `/admin/legal/privacy/publish` | Datenschutz veröffentlichen |
| GET | `/admin/legal/imprint` | Impressum-Übersicht |
| GET/POST | `/admin/legal/imprint/block/{id?}` | Block bearbeiten/anlegen |
| POST | `/admin/legal/imprint/block/delete/{id}` | Block löschen |
| POST | `/admin/legal/imprint/reorder` | Blöcke neu sortieren |
| POST | `/admin/legal/imprint/publish` | Impressum veröffentlichen |
| GET/POST | `/admin/legal/consent` | Consent-Kategorien |
| POST | `/admin/legal/consent/delete/{id}` | Consent-Kategorie löschen |
| GET/POST | `/admin/legal/services` | Dienste-Verwaltung |
| POST | `/admin/legal/services/delete/{id}` | Dienst löschen |
| GET | `/admin/legal/audit` | Audit-Ergebnisse |
| POST | `/admin/legal/audit/run` | Audit starten |
| GET | `/admin/legal/stats` | Statistiken |
| GET/POST | `/admin/legal/settings` | Einstellungen |

## Frontend-Routen

| Methode | Route | Beschreibung |
|---|---|---|
| GET | `/{privacy_slug}` | Datenschutzerklärung (Standard: `/datenschutz`) |
| GET | `/{imprint_slug}` | Impressum (Standard: `/impressum`) |

---

## Sicherheit

- Alle POST-Routen sind CSRF-geschützt
- Jede Route prüft Berechtigungen über `PermissionManager`
- Statistiken anonymisieren IP-Adressen via SHA-256 mit täglichem Salt
- Der Audit-Scanner scannt nur lokale Dateien, keine externen Verbindungen
- SQL-Injection wird durch Prepared Statements verhindert
- XSS-Schutz durch Twig-Autoescaping (`|raw` nur bei vertrauenswürdigen Inhalten)
