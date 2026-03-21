# Chamy – Content States, Versionen und Workflow-System

## Zweck dieses Dokuments

Dieses Dokument beschreibt das **State-, Versions- und Workflow-System** von Chamy.

Es definiert, wie Inhalte Zustände besitzen, wie Änderungen versioniert werden, wie Veröffentlichungen gesteuert werden und wie mehrere Benutzer kontrolliert an denselben Inhalten arbeiten können.

Während Datei 08 das **Content-Type-System** beschreibt (also die Struktur von Inhalten), behandelt dieses Dokument den **Lebenszyklus von Inhalten**.

Das betrifft insbesondere:

- Zustände von Inhalten
- Entwürfe und Veröffentlichungen
- Versionshistorie
- geplante Veröffentlichungen
- Freigabeprozesse
- Sperren von Inhalten während Bearbeitung
- Zusammenarbeit mehrerer Benutzer

Ohne ein solches System entstehen in CMS sehr schnell Probleme wie:

- Inhalte werden versehentlich veröffentlicht
- Änderungen überschreiben sich gegenseitig
- alte Versionen gehen verloren
- Redaktionen können nicht zusammenarbeiten
- Veröffentlichungen müssen manuell koordiniert werden

Dieses Dokument definiert daher die Architektur für **Inhaltszustände und Versionsverwaltung in Chamy**.

Verwandte Dokumente:

- 01_chamy_overview.md
- 02_system_architecture.md
- 03_module_system.md
- 04_theme_system.md
- 05_layout_component_content_system.md
- 06_marketplace_security_and_rules.md
- 07_api_system.md
- 08_content_types.md

---

# 1 Grundprinzip

Jeder Inhaltseintrag in Chamy besitzt einen **Zustand (State)**.

Der Zustand beschreibt, in welchem Abschnitt seines Lebenszyklus sich ein Inhalt befindet.

Zusätzlich besitzt jeder Inhalt eine **Versionshistorie**, damit Änderungen nachvollziehbar bleiben und ältere Zustände wiederhergestellt werden können.

Damit entstehen drei zentrale Ebenen:

- Content Entry → der aktuelle Inhalt
- Content State → der aktuelle Status des Inhalts
- Content Version → eine gespeicherte Revision des Inhalts

---

# 2 Ziele des Systems

Das State- und Versionssystem erfüllt mehrere Aufgaben.

## Kontrollierte Veröffentlichung

Inhalte sollen nicht sofort öffentlich sichtbar sein, sondern bewusst veröffentlicht werden.

## Redaktionelle Zusammenarbeit

Mehrere Benutzer sollen Inhalte erstellen, prüfen und freigeben können.

## Nachvollziehbarkeit

Alle Änderungen sollen nachvollziehbar und bei Bedarf rückgängig machbar sein.

## Stabilität

Fehlerhafte Änderungen dürfen nicht dauerhaft Inhalte zerstören.

## Automatisierung

Inhalte sollen zeitgesteuert veröffentlicht oder archiviert werden können.

---

# 3 Standard-Content-States

Chamy definiert eine Reihe von Standardzuständen.

Diese können erweitert werden, sollten jedoch systemweit konsistent bleiben.

## Draft

Der Inhalt befindet sich im Entwurf.

Eigenschaften:

- nicht öffentlich sichtbar
- kann beliebig bearbeitet werden
- nur für berechtigte Benutzer sichtbar

## Review

Der Inhalt wartet auf Prüfung oder Freigabe.

Eigenschaften:

- Bearbeitung eingeschränkt
- Reviewer können Kommentare hinterlassen
- Veröffentlichung noch nicht aktiv

## Published

Der Inhalt ist veröffentlicht.

Eigenschaften:

- öffentlich sichtbar
- Änderungen erzeugen neue Versionen
- bestehende Version bleibt stabil

## Archived

Der Inhalt ist archiviert.

Eigenschaften:

- nicht mehr öffentlich sichtbar
- bleibt im System erhalten
- kann reaktiviert werden

## Deleted

Der Inhalt wurde gelöscht.

Eigenschaften:

- logisch gelöscht
- optional wiederherstellbar
- endgültige Löschung erfolgt später

---

# 4 StateManager

Für das Zustandsmanagement wird ein eigener **StateManager** eingeführt.

Der StateManager verwaltet:

- mögliche Zustände
- erlaubte Übergänge
- Statusregeln
- Workflow-Bedingungen

## Aufgaben des StateManager

- Registrierung von Zuständen
- Definition erlaubter Zustandswechsel
- Validierung von Statusänderungen
- Berechtigungsprüfung
- Integration mit Workflow-System

---

# 5 Zustandsübergänge

Nicht jeder Zustand darf direkt in jeden anderen wechseln.

Der StateManager definiert erlaubte Übergänge.

Beispiel:

Draft → Review
Review → Published
Published → Archived
Draft → Deleted

Nicht erlaubt:

Archived → Published (ohne Reaktivierung)
Deleted → Published

Diese Regeln verhindern unlogische Zustandswechsel.

---

# 6 Versionierung

Jede Änderung an einem Inhalt erzeugt eine neue **Version**.

Eine Version speichert:

