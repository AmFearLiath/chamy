# Chamy Inhaltseditor – Layoutschutz und geschützte Strukturen

## Überblick
Bei komplexen Websites ist es oft notwendig, bestimmte Layoutbereiche vor unbeabsichtigten Änderungen zu schützen. Redakteure sollen Inhalte bearbeiten können, ohne dabei wichtige strukturelle Bereiche zu zerstören.

Dieses Dokument beschreibt das Konzept von **Layoutschutz und geschützten Strukturen** im Chamy-Inhaltseditor.

Ziel ist es, Redaktionsfreiheit mit struktureller Stabilität zu kombinieren.

---

# 1. Problemstellung

In klassischen Drag-&-Drop-Editoren können Redakteure oft jede Struktur verändern.

Typische Probleme:

- wichtige Layoutbereiche werden gelöscht
- Struktur wird versehentlich zerstört
- Komponenten werden an falsche Stellen verschoben
- Designvorgaben werden verletzt

Besonders bei größeren Teams führt das zu Fehlern.

---

# 2. Ziel des Layoutschutzes

Der Layoutschutz soll ermöglichen:

- feste Layoutstrukturen
- geschützte Designbereiche
- klare Redaktionszonen

Redakteure können Inhalte bearbeiten, ohne die Grundstruktur zu verändern.

---

# 3. Arten von Schutzmechanismen

Der Editor kann verschiedene Schutzstufen unterstützen.

## Strukturgesperrt

Der Block kann nicht verschoben oder gelöscht werden.

Redakteure können nur Inhalte bearbeiten.

## Vollständig gesperrt

Der Block ist komplett geschützt.

- keine Bearbeitung
- kein Verschieben
- kein Löschen

## Teilweise gesperrt

Nur bestimmte Eigenschaften sind editierbar.

Beispiel:

- Text darf geändert werden
- Layout bleibt unveränderlich

---

# 4. Schutzdefinition in Schemas

Layoutschutz kann direkt in Definitionen festgelegt werden.

Beispiel:

```
{
  "locked": true,
  "allowContentEditing": true
}
```

Diese Informationen werden vom Editor interpretiert.

---

# 5. Geschützte Layoutbereiche

Bestimmte Layoutbereiche können als **strukturgeschützt** definiert werden.

Beispiele:

- Header
- Navigation
- Footer
- Seitenlayout-Grundstruktur

Diese Bereiche werden typischerweise durch Themes definiert.

---

# 6. Redaktionszonen

Geschützte Layoutbereiche können **Redaktionszonen** enthalten.

Beispiel:

```
Header (locked)
Main Content (editable)
Footer (locked)
```

Nur die Redaktionszonen dürfen verändert werden.

---

# 7. Visuelle Darstellung im Editor

Geschützte Bereiche sollten im Editor klar erkennbar sein.

Mögliche Darstellung:

- Schloss-Symbol
- andere Hintergrundfarbe
- Hinweis "Layout geschützt"

Dies hilft Redakteuren zu verstehen, warum bestimmte Aktionen nicht möglich sind.

---

# 8. Schutz vor Löschung

Geschützte Elemente dürfen nicht gelöscht werden.

Wenn ein Redakteur versucht, ein geschütztes Element zu löschen:

- wird die Aktion blockiert
- optional erscheint ein Hinweis

---

# 9. Schutz vor Verschieben

Geschützte Layoutblöcke dürfen nicht verschoben werden.

Der Drag-&-Drop-Mechanismus muss diese Einschränkung respektieren.

---

# 10. Administrativer Override

Administratoren können Layoutschutz überschreiben.

Beispiel:

- spezielle Berechtigung
- "Layout bearbeiten" Modus

Dies ermöglicht strukturelle Änderungen durch erfahrene Nutzer.

---

# 11. Verwendung in Templates

Themes können geschützte Layoutstrukturen definieren.

Beispiel:

- Seitenlayout
- Landingpage-Template

Diese Strukturen dienen als Grundlage für neue Inhalte.

---

# 12. Kompatibilität mit Modulen

Module können ebenfalls geschützte Komponenten bereitstellen.

Beispiele:

- Tracking-Blöcke
- rechtliche Hinweise
- Systemkomponenten

Diese dürfen nicht entfernt werden.

---

# 13. Erweiterbarkeit

Das Layoutschutzsystem kann später erweitert werden.

Mögliche Erweiterungen:

- rollenbasierter Schutz
- zeitbasierte Sperren
- projektbezogene Layoutrichtlinien

---

# Fazit

Layoutschutz verhindert strukturelle Fehler im Editor und ermöglicht gleichzeitig sichere Redaktionsprozesse.

Durch geschützte Strukturen und definierte Redaktionszonen bleibt das Design stabil, während Inhalte weiterhin flexibel bearbeitet werden können.

