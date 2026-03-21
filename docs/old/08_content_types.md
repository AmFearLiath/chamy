# Chamy – Content-Type-System

## Zweck dieses Dokuments

Dieses Dokument beschreibt das **Content-Type-System** von Chamy.

Es definiert, wie strukturierte Inhaltstypen im System aufgebaut sind, wie sie registriert, validiert, gespeichert, bearbeitet und über APIs bereitgestellt werden.

Dieses Dokument ergänzt das bisherige Layout-, Komponenten- und Content-System um eine Ebene, die in modularen CMS-Systemen sehr früh sauber entschieden werden muss:

- strukturierte Inhaltstypen
- Felddefinitionen
- Validierung
- redaktionelle Bearbeitung
- API-Strukturen
- Rechte und Zuständigkeiten
- Zusammenspiel mit Modulen, Themes und Editoren

Ohne ein zentrales Content-Type-System entstehen später fast immer:

- doppelte Datenmodelle
- modulspezifische Einzellösungen
- inkonsistente APIs
- schwer wartbare Editoren
- unklare Zuständigkeiten für Inhalte

Dieses Dokument legt daher die verbindliche Grundlage für alle **strukturierten Inhalte** in Chamy fest.

Verwandte Dokumente:

- Systemübersicht → `01_chamy_overview.md`
- Systemarchitektur → `02_system_architecture.md`
- Modulsystem → `03_module_system.md`
- Theme-System → `04_theme_system.md`
- Layout-, Komponenten- und Content-System → `05_layout_component_content_system.md`
- Marketplace, Sicherheit und Prüfregeln → `06_marketplace_security_and_rules.md`
- API-System → `07_api_system.md`

---

# 1 Grundidee

Das bisherige Content-System von Chamy trennt bereits sinnvoll zwischen:

- Layout
- Komponenten
- Inhalten

Diese Trennung ist richtig, reicht aber für ein großes, modulares CMS alleine nicht aus.

Denn neben frei platzierbaren Inhaltsblöcken braucht ein System fast immer auch **strukturierte Inhalte mit fest definierten Feldern**.

Typische Beispiele:

- Seite
- Artikel
- News-Eintrag
- Event
- Produkt
- Dokumentationseintrag
- FAQ-Eintrag
- Personenprofil
- Portfolio-Eintrag
- Landingpage-Datensatz

Solche Inhalte bestehen nicht nur aus „irgendwelchen Blöcken“, sondern aus klaren Datenfeldern.

Beispiele:

- Titel
- Slug
- Kurzbeschreibung
- Langbeschreibung
- Veröffentlichungsdatum
- Autor
- Status
- Kategorie
- Tags
- SEO-Daten
- Vorschaubild
- Beziehungen zu anderen Inhalten

Genau dafür bekommt Chamy ein zentrales Content-Type-System.

---

# 2 Ziel des Content-Type-Systems

Das Content-Type-System verfolgt mehrere zentrale Ziele.

## Strukturierte Inhaltsmodelle

Inhalte sollen nicht nur lose Blöcke sein, sondern sauber definierte Datensätze mit stabilen Feldern.

## Einheitliche Bearbeitung

Redakteure und Administratoren sollen für ähnliche Inhaltstypen konsistente Bearbeitungsoberflächen erhalten.

## Klare Datenverträge

APIs, Module, Themes und interne Services sollen sich auf definierte Datenstrukturen verlassen können.

## Zentrale Validierung

Feldregeln sollen nicht an fünf verschiedenen Stellen neu erfunden werden, sondern an einer Stelle definiert sein.

## Erweiterbarkeit

Module sollen neue Inhaltstypen registrieren können, ohne den Core umzubauen.

## Rechte und Zuständigkeiten

Es soll eindeutig geregelt sein, wer Inhaltstypen definieren, erweitern, lesen, bearbeiten, veröffentlichen oder löschen darf.

---

# 3 Was ein Content Type in Chamy ist

Ein Content Type ist in Chamy eine **systemweit registrierte Inhaltsdefinition**.

Ein Content Type beschreibt mindestens:

