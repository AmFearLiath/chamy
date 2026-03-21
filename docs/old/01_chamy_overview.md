# Chamy – Systemübersicht

## Was ist Chamy

Chamy ist ein modular aufgebautes Content-Management-System mit klar getrennten Verantwortlichkeiten zwischen Systemkern, Darstellung, Inhalten und Erweiterungen.

Das System ist nicht als klassisches starres CMS gedacht, sondern als flexible, erweiterbare Plattform, auf der sich unterschiedliche Webseiten, Funktionen und Oberflächenstrukturen kontrolliert aufbauen lassen.

Chamy verbindet dabei drei zentrale Ziele:

- saubere technische Struktur
- langfristige Erweiterbarkeit
- einheitliche und kontrollierte Darstellung

---

# Zielgruppen

Chamy richtet sich an mehrere Zielgruppen:

- Administratoren, die Webseiten verwalten
- Entwickler, die Module und Themes erstellen
- Designer, die Themes und Layouts entwickeln
- Redakteure, die Inhalte erstellen und pflegen

Durch die klare Systemstruktur können diese Rollen unabhängig voneinander arbeiten.

---

# Grundidee

Chamy basiert auf dem Prinzip, dass Inhalte, Darstellung und Systemlogik niemals unkontrolliert miteinander vermischt werden.

Daraus ergeben sich drei klare Ebenen:

- **Systemlogik** steuert Abläufe, Regeln, Verwaltung und Erweiterungen
- **Themes** steuern die Darstellung und visuelle Ausgabe
- **Inhalte** werden strukturiert verwaltet und unabhängig vom Layout gepflegt

Diese Trennung bildet die Grundlage für ein wartbares, erweiterbares und themenfähiges System.

---

# Zielsetzung von Chamy

Chamy soll ein System sein, das sowohl für einfache Seiten als auch für komplexe, modulare Plattformen geeignet ist.

Dabei stehen folgende Ziele im Mittelpunkt:

- modulare Erweiterbarkeit
- klare Systemgrenzen
- kontrollierte Darstellung über Themes
- Wiederverwendbarkeit technischer Bausteine
- freie Gestaltung von Layouts
- saubere Inhaltsverwaltung
- Mehrsprachigkeit als Pflichtstandard
- einheitliche Regeln für Module und Themes

Chamy soll dadurch nicht nur Inhalte verwalten, sondern eine stabile Grundlage für eigene Systemwelten, Erweiterungen und projektspezifische Lösungen bieten.

---

# Zentrale Leitprinzipien

## Modulare Systembasis

Alle größeren Funktionen werden modular gedacht und umgesetzt.

Der Core stellt die notwendige Infrastruktur bereit, während fachliche oder projektbezogene Erweiterungen über Module ergänzt werden.

## Theme-gesteuerte Darstellung

Die gesamte Darstellung wird über das Theme-System gesteuert.

Module liefern Funktionen und Daten, nicht jedoch eine eigenständige visuelle Frontend-Welt.

## Kontrollierte Erweiterbarkeit

Erweiterungen dürfen das System ergänzen, aber nicht seine Grundregeln aufbrechen.

Darstellung, Sicherheitsregeln, Sprachsystem und Systemgrenzen bleiben zentral kontrolliert.

## Wiederverwendung vor Neuerstellung

Bevor neue Funktionen, Helfer, Styles oder andere Bausteine entstehen, wird geprüft, ob bereits etwas Passendes im System oder Theme vorhanden ist.

Nur wenn keine geeignete Lösung existiert, wird etwas Neues erstellt und anschließend systemweit registriert, dokumentiert und kommentiert.

## Keine hardcodierten Texte

Texte dürfen nicht direkt im Code oder in Templates fest eingebaut werden.

Alle textlichen Inhalte laufen über das Sprachsystem.

## Mehrsprachigkeit als Pflicht

Chamy ist von Beginn an mehrsprachig ausgelegt.

