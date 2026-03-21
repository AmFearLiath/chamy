# Chamy Inhaltseditor – Block, Komponenten und Snippet System

## Überblick
Der Inhaltseditor unterscheidet mehrere Arten von Bausteinen, die gemeinsam den Editor-Baum bilden. Diese Bausteine erfüllen unterschiedliche Aufgaben und besitzen unterschiedliche Regeln.

Die wichtigsten Kategorien sind:

- Layout-Blöcke
- Content-Blöcke
- Komponenten
- Snippets

Alle Elemente werden im Editor immer als **Instanzen** verwendet.

---

# 1. Layout-Blöcke

## Zweck
Layout-Blöcke definieren die strukturelle Anordnung von Inhalten.

Sie besitzen normalerweise selbst keine redaktionellen Inhalte.

Beispiele:

- Section
- Container
- Grid
- Columns

## Eigenschaften

Layout-Blöcke:

- können Kind-Elemente besitzen
- definieren strukturelle Bereiche
- beeinflussen Layoutstruktur
- sind meist mehrfach verschachtelbar

## Typische Regeln

Beispiele für mögliche Regeln:

- Section darf Container enthalten
- Container darf Blocks und Komponenten enthalten
- Grid darf Columns enthalten

Diese Regeln werden später über Layout-Schemas definiert.

---

# 2. Content-Blöcke

## Zweck
Content-Blöcke sind einfache redaktionelle Bausteine.

Sie enthalten meist direkte Inhalte.

Beispiele:

- Text
- Bild
- Video
- Galerie
- Button

## Eigenschaften

Content-Blöcke:

- enthalten Inhalte
- besitzen normalerweise keine Kind-Elemente
- werden direkt im Inspector bearbeitet

## Beispiel

Text-Block:

```
{
  "type": "block",
  "definition": "text",
  "props": {
    "content": "Beispieltext"
  }
}
```

---

# 3. Komponenten

## Zweck
Komponenten sind komplexere wiederverwendbare Bausteine.

Sie kombinieren Struktur, Inhalt und Designlogik.

Beispiele:

- Hero
- Slider
- Feature Grid
- FAQ
- CTA

## Herkunft

Komponenten stammen aus dem Komponenten-Katalog.

Mögliche Quellen:

- Core
- Theme
- Modul
- Benutzerdefiniert

## Instanziierung

Beim Einfügen wird aus der Vorlage eine Instanz erzeugt.

```
Hero Template
↓
Hero Instance
```

Diese Instanz wird lokal im Inhalt gespeichert.

## Bearbeitung

Komponenten besitzen editierbare Eigenschaften.

Diese werden im Inspector angezeigt.

Beispiele:

- Titel
- Untertitel
- Bilder
- Links

---

# 4. Snippets

## Zweck
Snippets sind kleine wiederverwendbare Inhaltsbausteine.

Sie sind einfacher als Komponenten.

Beispiele:

- Infobox
- Hinweis
- Kontaktblock

## Eigenschaften

Snippets:

- besitzen meist einfache Inhalte
- werden als Instanzen eingefügt
- können lokal angepasst werden

---

# 5. Unterschied zwischen Komponenten und Snippets

Komponenten:

- komplexere Struktur
- mehrere Eigenschaften
- oft visuelle Layoutlogik

Snippets:

- kleinere Inhalte
- meist textbasiert
- weniger komplex

---

# 6. Definitionen und Schemas

Alle Bausteine werden über Definitionen beschrieben.

Definitionen enthalten:

- ID
- Name
- Kategorie
- editierbare Felder
- erlaubte Kind-Elemente

Der Editor interpretiert diese Definitionen dynamisch.

---

# 7. Katalogsystem

Der Editor besitzt mehrere Kataloge:

- Layout-Katalog
- Block-Katalog
- Komponenten-Katalog
- Snippet-Katalog

Diese Kataloge liefern die Vorlagen für neue Instanzen.

---

# 8. Instanzaktionen

Jede Instanz im Editor unterstützt folgende Aktionen:

- bearbeiten
- duplizieren
- löschen
- verschieben
- als Komponente speichern
- auf Vorlage zurücksetzen

---

# 9. Erweiterbarkeit

Module können neue Bausteine hinzufügen.

Beispiele:

- neue Komponenten
- neue Blocktypen
- neue Layoutstrukturen

Diese Erweiterungen erscheinen automatisch im jeweiligen Katalog.

---

# Fazit

Das Block-, Komponenten- und Snippet-System bildet die Bausteine des visuellen Editors.

Durch die klare Trennung der Elementtypen bleibt der Editor strukturiert, erweiterbar und kompatibel mit der bestehenden Chamy-Architektur.