- technische ID
- Anzeigename
- Beschreibung
- Felder
- Feldtypen
- Validierungsregeln
- Standardwerte
- Editor-Darstellung
- API-Struktur
- Berechtigungsregeln
- Speicherverhalten
- optionale Beziehungen zu anderen Typen

Ein Content Type ist also kein einzelner Inhalt, sondern das **Modell**, nach dem Inhalte aufgebaut werden.

Beispiel:

- Content Type: `blog_post`
- Inhaltseintrag: „Warum Chamy nicht zum Datenchaos mutieren soll“

---

# 4 Abgrenzung zu anderen Systemen

Damit später nicht alles durcheinanderfliegt, wird die Rolle des Content-Type-Systems klar von anderen Bereichen getrennt.

## Content-Type-System

Definiert **was für ein strukturierter Inhalt ist**.

## Content-System

Verwaltet konkrete Inhalte und deren Daten.

## Layout-System

Definiert die strukturelle Seitenform.

## Komponenten-System

Stellt wiederverwendbare Bausteine bereit.

## Theme-System

Steuert die Darstellung der Inhalte.

## Modulsystem

Kann neue Inhaltstypen registrieren oder bestehende Typen erweitern.

Dadurch gilt die Trennung:

- Content Type = Datenmodell
- Content Entry = konkreter Datensatz
- Layout = Struktur
- Theme = Darstellung
- Modul = Logik oder fachliche Erweiterung

---

# 5 Typische Standard-Content-Types in Chamy

Chamy sollte bereits im Kern einige grundlegende Inhaltstypen kennen oder zumindest vorbereitet unterstützen.

## 5.1 Page

Für klassische Seiteninhalte.

Typische Felder:

- Titel
- Slug
- Seitentitel für Navigation
- Einleitung
- Hauptinhalt
- SEO-Titel
- SEO-Beschreibung
- Layout-Zuweisung
- Sprache

## 5.2 Article

Für redaktionelle Inhalte.

Typische Felder:

- Titel
- Slug
- Teaser
- Haupttext
- Titelbild
- Autor
- Veröffentlichungsdatum
- Kategorie
- Tags
- SEO-Daten

## 5.3 Documentation Entry

Für technische oder systemische Dokumentation.

Typische Felder:

- Titel
- Slug
- Zusammenfassung
- Inhalt
- Bereich
- Version
- Referenzen
- Änderungsnotizen

## 5.4 Media-linked Entry

Für Einträge mit starkem Medienbezug.

Typische Felder:

- Titel
- Medienreferenz
- Beschreibung
- Alt-Text
- Urheber
- Tags

Diese Standardtypen sind keine starre Pflichtliste, aber sie geben dem System früh eine saubere Richtung.

---

# 6 Fachliche Content Types durch Module

Ein großer Vorteil von Chamy ist die modulare Architektur.

Deshalb dürfen Module eigene fachliche Inhaltstypen registrieren.

Beispiele:

- `blog_post`
- `shop_product`
- `event_entry`
- `faq_entry`
- `team_member`
- `portfolio_item`
- `knowledge_base_article`

Wichtig ist dabei:

- Das Modul definiert den Typ
- Der ContentTypeManager registriert ihn
- Der ContentManager verwaltet konkrete Einträge
- Das Theme rendert die Darstellung

So bleibt die Verantwortung sauber getrennt und kein Modul baut heimlich sein eigenes Mini-CMS im CMS. Genau so entstehen später Albträume mit Bonus-Schrauben.

---

# 7 ContentTypeManager

Für das Content-Type-System wird ein eigener **ContentTypeManager** eingeführt.

Dieser Manager ist die zentrale Verwaltungsinstanz für alle registrierten Inhaltstypen.

## Aufgaben des ContentTypeManager

- Registrierung von Content Types
- Laden und Auflösen von Typdefinitionen
- Validierung der Typkonfiguration
- Bereitstellung für Editoren
- Bereitstellung für APIs
- Verwaltung von Erweiterungen bestehender Typen
- Abgleich mit Berechtigungen
- Bereitstellung von Typ-Metadaten für Themes und Module

## Typische Verantwortlichkeiten

Der Manager soll beantworten können:

