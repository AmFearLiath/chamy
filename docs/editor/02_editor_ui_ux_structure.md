# Chamy Inhaltseditor – UI & UX Struktur

## Überblick
Der Inhaltseditor wird als **fast-fullscreen Modal** innerhalb des Adminbereichs geöffnet. Dadurch bleibt der Kontext der Adminoberfläche erhalten, während gleichzeitig ausreichend Platz für eine visuelle Bearbeitungsoberfläche bereitsteht.

Der Editor besteht aus vier Hauptbereichen:

- Editor Header
- Linke Sidebar (Katalog)
- Canvas (Arbeitsfläche)
- Rechte Sidebar (Inspector)

Beide Sidebars sind **einklappbar**, um maximale Arbeitsfläche zu ermöglichen.

---

# 1. Editor Header

Der Header ist minimal gehalten und enthält ausschließlich editorrelevante Aktionen.

## Linker Bereich

- Zurück-Button (Editor schließen)
- Titel des bearbeiteten Inhalts
- Statusanzeige (z. B. Draft, Review, Published)

## Mittlerer Bereich

- Vorschau-Modus
- Gerätevorschau

Mögliche Ansichten:

- Desktop
- Tablet
- Mobile

## Rechter Bereich

- Undo
- Redo
- Versionsübersicht
- Speichern

Der Header enthält **keine regulären Admin-Navigationselemente**, um Ablenkung während der Bearbeitung zu vermeiden.

---

# 2. Linke Sidebar – Inhaltskatalog

Die linke Sidebar enthält alle Elemente, die in den Inhalt eingefügt werden können.

Die Sidebar kann ein- und ausgeklappt werden.

## Katalogbereiche

### Layout

Strukturelemente für Seitenaufbau:

- Section
- Container
- Grid
- Columns

### Standardblöcke

Grundlegende Inhaltsblöcke:

- Text
- Bild
- Galerie
- Video
- Button

### Komponenten

Wiederverwendbare UI-Bausteine aus dem ComponentManager.

Beispiele:

- Hero
- Feature Grid
- CTA
- Slider
- Card Grid

### Snippets

Kleine wiederverwendbare Inhalte.

Beispiele:

- Infobox
- Hinweistext
- Kontaktblock

## Funktionen

Der Katalog unterstützt:

- Suche
- Kategorien
- Favoriten
- zuletzt verwendete Elemente

Elemente werden per **Drag & Drop** auf die Canvas gezogen.

---

# 3. Canvas – Arbeitsfläche

Die Canvas ist der zentrale Bearbeitungsbereich.

Hier wird der Seiteninhalt visuell aufgebaut.

## Eigenschaften

- Drag & Drop Platzierung
- Strukturhierarchie
- visuelle Hervorhebung von Blöcken
- Drop-Zonen
- Hover-Indikatoren

## Strukturbaum

Der Inhalt wird intern als Baumstruktur dargestellt.

Beispiel:

```
Page
 ├ Section
 │   ├ Container
 │   │   ├ HeroComponentInstance
 │   │   └ TextBlock
 │   └ FeatureGridComponentInstance
 └ CTAComponentInstance
```

Jedes Element ist eine **Instanz**.

## Interaktionen

Mögliche Aktionen:

- Element auswählen
- Element verschieben
- Element duplizieren
- Element löschen
- Element als Komponente speichern
- Element auf Vorlage zurücksetzen

Beim Klick auf ein Element öffnet sich automatisch der Inspector.

---

# 4. Rechte Sidebar – Inspector

Der Inspector zeigt Eigenschaften des aktuell ausgewählten Elements.

Die Sidebar ist einklappbar.

## Tabs

### Inhalt

Bearbeitung der eigentlichen Inhalte.

Beispiele:

- Textinhalt
- Bilder
- Links

### Layout

Layout-Einstellungen:

- Margin
- Padding
- Ausrichtung
- Grid-Position

### Design

Darstellungsoptionen:

- Hintergrund
- Rahmen
- Schatten
- Animation

### Advanced

Erweiterte Optionen:

- CSS-Klassen
- Sichtbarkeit
- Bedingungen

## Dynamische Felder

Die angezeigten Felder werden aus den jeweiligen Schemas generiert.

Mögliche Quellen:

- Content-Type-Schema
- Komponenten-Schema
- Layout-Schema
- Snippet-Schema

---

# 5. Responsive Vorschau

Der Editor unterstützt eine Vorschau für verschiedene Bildschirmgrößen.

Modi:

- Desktop
- Tablet
- Mobile

Die Canvas passt sich entsprechend an.

---

# 6. Bedienkomfort

Der Editor unterstützt mehrere Komfortfunktionen.

## Hover-Hervorhebung

Beim Überfahren eines Elements wird dessen Bereich hervorgehoben.

## Breadcrumb-Navigation

Bei verschachtelten Elementen wird der Strukturpfad angezeigt.

## Kontextmenü

Elemente können über ein Kontextmenü bearbeitet werden.

Typische Aktionen:

- bearbeiten
- duplizieren
- löschen

---

# Ziel der UI

Die Benutzeroberfläche des Editors soll:

- visuell verständlich
- schnell bedienbar
- klar strukturiert

sein.

Redakteure sollen Inhalte ohne technisches Wissen visuell aufbauen können.