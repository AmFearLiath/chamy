# Chamy Inhaltseditor – Canvas State Engine

## Überblick
Die Canvas State Engine ist das interne Zustandsmodell des Inhaltseditors. Sie verwaltet den aktuell bearbeiteten Editor-Baum, die Auswahlzustände, Änderungsstände sowie alle Interaktionen, die auf der Canvas stattfinden.

Die State Engine ist damit das zentrale technische Herz des Editors.

Sie speichert keine Daten direkt in der Datenbank, sondern hält den aktuellen Arbeitszustand im Editor, bis dieser gespeichert oder verworfen wird.

---

# 1. Ziele der State Engine

Die Canvas State Engine verfolgt folgende Ziele:

- Verwaltung des aktuellen Editor-Baums
- Verwaltung der Auswahl und UI-Zustände
- Unterstützung von Undo und Redo
- kontrollierte Verarbeitung von Änderungen
- Vorbereitung der Serialisierung für das Speichern

---

# 2. Kernbereiche des Editor-States

Der Editor-State sollte logisch mindestens folgende Bereiche enthalten:

- document
- selection
- ui
- history
- meta

---

# 3. Bereich "document"

Der Bereich `document` enthält den eigentlichen Editor-Baum.

Beispiel:

```json
{
  "document": {
    "root": {
      "id": "root_1",
      "type": "root",
      "children": []
    }
  }
}
```

Dieser Bereich repräsentiert die inhaltliche Struktur der bearbeiteten Seite oder des bearbeiteten Inhalts.

---

# 4. Bereich "selection"

Der Bereich `selection` enthält Informationen über das aktuell ausgewählte Element.

Beispiel:

```json
{
  "selection": {
    "activeNodeId": "component_hero_1",
    "path": ["root_1", "layout_section_1", "component_hero_1"]
  }
}
```

Dieser Bereich steuert:

- welches Element im Inspector angezeigt wird
- welche Canvas-Markierungen sichtbar sind
- welche Aktionen verfügbar sind

---

# 5. Bereich "ui"

Der Bereich `ui` enthält rein editorbezogene Zustände.

Beispiele:

- ist linke Sidebar geöffnet
- ist rechte Sidebar geöffnet
- welcher Katalogbereich ist aktiv
- welcher Gerätemodus ist aktiv
- ist Drag-Modus aktiv

Beispiel:

```json
{
  "ui": {
    "leftSidebarOpen": true,
    "rightSidebarOpen": true,
    "activeCatalogTab": "components",
    "previewMode": "desktop",
    "isDragging": false
  }
}
```

---

# 6. Bereich "history"

Der Bereich `history` speichert Zustände für Undo und Redo.

Empfohlenes Modell:

- `past`
- `present`
- `future`

Beispiel:

```json
{
  "history": {
    "past": [],
    "future": []
  }
}
```

Der aktuelle Zustand befindet sich im aktiven Editor-State und wird bei Änderungen in die History verschoben.

---

# 7. Bereich "meta"

Der Bereich `meta` enthält technische Zusatzinformationen.

Beispiele:

- geladener Content-Type
- aktuelle Inhalt-ID
- Dirty-State
- Lock-Status
- letzte Speicherung

Beispiel:

```json
{
  "meta": {
    "contentId": 42,
    "contentType": "page",
    "dirty": true,
    "locked": false
  }
}
```

---

# 8. State-Änderungen

Änderungen am Editor-State dürfen nicht chaotisch erfolgen.

Jede Änderung sollte über klar definierte Aktionen laufen.

Beispiele für Aktionen:

- `ADD_NODE`
- `REMOVE_NODE`
- `MOVE_NODE`
- `UPDATE_PROPS`
- `SELECT_NODE`
- `TOGGLE_SIDEBAR`
- `SET_PREVIEW_MODE`

Diese Aktionen verändern den State kontrolliert und nachvollziehbar.

---

# 9. Bearbeitung des Editor-Baums

Die State Engine verwaltet alle Änderungen am Blockbaum.

Mögliche Operationen:

- Knoten hinzufügen
- Knoten entfernen
- Knoten verschieben
- Eigenschaften eines Knotens ändern
- Knoten duplizieren

Jede Operation muss dabei die Schema- und Strukturregeln respektieren.

---

# 10. Auswahlsteuerung

Die Auswahl eines Elements verändert den State.

Beim Selektieren eines Elements werden mindestens folgende Werte aktualisiert:

- aktive Instanz-ID
- Pfad im Baum
- Sichtbarkeit des Inspectors

Diese Auswahl ist die Grundlage für die gesamte Bearbeitungslogik.

---

# 11. Undo und Redo

Vor jeder relevanten strukturellen Änderung sollte der bisherige Zustand in die History übernommen werden.

Typische undo-fähige Änderungen:

- Hinzufügen
- Verschieben
- Löschen
- Eigenschaftsänderungen

Beim Undo:

- wird ein früherer Zustand wieder aktiv
- der bisherige Zustand wandert in `future`

Beim Redo erfolgt der umgekehrte Vorgang.

---

# 12. Dirty-State

Die State Engine sollte erkennen, ob ungespeicherte Änderungen vorliegen.

Dies geschieht über ein `dirty`-Flag.

Beispiele:

- Änderung im Inspector → `dirty = true`
- Element verschoben → `dirty = true`
- Speichern erfolgreich → `dirty = false`

Dieses Flag steuert u. a.:

- Speichern-Hinweise
- Warnung beim Verlassen des Editors

---

# 13. Serialisierung

Beim Speichern wird der aktuelle `document`-Bereich serialisiert und in das Content-Modell geschrieben.

Nicht alle UI-Zustände werden gespeichert.

Gespeichert werden primär:

- Editor-Baum
- Props
- Metadaten, soweit inhaltlich relevant

Nicht in den Content-Datensatz gehören z. B.:

- offene Sidebars
- temporäre Hover-Zustände
- Dragging-Zustände

---

# 14. Laden eines Inhalts

Beim Öffnen eines Inhalts lädt die State Engine:

- den gespeicherten Editor-Baum
- Meta-Informationen
- Definitionen aus der Registry
- Content-Type-Kontext

Anschließend wird daraus der initiale Editor-State aufgebaut.

---

# 15. Verhalten bei leeren Inhalten

Falls ein Inhalt noch keine Editor-Struktur besitzt, erzeugt die State Engine einen initialen Startzustand.

Beispiel:

```json
{
  "document": {
    "root": {
      "id": "root_1",
      "type": "root",
      "children": []
    }
  }
}
```

Optional können bestimmte Content Types Startstrukturen definieren.

---

# 16. Performance-Grundsätze

Die State Engine sollte effizient arbeiten.

Wichtige Prinzipien:

- Änderungen möglichst lokal anwenden
- keine unnötigen Komplett-Neuberechnungen
- klare Trennung von inhaltlichem State und UI-State

Dies ist besonders wichtig bei großen Seiten mit vielen Instanzen.

---

# 17. Fehlerresistenz

Die State Engine muss robust gegen ungültige Operationen sein.

Beispiele:

- Knoten existiert nicht
- ungültige Drop-Ziele
- ungültige Kind-Beziehungen

Solche Aktionen dürfen den Editor-State nicht zerstören.

---

# 18. Erweiterbarkeit

Die State Engine muss offen für spätere Erweiterungen bleiben.

Mögliche spätere Erweiterungen:

- kollaborative Bearbeitung
- Live-Kommentare
- Mehrfachauswahl
- lokale Entwurfszustände
- temporäre Vorschau-Overlays

---

# Fazit

Die Canvas State Engine ist das interne Betriebssystem des Chamy-Inhaltseditors.

Sie hält den aktuellen Arbeitszustand des Editors, steuert Auswahl und Interaktionen und bereitet die strukturierten Inhaltsdaten für das Speichern vor.

Damit bildet sie die technische Grundlage für ein stabiles, nachvollziehbares und erweiterbares Editor-Verhalten.