- Welche Content Types gibt es?
- Welche Felder besitzt ein Typ?
- Welche Felder sind Pflicht?
- Welche Feldtypen sind erlaubt?
- Welche UI-Komponenten sollen im Editor erscheinen?
- Welche Standardwerte gelten?
- Welche API-Struktur wird erwartet?
- Welche Berechtigungen sind nötig?

---

# 8 Registrierung von Content Types

Content Types müssen systemweit registriert werden.

Die Registrierung darf nicht in wilden Ad-hoc-Arrays irgendwo in Modulen oder Themes versteckt liegen.

## Registrierungsquellen

Ein Content Type kann stammen aus:

- Core
- Systempaketen
- Modulen

Themes definieren **keine** Content Types, weil Themes für Darstellung zuständig sind, nicht für fachliche Datenmodelle.

## Registrierungszeitpunkt

Die Registrierung erfolgt während des Boot- oder Modul-Lifecycle-Prozesses.

## Beispielhafte Definition

Eine Content-Type-Definition kann logisch Informationen enthalten wie:

- ID
- Name
- Beschreibung
- Gruppe
- Felder
- Editor-Metadaten
- API-Metadaten
- Rechte
- Quellmodul
- Version

## Wichtige Regel

Jede Typdefinition muss:

- eindeutig identifizierbar sein
- validiert werden
- dokumentiert sein
- über separate, passend benannte Dateien auffindbar bleiben

---

# 9 Aufbau einer Content-Type-Definition

Eine Content-Type-Definition soll klar strukturiert sein.

## Pflichtinformationen

- technische ID
- Anzeigename
- Beschreibung
- Version
- Quelle
- Feldliste
- primäre Verwaltungsrechte

## Optionale Informationen

- Gruppierung
- Icon
- Sortierung im Adminbereich
- Standardlayout-Empfehlung
- API-Sichtbarkeit
- Suchbarkeit
- Übersetzbarkeit
- Revisionierbarkeit
- Vorschaukonfiguration

## Beispielhafte technische Metadaten

- `id`: `blog_post`
- `label`: `Blogbeitrag`
- `source`: `module.blog`
- `version`: `1.0.0`
- `is_translatable`: `true`
- `is_revisionable`: `true`
- `is_publicly_queryable`: `true`

---

# 10 Feldsystem

Das Herzstück eines Content Types sind seine Felder.

Ein Feld beschreibt:

- welchen Wert es speichert
- in welchem Datentyp
- mit welchen Regeln
- wie es im Editor erscheint
- ob es in APIs sichtbar ist
- ob es übersetzbar ist

## Typische Feldtypen

Chamy sollte mindestens ein flexibles Grundset unterstützen.

### Basisfelder

- text
- textarea
- richtext
- number
- boolean
- date
- datetime
- time
- email
- url
- slug

### Auswahlfelder

- select
- multiselect
- radio
- checkbox-group

### Medien- und Beziehungsfelder

- media
- gallery
- relation
- relation-many
- user-reference
- module-reference

### Strukturierte Spezialfelder

- json
- repeater
- group
- key-value
- seo
- address
- coordinates

### Editornahe Felder

- component-slot
- content-block-area
- layout-reference

Dabei soll Chamy nicht alles sofort bis zur letzten Schraube ausimplementieren müssen, aber die Architektur soll diese Typen sauber vorbereiten.

---

# 11 Felddefinitionen

Jedes Feld benötigt eigene Metadaten.

## Typische Feldeigenschaften

- Feld-ID
- Label
- Beschreibung
- Feldtyp
- Pflichtfeld ja/nein
- Standardwert
- Validierungsregeln
- Placeholder-Text über Sprachsystem
- Hilfehinweis über Sprachsystem
- Editor-Komponente
- API-Sichtbarkeit
- Suchbarkeit
- Sortierbarkeit
- Filterbarkeit
- Übersetzbarkeit
- interne oder öffentliche Sichtbarkeit

## Beispielhafte Regeln

- Mindestlänge
- Maximallänge
- Regex-Muster
- erlaubte Auswahlwerte
- minimale Zahl
- maximale Zahl
- Datumsgrenzen
- Dateitypen
- maximale Anzahl an Beziehungen

