# Chamy Inhaltseditor – Katalog- und Registry-System

## Überblick
Der Editor stellt in der linken Sidebar einen Katalog aller verfügbaren Elemente bereit. Dieser Katalog ist die zentrale Quelle für alle Bausteine, die per Drag & Drop in den Editor eingefügt werden können.

Der Katalog basiert vollständig auf dem **Definitions- und Registry-System**. Er enthält keine hart kodierten Elemente.

Alle Elemente werden zur Laufzeit aus registrierten Definitionen erzeugt.

---

# 1. Ziele des Katalogsystems

Das Katalogsystem verfolgt mehrere Ziele:

- klare Organisation aller verfügbaren Bausteine
- schnelle Auffindbarkeit für Redakteure
- dynamische Erweiterbarkeit
- automatische Integration von Modul- und Theme-Elementen

---

# 2. Katalogbereiche

Der Editor besitzt mehrere Hauptbereiche im Katalog.

## Layout

Strukturelemente für den Seitenaufbau.

Beispiele:

- Section
- Container
- Grid
- Columns

Diese Elemente definieren die Struktur der Seite.

---

## Blöcke

Einfache Inhaltsbausteine.

Beispiele:

- Text
- Bild
- Video
- Galerie
- Button

Diese Elemente enthalten meist direkte redaktionelle Inhalte.

---

## Komponenten

Komplexe wiederverwendbare Bausteine.

Beispiele:

- Hero
- Slider
- Feature Grid
- FAQ
- CTA

Komponenten können aus verschiedenen Quellen stammen:

- Core
- Theme
- Modul
- Benutzer

---

## Snippets

Kleine wiederverwendbare Inhaltsstücke.

Beispiele:

- Infobox
- Hinweistext
- Kontaktblock

Snippets sind meist kompakter als Komponenten.

---

# 3. Kategorien innerhalb der Kataloge

Innerhalb jedes Katalogbereichs können Kategorien existieren.

Beispiele:

- Text
- Media
- Layout
- Navigation
- Marketing

Diese Kategorien stammen aus den jeweiligen Definitionen.

---

# 4. Darstellung der Elemente

Jedes Element im Katalog wird visuell dargestellt.

Empfohlene Informationen:

- Icon
- Name
- kurze Beschreibung

Optional:

- Vorschaubild
- Kennzeichnung der Quelle (Core, Theme, Modul)

---

# 5. Drag-Quelle

Alle Elemente im Katalog fungieren als Drag-Quelle.

Beim Draggen eines Elements erzeugt der Editor:

- eine neue Instanz
- basierend auf der jeweiligen Definition
- inklusive Standardwerten

---

# 6. Registry-System

Alle verfügbaren Definitionen werden beim Start des Editors registriert.

Die Registry sammelt:

- Layout-Definitionen
- Block-Definitionen
- Komponenten-Definitionen
- Snippet-Definitionen

Die Registry dient als zentrale Datenquelle für:

- den Katalog
- das Drag & Drop System
- die Inspector-Felder

---

# 7. Registrierung von Definitionen

Definitionen können aus mehreren Quellen stammen.

Mögliche Quellen:

- Core
- Theme
- Modul
- Benutzerdefinierte Erweiterungen

Beim Laden des Editors werden diese Quellen zusammengeführt.

---

# 8. Favoriten

Redakteure können häufig verwendete Elemente als Favoriten markieren.

Favoriten erscheinen in einem eigenen Bereich im Katalog.

Dies beschleunigt typische Arbeitsabläufe.

---

# 9. Suche

Der Katalog besitzt eine Suchfunktion.

Suchkriterien können sein:

- Name
- Beschreibung
- Kategorie

Die Suche hilft bei großen Katalogen mit vielen Komponenten.

---

# 10. Zuletzt verwendete Elemente

Der Editor kann eine Liste zuletzt verwendeter Elemente anzeigen.

Diese Liste kann automatisch aus der Editor-Historie generiert werden.

---

# 11. Erweiterbarkeit

Module und Themes können neue Elemente registrieren.

Diese erscheinen automatisch im passenden Katalogbereich.

Beispiele:

- neues Layoutsystem
- spezielle Marketing-Komponenten
- zusätzliche Content-Blöcke

---

# 12. Sichtbarkeitsregeln

Definitionen können festlegen, ob sie im Katalog sichtbar sind.

Beispiele:

- nur für bestimmte Content Types
- nur für bestimmte Benutzerrollen
- nur im Adminbereich

---

# Fazit

Das Katalog- und Registry-System stellt sicher, dass der Editor alle verfügbaren Bausteine dynamisch darstellen kann.

Durch die zentrale Registry bleiben Erweiterungen durch Module und Themes vollständig kompatibel mit dem Editor.

