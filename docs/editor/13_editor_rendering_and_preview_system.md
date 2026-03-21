# Chamy Inhaltseditor – Rendering- und Vorschau-System

## Überblick
Das Rendering- und Vorschau-System bestimmt, wie Inhalte innerhalb der Editor-Canvas dargestellt werden. Ziel ist es, Redakteuren eine möglichst realistische Vorschau des späteren Frontends zu zeigen, ohne dabei das eigentliche Theme-Rendering vollständig zu duplizieren.

Der Editor rendert Inhalte daher **nicht eigenständig als endgültiges Frontend**, sondern nutzt eine Kombination aus:

- strukturellem Editor-Rendering
- Theme-Komponenten
- Vorschau-Rendering über das Backend

Dadurch bleibt die Darstellung möglichst nah am realen Frontend.

---

# 1. Ziele des Rendering-Systems

Das Rendering-System verfolgt mehrere wichtige Ziele:

- möglichst realistische Darstellung im Editor
- schnelle Reaktionszeit bei Änderungen
- klare Trennung zwischen Editor-UI und Frontend-Theme
- Wiederverwendung der Theme-Komponenten
- stabile Vorschau komplexer Inhalte

---

# 2. Editor-Rendering vs. Frontend-Rendering

Der Editor nutzt zwei unterschiedliche Rendering-Strategien.

## Editor-Rendering

Das Editor-Rendering ist eine schnelle Darstellung innerhalb der Canvas.

Eigenschaften:

- schnelle Aktualisierung
- Darstellung der Struktur
- Hervorhebung editierbarer Bereiche
- zusätzliche Editor-Overlays

Dieses Rendering dient hauptsächlich der Bearbeitung.

---

## Frontend-Vorschau

Für eine realistische Darstellung kann der Editor zusätzlich eine Frontend-Vorschau laden.

Diese wird über das Backend gerendert und zeigt den Inhalt so, wie er im Frontend erscheinen würde.

---

# 3. Canvas-Darstellung

Die Canvas stellt den Editor-Baum visuell dar.

Jedes Element wird über seine Definition gerendert.

Beispielstruktur:

```
Page
 ├ Section
 │   ├ Container
 │   │   ├ Hero
 │   │   └ Text
 │   └ FeatureGrid
 └ CTA
```

Während der Bearbeitung können zusätzliche visuelle Elemente erscheinen:

- Hover-Markierungen
- Auswahlrahmen
- Drop-Zonen
- Inline-Aktionsleisten

Diese Elemente gehören ausschließlich zur Editor-Oberfläche.

---

# 4. Rendering von Komponenten

Komponenten können auf unterschiedliche Weise gerendert werden.

## Editor-Vorschau

Komponenten besitzen eine vereinfachte Vorschau-Darstellung im Editor.

Diese Darstellung kann enthalten:

- reduzierte Layoutstruktur
- Platzhalterbilder
- vereinfachte Texte

---

## Theme-Vorschau

Optional kann eine Komponente über das Theme gerendert werden.

Dies ermöglicht eine genauere Darstellung, ist jedoch langsamer als das Editor-Rendering.

---

# 5. Vorschau-Modi

Der Editor unterstützt mehrere Vorschau-Modi.

## Desktop

Standardansicht der Seite.

## Tablet

Simulierte Darstellung für mittlere Bildschirmgrößen.

## Mobile

Darstellung für kleine Bildschirme.

Die Canvas passt ihre Breite entsprechend an.

---

# 6. Live-Aktualisierung

Änderungen im Inspector oder durch Drag & Drop sollen möglichst sofort in der Canvas sichtbar werden.

Beispiele:

- Textänderung → sofort sichtbar
- Bildwechsel → sofort aktualisiert
- Layoutänderung → direkte Anpassung

Diese Live-Vorschau verbessert die Bearbeitbarkeit erheblich.

---

# 7. Vorschau über das Backend

Für eine realistische Darstellung kann der Editor eine Vorschau vom Backend anfordern.

## Ablauf

1. Editor sendet aktuelle Inhaltsstruktur
2. Backend rendert Inhalt über das Theme-System
3. Ergebnis wird als Vorschau angezeigt

Diese Vorschau entspricht der tatsächlichen Frontend-Ausgabe.

---

# 8. Vorschau ohne Veröffentlichung

Die Vorschau funktioniert auch für Inhalte im Status:

- draft
- review

Dadurch können Inhalte überprüft werden, bevor sie veröffentlicht werden.

---

# 9. Rendering von Medien

Medien innerhalb des Editors werden über das Mediasystem geladen.

Typische Beispiele:

- Bilder
- Videos
- Galerien

Der Editor kann Platzhalter anzeigen, wenn Medien noch nicht geladen wurden.

---

# 10. Fehlerbehandlung im Rendering

Wenn eine Komponente nicht korrekt gerendert werden kann, sollte der Editor:

- eine Fehlermeldung anzeigen
- einen Fallback darstellen
- die Bearbeitung weiterhin ermöglichen

Dies verhindert, dass einzelne Fehler den gesamten Editor blockieren.

---

# 11. Performance-Grundsätze

Rendering innerhalb des Editors muss effizient sein.

Empfohlene Maßnahmen:

- nur betroffene Bereiche neu rendern
- keine vollständigen Canvas-Neuberechnungen
- Lazy-Loading für komplexe Komponenten

---

# 12. Erweiterbarkeit

Module und Themes können eigene Rendering-Strategien für ihre Komponenten definieren.

Beispiele:

- spezielle Vorschau-Komponenten
- vereinfachte Editor-Darstellungen
- zusätzliche Vorschau-Modi

Diese Erweiterungen werden über das Schema- und Registry-System registriert.

---

# Fazit

Das Rendering- und Vorschau-System ermöglicht eine realistische Darstellung von Inhalten im Editor, ohne die Bearbeitungsgeschwindigkeit zu beeinträchtigen.

Durch die Kombination aus Editor-Rendering und Theme-Vorschau bleibt der Editor sowohl schnell als auch nah am tatsächlichen Frontend-Verhalten.

