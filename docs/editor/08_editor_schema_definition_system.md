# Chamy Inhaltseditor – Schema- und Definitionssystem

## Überblick
Das Schema- und Definitionssystem ist die Grundlage für die Erweiterbarkeit des Inhaltseditors.

Der Editor besitzt **keine fest verdrahtete Logik für einzelne Blöcke oder Komponenten**. Stattdessen liest er Definitionsdateien (Schemas) und erzeugt daraus dynamisch:

- Inspector-Felder
- erlaubte Kind-Elemente
- Drag & Drop Regeln
- Standardwerte
- Validierungsregeln

Dadurch können neue Elemente hinzugefügt werden, ohne den Editor-Code selbst ändern zu müssen.

---

# 1. Ziele des Schema-Systems

Das Schema-System verfolgt mehrere Ziele:

- dynamische Erweiterbarkeit
- klare Strukturdefinition
- automatische Generierung von Editor-UI
- zentrale Validierung
- modulare Erweiterung durch Themes und Module

---

# 2. Arten von Definitionen

Der Editor kennt mehrere Definitionstypen.

## Layout-Definitionen

Beschreiben strukturelle Layout-Blöcke.

Beispiele:

- section
- container
- grid
- columns

Layout-Definitionen definieren:

- erlaubte Kind-Elemente
- Layout-Eigenschaften
- Standardwerte

---

## Block-Definitionen

Beschreiben einfache Content-Blöcke.

Beispiele:

- text
- image
- gallery
- video

Block-Definitionen definieren:

- Inhaltsfelder
- Validierungsregeln
- Inspector-Felder

---

## Komponenten-Definitionen

Beschreiben komplexe UI-Komponenten.

Beispiele:

- hero
- slider
- faq
- cta

Komponenten-Definitionen definieren:

- Struktur
- editierbare Felder
- mögliche Kind-Elemente
- Standardwerte

---

## Snippet-Definitionen

Beschreiben kleine wiederverwendbare Inhalte.

Beispiele:

- info_box
- notice
- contact_short

Snippet-Definitionen ähneln Block-Definitionen, besitzen aber eine stärkere Wiederverwendungslogik.

---

# 3. Grundstruktur einer Definition

Jede Definition sollte mindestens folgende Informationen enthalten:

```json
{
  "id": "hero",
  "type": "component",
  "label": "Hero",
  "category": "layout",
  "source": "core",
  "icon": "hero",
  "fields": [],
  "allowedChildren": [],
  "defaultProps": {}
}
```

---

# 4. Felder

Die `fields` definieren die editierbaren Eigenschaften eines Elements.

Beispiel:

```json
{
  "fields": [
    {
      "type": "text",
      "name": "title",
      "label": "Titel",
      "required": true
    },
    {
      "type": "textarea",
      "name": "subtitle",
      "label": "Untertitel"
    }
  ]
}
```

Diese Felder werden automatisch im Inspector dargestellt.

---

# 5. Validierung

Definitionen können Validierungsregeln enthalten.

Beispiele:

- Pflichtfelder
- maximale Textlänge
- erlaubte Werte
- Zahlenbereiche

Diese Regeln werden sowohl im Editor als auch beim Speichern überprüft.

---

# 6. Standardwerte

Definitionen können Standardwerte enthalten.

Beispiel:

```json
{
  "defaultProps": {
    "title": "Standardtitel",
    "alignment": "center"
  }
}
```

Beim Einfügen eines Elements wird daraus die initiale Instanz erzeugt.

---

# 7. erlaubte Kind-Elemente

Definitionen können festlegen, welche Elemente als Kinder erlaubt sind.

Beispiel:

```json
{
  "allowedChildren": [
    "block",
    "component",
    "snippet"
  ]
}
```

Diese Regeln werden vom Drag & Drop System verwendet.

---

# 8. Kategorien

Definitionen besitzen Kategorien, damit sie im Katalog organisiert werden können.

Beispiele:

- layout
- text
- media
- navigation
- marketing

Diese Kategorien helfen Redakteuren beim Finden von Elementen.

---

# 9. Quellen

Definitionen können aus verschiedenen Quellen stammen.

Mögliche Quellen:

- core
- theme
- module
- user

Die Quelle wird in der Definition gespeichert.

---

# 10. Registrierung

Beim Start des Editors werden alle verfügbaren Definitionen registriert.

Mögliche Registrierungsquellen:

- Core-Definitionen
- Theme-Definitionen
- Modul-Definitionen

Diese Definitionen werden in einer zentralen Registry gesammelt.

---

# 11. Registry-System

Der Editor besitzt eine zentrale Registry für Definitionen.

Diese Registry enthält:

- Layout-Definitionen
- Block-Definitionen
- Komponenten-Definitionen
- Snippet-Definitionen

Die Registry wird vom Editor verwendet für:

- Katalogdarstellung
- Drag & Drop Regeln
- Inspector-Felder

---

# 12. Erweiterbarkeit

Module und Themes können neue Definitionen registrieren.

Beispiele:

- neue Komponenten
- neue Blocktypen
- neue Layoutstrukturen

Diese Erweiterungen erscheinen automatisch im Editor-Katalog.

---

# 13. Versionskompatibilität

Definitionen können versioniert werden.

Beispiel:

```
hero@1
hero@2
```

Dies ermöglicht spätere Änderungen an Komponenten, ohne bestehende Inhalte zu zerstören.

---

# Fazit

Das Schema- und Definitionssystem ist die Grundlage für die Flexibilität des Chamy-Inhaltseditors.

Durch diese Architektur können neue Bausteine, Komponenten und Layouts hinzugefügt werden, ohne den Editor selbst verändern zu müssen.

