# Chamy Inhaltseditor – API und Backend-Integration

## Überblick
Der Inhaltseditor ist eine Admin-Oberfläche, die mit dem bestehenden Chamy-Core kommuniziert. Der Editor greift **nicht direkt auf die Datenbank** zu, sondern verwendet die vorhandenen Core-Manager und Controller-Strukturen.

Beteiligte Core-Komponenten:

- ContentManager
- ContentTypeManager
- VersionManager
- StateManager
- LayoutManager
- ComponentManager

Der Editor fungiert damit als Client innerhalb des Adminbereichs.

---

# 1. Ziele der Backend-Integration

Die Integration zwischen Editor und Core verfolgt folgende Ziele:

- klare Trennung zwischen UI und Businesslogik
- Wiederverwendung bestehender Core-Systeme
- sichere Datenvalidierung
- konsistente Versionierung
- stabile API-Struktur

---

# 2. Laden eines Inhalts

Beim Öffnen eines Inhalts im Editor werden mehrere Datenquellen geladen.

## Ablauf

1. Anfrage an den ContentController
2. Laden des Content-Eintrags
3. Laden des Content-Type-Schemas
4. Laden vorhandener Editor-Daten
5. Laden aller registrierten Definitionen

## Ergebnis

Der Editor erhält:

- Content-Metadaten
- Editor-Struktur
- Content-Type-Felder
- registrierte Block- und Komponenten-Definitionen

---

# 3. Editor-Datenstruktur beim Laden

Beispielhafte Antwortstruktur:

```json
{
  "content": {},
  "contentType": {},
  "editor": {},
  "definitions": {}
}
```

Diese Daten bilden die Grundlage für den initialen Editor-State.

---

# 4. Speichern eines Inhalts

Beim Speichern übergibt der Editor den serialisierten Editor-Baum an das Backend.

## Ablauf

1. Serialisierung des Editor-States
2. Übermittlung an ContentController
3. Validierung der Daten
4. Speicherung über ContentManager
5. Erstellung einer Version über VersionManager

---

# 5. Validierung

Bevor Daten gespeichert werden, erfolgt eine Validierung.

Validierungsquellen:

- Content-Type-Regeln
- Schema-Definitionen
- Pflichtfelder
- Strukturregeln

Fehler werden an den Editor zurückgegeben.

---

# 6. Statuswechsel

Statuswechsel erfolgen über den StateManager.

Typische Aktionen:

- Entwurf speichern
- Veröffentlichung
- Archivierung

Der Editor sendet lediglich die gewünschte Aktion.

---

# 7. Versionierung

Nach erfolgreichem Speichern wird automatisch eine neue Version erstellt.

Der VersionManager speichert:

- Editor-Struktur
- Metadaten
- Änderungszeitpunkt
- Benutzer

---

# 8. Vorschau

Der Editor kann eine Vorschau anfordern.

## Ablauf

1. Editor sendet aktuelle Struktur
2. Backend rendert Inhalt über Theme-System
3. Ergebnis wird als Vorschau angezeigt

Dies ermöglicht eine realistische Darstellung ohne Veröffentlichung.

---

# 9. Fehlerbehandlung

Das Backend muss strukturierte Fehlermeldungen liefern.

Beispiel:

```json
{
  "error": "validation_error",
  "fields": {
    "title": "Titel ist erforderlich"
  }
}
```

Der Editor kann diese Informationen direkt im Inspector darstellen.

---

# 10. Sicherheit

Alle Editoraktionen müssen serverseitig überprüft werden.

Wichtige Punkte:

- Benutzerrechte
- erlaubte Content-Typen
- erlaubte Statuswechsel

---

# 11. Performance

Das Backend sollte möglichst effiziente Antworten liefern.

Empfehlungen:

- Definitionen nur einmal laden
- kompakte Datenstrukturen
- klare API-Antworten

---

# Fazit

Die API- und Backend-Integration verbindet den visuellen Editor mit den bestehenden Chamy-Core-Systemen. Dadurch bleibt der Editor vollständig kompatibel mit der bestehenden Architektur und nutzt alle vorhandenen Manager für Speicherung, Versionierung und Workflow.