Die Standardsprache für System und Benutzerprofile ist Deutsch. Englisch ist als zweite Systemsprache verpflichtend vorgesehen.

---

# Wichtige Systembereiche

Chamy besteht aus mehreren klar getrennten Hauptbereichen.

## Core

Der Core bildet die technische Grundlage des Systems und stellt die zentralen Verwaltungs- und Steuerungsstrukturen bereit.

## Theme-System

Das Theme-System steuert die komplette Darstellung des Frontends und des Adminbereichs innerhalb der dafür vorgesehenen Regeln.

## Modulsystem

Das Modulsystem erweitert Chamy um zusätzliche Funktionen, Ansichten, Integrationen und Verwaltungsbereiche.

## Layout-, Komponenten- und Content-System

Diese Systeme regeln den Aufbau von Seitenstrukturen, die Umwandlung von Layoutteilen in wiederverwendbare Komponenten sowie die eigentliche Pflege von Inhalten.

## Marketplace-System

Der Marketplace verwaltet Erweiterungen, Prüfprozesse, Moderation, Updates und die Verteilung von Themes und Modulen.

## Sprach- und Konfigurationssystem

Diese Systeme steuern systemweite Einstellungen, Mehrsprachigkeit und konfigurierbare Bereiche.

---

# Zusammenspiel der Systembereiche

Chamy ist so aufgebaut, dass jeder Hauptbereich eine klar definierte Aufgabe besitzt.

- Der Core steuert
- Themes stellen dar
- Module erweitern
- Layouts strukturieren
- Komponenten machen Teile wiederverwendbar
- Inhalte füllen die Seite
- der Marketplace verteilt und prüft Erweiterungen

Dieses Zusammenspiel sorgt dafür, dass das System flexibel bleibt, ohne unkontrolliert zu wachsen.

---

# Themes und Module in Chamy

Themes und Module haben in Chamy bewusst unterschiedliche Rollen.

## Themes

Themes sind für die Darstellung verantwortlich.  
Sie definieren Layout, Struktur, UI-Komponenten und visuelle Regeln.

## Module

Module erweitern die Funktionalität des Systems.  
Sie liefern Logik, Daten, Konfigurationen und Integrationen.

Dadurch bleibt die Trennung klar:

- Funktionen kommen aus Modulen
- Darstellung kommt aus dem Theme-System

---

# Layouts und Inhalte

Chamy trennt bewusst zwischen Layout und Inhalt.

Layouts definieren die strukturelle Form einer Seite.  
Inhalte werden unabhängig davon gepflegt.

Zusätzlich können während des Layoutbaus wiederverwendbare Komponenten entstehen, die später im Inhaltseditor genutzt werden können.

Dadurch verbindet Chamy freie Strukturentwicklung mit systematischer Wiederverwendbarkeit.

---

# Erweiterungsfähigkeit und Zukunftsausrichtung

Chamy ist darauf ausgelegt, langfristig erweitert zu werden.

Dazu gehören unter anderem:

- zusätzliche Module
- zusätzliche Themes
- eigene Layoutbibliotheken
- Inhaltseditor-Erweiterungen
- Marketplace-Anbindungen
- Entwicklungswerkzeuge und SDK-Strukturen

Chamy soll damit nicht nur als CMS nutzbar sein, sondern als stabile Plattform für wachsende Systeme.

---

# Zusammenfassung

Chamy ist ein modular aufgebautes, theme-gesteuertes und mehrsprachiges CMS mit klarer Trennung zwischen Logik, Darstellung und Inhalt.

Der Fokus liegt auf:

- sauberer Architektur
- kontrollierter Erweiterbarkeit
- konsistenter Darstellung
- Wiederverwendbarkeit technischer Bausteine
- freier, aber strukturierter Seiten- und Inhaltsgestaltung

Diese Systemübersicht dient als Einstieg in die Gesamtarchitektur von Chamy.  
Die technischen Details der einzelnen Bereiche werden in den zugehörigen Fachdokumenten beschrieben.