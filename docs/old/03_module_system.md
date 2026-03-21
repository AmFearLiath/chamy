# Chamy – Modulsystem

## Zweck dieses Dokuments

Dieses Dokument beschreibt das Modulsystem von Chamy. Es definiert, wie Module aufgebaut sind, welche Regeln für Erweiterungen gelten und wie Module in das System integriert werden.

Das Ziel des Modulsystems ist es, neue Funktionen zu ermöglichen, ohne den Core verändern zu müssen.

Das Modulsystem arbeitet eng mit folgenden Systembereichen zusammen:

- Systemarchitektur → `02_system_architecture.md`
- Theme-System → `04_theme_system.md`
- Layout-, Komponenten- und Content-System → `05_layout_component_content_system.md`
- Marketplace und Sicherheitsregeln → `06_marketplace_security_and_rules.md`

---

# 1 Grundidee des Modulsystems

Module sind Erweiterungspakete, die zusätzliche Funktionen für Chamy bereitstellen.

Ein Modul kann beispielsweise bereitstellen:

- neue Systemfunktionen
- neue Verwaltungsbereiche im Adminbereich
- neue Inhaltstypen
- Integrationen mit externen Systemen
- Erweiterungen bestehender Systeme

Module verändern niemals direkt den Core.

Alle Erweiterungen erfolgen über definierte Schnittstellen, Manager und Hooks.

---

# 2 Rolle von Modulen im System

In Chamy besitzen Module eine klar definierte Rolle.

## Module liefern

- Funktionalität
- Daten
- Systemlogik
- Integrationen

## Module liefern nicht

- eigenständige Frontend-Designsysteme
- eigene visuelle Oberflächen außerhalb der Theme-Regeln

Die Darstellung erfolgt immer über das Theme-System.

---

# 3 Modulstruktur

Module werden als Pakete ausgeliefert.

Format:

ZIP-Paket

Ein Modul besitzt eine definierte interne Struktur.

Beispielstruktur:

/modules/<module-id>/

- manifest.json
- module.php
- src/
- config/
- languages/
- migrations/
- hooks/

## Typische Inhalte eines Moduls

### Manifest

Definiert grundlegende Metadaten und Systemanforderungen.

### Modulcode

Enthält die eigentliche Funktionalität des Moduls.

### Sprachdateien

Module müssen Mehrsprachigkeit unterstützen.

### Konfigurationsdefinitionen

Definieren Einstellungen für das Modul.

### Datenbankmigrationen (optional)

Werden beim Installationsprozess ausgeführt.

### Installationsroutinen (optional)

Können zusätzliche Setup-Prozesse enthalten.

---

# 4 Manifest-Datei

Jedes Modul besitzt eine Manifestdatei.

Diese Datei enthält wichtige Informationen für das System.

Typische Felder:

- Modul-ID
- Name
- Version
- Autor
- Beschreibung
- kompatible Chamy-Version
- Abhängigkeiten
- benötigte Rechte
- Einstiegspunkt des Moduls

Beispiel:

```
{
  "id": "example.blog",
  "name": "Blog Modul",
  "version": "1.0.0",
  "author": "Example",
  "description": "Erweitert Chamy um ein Blogsystem.",
  "chamy": {
    "min": "1.0",
    "max": "1.x"
  },
  "dependencies": [],
  "permissions": [],
  "entry": "module.php"
}
```

Das Manifest wird während Installation und Updates validiert.

---

# 5 Modul-Lifecycle

Module durchlaufen mehrere definierte Zustände.

1 Installation

Das Modul wird erkannt, geprüft und registriert.

2 Aktivierung

Das Modul wird geladen und in das System integriert.

3 Deaktivierung

Das Modul wird deaktiviert, bleibt jedoch installiert.

4 Update

Das Modul wird auf eine neue Version aktualisiert.

5 Deinstallation

Das Modul wird vollständig entfernt.

Diese Prozesse werden durch den **ModuleManager** gesteuert.

---

# 6 Integration über Hooks

Module integrieren sich über das Hook-System in Chamy.

Hooks ermöglichen es Modulen:

- Systemprozesse zu erweitern
- neue Funktionen einzubinden
- zusätzliche Inhalte bereitzustellen

Die Verwaltung aller Hooks erfolgt über den HookManager.

Hooks definieren klar, an welchen Stellen Erweiterungen erlaubt sind.

---

# 7 Modul-Konfiguration

Module können eigene Konfigurationsbereiche besitzen.

Diese Konfigurationsoberflächen können technisch außerhalb von Twig implementiert werden.