---

# 12 Pflichtfelder und Systemfelder

Einige Felder werden häufig systemweit benötigt und sollten als **Systemfelder** verstanden werden.

Diese können je nach Typ automatisch vorhanden oder zentral zuschaltbar sein.

## Typische Systemfelder

- ID
- UUID
- Sprache
- Erstellungsdatum
- Änderungsdatum
- Ersteller
- letzter Bearbeiter
- Status
- Version
- Slug
- Sortierung
- Sichtbarkeit

Diese Felder gehören nicht zur Darstellung, sondern zur Inhaltsverwaltung.

---

# 13 Editor-Darstellung pro Content Type

Ein Content Type definiert nicht nur Daten, sondern auch, wie die Bearbeitung organisiert wird.

## Editor-Metadaten können beschreiben

- Feldreihenfolge
- Tabs
- Gruppen
- Akkordeons
- Sidebar-Felder
- Hauptbereich vs. Meta-Bereich
- Hilfetexte
- Standardwerte
- abhängige Felder
- bedingte Sichtbarkeit

## Beispiel

Ein `blog_post` könnte im Editor so gegliedert werden:

- Hauptinhalt
- Meta-Daten
- SEO
- Veröffentlichung
- Beziehungen

Dadurch wird Bearbeitung deutlich konsistenter und weniger chaotisch.

---

# 14 Validierungssystem

Ein Content-Type-System ohne zentrale Validierung ist wie ein Tresor ohne Tür. Sieht stabil aus, bringt aber nichts.

Chamy braucht daher ein zentrales Validierungssystem für Content Types.

## Validierungsebenen

### Definitionsebene

Prüft, ob der Content Type selbst gültig definiert ist.

### Feldebene

Prüft einzelne Feldwerte.

### Datensatzebene

Prüft fachliche Regeln über mehrere Felder hinweg.

### Kontextbezogene Validierung

Prüft Regeln abhängig von Status, Sprache, Benutzerrolle oder Workflow-Schritt.

## Beispiele

- Slug muss eindeutig sein
- Veröffentlichungsdatum darf nicht vor Erstellungsdatum liegen
- SEO-Titel darf maximale Länge nicht überschreiten
- Event-Enddatum darf nicht vor Startdatum liegen
- Pflichtbild nur bei veröffentlichtem Status

---

# 15 Content-Type-Erweiterungen

Ein starkes modulares System muss Typen **erweitern** können, nicht nur neu anlegen.

## Beispielhafte Erweiterungen

Ein SEO-Modul erweitert `page` um:

- SEO-Titel
- SEO-Beschreibung
- noindex-Flag

Ein Analyse-Modul erweitert `article` um:

- Tracking-Kategorie
- Kampagnenkennung

Ein Freigabe-Modul ergänzt:

- Reviewer
- Freigabekommentar

## Regeln für Erweiterungen

- Erweiterungen müssen registriert werden
- Konflikte müssen erkannt werden
- Feld-IDs dürfen nicht kollidieren
- Änderungen müssen dokumentiert werden
- Rechte und API-Sichtbarkeit müssen neu geprüft werden

---

# 16 Konfliktmanagement

Wenn mehrere Module denselben Content Type erweitern, muss Chamy sauber reagieren.

## Typische Konflikte

- doppelte Feld-IDs
- widersprüchliche Feldtypen
- unterschiedliche Pflichtdefinitionen
- konkurrierende Standardwerte
- kollidierende Editorpositionen

## Lösungsmöglichkeiten

- harte Validierungsfehler
- Prioritätsregeln
- Namensräume
- registrierte Erweiterungsreihenfolge
- Moderations- oder Entwicklerhinweise im System

Der ContentTypeManager muss solche Konflikte erkennen und nicht einfach still lächelnd akzeptieren.

---

# 17 Speicherung strukturierter Inhalte

Das Content-Type-System definiert die Datenstruktur, aber die konkreten Inhalte werden durch das Content-System gespeichert.

## Grundregel

- Content Type definiert das Modell
- Content Entry speichert konkrete Werte

