# Chamy Inhaltseditor – Interaktionen und Drag & Drop

## Überblick
Diese Datei beschreibt die Bedienlogik des Chamy-Inhaltseditors. Ziel ist ein visuell verständlicher, präziser und kontrollierter Editor, der Drag & Drop unterstützt, ohne die strukturellen Regeln des Systems zu verletzen.

Der Editor arbeitet mit einem strukturierten Instanzbaum. Interaktionen dürfen diesen Baum verändern, müssen dabei aber immer die Regeln der jeweiligen Definitionen und Schemas respektieren.

---

# 1. Grundprinzipien der Interaktion

Der Editor folgt folgenden Grundprinzipien:

- direkte Manipulation auf der Canvas
- klare visuelle Rückmeldung bei jeder Aktion
- strukturkonformes Verhalten statt freiem Chaos
- möglichst wenige verdeckte Zustände
- jederzeit nachvollziehbare Auswahl und Platzierung

Interaktionen sollen sich modern und flüssig anfühlen, aber nie unkontrolliert wirken.

---

# 2. Auswahl von Elementen

Elemente auf der Canvas sind direkt anklickbar.

## Verhalten bei Auswahl

Beim Anklicken eines Elements passiert Folgendes:

- das Element wird visuell markiert
- die rechte Sidebar öffnet sich automatisch, falls sie eingeklappt ist
- der Inspector zeigt die Eigenschaften des ausgewählten Elements
- der Strukturpfad des Elements wird sichtbar

## Visuelle Markierung

Die Auswahl sollte klar erkennbar sein, zum Beispiel durch:

- farbige Outline
- leicht hervorgehobenen Hintergrund
- sichtbare Aktionsleiste am Element

---

# 3. Hover-Verhalten

Beim Überfahren eines Elements mit der Maus wird dessen Bereich visuell hervorgehoben.

## Ziele des Hover-Verhaltens

- bessere Orientierung bei verschachtelten Strukturen
- einfachere Auswahl kleiner oder tiefer Elemente
- Vorschau auf den betroffenen Bereich

Empfohlene Signale:

- subtile Outline
- Label mit Elementtyp und Name
- Andeutung möglicher Aktionen

---

# 4. Drag & Drop Grundverhalten

Elemente aus dem linken Katalog können per Drag & Drop in die Canvas gezogen werden.

Ebenso können bestehende Instanzen innerhalb der Canvas verschoben werden.

## Drag-Quellen

- Layout-Katalog
- Block-Katalog
- Komponenten-Katalog
- Snippet-Katalog
- bestehende Instanzen innerhalb der Canvas

## Drop-Ziele

Drop-Ziele sind nur dort erlaubt, wo die jeweilige Strukturregel dies zulässt.

Beispiele:

- Section darf auf Root-Ebene eingefügt werden
- Container darf in Section eingefügt werden
- Textblock darf in Container eingefügt werden
- Grid-Column darf nur innerhalb eines Grid eingefügt werden

---

# 5. Drop-Zonen

Drop-Zonen sind die gültigen Einfügebereiche innerhalb der Canvas.

## Anforderungen an Drop-Zonen

- klar sichtbar beim Draggen
- nur anzeigen, wenn Platzierung erlaubt ist
- eindeutige Einfügerichtung zeigen

## Visuelle Hinweise

Mögliche Einfügehinweise:

- horizontale Einfügelinie
- markierter Containerbereich
- Platzhalterfläche mit Beschriftung

---

# 6. Erlaubte und unerlaubte Platzierungen

Der Editor darf keine freien Platzierungen erlauben, wenn sie die Strukturregeln verletzen.

## Beispiele erlaubter Platzierungen

- Textblock in Container
- Hero-Komponente in Section
- Snippet in Container

## Beispiele unerlaubter Platzierungen

- Container direkt in Textblock
- Grid außerhalb eines erlaubten Layoutbereichs
- Snippet als Root-Element, falls nicht erlaubt

## Verhalten bei ungültiger Platzierung

Wenn eine Platzierung ungültig ist:

- wird keine Drop-Zone angezeigt
- das Element lässt sich nicht dort ablegen
- optional kann ein kurzes visuelles Feedback erscheinen

---

# 7. Einfügemodi

Der Editor sollte unterschiedliche Einfügearten unterstützen.

## Einfügen als Kind

Das Element wird innerhalb eines Containers oder Layout-Blocks abgelegt.

## Einfügen vor oder nach einem Element

Das Element wird direkt vor oder nach einem bestehenden Geschwisterelement eingefügt.

## Automatisches Wrapping

Optional kann der Editor in bestimmten Fällen automatisch benötigte Strukturcontainer einfügen.

Beispiel:

- ein Block wird in eine Section gezogen
- falls notwendig, erzeugt der Editor automatisch einen Container

Dieses Verhalten muss aber streng regelbasiert und transparent bleiben.

---

# 8. Verschieben bestehender Instanzen

Bestehende Elemente auf der Canvas können innerhalb des Instanzbaums verschoben werden.

## Mögliche Aktionen

- innerhalb desselben Elternteils umsortieren
- in einen anderen erlaubten Container verschieben
- zwischen Layoutbereichen verschieben

## Einschränkungen

- nur erlaubte Zielbereiche
- keine Zyklen oder ungültigen Eltern-Kind-Beziehungen
- keine Verletzung definierter Kindlimits

