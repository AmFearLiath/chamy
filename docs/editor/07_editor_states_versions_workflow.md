# Chamy Inhaltseditor – States, Versionen und Workflow

## Überblick
Der Inhaltseditor selbst implementiert kein eigenes Workflow-System. Stattdessen nutzt er vollständig die bestehenden Core-Systeme von Chamy:

- StateManager
- VersionManager
- ContentManager

Der Editor ist lediglich die visuelle Oberfläche, über die Redakteure Inhalte bearbeiten und Änderungen speichern.

Alle Statusänderungen und Versionierungen bleiben Aufgabe des Core-Systems.

---

# 1. Statusmodell

Chamy verwendet ein festes Statusmodell für Inhalte.

Empfohlene Standardzustände:

- draft
- review
- published
- archived
- deleted

Diese States existieren unabhängig vom Editor und gelten für alle Content Types.

---

# 2. Statusanzeige im Editor

Der aktuelle Status eines Inhalts wird im Editor-Header angezeigt.

Beispiel:

```
Seite bearbeiten
Status: Draft
```

Der Status dient als Orientierung für Redakteure.

---

# 3. Statuswechsel

Der Editor kann Statuswechsel auslösen, führt diese aber nicht selbst aus.

Typische Aktionen:

- Entwurf speichern
- zur Prüfung einreichen
- veröffentlichen
- archivieren

Die eigentliche Logik bleibt beim StateManager.

---

# 4. Versionssystem

Jede gespeicherte Änderung erzeugt eine neue Version.

Versionen werden im bestehenden VersionManager gespeichert.

Versionen enthalten:

- gespeicherte Inhaltsstruktur
- Metadaten
- Änderungszeitpunkt
- Benutzer

---

# 5. Versionshistorie

Der Editor sollte eine Versionsübersicht anzeigen können.

Diese kann über eine eigene Ansicht oder ein Modal geöffnet werden.

Typische Informationen:

- Version-ID
- Änderungsdatum
- Autor
- Kommentar

---

# 6. Versionsvergleich

Optional kann ein Versionsvergleich angezeigt werden.

Mögliche Darstellung:

- Unterschiede im Text
- Unterschiede im Blockbaum
- geänderte Eigenschaften

Dieser Vergleich ist besonders bei komplexen Seiten hilfreich.

---

# 7. Wiederherstellung einer Version

Eine frühere Version kann wiederhergestellt werden.

Der Editor lädt dabei den gespeicherten Editor-Baum der gewählten Version.

Nach der Wiederherstellung entsteht eine neue aktuelle Version.

---

# 8. Autosave

Der Editor kann optional eine Autosave-Funktion besitzen.

Mögliche Varianten:

- periodisches Autosave
- Autosave nach Änderungen

Autosave speichert temporäre Entwürfe, ohne den offiziellen Status zu ändern.

---

# 9. Manuelles Speichern

Der Speichern-Button im Editor führt folgende Schritte aus:

1. aktuellen Editor-State serialisieren
2. Daten an ContentManager übergeben
3. Version erstellen
4. Änderungen speichern

Der Editor speichert keine Daten direkt in der Datenbank.

---

# 10. Bearbeitungssperren (Locking)

Um Konflikte zu vermeiden, kann ein Bearbeitungs-Lock verwendet werden.

Beim Öffnen eines Inhalts:

- wird ein Bearbeitungs-Lock gesetzt
- andere Nutzer sehen, dass der Inhalt aktuell bearbeitet wird

Mögliche Strategien:

- Soft Lock (Hinweis)
- Hard Lock (Bearbeitung blockiert)

---

# 11. Gleichzeitige Bearbeitung

Falls mehrere Benutzer gleichzeitig bearbeiten, muss das System Konflikte vermeiden.

Mögliche Ansätze:

- Warnung bei paralleler Bearbeitung
- Versionskonfliktprüfung beim Speichern
- automatische Merge-Versuche

Für das erste System reicht meist eine einfache Bearbeitungssperre.

---

# 12. Veröffentlichungsplanung

Der Editor kann eine geplante Veröffentlichung unterstützen.

Beispiele:

- Veröffentlichung zu bestimmtem Zeitpunkt
- automatische Archivierung

Diese Funktionen gehören ebenfalls zum Workflow-System des Core.

---

# 13. Änderungsnotizen

Beim Speichern kann optional eine Änderungsnotiz hinterlegt werden.

Beispiel:

"Hero-Bereich aktualisiert"

Diese Notizen erscheinen später in der Versionshistorie.

---

# Fazit

Der Inhaltseditor nutzt vollständig die bestehenden Workflow- und Versionssysteme von Chamy.

Er stellt lediglich die visuelle Oberfläche bereit, über die Redakteure Inhalte bearbeiten und Änderungen speichern können.

Dadurch bleibt die Architektur klar getrennt und konsistent mit dem restlichen System.