## Speicherstrategie

Die endgültige technische Umsetzung kann unterschiedlich ausfallen, sollte aber den Architekturregeln folgen.

Mögliche Ansätze:

- zentrale Content-Tabelle plus Feldspeicher
- hybride Struktur mit Basisdaten und typbezogenen Feldern
- relationale Zusatztabellen für komplexe Datentypen

Wichtig ist weniger die konkrete SQL-Form als die saubere Trennung der Zuständigkeiten.

## Verbindliche Regel

Module sollen nicht für jeden fachlichen Inhaltstyp unkontrolliert eigene komplett getrennte Inhaltsmodelle erfinden, wenn der Inhalt logisch ein Content Entry ist.

Module dürfen eigene Logikdaten besitzen.

Strukturierte redaktionelle Inhalte gehören jedoch grundsätzlich in das Content- und Content-Type-System.

---

# 18 Data Ownership im Content-Type-System

Das Content-Type-System ist eng mit der Frage der Datenverantwortung verbunden.

Für Chamy gilt:

## ContentManager besitzt

- konkrete Inhaltseinträge
- Basismetadaten von Inhalten
- Speicherung und Abruf der Datensätze

## ContentTypeManager besitzt

- Typdefinitionen
- Feldmodelle
- Typ-Metadaten
- Validierungsregeln auf Typbasis

## Module besitzen

- fachliche Logik
- optionale Zusatzdaten, die nicht eigentlicher Content sind
- Typregistrierungen für eigene Fachbereiche

## Themes besitzen nicht

- keine fachlichen Inhalte
- keine Typdefinitionen
- keine strukturellen Inhaltsdaten

## LayoutManager besitzt nicht

- keine eigentlichen Inhaltswerte
- nur Strukturdefinitionen

Diese Trennung ist für Chamy absolut wichtig, weil sie später Datenchaos, Doppelhaltung und API-Unfälle verhindert.

---

# 19 Beziehung zum Block- und Komponenten-Content

Chamy soll sowohl strukturierte Inhaltstypen als auch flexible Inhaltsbereiche unterstützen.

Das ist kein Widerspruch, sondern eine sinnvolle Kombination.

## Beispielhafte Mischform

Ein `page`-Content-Type kann Felder besitzen wie:

- Titel
- Slug
- Seitenbeschreibung
- Hero-Komponente
- Content-Bereich mit frei platzierbaren Komponenten
- SEO-Felder

Das bedeutet:

- der Content Type bildet den Rahmen
- Komponenten und Blöcke füllen flexible Inhaltszonen

So bekommt Chamy Struktur **und** kreative Freiheit, statt entweder völlig starr oder völlig formlos zu werden.

---

# 20 Mehrsprachigkeit

Da Mehrsprachigkeit in Chamy Pflicht ist, muss das Content-Type-System dafür vorbereitet sein.

## Übersetzbare Content Types

Ein Content Type kann festlegen:

- vollständig übersetzbar
- teilweise übersetzbar
- sprachneutral

## Übersetzbare Felder

Pro Feld soll definierbar sein:

- übersetzbar ja/nein
- fallback-fähig ja/nein
- sprachgebunden oder global

## Beispiele

Übersetzbar:

- Titel
- Beschreibung
- Haupttext
- SEO-Titel

Nicht zwingend übersetzbar:

- interne Referenz-ID
- globale Sortierung
- technischer Schlüssel
- Publikationsflag

---

# 21 Rechte und Berechtigungen

Content Types benötigen eigene Rechteebenen.

## Typische Berechtigungen

- Content Type sehen
- Einträge erstellen
- Einträge bearbeiten
- Einträge löschen
- Einträge veröffentlichen
- Einträge archivieren
- Typdefinition verwalten
- Felder erweitern
- API-Zugriff nutzen

## Feingranulare Regeln

Rechte können gelten für:

- Benutzerrollen
- bestimmte Module
- bestimmte Adminbereiche
- sprachabhängige Zuständigkeiten
- bestimmte Status oder Workflows

---

# 22 API-Strukturen pro Content Type

Das API-System von Chamy soll strukturierte Inhalte konsistent bereitstellen können.

