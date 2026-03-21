# Chamy – System Architecture

## Zweck dieses Dokuments

Dieses Dokument beschreibt die technische Architektur von Chamy. Es erklärt, wie das System intern aufgebaut ist, welche Kernkomponenten existieren und wie diese miteinander interagieren.

Dieses Dokument konzentriert sich ausschließlich auf die technische Struktur des Systems.

Detaillierte Beschreibungen einzelner Bereiche befinden sich in den entsprechenden Dokumenten:

- Modul-System → `03_module_system.md`
- Theme-System → `04_theme_system.md`
- Layout-, Komponenten- und Content-System → `05_layout_component_content_system.md`
- Marketplace, Sicherheitsregeln und Prüfprozesse → `06_marketplace_security_and_rules.md`

---

# 1 Architekturprinzipien

Die Architektur von Chamy folgt mehreren grundlegenden Prinzipien, die sicherstellen sollen, dass das System langfristig wartbar, erweiterbar und stabil bleibt.

## Modulare Struktur

Das gesamte System ist modular aufgebaut. Funktionen werden nicht direkt im Kern implementiert, wenn sie auch als eigenständige Module realisiert werden können.

Der Core stellt die Infrastruktur bereit, während funktionale Erweiterungen über Module integriert werden.

## Klare Verantwortlichkeiten

Jede Systemkomponente besitzt eine klar definierte Aufgabe. Logik, Darstellung und Inhalte werden strikt voneinander getrennt.

## Theme-gesteuerte Darstellung

Die visuelle Darstellung des Systems wird vollständig über das Theme-System gesteuert.

Module liefern Funktionalität und Daten, nicht jedoch eine eigene visuelle Frontend-Struktur.

## Routing-System

Das Routing-System ist verantwortlich für die Zuordnung von Anfragen zu Controllern oder Systemfunktionen.

Das Routing-System verarbeitet:

- Seitenanfragen
- API-Anfragen
- Modulrouten
- Systemrouten

Module können eigene Routen registrieren, die anschließend durch das Routing-System verarbeitet werden.

## Modul-Routing

Module können eigene Routen definieren.

Diese Routen werden beim Aktivieren des Moduls im Routing-System registriert.

Routen können betreffen:

- Frontend-Seiten
- Adminbereiche
- API-Endpunkte

## Interface-Registry

Alle Systemschnittstellen werden zentral registriert.

Diese Registry dokumentiert:

- verfügbare Systeminterfaces
- erwartete Parameter
- Rückgabestrukturen

Dadurch können Module und Themes sicher mit dem System interagieren.

## Erweiterbarkeit über definierte Schnittstellen

Alle Erweiterungen erfolgen über definierte System-Schnittstellen, Hooks und Manager. Dadurch bleibt das System kontrollierbar und vorhersehbar.

## Wiederverwendung vor Neuerstellung

Vor jeder neuen Implementierung wird geprüft, ob bereits eine geeignete Funktion, Komponente oder ein Layout im System existiert.

Neue Funktionen werden nur erstellt, wenn keine geeignete Lösung vorhanden ist. Neue Elemente müssen anschließend global registriert, dokumentiert und kommentiert werden.

## Mehrsprachigkeit

Das gesamte System ist mehrsprachig aufgebaut. Texte werden niemals direkt im Code oder in Templates definiert.

Die initialen Systemsprachen sind:

- Deutsch (Standard)
- Englisch

---

# 2 Systemschichten

Die Architektur von Chamy ist in mehrere technische Schichten unterteilt. Diese Trennung verhindert, dass sich Systemlogik, Darstellung und Erweiterungen unkontrolliert vermischen.

## Presentation Layer

Diese Schicht ist für die Darstellung verantwortlich.

Sie umfasst:

- Twig Templates
- Themes
- UI-Komponenten
- Layoutdefinitionen

## Application Layer

Diese Schicht verarbeitet Benutzerinteraktionen und steuert Inhalte.

Sie umfasst:

- Inhaltsverwaltung
- Seitenlogik
- Layoutstrukturen
- Editor-System

## Extension Layer

Diese Schicht ermöglicht Erweiterungen durch Module.

Sie umfasst:

- Modulregistrierung
- Modul-Lifecycle
- Hook-Integration

## Core Layer

Der Core bildet das technische Fundament des Systems.

Er umfasst:

- Kernel
- Manager-System
- Routing
- Sicherheitsmechanismen

## Infrastructure Layer

Diese Schicht stellt grundlegende technische Dienste bereit.

Beispiele:

- Datenbankzugriff
- Caching
- Dateisystem
- Logging

---

# 3 Systemkernel

Der Kernel ist das zentrale Steuerungselement von Chamy.

Beim Start des Systems übernimmt der Kernel die Initialisierung der wichtigsten Systemkomponenten.

Zu seinen Aufgaben gehören:

- Laden der Systemkonfiguration
- Registrierung aller Manager
- Initialisierung des Hook-Systems
- Laden aktiver Module
- Aktivierung des Theme-Systems
- Initialisierung der Routing-Struktur
- Vorbereitung der Anfrageverarbeitung

Der Kernel bildet damit die zentrale Steuerinstanz für alle weiteren Systemabläufe.

---

# 4 Manager-System

Viele zentrale Funktionen von Chamy werden über spezialisierte Manager verwaltet.