---

# 9. Duplizieren

Jede Instanz kann dupliziert werden.

## Verhalten

- neue eindeutige Instanz-ID erzeugen
- Eigenschaften und Inhalte kopieren
- neue Instanz neben oder unter dem Original einfügen

Duplizieren betrifft immer nur die lokale Instanz.

---

# 10. Löschen

Instanzen können aus dem Editor-Baum entfernt werden.

## Verhalten

- das Element wird aus der Canvas entfernt
- Kind-Elemente werden mit entfernt, sofern keine spezielle Auflösung definiert ist
- Aktion ist rückgängig machbar

Später kann zusätzlich eine editorinterne Wiederherstellung oder Anbindung an den globalen Papierkorb ergänzt werden.

---

# 11. Kontextmenüs

Jedes ausgewählte Element sollte ein Kontextmenü besitzen.

## Typische Aktionen

- bearbeiten
- duplizieren
- löschen
- nach oben verschieben
- nach unten verschieben
- als Komponente speichern
- auf Vorlage zurücksetzen

Das Kontextmenü soll nur sinnvolle Aktionen für den jeweiligen Elementtyp anbieten.

---

# 12. Inline-Aktionsleiste

Zusätzlich zum Kontextmenü kann direkt am ausgewählten Element eine kleine Aktionsleiste angezeigt werden.

Mögliche Schnellaktionen:

- bearbeiten
- duplizieren
- löschen
- verschieben

Dies verbessert die Geschwindigkeit bei häufigen Arbeitsabläufen.

---

# 13. Breadcrumbs und Strukturpfad

Bei ausgewählten oder tief verschachtelten Elementen sollte der Strukturpfad sichtbar sein.

Beispiel:

```
Page > Section > Container > Hero
```

Vorteile:

- bessere Orientierung
- schnelleres Wechseln zu übergeordneten Elementen
- verständlichere Bearbeitung komplexer Seiten

---

# 14. Undo und Redo

Der Editor muss Undo und Redo unterstützen.

## Rückgängig machbare Aktionen

- hinzufügen
- verschieben
- löschen
- duplizieren
- Eigenschaftsänderungen

## Anforderungen

- nachvollziehbare Änderungshistorie pro Editor-Sitzung
- stabile Wiederherstellung des Editor-Baums
- keine Inkonsistenzen bei komplexen Operationen

---

# 15. Multi-Select

Multi-Select ist optional und kein Muss für das MVP.

Falls umgesetzt, sollte Multi-Select nur dort erlaubt sein, wo das Verhalten eindeutig bleibt.

Mögliche Szenarien:

- mehrere Geschwisterelemente markieren
- mehrere Blöcke gemeinsam verschieben
- mehrere Elemente gemeinsam löschen

Für den Start ist Single-Selection die stabilere Standardlösung.

---

# 16. Tastatursteuerung und Shortcuts

Der Editor sollte grundlegende Tastenkürzel unterstützen.

Empfohlene Shortcuts:

- `Ctrl/Cmd + S` → speichern
- `Ctrl/Cmd + Z` → undo
- `Ctrl/Cmd + Shift + Z` oder `Ctrl/Cmd + Y` → redo
- `Entf` → ausgewähltes Element löschen
- `Esc` → Auswahl aufheben oder Modus verlassen

Shortcuts dürfen keine normalen Browserfunktionen auf problematische Weise brechen.

---

# 17. Automatisches Öffnen des Inspectors

Beim direkten Klick auf ein Element in der Canvas öffnet sich automatisch der Inspector auf der rechten Seite.

Dieses Verhalten ist verbindlich, damit die Bearbeitung unmittelbar verständlich bleibt.

Falls die rechte Sidebar eingeklappt ist, wird sie geöffnet.

---

# 18. Interaktionen mit eingeklappten Sidebars

Da beide Sidebars einklappbar sind, muss das Verhalten klar definiert sein.

## Linke Sidebar

- kann für mehr Canvas-Fläche eingeklappt werden
- beim Öffnen bleibt der zuletzt genutzte Katalogbereich erhalten

## Rechte Sidebar

- öffnet sich automatisch bei Elementauswahl
- kann manuell wieder eingeklappt werden

---

# 19. Leere Zustände

Leere Bereiche der Canvas sollten klar dargestellt werden.

Beispiele:

- "Ziehe hier ein Element hinein"
- "Noch keine Inhalte vorhanden"

Leere Zustände sollen helfen, den nächsten möglichen Schritt verständlich zu machen.

---

# 20. Fehlertoleranz und Schutz vor Frust

Der Editor soll möglichst fehlertolerant sein.

Dazu gehören:

- kein versehentliches zerstörerisches Verhalten ohne Undo
- keine stillen Strukturfehler
- nachvollziehbare visuelle Hinweise bei ungültigen Aktionen
- klare Zustände bei Auswahl, Dragging und Drop

---

# Fazit

Die Interaktions- und Drag-&-Drop-Logik des Chamy-Inhaltseditors muss visuell angenehm, aber streng regelbasiert sein.

Der Editor soll sich frei und modern anfühlen, ohne dass Strukturregeln oder Inhaltsintegrität verloren gehen.

Damit wird er für Redakteure verständlich und für Chamy technisch dauerhaft wartbar.

