# Chamy Inhaltseditor – Content Model

## Überblick
Der Inhaltseditor speichert Inhalte nicht als fertiges Rendering, sondern als strukturierte Inhaltsinstanzen innerhalb des bestehenden Content-Systems.

Die Daten des Editors werden im JSON-Feld `data` eines Content-Eintrags abgelegt.

Damit bleibt der Editor vollständig kompatibel zum bestehenden Chamy-Ist-Zustand:

- Content bleibt strukturiert
- Rendering bleibt beim Theme-System
- Versionierung und States bleiben im Core

---

# 1. Grundmodell

Jeder im Editor bearbeitete Inhalt besitzt intern einen **Editor-Baum**.

Dieser Baum besteht aus verschachtelten Inhaltsinstanzen.

Beispiel:

```
root
 ├ section
 │   ├ container
 │   │   ├ hero_component_instance
 │   │   └ text_block_instance
 │   └ feature_grid_component_instance
 └ cta_component_instance
```

Jeder Knoten dieses Baums ist ein eigenständiges Editor-Element.

---

# 2. Ziele des Content Models

Das Content Model des Editors verfolgt folgende Ziele:

- kompatible Speicherung im bestehenden Content-System
- Trennung von Inhalt, Struktur und Rendering
- flexible Erweiterbarkeit durch Module und neue Schemas
- eindeutige Unterscheidung zwischen Vorlage und Instanz
- stabile Basis für Drag & Drop, Vorschau, Versionierung und Validierung

---

# 3. Kernelemente des Editor-Baums

## Root

Jeder Editor-Inhalt besitzt genau einen Root-Knoten.

Aufgaben des Root-Knotens:

- Einstiegspunkt des Blockbaums
- Referenz auf den bearbeiteten Content-Eintrag
- globale Editor-Metadaten

## Layout-Instanzen

Layout-Instanzen definieren strukturelle Bereiche.

Beispiele:

- section
- container
- grid
- columns

Eigenschaften:

- dürfen Kind-Elemente enthalten
- definieren keine eigentlichen redaktionellen Inhalte
- bilden das strukturelle Gerüst

## Content-Block-Instanzen

Content-Blöcke bilden einfache redaktionelle Bausteine.

Beispiele:

- text
- image
- gallery
- video
- button

Eigenschaften:

- enthalten konkrete Inhalte
- besitzen einfache, schema-gesteuerte Eigenschaften

## Komponenten-Instanzen

Komponenten-Instanzen sind wiederverwendbare UI-Bausteine, die aus dem Katalog eingefügt werden.

Beispiele:

- hero
- slider
- faq
- cta
- feature_grid

Eigenschaften:

- entstehen immer aus einer Vorlage
- werden beim Einfügen zu eigenständigen Instanzen
- können vollständig bearbeitet werden
- verändern die Vorlage nicht

## Snippet-Instanzen

Snippet-Instanzen sind kleine wiederverwendbare Inhaltsstücke.

Beispiele:

- info_box
- contact_short
- notice_bar

Eigenschaften:

- kompakter als Komponenten
- meist text- oder informationszentriert
- können lokal angepasst werden

---

# 4. Vorlage und Instanz

Der Editor unterscheidet strikt zwischen Vorlage und Instanz.

## Vorlage

Eine Vorlage ist ein katalogisiertes, wiederverwendbares Element.

Vorlagen liegen im jeweiligen Katalogsystem:

- Komponenten-Katalog
- Snippet-Katalog
- Layout-Katalog

Vorlagen enthalten:

- Basisstruktur
- Standardwerte
- editierbare Felder
- Regeln

## Instanz

Eine Instanz ist die konkrete Verwendung einer Vorlage innerhalb eines Inhalts.

Eigenschaften:

- lokal im Inhalt gespeichert
- vollständig bearbeitbar
- unabhängig von der Vorlage

---

# 5. Grundstruktur eines Editor-Elements

Jedes Element im Editor-Baum sollte logisch mindestens folgende Informationen besitzen:

```json
{
  "id": "node_hero_1001",
  "type": "component",
  "definition": "hero",
  "source": "core",
  "label": "Hero",
  "props": {},
  "children": [],
  "meta": {}
}
```

## Bedeutung der Felder

- `id` → eindeutige Instanz-ID im aktuellen Inhalt
- `type` → Art des Elements
- `definition` → definierter Block-/Komponenten-/Snippet-Typ
- `source` → Herkunft, z. B. `core`, `theme`, `module`, `custom`
- `label` → Anzeigename im Editor
- `props` → editierbare Eigenschaften und Inhalte
- `children` → Kind-Elemente
- `meta` → technische Zusatzinformationen

---

# 6. Empfohlene Elementtypen

Der Editor sollte intern mindestens folgende Typen unterscheiden:

- `root`
- `layout`
- `block`
- `component`
- `snippet`

Optional später:

- `dynamic`
- `slot`
- `reference`

