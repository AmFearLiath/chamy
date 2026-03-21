# Chamy Inhaltseditor – Überblick

## Zweck
Der Inhaltseditor ist die zentrale visuelle Arbeitsumgebung für Redakteure innerhalb des Chamy‑Adminbereichs. Er ermöglicht das Erstellen, Bearbeiten und Strukturieren von Inhalten wie Seiten, Beiträgen, Komponenten, Layoutblöcken und Snippets über eine visuelle Drag‑&‑Drop‑Oberfläche.

Der Editor ersetzt **keine bestehenden Core-Systeme**, sondern stellt eine komfortable Oberfläche dar, um mit diesen zu arbeiten.

Der Editor greift primär auf folgende bestehende Manager zu:

- ContentManager
- ContentTypeManager
- StateManager
- VersionManager
- LayoutManager
- ComponentManager

Der Editor ist damit **kein eigenständiges CMS im CMS**, sondern eine visuelle Redaktionsoberfläche.

---

# Grundprinzipien

## Strukturierte Inhaltsinstanzen

Der Editor speichert Inhalte **nicht als fertiges HTML**, sondern als strukturierte Inhaltsinstanzen.

Die Daten werden im bestehenden JSON‑Feld `data` eines Content‑Eintrags gespeichert.

Beispielstruktur:

```
root
 ├ section
 │   ├ container
 │   │   ├ hero_component_instance
 │   │   └ text_block
 │   └ feature_grid_component_instance
 └ cta_component_instance
```

Diese Struktur beschreibt ausschließlich:

- Inhalt
- Struktur
- Konfiguration

Rendering erfolgt weiterhin ausschließlich über das Theme-System.

---

## Komponenten als Instanzen

Alle Elemente, die aus einem Katalog eingefügt werden, werden im Inhalt zu **eigenständigen Instanzen**.

Beispiel:

```
Component Catalog
  └ Hero Component

Editor
  └ HeroInstance_1045
```

Eigenschaften:

- Instanzen sind vollständig bearbeitbar
- Änderungen betreffen nur den aktuellen Inhalt
- Die ursprüngliche Vorlage bleibt unverändert

---

## Vorlagen und Instanzen

Der Editor unterscheidet klar zwischen:

### Vorlagen

Vorlagen sind Elemente aus dem Katalog:

- Layoutblöcke
- Komponenten
- Snippets

Diese dienen nur als **Startstruktur**.

### Instanzen

Instanzen entstehen beim Einfügen in einen Inhalt.

Sie werden im Content gespeichert und sind unabhängig von der Vorlage.

---

## Instanz → neue Komponente

Eine bearbeitete Instanz kann als neue Komponente gespeichert werden.

Workflow:

```
Instanz erstellen
↓
Bearbeiten
↓
Als Komponente speichern
↓
neue Vorlage im Komponenten-Katalog
```

Dies ermöglicht es Redakteuren, neue wiederverwendbare Bausteine zu erstellen.

---

## Instanz auf Vorlage zurücksetzen

Instanzen können auf die ursprüngliche Vorlage zurückgesetzt werden.

Beispiel:

```
Komponente stark verändert
↓
Reset auf Vorlage
↓
Originalstruktur wird wiederhergestellt
```

---

# Rolle des Editors

Der Editor übernimmt folgende Aufgaben:

- visuelle Strukturierung von Inhalten
- Drag‑&‑Drop von Layoutblöcken und Komponenten
- Bearbeitung von Instanz‑Eigenschaften
- Vorschau des Seitenaufbaus
- Speichern strukturierter Inhaltsdaten

Der Editor übernimmt **nicht**:

- Rendering
- Theme‑Logik
- Datenpersistenzlogik

Diese Aufgaben bleiben bei den jeweiligen Core-Managern.

---

# Integration in den Adminbereich

Der Editor läuft als **fast‑fullscreen Modal** innerhalb des Admin‑Themes.

Layout:

- minimaler Editor‑Header
- einklappbare linke Sidebar (Katalog)
- zentrale Canvas‑Arbeitsfläche
- einklappbare rechte Sidebar (Inspector)

Der Editor verwendet weiterhin das bestehende Twig‑Rendering im Admin‑Theme.

---

# Unterstützte Inhaltstypen

Der Editor soll mindestens folgende Content Types unterstützen:

- page
- article
- documentation
- snippet

Weitere Typen können durch Module ergänzt werden.

---

# Ziel des Editors

Der Chamy‑Inhaltseditor soll:

- visuell verständlich
- modular erweiterbar
- themeneutral
- kompatibel mit der bestehenden Core‑Architektur

sein.

Er bildet die Grundlage für ein modernes Redaktionssystem innerhalb von Chamy.

