# Chamy Inhaltseditor – Content References und verknüpfte Inhalte

## Überblick
Neben globalen Komponenten gibt es einen zweiten wichtigen Mechanismus zur Wiederverwendung von Informationen: **Content References**.

Während globale Komponenten wiederverwendbare **Bausteine** darstellen, ermöglichen Content References die Wiederverwendung von **Daten oder Inhalten** aus anderen Content-Einträgen.

Typische Beispiele:

- Autorenprofil auf mehreren Artikeln
- Produktdaten auf verschiedenen Landingpages
- Eventinformationen auf mehreren Seiten
- FAQ-Daten in unterschiedlichen Bereichen

Content References sorgen dafür, dass Inhalte zentral gepflegt werden können und automatisch überall aktualisiert werden.

---

# 1. Problemstellung

Ohne Referenzsystem werden Inhalte häufig kopiert.

Typische Folgen:

- Daten werden mehrfach gepflegt
- Informationen werden inkonsistent
- Änderungen müssen an vielen Stellen erfolgen

Content References lösen dieses Problem durch **verknüpfte Inhalte**.

---

# 2. Grundprinzip

Ein Editor-Element kann statt eigener Inhalte eine Referenz auf einen anderen Content-Eintrag enthalten.

Beispiel:

```
Article Page
 ├ Hero
 ├ Text
 └ Author Reference
      → verweist auf Content: Author_Profile_23
```

Die eigentlichen Daten werden aus dem referenzierten Inhalt geladen.

---

# 3. Referenztypen

Der Editor kann mehrere Arten von Referenzen unterstützen.

## Einfache Referenz

Verweist auf einen einzelnen Content-Eintrag.

Beispiel:

- Autorprofil
- Produkt

## Mehrfachreferenz

Verweist auf mehrere Einträge.

Beispiel:

- Liste verwandter Artikel
- Liste von FAQs

## Dynamische Referenz

Verweist auf Inhalte basierend auf Kriterien.

Beispiel:

- neueste Artikel
- Events in einer Kategorie

---

# 4. Darstellung im Editor

Referenzen sollten im Editor klar erkennbar sein.

Mögliche Hinweise:

- Link-Icon
- Hinweis "verknüpfter Inhalt"
- Anzeige des referenzierten Content-Titels

---

# 5. Bearbeitung referenzierter Inhalte

Wenn ein Redakteur einen referenzierten Inhalt bearbeiten möchte, sollte der Editor eine direkte Navigation ermöglichen.

Beispiel:

```
Author Reference
→ "Autorprofil bearbeiten"
→ öffnet entsprechenden Inhalt
```

---

# 6. Lokale Darstellung

Im Editor kann eine Vorschau der referenzierten Inhalte angezeigt werden.

Beispiel:

- Autorenname
- Profilbild
- Kurzbeschreibung

Die tatsächlichen Daten stammen jedoch aus dem referenzierten Content-Eintrag.

---

# 7. Aktualisierung

Wenn sich der referenzierte Inhalt ändert, erscheinen die Änderungen automatisch überall dort, wo die Referenz verwendet wird.

Dadurch bleiben Inhalte konsistent.

---

# 8. Auflösen einer Referenz

In bestimmten Fällen kann eine Referenz in eine lokale Kopie umgewandelt werden.

Beispiel:

```
Content Reference
↓
"Referenz auflösen"
↓
lokaler Inhalt
```

Danach besteht keine Verbindung mehr zum ursprünglichen Inhalt.

---

# 9. Kombination mit Komponenten

Content References können innerhalb von Komponenten verwendet werden.

Beispiel:

- Team-Komponente lädt Mitarbeiterdaten
- Produkt-Komponente lädt Produktinformationen

Dadurch können Komponenten dynamisch Inhalte anzeigen.

---

# 10. Berechtigungen

Nicht jeder Nutzer darf referenzierte Inhalte bearbeiten.

Typische Regeln:

- Redakteure dürfen Referenzen verwenden
- Administratoren dürfen referenzierte Inhalte ändern

---

# 11. Integration mit Content Types

Content References basieren auf dem bestehenden Content-Type-System.

Beispiel:

```
Content Type: Author
Content Type: Product
Content Type: Event
```

Referenzen können gezielt auf bestimmte Content Types beschränkt werden.

---

# 12. Performance

Bei vielen Referenzen sollte der Editor Daten effizient laden.

Strategien:

- Batch-Laden referenzierter Inhalte
- Caching häufiger Referenzen

---

# Fazit

Content References ermöglichen eine strukturierte Wiederverwendung von Inhalten innerhalb des Editors.

Sie sorgen für konsistente Datenpflege, reduzieren Redaktionsaufwand und ermöglichen komplexe Inhaltsstrukturen innerhalb von Chamy.