Manager sind Systemkomponenten, die für Registrierung, Verwaltung und Zugriff auf bestimmte Systembereiche zuständig sind.

Diese Struktur sorgt dafür, dass Systembereiche klar voneinander getrennt bleiben.

## Wichtige Manager

### ModuleManager

Verwaltet:

- Installation von Modulen
- Aktivierung
- Deaktivierung
- Updates
- Entfernung

### ThemeManager

Verwaltet:

- verfügbare Themes
- Aktivierung von Themes
- Theme-Konfiguration
- Theme-bezogene Ressourcen

### HookManager

Verwaltet das Hook-System des gesamten Systems.

Der HookManager ermöglicht:

- Registrierung neuer Hooks
- Verwaltung vorhandener Hooks
- Anzeige verfügbarer Hooks
- Vorschau des Template-Codes
- Anzeige der voraussichtlichen Einbindungsdatei

### LayoutManager

Verwaltet Layoutdefinitionen und Layoutquellen.

Layouts können stammen aus:

- System
- Themes
- Modulen

### ComponentManager

Verwaltet wiederverwendbare Komponenten für den Inhaltseditor.

Komponenten können aus Layoutstrukturen erzeugt werden und anschließend im Editor verwendet werden.

### ContentManager

Verwaltet Inhalte, Seiten und strukturierte Daten.

### LanguageManager

Steuert Mehrsprachigkeit und Übersetzungen.

### MarketplaceManager

Verwaltet Marketplace-Integration sowie Erweiterungen für Module und Themes.

### UpdateManager

Verwaltet Updates für:

- System
- Module
- Themes

### PermissionManager

Verwaltet Rechte und Zugriffskontrollen im System.

### ConfigManager

Verwaltet Systemkonfigurationen und globale Einstellungen.

### AssetManager

Verwaltet statische Ressourcen wie:

- Styles
- Skripte
- Medien

---

# 5 Hook-System

Chamy verwendet ein zentrales Hook-System, um Erweiterungen kontrolliert in Systemabläufe einzubinden.

Hooks ermöglichen es Modulen und Themes, sich an definierten Stellen in das System einzuklinken.

Das Hook-System wird vollständig durch den HookManager verwaltet.

Der HookManager ermöglicht:

- Registrierung neuer Hooks
- Verwaltung vorhandener Hooks
- Anzeige verfügbarer Hooks
- Vorschau für Template-Code
- Anzeige der voraussichtlichen Einbindungsdatei

Hooks bilden eine zentrale Grundlage für Erweiterbarkeit im gesamten System.

---

# 6 Schnittstellen und Interfaces

Während der Entwicklung werden alle wichtigen System-Schnittstellen in separaten Dateien dokumentiert.

Diese Schnittstellen beschreiben:

- verfügbare Systemfunktionen
- Erweiterungspunkte
- erwartete Datenstrukturen
- Rückgabewerte

Schnittstellen sollen möglichst früh in das System integriert werden, damit Erweiterungen darauf aufbauen können.

Alle Schnittstellen werden:

- dokumentiert
- kommentiert
- mit funktionsrelevanten Hinweisen versehen

---

# 7 Entwicklungsprotokolle

Größere Änderungen an der Systemarchitektur oder an zentralen Systemfunktionen werden protokolliert.

Das Protokoll dient dazu:

- Änderungen nachvollziehbar zu machen
- Systementwicklung zu dokumentieren
- Auswirkungen auf Module und Themes zu erkennen

Kleine Codeänderungen werden nicht protokolliert.

Dokumentiert werden ausschließlich strukturelle oder funktionale Eingriffe in das System.

---

# 8 Modul- und Theme-Lifecycle

Die Systemarchitektur unterstützt Lifecycle-Prozesse für Module und Themes.

Diese Prozesse umfassen:

- Installation
- Aktivierung
- Deaktivierung
- Aktualisierung
- Entfernung

Die detaillierte Beschreibung dieser Prozesse befindet sich in den jeweiligen Dokumentationen für Module und Themes.

---

# 9 Datenfluss im System

Der typische Datenfluss innerhalb von Chamy folgt einer klaren Struktur.

Request

→ Kernel

→ Router

→ Controller

→ Module oder Content-System

→ Theme Rendering

→ Response

Der Kernel steuert dabei die gesamte Anfrageverarbeitung.

---

# 10 Systemstart

Beim Start des Systems wird ein definierter Bootprozess ausgeführt.

Dieser Prozess umfasst:

1. Laden der Systemkonfiguration
2. Initialisierung des Kernels
3. Registrierung aller Manager
4. Initialisierung des Hook-Systems
5. Laden aktiver Module
6. Aktivierung des Themes
7. Aufbau des Routings
8. Start der Anfrageverarbeitung

Dieser Bootprozess stellt sicher, dass alle Systemkomponenten korrekt initialisiert werden, bevor eine Anfrage verarbeitet wird.

---

# Zusammenfassung

Die Systemarchitektur von Chamy basiert auf einem modularen Aufbau mit klar getrennten Verantwortlichkeiten.

Der Core stellt die Infrastruktur bereit, während Themes die Darstellung steuern und Module zusätzliche Funktionen integrieren.

Manager-Systeme, Hooks und definierte Schnittstellen sorgen dafür, dass Erweiterungen kontrolliert in das System eingebunden werden können.

Diese Architektur ermöglicht eine stabile, erweiterbare und langfristig wartbare Systembasis.