Die Darstellung erfolgt jedoch immer innerhalb des Admin-Themes.

### Wichtige Regeln

- Module müssen das Admin-Theme verwenden
- eigene Admin-Designsysteme sind nicht erlaubt
- Overrides für Modul-Konfigurationsoberflächen sind nicht erlaubt

---

# 8 Darstellung von Modulen

Die Darstellung von Modulinhalten erfolgt über das Theme-System.

Module liefern Daten und Funktionen.

Die visuelle Darstellung wird durch Themes gesteuert.

### Wichtige Regel

Module dürfen keine eigenen Frontend-Styles erzwingen.

Wenn ein Modul eigene Styles bereitstellt, werden diese ignoriert.

Das System verwendet stattdessen die Styles des aktiven Themes.

Dies stellt sicher, dass die Darstellung im gesamten System konsistent bleibt.

---

# 9 Modul-Overrides im Theme

Wenn ein Modul spezielle Darstellungsanpassungen benötigt, werden diese im Theme gespeichert.

Overrides befinden sich niemals im Modul selbst.

Stattdessen erstellt das Theme eine modulbezogene Override-Datei.

Beispielstruktur:

/themes/<theme>/module-overrides/<module-id>.css

Diese Datei enthält:

- Informationen zum Modul
- Version des Moduls
- Style-Overrides

Dadurch bleiben Module updatefähig und Darstellung bleibt themegesteuert.

---

# 10 Modulprüfung

Bevor ein Modul im Marketplace veröffentlicht werden kann, wird es automatisch geprüft.

Die Prüfung kontrolliert unter anderem:

- Einhaltung der Modulstruktur
- gültiges Manifest
- kompatible Systemversion
- Nutzung erlaubter Hooks
- Einhaltung des Theme-Systems

Zusätzlich wird geprüft:

- ob unerlaubtes Inline-CSS verwendet wird
- ob eigene Frontend-Styles eingebunden werden
- ob das Modul versucht, Theme-Regeln zu umgehen

Module, die diese Regeln verletzen, werden nicht freigegeben.

---

# 11 Modul-SDK

Chamy stellt ein SDK für Modulentwickler bereit.

Dieses SDK erleichtert:

- Erstellung neuer Module
- Nutzung der System-Schnittstellen
- Registrierung von Hooks
- Integration in das System

Das SDK stellt außerdem Vorlagen für typische Modulstrukturen bereit.

---

# 12 Rechte und Sicherheit

Module dürfen nur die Rechte verwenden, die im Manifest definiert sind.

Das Permission-System kontrolliert:

- Zugriff auf Adminbereiche
- Zugriff auf Systemfunktionen
- Zugriff auf Inhalte

Dadurch wird verhindert, dass Module unkontrolliert auf Systembereiche zugreifen.

---

# 13 Updates

Module können Updates über den Marketplace erhalten.

Updates können enthalten:

- neue Funktionen
- Fehlerkorrekturen
- Sicherheitsupdates

Auto-Updates können optional aktiviert werden.

Die Auto-Update-Funktion kann konfiguriert werden:

- pro Benutzer
- pro Modul

---

# 14 Zusammenspiel mit anderen Systemen

Module arbeiten mit mehreren Systembereichen zusammen.

Wichtige Integrationen bestehen mit:

- Hook-System
- Theme-System
- Layout-System
- Inhaltseditor
- Marketplace

Diese Systeme ermöglichen eine kontrollierte Erweiterung der Plattform.

---

---

# 15 Modul-Routing

Module können eigene Routen definieren.

Diese Routen werden beim Aktivieren des Moduls im Routing-System registriert.

Routen können betreffen:

- Frontend-Seiten
- Adminbereiche
- API-Endpunkte

# 16 Modul-Datenstrukturen

Module können eigene Datenstrukturen definieren.

Diese Strukturen können umfassen:

- Datenbanktabellen
- Konfigurationsstrukturen
- Inhaltstypen

# 17 Modul-Abhängigkeiten

Module können Abhängigkeiten zu anderen Modulen definieren.

Das System prüft bei Installation:

- ob abhängige Module vorhanden sind
- ob kompatible Versionen installiert sind


# Zusammenfassung

Das Modulsystem ist die zentrale Erweiterungsmechanik von Chamy.

Module erweitern das System um neue Funktionen, ohne den Core zu verändern.

Die Darstellung bleibt stets durch das Theme-System kontrolliert.

Klare Regeln, definierte Schnittstellen und automatisierte Prüfprozesse sorgen dafür, dass Erweiterungen stabil und konsistent bleiben.

