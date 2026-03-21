# Chamy – Theme System

## Zweck dieses Dokuments

Dieses Dokument beschreibt das Theme-System von Chamy. Es definiert, wie Themes aufgebaut sind, welche Aufgaben sie übernehmen und welche Regeln für Darstellung und Layout gelten.

Das Theme-System ist verantwortlich für die komplette visuelle Darstellung des Systems. Dazu gehören sowohl das Frontend als auch der Administrationsbereich.

Dieses Dokument beschreibt ausschließlich das Darstellungssystem. Details zu anderen Systembereichen befinden sich in folgenden Dokumenten:

- Systemarchitektur → 02_system_architecture.md
- Modulsystem → 03_module_system.md
- Layout-, Komponenten- und Content-System → 05_layout_component_content_system.md
- Marketplace und Sicherheitsregeln → 06_marketplace_security_and_rules.md

---

# 1 Grundidee des Theme-Systems

Das Theme-System steuert die visuelle Darstellung von Chamy.

Themes definieren:

- Layoutstrukturen
- UI-Komponenten
- Templates
- visuelle Styles
- Struktur der Seiten

Die Darstellung wird vollständig über Themes gesteuert. Module liefern lediglich Daten und Funktionen.

Dadurch bleibt die Darstellung im gesamten System konsistent und kontrollierbar.

---

# 2 Rolle von Themes im System

Themes besitzen eine klar definierte Aufgabe.

## Themes steuern

- visuelle Darstellung
- Layoutstruktur
- UI-Komponenten
- Darstellung von Inhalten
- Darstellung von Modulinhalten

## Themes steuern nicht

- Systemlogik
- Geschäftslogik
- Datenverarbeitung

Diese Bereiche gehören zum Core oder zu Modulen.

---

# 3 Template-Engine

Chamy verwendet Twig als Template-Engine.

Twig ermöglicht eine klare Trennung zwischen:

- Logik
- Daten
- Darstellung

Das System stellt Variablen und Daten bereit, während Twig die Darstellung rendert.

Typische Funktionen von Twig im System:

- Template-Vererbung
- Includes
- Komponentenstruktur
- Filter und Funktionen

---

# 4 Theme-Struktur

Jedes Theme besitzt eine definierte Ordnerstruktur.

Beispiel:

/themes/<theme-name>/

- theme.json
- templates/
- components/
- layouts/
- module-overrides/
- assets/
- languages/

## Typische Inhalte

### theme.json

Definiert Metadaten des Themes.

### templates

Enthält Twig-Templates für Seiten und Systembereiche.

### components

Enthält wiederverwendbare UI-Komponenten.

### layouts

Definiert Layoutstrukturen für Seiten.

### module-overrides

Enthält darstellungsbezogene Anpassungen für Module.

### assets

Enthält Styles, Skripte und Medien.

### languages

Enthält Übersetzungen für themebezogene Texte.

---

# 5 Theme-Manifest

Das Theme besitzt eine Manifestdatei (theme.json).

Diese Datei beschreibt grundlegende Informationen.

Beispiel:

{
  "id": "example.theme",
  "name": "Example Theme",
  "version": "1.0.0",
  "author": "Example",
  "description": "Standard Theme",
  "chamy": {
    "min": "1.0",
    "max": "1.x"
  }
}

Diese Informationen werden vom ThemeManager verwendet.

---

# 6 Dark- und Light-Mode

Alle Themes müssen einen Dark- und Light-Mode unterstützen.

Der Benutzer kann den Darstellungsmodus wechseln.

Themes müssen dafür entsprechende Styles bereitstellen.

Typische Umsetzung:

- CSS-Variablen
- getrennte Styles
- dynamische Umschaltung

---

# 7 Darstellung von Modulen

Module liefern Funktionen und Daten.

Die Darstellung von Modulinhalten erfolgt über das Theme-System.

Module dürfen keine eigenen Frontend-Styles erzwingen.

Wenn Module eigene Styles enthalten, werden diese ignoriert.

Das Theme bestimmt die Darstellung.

---

# 8 Modul-Overrides

Wenn ein Modul spezielle Darstellungsanpassungen benötigt, können Overrides im Theme erstellt werden.

Diese Overrides befinden sich niemals im Modul selbst.

Beispielstruktur:

/themes/<theme>/module-overrides/<module-id>.css

Die Override-Datei enthält:

- Informationen über das Modul
- Versionsinformationen
- Style-Anpassungen

Dadurch bleiben Module updatefähig.

---

# 9 Template-Struktur

Themes nutzen eine klare Template-Struktur.

Beispiel:

/templates/

- layout.twig
- page.twig
- error.twig
- partials/
- components/

Templates können andere Templates einbinden.

Beispiel:

{% include "components/header.twig" %}

Twig ermöglicht außerdem Template-Vererbung.

---

# 10 Komponenten

Themes können UI-Komponenten definieren.

Diese Komponenten werden in Templates verwendet.

Beispiele:

- Navigation
- Buttons
- Karten
- Formulare

Komponenten ermöglichen eine konsistente UI.

---

# 11 Layoutdefinitionen

Themes definieren Layoutstrukturen für Seiten.

Layouts bestimmen:

- Seitenstruktur
- Container
- Inhaltsbereiche

Layouts können aus verschiedenen Quellen stammen:

- System
- Themes
- Module

---

# 12 Integration mit dem Inhaltseditor

Während des Layoutbaus können Layoutteile in wiederverwendbare Komponenten umgewandelt werden.

Diese Komponenten werden anschließend im Inhaltseditor verwendet.

Ein eigener Komponenten-Manager verwaltet diese Elemente.

---

# 13 Theme-Aktivierung

Der ThemeManager verwaltet verfügbare Themes.

Beim Aktivieren eines Themes werden:

- Templates registriert
- Assets geladen
- Layoutdefinitionen aktiviert

---

# 14 Theme-Updates

Themes können Updates über den Marketplace erhalten.

Updates können enthalten:

- neue Layouts
- neue Komponenten
- Fehlerkorrekturen

Auto-Updates können optional aktiviert werden.

Diese Einstellung kann pro Benutzer oder pro Theme definiert werden.

---

# 15 Sicherheit und Regeln

Themes müssen bestimmte Systemregeln einhalten.

Dazu gehören:

- Nutzung der Theme-Struktur
- Einhaltung der Template-Regeln
- Nutzung des Sprachsystems

Hardcodierte Texte sind nicht erlaubt.

Alle Texte müssen über das Sprachsystem verwaltet werden.

# 16 Theme-Vererbung

Themes können andere Themes erweitern.

Ein Theme kann dadurch:

- bestehende Templates überschreiben
- zusätzliche Komponenten hinzufügen
- Styles erweitern

Dies ermöglicht z. B.:

- Child-Themes
- Designvarianten
- projektspezifische Anpassungen

---

# Zusammenfassung

Das Theme-System steuert die komplette visuelle Darstellung von Chamy.

Themes definieren Layouts, Templates und UI-Komponenten.

Module liefern Funktionen und Daten, während Themes für Darstellung verantwortlich sind.

Diese klare Trennung sorgt für ein konsistentes und erweiterbares System.