Dafür muss jeder Content Type eine saubere API-Sicht besitzen.

## Ein Content Type kann definieren

- welche Felder öffentlich sichtbar sind
- welche Felder intern sichtbar sind
- welche Felder filterbar sind
- welche Felder sortierbar sind
- welche Beziehungen expandierbar sind
- welche Schreibrechte per API existieren

## Beispiel

Für `blog_post`:

- öffentlich sichtbar: Titel, Slug, Teaser, Datum, Autorname
- intern sichtbar: Status, Änderungsprotokoll, interne Flags
- schreibbar: Titel, Inhalt, SEO-Daten
- nicht schreibbar über Public-API: Review-Felder, Systemmetadaten

---

# 23 Suchbarkeit und Filterbarkeit

Content Types sollen definieren können, wie gut sie im System durchsuchbar und filterbar sind.

## Typische Eigenschaften

- in globaler Suche sichtbar
- im Admin filterbar
- im Marketplace- oder Content-Browser sichtbar
- nach Datum sortierbar
- nach Status filterbar
- nach Beziehungen filterbar

Diese Informationen sind wichtig für:

- Adminlisten
- Suchfunktionen
- APIs
- Dashboards
- modulare Übersichten

---

# 24 Vorschau- und Rendering-Kontext

Ein Content Type soll definieren können, wie Vorschauen und themeseitige Übergaben aussehen.

## Mögliche Metadaten

- bevorzugtes Vorschau-Template
- bevorzugte Kartenansicht
- Listenansicht
- Detailansicht
- Standarddarstellung im Adminbrowser

Wichtig ist dabei:

Der Content Type bestimmt **nicht** das endgültige Design, sondern liefert nur strukturierte Hinweise.

Die Darstellung selbst bleibt weiterhin beim Theme.

---

# 25 Content-Type-Kategorien und Gruppierung

Damit viele Inhaltstypen nicht in einer wilden Liste enden, sollten sie gruppierbar sein.

## Beispielhafte Gruppen

- Seiten
- Redaktion
- Commerce
- Community
- Dokumentation
- System
- Mediennah
- Modulbezogen

Dies verbessert:

- Adminübersicht
- Editor-Auswahl
- Entwicklerfreundlichkeit
- Skalierung bei vielen Modulen

---

# 26 Entwickler- und Dateistruktur

Da Chamy Schnittstellen sauber dokumentieren will, müssen auch Content Types sauber organisiert sein.

## Empfohlene Struktur

Beispielhaft:

`/system/content-types/`

- `page.content-type.php`
- `article.content-type.php`
- `documentation-entry.content-type.php`

`/modules/<module-id>/content-types/`

- `blog-post.content-type.php`
- `event-entry.content-type.php`
- `product.content-type.php`

## Begleitende Dokumentation

`/docs/content-types/`

- `page.md`
- `article.md`
- `blog-post.md`

## Inhalt der Dokumentation

- Zweck des Typs
- Felder
- Validierungsregeln
- API-Sichtbarkeit
- Rechte
- Herkunft
- Änderungen
- bekannte Erweiterungen

---

# 27 Logging und Entwicklungsprotokolle

Größere Änderungen an Content Types müssen nachvollziehbar protokolliert werden.

## Zu protokollieren sind insbesondere

- neue Content Types
- hinzugefügte oder entfernte Felder
- geänderte Validierungsregeln
- API-Sichtbarkeitsänderungen
- Rechteänderungen
- migrationsrelevante Strukturänderungen

Kleine Textkorrekturen in Beschreibungen müssen nicht extra mit Trommelwirbel geloggt werden.

---

# 28 Migration und Weiterentwicklung

Da sich Inhalte und Projekte entwickeln, muss das Content-Type-System migrationsfähig sein.

## Typische Änderungen

- neues Feld hinzufügen
- Feld umbenennen
- Feldtyp anpassen
- Pflichtstatus ändern
- Feld entfernen
- Struktur in Gruppen verschieben

## Notwendige Regeln