- vollständige Felddaten
- Zeitpunkt der Änderung
- Benutzer
- Änderungsbeschreibung
- Versionsnummer

Damit kann das System jederzeit:

- alte Versionen anzeigen
- Änderungen vergleichen
- Versionen wiederherstellen

---

# 7 VersionManager

Die Versionsverwaltung wird durch den **VersionManager** gesteuert.

Aufgaben:

- Erstellung neuer Versionen
- Speicherung historischer Daten
- Wiederherstellung alter Versionen
- Versionsvergleich
- Versionsbereinigung

---

# 8 Versionsstrategie

Es gibt zwei typische Strategien für Versionierung.

Chamy kann eine kombinierte Strategie nutzen.

## Vollständige Snapshot-Versionen

Jede Version speichert den vollständigen Datensatz.

Vorteile:

- einfache Wiederherstellung
- klare Datenstruktur

Nachteil:

- höherer Speicherbedarf

## Delta-Versionen

Nur Änderungen werden gespeichert.

Vorteile:

- geringerer Speicherbedarf

Nachteil:

- komplexere Rekonstruktion

Für Chamy ist eine Snapshot-basierte Strategie meist robuster.

---

# 9 Content Locking

Wenn mehrere Benutzer gleichzeitig an Inhalten arbeiten, kann es zu Konflikten kommen.

Chamy führt daher ein **Locking-System** ein.

## Soft Lock

Ein Benutzer bearbeitet einen Inhalt.

Andere Benutzer sehen eine Warnung, können aber trotzdem bearbeiten.

## Hard Lock

Der Inhalt ist exklusiv gesperrt.

Andere Benutzer können ihn nur ansehen.

---

# 10 Geplante Veröffentlichungen

Inhalte können zeitgesteuert veröffentlicht werden.

Beispiele:

- Artikel erscheint morgen um 09:00
- Event wird automatisch archiviert

Dafür besitzt ein Inhalt optional:

- publish_at
- unpublish_at

Ein Hintergrundprozess prüft regelmäßig diese Zeiten.

---

# 11 Workflow-System

Für größere Projekte reicht ein einfaches State-System oft nicht aus.

Chamy kann daher ein optionales Workflow-System unterstützen.

Ein Workflow definiert:

- Rollen
- Schritte
- Freigaben

Beispiel:

Autor → erstellt Entwurf
Editor → prüft Inhalt
Administrator → veröffentlicht

---

# 12 Kommentare und Review-System

Während der Review-Phase können Benutzer Kommentare hinterlassen.

Diese Kommentare gehören nicht zur Version des Inhalts, sondern zum Workflow.

Typische Funktionen:

- Kommentar hinzufügen
- Benutzer erwähnen
- Status markieren
- Kommentar auflösen

---

# 13 Änderungsverlauf

Neben der Versionshistorie kann ein **Audit Log** existieren.

Dieses protokolliert:

- Statusänderungen
- Benutzeraktionen
- Veröffentlichungen
- Wiederherstellungen

Damit wird jede wichtige Aktion nachvollziehbar.

---

# 14 API-Unterstützung

Das API-System muss mit States und Versionen umgehen können.

Beispiele:

GET /content/{id}

liefert standardmäßig:

- veröffentlichte Version

Optional:

GET /content/{id}?version=5

oder

GET /content/{id}?state=draft

---

# 15 Rechteverwaltung

States beeinflussen Berechtigungen.

Beispiele:

- Autor darf Draft bearbeiten
- Editor darf Review freigeben
- Admin darf veröffentlichen

Diese Regeln werden im Permission-System integriert.

---

# 16 Integration mit Content Types

Content Types können festlegen:

- ob Versionierung aktiv ist
- ob Workflow genutzt wird
- ob Planung erlaubt ist

Beispiel:

blog_post

- versionierbar: ja
- workflow: optional

system_config

- versionierbar: nein

---

# 17 Datenstruktur

Ein mögliches Datenmodell könnte enthalten:

content_entries
content_versions
content_states
workflow_comments

Diese Tabellen bilden zusammen das Lebenszyklus-System der Inhalte.

---

# 18 Vorteile für Chamy

Ein sauberes State- und Versionssystem bietet viele Vorteile.

- sichere Redaktionsprozesse
- nachvollziehbare Änderungen
- stabile Veröffentlichungen
- Zusammenarbeit mehrerer Benutzer
- bessere Fehlerkontrolle

---

# 19 Architekturregeln

Für Chamy gelten folgende Regeln:

- jeder Inhalt besitzt einen State
- Versionierung ist standardmäßig aktiv
- Veröffentlichungen erfolgen über Statuswechsel
- alte Versionen dürfen nicht still überschrieben werden
- Workflow-Funktionen bleiben optional

---

# 20 Zusammenfassung

Das State- und Versionssystem erweitert Chamy um eine zentrale Lebenszyklussteuerung für Inhalte.

Gemeinsam mit dem Content-Type-System entsteht eine stabile Grundlage für:

- strukturierte Inhalte
- kontrollierte Veröffentlichungen
- redaktionelle Zusammenarbeit
- sichere Versionierung

Damit kann Chamy auch große Projekte mit vielen Redakteuren zuverlässig verwalten.

