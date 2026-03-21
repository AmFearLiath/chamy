# Chamy Inhaltseditor – Globale Komponenten und geteilte Instanzen

## Überblick
In vielen Projekten existieren Inhalte, die auf mehreren Seiten gleichzeitig verwendet werden. Beispiele sind Banner, Call‑to‑Action‑Blöcke, Hinweise oder Promotions.

Damit Änderungen nicht auf jeder Seite einzeln vorgenommen werden müssen, unterstützt der Chamy-Inhaltseditor **globale Komponenten und geteilte Instanzen**.

Diese ermöglichen es, Inhalte zentral zu definieren und automatisch auf allen Seiten zu aktualisieren, auf denen sie verwendet werden.

---

# 1. Problemstellung

Ohne globale Komponenten entstehen typische Probleme:

- derselbe Inhalt wird auf vielen Seiten manuell gepflegt
- Änderungen müssen mehrfach durchgeführt werden
- Inhalte werden inkonsistent
- Redaktionsaufwand steigt stark

Ein globales Komponenten-System löst dieses Problem.

---

# 2. Grundprinzip

Globale Komponenten werden einmal definiert und können anschließend auf beliebigen Seiten eingebunden werden.

Beispiel:

```
Global Component: Newsletter CTA

Seite A → verwendet Newsletter CTA
Seite B → verwendet Newsletter CTA
Seite C → verwendet Newsletter CTA
```

Wenn die globale Komponente geändert wird, erscheinen die Änderungen automatisch auf allen Seiten.

---

# 3. Unterschied zu normalen Komponenten

Normale Komponenten:

- werden beim Einfügen zu lokalen Instanzen
- Änderungen betreffen nur diese eine Seite

Globale Komponenten:

- behalten eine Verbindung zur globalen Quelle
- Änderungen wirken sich auf alle Instanzen aus

---

# 4. Shared Instances

Wenn eine globale Komponente eingefügt wird, entsteht eine **geteilte Instanz**.

Diese Instanz enthält eine Referenz auf die globale Komponente.

Beispiel:

```json
{
  "type": "component",
  "definition": "newsletter_cta",
  "global": true,
  "referenceId": "global_component_42"
}
```

---

# 5. Bearbeitung globaler Komponenten

Globale Komponenten können zentral bearbeitet werden.

Mögliche Orte:

- globaler Komponentenmanager
- spezielle Editoransicht

Änderungen wirken sich sofort auf alle Referenzen aus.

---

# 6. Lokales Überschreiben

In bestimmten Fällen kann eine globale Komponente lokal überschrieben werden.

Beispiel:

- globaler CTA
- auf einer Seite wird ein anderer Buttontext benötigt

Der Editor kann lokale Overrides erlauben.

---

# 7. Verbindung lösen

Eine geteilte Instanz kann in eine lokale Instanz umgewandelt werden.

Beispiel:

```
Global Component Instance
↓
"Verbindung lösen"
↓
Lokale Komponente
```

Nach dem Lösen der Verbindung reagiert die Instanz nicht mehr auf globale Änderungen.

---

# 8. Visuelle Darstellung im Editor

Globale Komponenten sollten im Editor klar erkennbar sein.

Mögliche Hinweise:

- Globus- oder Link-Symbol
- Label "Global"
- Hinweis auf zentrale Verwaltung

---

# 9. Aktualisierungsstrategie

Wenn eine globale Komponente geändert wird:

- werden alle Referenzen automatisch aktualisiert
- Seiten müssen nicht manuell angepasst werden

Optional kann ein Cache-Invalidierungsmechanismus eingesetzt werden.

---

# 10. Verwendung im Layout

Globale Komponenten sind besonders nützlich für:

- Promotions
- Banner
- Newsletter-Aufrufe
- wiederkehrende Informationsblöcke

Sie reduzieren Redaktionsaufwand erheblich.

---

# 11. Berechtigungen

Nicht jeder Nutzer sollte globale Komponenten bearbeiten dürfen.

Typische Regeln:

- Redakteure dürfen globale Komponenten verwenden
- Administratoren dürfen sie bearbeiten

---

# 12. Integration mit Modulen

Module können ebenfalls globale Komponenten bereitstellen.

Beispiele:

- Marketingmodule
- Eventmodule
- E-Commerce-Widgets

Diese Komponenten können systemweit genutzt werden.

---

# 13. Versionierung

Globale Komponenten sollten versioniert werden.

Dadurch können Änderungen nachvollzogen und bei Bedarf rückgängig gemacht werden.

---

# Fazit

Globale Komponenten und geteilte Instanzen ermöglichen eine zentrale Verwaltung wiederkehrender Inhalte.

Sie reduzieren Redaktionsaufwand, verbessern Konsistenz und sind ein wichtiger Bestandteil moderner Inhaltseditoren.