---

# 7. Beispielstruktur im Content-Feld

Beispiel für das JSON-Feld `data` eines Inhalts:

```json
{
  "editor": {
    "version": 1,
    "contentType": "page",
    "root": {
      "id": "root_1",
      "type": "root",
      "children": [
        {
          "id": "layout_section_1",
          "type": "layout",
          "definition": "section",
          "label": "Section",
          "props": {
            "variant": "default"
          },
          "children": [
            {
              "id": "layout_container_1",
              "type": "layout",
              "definition": "container",
              "label": "Container",
              "props": {},
              "children": [
                {
                  "id": "component_hero_1",
                  "type": "component",
                  "definition": "hero",
                  "label": "Hero",
                  "props": {
                    "title": "Willkommen bei Chamy",
                    "subtitle": "Modulares CMS mit visuellem Editor"
                  },
                  "children": []
                },
                {
                  "id": "block_text_1",
                  "type": "block",
                  "definition": "text",
                  "label": "Text",
                  "props": {
                    "content": "Dies ist ein Beispieltext."
                  },
                  "children": []
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

---

# 8. Eigenschaften (props)

Die eigentlichen Inhalte und Einstellungen eines Elements werden in `props` gespeichert.

Beispiele:

## Textblock

```json
{
  "content": "Hallo Welt",
  "alignment": "left"
}
```

## Hero-Komponente

```json
{
  "title": "Willkommen",
  "subtitle": "Schön, dass du da bist",
  "buttonLabel": "Mehr erfahren"
}
```

Die Struktur der `props` wird nicht hart im Editor verdrahtet, sondern durch Schemas definiert.

---

# 9. Metadaten

Zusätzliche technische Informationen werden in `meta` gespeichert.

Mögliche Inhalte:

- ursprüngliche Vorlagen-ID
- Zeitpunkt der Instanzerstellung
- Bearbeitungsflags
- interne Editor-Informationen
- spätere Migrationshinweise

Beispiel:

```json
{
  "templateId": "hero_default",
  "createdFrom": "component_catalog",
  "resettable": true,
  "savableAsComponent": true
}
```

---

# 10. Kinderbeziehungen

Nicht jedes Element darf Kind-Elemente besitzen.

## Typische Regeln

- `layout` darf Kinder besitzen
- `root` darf Kinder besitzen
- `component` darf je nach Definition Kinder besitzen oder nicht
- `block` besitzt normalerweise keine Kinder
- `snippet` besitzt normalerweise keine Kinder

Diese Regeln werden später durch Definitionsschemata gesteuert.

---

# 11. Kompatibilität zum ContentType-System

Der Editor ersetzt das ContentType-System nicht.

Stattdessen ergänzt er es.

## Bestehende ContentType-Felder

Das ContentType-System bleibt verantwortlich für:

- Metafelder
- Slugs
- Veröffentlichungslogik
- Übersetzbarkeit
- Revisionierbarkeit
- SEO-Felder

## Editor-Daten

Der Editor ergänzt innerhalb des Datensatzes den strukturierten Inhaltsbaum.

Empfohlene Trennung:

- klassische Metadaten → bestehende ContentType-Felder
- visuelle Inhaltsstruktur → `data.editor`

---

# 12. Laden und Speichern

## Laden

Beim Öffnen eines Inhalts lädt der Editor:

- Content-Eintrag
- Content-Type-Definition
- vorhandenen Editor-Baum
- verfügbare Block-/Komponenten-/Snippet-Definitionen

## Speichern

Beim Speichern schreibt der Editor:

- aktualisierten Editor-Baum
- angepasste Inhaltswerte
- technische Metadaten

in den bestehenden Content-Datensatz zurück.

Versionierung und States bleiben unverändert beim Core.

---

# 13. Reset und Komponentenspeicherung

## Reset auf Vorlage

Eine Instanz kann anhand ihrer Metadaten auf den Stand der ursprünglichen Vorlage zurückgesetzt werden.

## Instanz als neue Komponente speichern

Eine Instanz kann inklusive ihrer Struktur und Props als neue Vorlage in den Komponenten-Katalog übernommen werden.

---

# 14. Erweiterbarkeit

Das Content Model muss offen für spätere Erweiterungen bleiben.

Mögliche spätere Ergänzungen:

- dynamische Datenblöcke
- Slot-Systeme
- referenzierte globale Inhalte
- Varianten-Systeme
- mehrsprachige Feldgruppen innerhalb einzelner Komponenten

---

# Fazit

Das Content Model des Chamy-Inhaltseditors basiert auf einem strukturierten Editor-Baum aus Instanzen.

Dadurch bleibt der Editor:

- kompatibel mit dem bestehenden Content-System
- theme-neutral
- modular erweiterbar
- geeignet für Drag & Drop, Versionierung und schema-gesteuerte Bearbeitung

Es bildet die technische Grundlage für alle weiteren Editor-Systeme.

