# Chamy – Layout-, Komponenten- und Content-System

## Zweck dieses Dokuments

Dieses Dokument beschreibt die Systeme, die in Chamy für den strukturellen Aufbau von Seiten und die Verwaltung von Inhalten verantwortlich sind.

Diese Systeme bilden gemeinsam die Grundlage für die Erstellung und Pflege von Seiten:

- Layout-System
- Komponenten-System
- Content-System

Die Systeme arbeiten eng zusammen, bleiben jedoch technisch getrennt, damit Struktur, Wiederverwendbarkeit und Inhalte unabhängig voneinander verwaltet werden können.

Weitere Systembereiche werden in separaten Dokumenten beschrieben:

- Systemarchitektur → `02_system_architecture.md`
- Modulsystem → `03_module_system.md`
- Theme-System → `04_theme_system.md`
- Marketplace und Sicherheitsregeln → `06_marketplace_security_and_rules.md`

---

# 1 Grundprinzip

Chamy trennt bewusst zwischen drei Ebenen:

**Layout**  
Bestimmt die strukturelle Form einer Seite.

**Komponenten**  
Sind wiederverwendbare Bausteine.

**Content (Inhalt)**  
Enthält die tatsächlichen Inhalte.

Diese Trennung ermöglicht:

- freie Gestaltung von Layouts
- Wiederverwendung von Layoutteilen
- flexible Inhaltsverwaltung
- klare Verantwortlichkeiten im System

---

# 2 Layout-System

Das Layout-System beschreibt die strukturelle Organisation einer Seite.

Layouts definieren ausschließlich die Struktur einer Seite, nicht deren Inhalte.

Ein Layout kann beispielsweise definieren:

- Seitenbereiche
- Container
- Grid-Strukturen
- Inhaltszonen
- Position von Seitenelementen

Layouts bilden damit das strukturelle Grundgerüst einer Seite.

---

# 3 Layoutquellen

Layouts können aus mehreren Quellen stammen.

## Systemlayouts

Das System kann grundlegende Layoutstrukturen bereitstellen.

Diese Layouts dienen als Basis für häufig genutzte Seitenstrukturen.

## Theme-Layouts

Themes können eigene Layouts definieren.

Diese Layouts sind häufig an ein bestimmtes Design oder eine bestimmte Seitentyp-Struktur gebunden.

Beispiele:

- Landingpage
- Blogseite
- Artikelansicht
- Portfolioseite

## Modul-Layouts

Module können zusätzliche Layouts bereitstellen.

Diese Layouts werden meist für funktionale Seiten benötigt.

Beispiele:

- Shopseiten
- Eventseiten
- Foren

Auch in diesem Fall wird die Darstellung weiterhin vom Theme gesteuert.

---

# 4 Layoutstruktur

Layouts bestehen aus strukturierten Bereichen.

Typische Layoutbereiche sind:

- Header
- Navigation
- Content-Bereich
- Sidebar
- Footer

Innerhalb dieser Bereiche können Inhalte oder Komponenten platziert werden.

Layouts definieren nur Struktur und Positionierung.

Die tatsächlichen Inhalte werden über das Content-System eingefügt.

---

# 5 Freier Layoutbau

Chamy erlaubt bewusst flexible Layoutstrukturen.

Layouts sollen nicht künstlich eingeschränkt werden.

Beim Erstellen von Seiten können deshalb sehr unterschiedliche Layouts entstehen.

Diese Freiheit ermöglicht:

- individuelle Seitendesigns
- unterschiedliche Inhaltsstrukturen
- kreative Layoutlösungen

---

# 6 Umwandlung von Layoutteilen in Komponenten

Während des Layoutbaus können einzelne Layoutbereiche oder komplette Strukturen in wiederverwendbare Komponenten umgewandelt werden.

Dies ist ein zentraler Bestandteil des Systems.

Beispiele für solche Komponenten:

- Hero-Bereiche
- Feature-Sektionen
- Call-To-Action-Blöcke
- Kartenlayouts
- Inhaltssektionen

Diese Komponenten können anschließend im Content-Editor verwendet werden.

---

# 7 Komponenten-System

Das Komponenten-System verwaltet wiederverwendbare Bausteine.

Komponenten können aus verschiedenen Quellen stammen:

- aus Layoutstrukturen
- aus Themes
- aus Modulen

Komponenten ermöglichen es, komplexe Layout- oder Inhaltsbereiche mehrfach zu verwenden.

---

# 8 ComponentManager

Der ComponentManager verwaltet alle Komponenten im System.

Seine Aufgaben umfassen:

- Registrierung von Komponenten
- Verwaltung vorhandener Komponenten
- Organisation nach Kategorien
- Aktivierung und Deaktivierung
- Bereitstellung für den Content-Editor

Der Manager sorgt außerdem dafür, dass Komponenten systemweit verfügbar sind.

---

# 9 Komponenten-Editor / Komponenten-Manager

Chamy besitzt einen eigenen Editor zur Verwaltung von Komponenten.

Dieser Manager ermöglicht:

- Umwandlung von Layoutteilen in Komponenten
- Bearbeitung bestehender Komponenten
- Organisation der Komponentenbibliothek
- Verwaltung von Metadaten

Der Komponenten-Manager dient als zentrale Bibliothek für wiederverwendbare Bausteine.

---

# 10 Content-System

Das Content-System verwaltet die tatsächlichen Inhalte von Seiten.

Inhalte bestehen aus strukturierten Inhaltsblöcken.

Beispiele für Inhaltstypen:

- Text
- Bilder
- Medien
- Komponenten
- dynamische Inhalte

Das Content-System speichert Inhalte unabhängig von Layout und Theme.

---

# 11 Content-Editor

Der Content-Editor ermöglicht das Bearbeiten von Seiteninhalten.

Der Editor erlaubt:

- Platzieren von Komponenten
- Bearbeiten von Inhalten
- Strukturieren von Seiten

Der Editor verändert nicht das Layout oder das Theme.

Er arbeitet ausschließlich mit Inhalten.

---

# 12 Integration mit dem Theme-System

Layouts und Komponenten arbeiten eng mit dem Theme-System zusammen.

Themes definieren:

- visuelle Darstellung
- Styles
- UI-Strukturen

Layouts und Inhalte liefern lediglich Struktur und Daten.

---

# 13 Integration mit Modulen

Module können zusätzliche Elemente bereitstellen.

Beispiele:

- neue Inhaltstypen
- neue Komponenten
- zusätzliche Layoutdefinitionen

Die Darstellung dieser Elemente erfolgt weiterhin über das Theme-System.

---

# 14 Vorteile der Architektur

Die Trennung zwischen Layout, Komponenten und Inhalt bietet mehrere Vorteile.

## Klare Verantwortlichkeiten

Jeder Systembereich besitzt eine klare Aufgabe.

## Wiederverwendbarkeit

Komponenten können mehrfach verwendet werden.

## Flexible Layoutgestaltung

Layouts können frei gestaltet werden.

## Konsistente Darstellung

Themes behalten die vollständige Kontrolle über das Design.

---

# 15 Komponenten-Metadaten

Jede Komponente besitzt Metadaten.

Diese können enthalten:

- Name
- Beschreibung
- Kategorie
- Version
- Quelle (Layout, Theme, Modul)
- kompatible Layouttypen

# Zusammenfassung

Das Layout-, Komponenten- und Content-System bildet die Grundlage für den strukturellen Aufbau von Seiten in Chamy.

Layouts definieren die Struktur einer Seite.

Komponenten stellen wiederverwendbare Bausteine bereit.

Das Content-System verwaltet die Inhalte.

Diese klare Trennung ermöglicht flexible Seitenstrukturen, wiederverwendbare Elemente und eine konsistente Darstellung über das Theme-System.