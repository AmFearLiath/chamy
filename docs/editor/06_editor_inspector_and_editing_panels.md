# Chamy Inhaltseditor – Inspector und Bearbeitungspanels

## Überblick
Der Inspector ist die rechte Sidebar des Inhaltseditors. Er zeigt alle bearbeitbaren Eigenschaften des aktuell ausgewählten Elements an.

Der Inspector ist **kontextsensitiv**: Seine Inhalte ändern sich abhängig vom ausgewählten Element im Editor-Baum.

Die Felder im Inspector werden **nicht hart im Editor definiert**, sondern dynamisch aus Schemas generiert.

Quellen für diese Schemas können sein:

- Content-Type-Definitionen
- Komponenten-Definitionen
- Layout-Definitionen
- Snippet-Definitionen

Dadurch bleibt der Editor modular und erweiterbar.

---

# 1. Öffnen des Inspectors

Der Inspector öffnet sich automatisch, wenn ein Element auf der Canvas ausgewählt wird.

Falls die rechte Sidebar eingeklappt ist:

- wird sie automatisch geöffnet
- das ausgewählte Element wird direkt im Inspector dargestellt

Dieses Verhalten stellt sicher, dass Redakteure sofort verstehen, wo Inhalte bearbeitet werden.

---

# 2. Grundstruktur des Inspectors

Der Inspector besteht aus mehreren Tabs.

Empfohlene Standardstruktur:

- Inhalt
- Layout
- Design
- Advanced

Nicht jeder Elementtyp benötigt alle Tabs.

Der Editor zeigt nur die Tabs an, die für das ausgewählte Element relevant sind.

---

# 3. Tab "Inhalt"

Der Inhalt-Tab enthält die redaktionellen Daten eines Elements.

Beispiele für Felder:

- Text
- Titel
- Beschreibung
- Bildauswahl
- Links
- Listen

Diese Felder werden typischerweise aus Komponenten- oder Block-Schemas generiert.

Beispiel:

```
Hero-Komponente

Titel
Untertitel
Button-Label
Button-Link
Hintergrundbild
```

---

# 4. Tab "Layout"

Der Layout-Tab enthält strukturelle Einstellungen.

Typische Eigenschaften:

- Margin
- Padding
- Alignment
- Breite
- Grid-Position

Diese Einstellungen beeinflussen das Layoutverhalten eines Elements innerhalb seines Containers.

Layoutoptionen können abhängig vom Elementtyp variieren.

---

# 5. Tab "Design"

Der Design-Tab enthält visuelle Optionen.

Beispiele:

- Hintergrundfarbe
- Hintergrundbild
- Border
- Schatten
- Animation

Diese Optionen bleiben **theme-neutral** und dürfen keine theme-spezifischen Klassen oder Styles fest codieren.

Das Theme entscheidet letztlich, wie diese Eigenschaften interpretiert werden.

---

# 6. Tab "Advanced"

Der Advanced-Tab enthält technische Optionen.

Beispiele:

- CSS-Klassen
- Sichtbarkeit
- Bedingungen
- interne Flags

Diese Optionen richten sich eher an fortgeschrittene Nutzer oder Entwickler.

---

# 7. Dynamische Feldgenerierung

Die Felder im Inspector werden dynamisch generiert.

Der Editor liest die Definitionen eines Elements und erstellt daraus die passenden Eingabefelder.

Beispieldefinition:

```
{
  "fields": [
    {
      "type": "text",
      "name": "title",
      "label": "Titel"
    },
    {
      "type": "textarea",
      "name": "subtitle",
      "label": "Untertitel"
    }
  ]
}
```

Der Inspector erstellt daraus automatisch die entsprechenden Eingabefelder.

---

# 8. Feldtypen

Der Inspector sollte mehrere Feldtypen unterstützen.

Typische Beispiele:

- text
- textarea
- richtext
- select
- multiselect
- checkbox
- toggle
- number
- datetime
- media

Diese Feldtypen orientieren sich an den bereits im Content-Type-System verwendeten Feldtypen.

---

# 9. Validierung

Eingaben im Inspector können validiert werden.

Mögliche Validierungen:

- Pflichtfelder
- maximale Länge
- erlaubte Werte
- Datentypen

Validierungsregeln werden ebenfalls aus den jeweiligen Schemas übernommen.

---

# 10. Vorschau-Updates

Änderungen im Inspector sollten möglichst sofort in der Canvas sichtbar werden.

Beispiele:

- Textänderung → sofortige Aktualisierung
- Bildwechsel → sofortige Vorschau

Diese Echtzeitvorschau verbessert das Verständnis für die Auswirkungen von Änderungen.

---

# 11. Speicherung

Änderungen im Inspector werden zunächst im Editor-State gehalten.

Erst beim Speichern des Inhalts werden die Änderungen in den Content-Datensatz geschrieben.

Dies erlaubt:

- Undo/Redo
- mehrere Änderungen ohne sofortige Persistenz

---

# 12. Kontextabhängige Panels

Bestimmte Elementtypen können zusätzliche Panels besitzen.

Beispiele:

- SEO-Einstellungen
- Datenquellen
- Varianten

Diese Panels werden nur angezeigt, wenn das jeweilige Element sie definiert.

---

# Fazit

Der Inspector ist das zentrale Werkzeug zur Bearbeitung einzelner Editor-Elemente.

Durch die schema-gesteuerte Generierung der Panels bleibt der Editor flexibel, erweiterbar und kompatibel mit der bestehenden Chamy-Architektur.