- Änderungen müssen versionierbar sein
- migrationsrelevante Änderungen müssen erkannt werden
- bestehende Daten dürfen nicht still zerstört werden
- Module müssen Typänderungen kompatibel deklarieren

---

# 29 Marketplace-Bezug

Wenn Module Content Types mitbringen, ist das auch für den Marketplace relevant.

## Der Marketplace sollte prüfen können

- ob Typdefinitionen gültig sind
- ob Feldtypen erlaubt sind
- ob Konflikte mit Systemregeln entstehen
- ob Texte über das Sprachsystem laufen
- ob API-Sichtbarkeit korrekt dokumentiert ist
- ob Rechte sauber definiert sind

Dadurch wird verhindert, dass ein Modul beim Hochladen still und heimlich zehn halbfertige Sondertabellen mitbringt und so tut, als sei das eine elegante Lösung.

---

# 30 Zusammenspiel mit zukünftigen State- und Versionssystemen

Das Content-Type-System ist direkt vorbereitend für Datei 09 relevant.

Denn strukturierte Inhalte brauchen fast immer zusätzlich:

- States
- Versionen
- Entwürfe
- Freigaben
- geplante Veröffentlichungen

Deshalb soll bereits jetzt pro Content Type vorbereitbar sein:

- statefähig ja/nein
- versionierbar ja/nein
- reviewpflichtig ja/nein
- planbar ja/nein
- sperrbar ja/nein

Damit lässt sich das kommende State- und Versionssystem sauber auf definierte Inhaltstypen aufsetzen.

---

# 31 Vorteile für Chamy

Ein zentrales Content-Type-System bringt Chamy mehrere große Vorteile.

## Saubere Datenmodelle

Inhalte werden systemweit einheitlicher.

## Weniger Modul-Chaos

Module registrieren Typen, statt eigene Insel-Logiken zu erfinden.

## Stabile APIs

Klare Felder bedeuten klare Antwortstrukturen.

## Bessere Editoren

Bearbeitungsoberflächen können automatisch aus Typdefinitionen erzeugt oder ergänzt werden.

## Bessere Rechteverwaltung

Zugriffe lassen sich feiner steuern.

## Zukunftssicherheit

Workflows, Versionen, Übersetzungen und Vorschauen lassen sich deutlich sauberer ergänzen.

---

# 32 Verbindliche Architekturregeln für Chamy

Für Chamy gelten im Zusammenhang mit dem Content-Type-System folgende verbindliche Regeln.

## Regel 1

Strukturierte redaktionelle Inhalte gehören in das Content-Type- und Content-System, nicht in beliebige modulspezifische Eigenkonstruktionen.

## Regel 2

Themes definieren keine Content Types.

## Regel 3

Jeder Content Type muss zentral registriert, validiert, dokumentiert und versionierbar sein.

## Regel 4

Felder, Validierung, API-Sichtbarkeit und Rechte müssen pro Content Type nachvollziehbar beschrieben sein.

## Regel 5

Erweiterungen bestehender Content Types sind erlaubt, aber konfliktprüfpflichtig.

## Regel 6

Darstellung bleibt Aufgabe des Theme-Systems.

## Regel 7

Mehrsprachigkeit und das Sprachsystem müssen berücksichtigt werden.

## Regel 8

Größere Strukturänderungen an Content Types müssen protokolliert werden.

---

# 33 Zusammenfassung

Das Content-Type-System ergänzt Chamy um eine zentrale Ebene für strukturierte Inhalte.

Es sorgt dafür, dass fachliche Inhalte nicht in unkoordinierten Modulmodellen, Zufallsfeldern und späteren Reparatur-Orgien enden, sondern sauber beschrieben, registriert, validiert und verwaltet werden.

Mit dem ContentTypeManager, klaren Felddefinitionen, zentraler Validierung, API-Metadaten, Rechteintegration und sauberer Ownership-Struktur bekommt Chamy eine stabile Grundlage für:

- strukturierte Seiteninhalte
- modulare Fachobjekte
- konsistente Editoren
- saubere APIs
- zukünftige States, Versionen und Workflows

Damit schließt Datei 08 genau die Lücke, die bei modularen CMS-Systemen sonst später richtig teuer wird.

