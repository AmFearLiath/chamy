# Chamy Inhaltseditor – Erweiterbarkeit durch Module und Themes

## Überblick
Der Inhaltseditor ist vollständig modular aufgebaut und kann durch **Core, Themes, Module und Benutzerdefinitionen** erweitert werden.

Neue Bausteine können hinzugefügt werden, ohne den Editor-Code selbst zu verändern.

Diese Erweiterungen nutzen das bestehende:

- Schema-System
- Registry-System
- Katalogsystem

Damit bleiben Erweiterungen kompatibel mit der Chamy-Architektur.

---

# 1. Erweiterungsquellen

Editor-Bausteine können aus mehreren Quellen stammen:

- Core
- Theme
- Modul
- Benutzerdefiniert

Diese Quelle wird in jeder Definition gespeichert.

Beispiel:

```
source: core
source: theme
source: module
source: user
```

---

# 2. Erweiterungen durch Module

Module können neue Editor-Elemente registrieren.

Typische Beispiele:

- neue Komponenten
- neue Blocktypen
- neue Layoutsysteme
- spezielle Inhaltsblöcke

Diese Erweiterungen erscheinen automatisch im Editor-Katalog.

---

# 3. Erweiterungen durch Themes

Themes können ebenfalls neue Editor-Elemente bereitstellen.

Beispiele:

- theme-spezifische Komponenten
- spezielle Layoutblöcke
- designorientierte Inhaltsmodule

Diese Elemente erscheinen ebenfalls im Katalog.

---

# 4. Theme-Regeln

Damit das Erscheinungsbild konsistent bleibt, gelten folgende Regeln:

- Module liefern keine eigenen Frontend-Styles
- Darstellung erfolgt immer über das aktive Theme
- Themes definieren die tatsächliche Darstellung der Komponenten

Dadurch bleibt das Erscheinungsbild der Seite konsistent.

---

# 5. Registrierung von Erweiterungen

Neue Definitionen werden über das Registry-System registriert.

Beim Start des Editors werden alle Definitionen aus folgenden Quellen geladen:

- Core
- aktive Themes
- aktive Module

Die Registry führt diese Definitionen zusammen.

---

# 6. Sichtbarkeitsregeln

Definitionen können festlegen, wann sie sichtbar sind.

Mögliche Regeln:

- nur für bestimmte Content Types
- nur für bestimmte Benutzerrollen
- nur im Adminbereich

Diese Regeln verhindern unpassende Elemente im Editor.

---

# 7. Modul-Kompatibilität

Module müssen sich an die Editor-Regeln halten.

Wichtige Punkte:

- Nutzung des Schema-Systems
- keine direkte Manipulation des Editor-States
- keine Umgehung der Core-Manager

Dies schützt die Stabilität des Editors.

---

# 8. Erweiterung des Inspectors

Module können zusätzliche Inspector-Felder definieren.

Beispiele:

- spezielle Einstellungen
- zusätzliche Inhaltsfelder
- Konfigurationsoptionen

Diese Felder werden automatisch generiert.

---

# 9. Marketplace-Integration

Da Chamy einen Marketplace besitzt, können dort angebotene Module ebenfalls neue Editor-Bausteine liefern.

Beispiele:

- neue Marketing-Komponenten
- spezielle Layoutsysteme
- branchenspezifische Inhaltsmodule

Nach Installation erscheinen diese automatisch im Editor-Katalog.

---

# 10. Versionskompatibilität

Komponenten und Blöcke können versioniert werden.

Beispiel:

```
hero@1
hero@2
```

Dadurch bleiben bestehende Inhalte stabil, selbst wenn neue Versionen veröffentlicht werden.

---

# 11. Konfliktvermeidung

Bei mehreren Erweiterungen mit ähnlichen Elementen gelten folgende Prinzipien:

- eindeutige IDs
- klare Quellenangaben
- Prioritätsregeln zwischen Core, Theme und Modul

---

# 12. Benutzerdefinierte Komponenten

Redakteure können eigene Komponenten erstellen.

Beispiel:

- eine bearbeitete Instanz wird als neue Komponente gespeichert
- diese erscheint anschließend im Komponenten-Katalog

Solche Komponenten erhalten als Quelle `user`.

---

# Fazit

Die Erweiterbarkeit des Inhaltseditors stellt sicher, dass neue Funktionen, Komponenten und Layoutsysteme durch Themes und Module integriert werden können.

Dadurch bleibt der Editor flexibel, modular und vollständig kompatibel mit der modularen Architektur von Chamy.

