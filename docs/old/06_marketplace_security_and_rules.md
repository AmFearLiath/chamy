# Chamy – Marketplace, Sicherheit und Prüfregeln

## Zweck dieses Dokuments

Dieses Dokument beschreibt den Marketplace von Chamy sowie die Sicherheits- und Prüfmechanismen, die für Module und Themes gelten.

Der Marketplace dient nicht nur als Downloadplattform, sondern als vollständiges System zur Verwaltung, Verteilung, Prüfung und Aktualisierung von Erweiterungen.

Dieses Dokument behandelt:

- Aufbau des Marketplace
- Upload- und Prüfprozesse
- Sicherheitsregeln
- Update-Mechanismen
- Entwickler-SDK

Weitere Systembereiche werden in separaten Dokumenten beschrieben:

- Systemarchitektur → `02_system_architecture.md`
- Modulsystem → `03_module_system.md`
- Theme-System → `04_theme_system.md`
- Layout-, Komponenten- und Content-System → `05_layout_component_content_system.md`

---

# 1 Grundidee des Marketplace

Der Chamy Marketplace ist ein zentraler Ort für Erweiterungen.

Er ermöglicht:

- Veröffentlichung von Modulen
- Veröffentlichung von Themes
- Download von Erweiterungen
- Updateverwaltung
- Bewertung und Feedback

Der Marketplace dient gleichzeitig als Sicherheitsfilter, um sicherzustellen, dass Erweiterungen den Systemregeln entsprechen.

---

# 2 Erweiterungstypen

Der Marketplace kann verschiedene Erweiterungstypen verwalten.

## Module

Module erweitern die Funktionalität des Systems.

Beispiele:

- Blogsystem
- Shopfunktionen
- Integrationen

## Themes

Themes steuern die visuelle Darstellung des Systems.

Beispiele:

- Webseitenlayouts
- UI-Designs
- Admin-Designs

---

# 3 Marketplace-Funktionen

Der Marketplace bietet mehrere Verwaltungsfunktionen.

## Erweiterungen entdecken

Benutzer können:

- neue Erweiterungen durchsuchen
- beliebte Erweiterungen finden
- nach Kategorien filtern

## Installation

Erweiterungen können direkt aus dem Marketplace installiert werden.

Der Installationsprozess umfasst:

- Download
- Validierung
- Registrierung im System

## Aktivierung und Deaktivierung

Installierte Erweiterungen können aktiviert oder deaktiviert werden.

## Deinstallation

Erweiterungen können vollständig entfernt werden.

## Bewertungen

Benutzer können Erweiterungen bewerten.

## Kommentare

Benutzer können Feedback oder Hinweise zu Erweiterungen hinterlassen.

---

# 4 Erweiterungs-Upload

Entwickler können Erweiterungen über den Marketplace hochladen.

Der Upload-Prozess umfasst mehrere Schritte.

## 1 Paket-Upload

Die Erweiterung wird als ZIP-Paket hochgeladen.

## 2 Automatische Prüfung

Das System führt automatische Prüfungen durch.

## 3 Moderation

Nach der automatischen Prüfung erfolgt eine manuelle Moderation.

## 4 Veröffentlichung

Nach erfolgreicher Prüfung wird die Erweiterung im Marketplace veröffentlicht.

---

# 5 Automatische Prüfungen

Beim Upload wird jede Erweiterung automatisch geprüft.

Diese Prüfungen dienen der Systemsicherheit.

## Strukturprüfung

Es wird überprüft:

- ob die Ordnerstruktur korrekt ist
- ob notwendige Dateien vorhanden sind

## Manifestprüfung

Die Manifestdatei wird validiert.

Es wird geprüft:

- Modul- oder Theme-ID
- Version
- kompatible Systemversion

## Theme-System-Prüfung

Es wird geprüft, ob die Erweiterung das Theme-System korrekt verwendet.

Beispielsweise wird kontrolliert:

- ob eigene Frontend-Styles erzwungen werden
- ob das Modul versucht, Darstellung zu überschreiben

## Inline-CSS-Prüfung

Das System überprüft, ob unerlaubtes Inline-CSS vorhanden ist.

Inline-CSS ist nicht erlaubt, da es die konsistente Darstellung des Systems gefährden kann.

## Sicherheitsprüfung

Es wird geprüft, ob das Paket potenziell gefährlichen Code enthält.

---

# 6 Moderationsprozess

Nach den automatischen Prüfungen erfolgt eine manuelle Moderation.

Moderatoren prüfen unter anderem:

- Funktionsweise der Erweiterung
- Einhaltung der Systemregeln
- Benutzerfreundlichkeit
- Dokumentation

Erst nach erfolgreicher Moderation wird eine Erweiterung veröffentlicht.

---

# 7 Update-System

Der Marketplace stellt Updates für Erweiterungen bereit.

Updates können enthalten:

- neue Funktionen
- Fehlerkorrekturen
- Sicherheitsupdates

## Updateprüfung

Das System prüft regelmäßig, ob Updates verfügbar sind.

---

# 8 Auto-Updates

Auto-Updates können optional aktiviert werden.

Die Einstellung kann konfiguriert werden:

- pro Benutzer
- pro Modul
- pro Theme

Dadurch kann jeder Benutzer individuell entscheiden, ob Updates automatisch installiert werden sollen.

---

# 9 Entwickler-SDK

Chamy stellt ein SDK für Entwickler bereit.

Dieses SDK erleichtert:

- Entwicklung neuer Module
- Entwicklung neuer Themes
- Nutzung von System-Schnittstellen

Das SDK enthält:

- Beispielprojekte
- Modulvorlagen
- Themevorlagen
- Dokumentation

---

# 10 Sicherheitsregeln

Erweiterungen müssen bestimmte Regeln einhalten.

## Theme-Regeln

Module dürfen keine eigenen Frontend-Designsysteme erzwingen.

Die Darstellung erfolgt ausschließlich über das Theme-System.

## Styling-Regeln

Eigene Frontend-Styles von Modulen werden ignoriert.

Das System verwendet stattdessen die Styles des aktiven Themes.

## Sprachsystem

Hardcodierte Texte sind nicht erlaubt.

Alle Texte müssen über das Sprachsystem bereitgestellt werden.

## Zugriffskontrollen

Erweiterungen dürfen nur auf Systembereiche zugreifen, für die sie Berechtigungen besitzen.

---

# 11 Erweiterungsverwaltung

Der Marketplace arbeitet eng mit mehreren Systemkomponenten zusammen.

Dazu gehören:

- ModuleManager
- ThemeManager
- UpdateManager
- PermissionManager

Diese Manager steuern Installation, Updates und Zugriff.

---

# 12 Erweiterungskategorien

Erweiterungen werden in Kategorien organisiert.

Beispiele:

- Content
- Layout
- Integration
- Marketing
- E-Commerce

# 13 Kompatibilitätsprüfung

Der Marketplace prüft:

- kompatible Chamy-Version
- kompatible Modulversionen
- kompatible Theme-Versionen

# Zusammenfassung

Der Chamy Marketplace ist ein zentraler Bestandteil des Systems.

Er ermöglicht die sichere Verteilung und Verwaltung von Erweiterungen.

Automatische Prüfungen, Moderation und klare Sicherheitsregeln sorgen dafür, dass Erweiterungen stabil, kompatibel und sicher bleiben.

