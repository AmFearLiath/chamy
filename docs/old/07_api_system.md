# Chamy – API-System

## Zweck dieses Dokuments

Dieses Dokument beschreibt das API-System von Chamy. Es definiert die internen und externen Schnittstellen des Systems, die Art ihrer Nutzung sowie die Regeln für Aufbau, Sicherheit, Versionierung und Erweiterbarkeit.

Das API-System dient dazu, klar definierte Kommunikationswege zwischen Core, Modulen, Themes, Editoren, Marketplace und optional externen Anwendungen bereitzustellen.

Dieses Dokument behandelt insbesondere:

- die Rolle der API im Gesamtsystem
- interne und externe API-Bereiche
- API-Versionierung
- Authentifizierung und Autorisierung
- Routing und Endpunktstruktur
- Antwortformate und Fehlerbehandlung
- Modul- und Marketplace-Integration
- Logging, Dokumentation und Schnittstellenpflege

Weitere Systembereiche werden in separaten Dokumenten beschrieben:

- Systemarchitektur → `02_system_architecture.md`
- Modulsystem → `03_module_system.md`
- Theme-System → `04_theme_system.md`
- Layout-, Komponenten- und Content-System → `05_layout_component_content_system.md`
- Marketplace und Sicherheitsregeln → `06_marketplace_security_and_rules.md`

---

# 1 Grundidee des API-Systems

Das API-System von Chamy stellt definierte Schnittstellen für den Datenaustausch und die Systemkommunikation bereit.

Es sorgt dafür, dass Systemfunktionen nicht unkontrolliert direkt angesprochen werden, sondern über nachvollziehbare und dokumentierte Zugriffswege erreichbar sind.

Die API erfüllt dabei mehrere Aufgaben gleichzeitig:

- Kommunikation zwischen Frontend und Backend
- Kommunikation zwischen Core und Modulen
- Kommunikation zwischen Editoren und Systemdiensten
- Kommunikation mit dem Marketplace
- optionale Kommunikation mit externen Anwendungen oder Diensten

Das API-System ist damit nicht nur eine externe Entwicklerschnittstelle, sondern ein zentraler technischer Kommunikationslayer innerhalb von Chamy.

---

# 2 Ziele des API-Systems

Das API-System verfolgt mehrere technische Ziele.

## Klare Schnittstellen

Jede API-Funktion soll eindeutig definiert, dokumentiert und versionierbar sein.

## Kontrollierter Zugriff

Zugriffe auf Systemfunktionen dürfen nur über freigegebene Endpunkte und definierte Berechtigungen erfolgen.

## Erweiterbarkeit

Module sollen definierte Möglichkeiten erhalten, eigene API-Endpunkte bereitzustellen, ohne die Grundregeln des Systems zu verletzen.

## Stabilität

Einmal veröffentlichte API-Strukturen sollen nachvollziehbar versioniert werden, damit Erweiterungen und externe Integrationen nicht unkontrolliert brechen.

## Nachvollziehbarkeit

Schnittstellen sollen dokumentiert, kommentiert und in separaten Dateien geloggt werden, damit sie systemweit sichtbar und pflegbar bleiben.

---

# 3 Arten von APIs in Chamy

Chamy unterscheidet mehrere API-Bereiche mit unterschiedlichen Aufgaben.

## 3.1 Interne System-API

Die interne System-API dient der Kommunikation zwischen Kernsystem, Managern, Editoren und internen Diensten.

Beispiele:

- Inhaltsverwaltung
- Komponentenverwaltung
- Layoutverwaltung
- Benutzeraktionen im Adminbereich
- systeminterne Statusabfragen

Diese API ist primär für interne Prozesse gedacht und muss nicht automatisch für externe Anwendungen freigegeben sein.

## 3.2 Modul-API

Module können über definierte Regeln eigene API-Endpunkte bereitstellen.

Diese Endpunkte dürfen nur innerhalb der freigegebenen Systemgrenzen arbeiten.

Beispiele:

- Abruf modulspezifischer Daten
- Speichern modulspezifischer Konfigurationen
- Bereitstellung eigener Adminfunktionen
- modulinterne Aktionen für Editor oder Dashboard

## 3.3 Externe API

Chamy kann optional externe API-Endpunkte bereitstellen, damit andere Systeme oder Anwendungen mit Chamy kommunizieren können.

Beispiele:

- Abruf öffentlicher Inhalte
- Integration in mobile Apps
- Verbindung mit Drittanwendungen
- externe Verwaltungswerkzeuge

Externe APIs sind besonders sicherheitsrelevant und müssen gesondert versioniert, geschützt und dokumentiert werden.

## 3.4 Marketplace-API

Der Marketplace besitzt eine eigene API-Ebene für:

- Abruf verfügbarer Erweiterungen
- Update-Prüfungen
- Upload-Prozesse
- Moderationsstatus
- Bewertungen und Kommentare

## 3.5 Editor-API

Editoren wie Content-Editor, Komponenten-Manager oder Hook-Manager benötigen definierte Endpunkte für ihre Arbeitsprozesse.

Beispiele:

- Laden von Komponentenbibliotheken
- Speichern von Editorzuständen
- Vorschau von Layoutbereichen
- Abruf verfügbarer Hooks
- Validierung von Eingaben

---

# 4 Grundstruktur des API-Systems

Das API-System soll klar strukturiert und in logische Bereiche aufgeteilt sein.

Typische Bereiche sind:

- System-API
- Admin-API
- Modul-API
- Marketplace-API
- Externe Public-API

Eine mögliche Pfadstruktur kann beispielsweise so aussehen:

```text
/api/system/
/api/admin/
/api/modules/
/api/marketplace/
/api/public/
```

Diese Trennung sorgt dafür, dass unterschiedliche Sicherheitsniveaus, Rechte und Versionierungsregeln pro Bereich eindeutig angewendet werden können.

---

# 5 Routing und API-Endpunkte

Alle API-Anfragen werden über das Routing-System von Chamy verarbeitet.

Das Routing-System übernimmt dabei:

- Zuordnung von Anfragen zu API-Controllern
- Prüfung des Zielbereichs
- Übergabe an Berechtigungs- und Sicherheitsprüfungen
- Versionserkennung
- Fehlerbehandlung bei ungültigen Anfragen

API-Endpunkte sollen nach einem einheitlichen Schema aufgebaut werden.

Beispielhafte Struktur:

```text
/api/v1/system/content/list
/api/v1/system/content/save
/api/v1/admin/users/list
/api/v1/modules/blog/posts/list
/api/v1/marketplace/extensions/check-updates
/api/v1/public/pages/view
```

Die Pfade sollen klar lesbar und funktional gegliedert sein.

---

# 6 API-Versionierung

Das API-System von Chamy muss versionierbar sein.

Versionierung ist notwendig, damit:

- bestehende Integrationen stabil bleiben
- Module nicht bei jeder internen Änderung brechen
- externe Anwendungen zuverlässig mit Chamy arbeiten können

## 6.1 Versionsprinzip

API-Endpunkte sollen eine klar erkennbare Version enthalten.

Beispiel:

```text
/api/v1/
/api/v2/
```

## 6.2 Rückwärtskompatibilität

Wenn möglich, sollen bestehende Versionen für einen definierten Zeitraum weiter unterstützt werden.

## 6.3 Änderungen an Schnittstellen

Änderungen an Parametern, Antwortformaten oder Sicherheitsregeln müssen dokumentiert und in den Schnittstellenprotokollen festgehalten werden.

---

# 7 Authentifizierung

Je nach API-Bereich gelten unterschiedliche Authentifizierungsanforderungen.

## 7.1 Interne Admin- und System-API

Diese Bereiche arbeiten in der Regel mit einer bestehenden Systemsession des angemeldeten Benutzers.

## 7.2 Externe API

Externe Schnittstellen benötigen explizite Authentifizierungsverfahren.

Mögliche Verfahren:

- API-Token
- Zugriffsschlüssel
- signierte Anfragen
- später optional OAuth-ähnliche Modelle

## 7.3 Marketplace-API

Zugriffe auf Entwickler- und Upload-Funktionen benötigen eigene Authentifizierungs- und Freigabelogiken.

---

# 8 Autorisierung und Berechtigungen

Neben der Authentifizierung muss jede API-Anfrage autorisiert werden.

Das Permission-System von Chamy prüft, ob ein Benutzer, Modul oder externer Schlüssel die angeforderte Aktion ausführen darf.

Die Autorisierung soll mindestens prüfen:

- API-Bereich
- Aktion
- Zielressource
- Benutzerrolle oder Berechtigung
- modulspezifische Freigaben

Beispiele:

- Inhalte lesen
- Inhalte bearbeiten
- Komponenten verwalten
- Modulkonfigurationen speichern
- Marketplace-Uploads durchführen

---

# 9 Datenformate

Das API-System soll mit klar definierten, maschinenlesbaren Datenformaten arbeiten.

Für strukturierte Antworten ist ein konsistentes Standardformat erforderlich.

Ein typisches Antwortschema sollte enthalten:

- Status
- Ergebnisdaten
- Fehlermeldungen
- Metadaten

Beispiel:

```json
{
  "success": true,
  "data": {},
  "meta": {},
  "errors": []
}
```

Dadurch werden Antworten für Frontend, Editoren, Module und externe Anwendungen konsistent auswertbar.

---

# 10 Fehlerbehandlung

Fehlerantworten müssen einheitlich und nachvollziehbar sein.

Das System soll unterscheiden zwischen:

- ungültiger Anfrage
- fehlender Authentifizierung
- fehlender Berechtigung
- Validierungsfehlern
- internen Verarbeitungsfehlern
- nicht gefundenen Ressourcen

Beispielhafte Fehlerstruktur:

```json
{
  "success": false,
  "data": null,
  "meta": {},
  "errors": [
    {
      "code": "validation_failed",
      "message": "Die Anfrage konnte nicht verarbeitet werden."
    }
  ]
}
```

Fehlermeldungen dürfen nicht unnötig interne Systemdetails offenlegen.

---

# 11 Validierung von API-Anfragen

Jede API-Anfrage muss vor der Verarbeitung validiert werden.

Die Validierung umfasst je nach Endpunkt:

- Pflichtfelder
- Datentypen
- erlaubte Werte
- Rechteprüfung
- Formatprüfung
- Versionsprüfung

Ungültige Anfragen dürfen nicht in die eigentliche Geschäftslogik gelangen.

---

# 12 Schnittstellenprotokolle und Interface-Dateien

Für Chamy gilt die Regel, dass relevante Schnittstellen in separaten, passend benannten Dateien dokumentiert und geloggt werden.

Für das API-System bedeutet das:

- jeder wichtige API-Bereich erhält eigene Schnittstellendateien
- Endpunkte werden dokumentiert
- erwartete Parameter werden festgehalten
- Antwortstrukturen werden beschrieben
- funktionsrelevante Hinweise werden ergänzt

Beispielhafte Struktur:

```text
/interfaces/api/system/
/interfaces/api/admin/
/interfaces/api/modules/
/interfaces/api/marketplace/
/interfaces/api/public/
```

Diese Dokumentation dient sowohl Entwicklern als auch der internen Qualitätssicherung.

---

# 13 Logging und Nachvollziehbarkeit

Das API-System soll systemseitig protokollieren, welche relevanten Anfragen verarbeitet wurden.

Dabei ist zwischen technischem Logging und fachlichem Logging zu unterscheiden.

## Technisches Logging

Beispiele:

- fehlerhafte API-Aufrufe
- Sicherheitsverstöße
- ungültige Authentifizierung
- unerwartete Serverfehler

## Fachliches Logging

Beispiele:

- Speichern von Inhalten
- Änderungen an Komponenten
- Aktivierung oder Deaktivierung von Modulen
- Änderungen an Theme-Einstellungen

Größere Eingriffe und relevante API-Änderungen sollen zusätzlich in die Entwicklungsprotokolle aufgenommen werden.

---

# 14 Interne API und Theme-System

Das Theme-System selbst steuert die Darstellung und soll nicht unkontrolliert durch API-Aufrufe umgangen werden.

Daher gilt:

- APIs liefern Daten und Zustände
- Themes steuern die Darstellung
- Module dürfen über APIs keine eigene Frontend-Styling-Logik erzwingen

Dadurch bleibt die visuelle Kontrolle weiterhin beim Theme-System.

---

# 15 Interne API und Modulsystem

Module dürfen eigene API-Endpunkte bereitstellen, wenn sie sich an die Systemregeln halten.

Dabei gelten insbesondere folgende Regeln:

- Registrierung nur über definierte Schnittstellen
- Einhaltung der API-Versionierung
- Einhaltung des Berechtigungssystems
- keine Umgehung des Theme-Systems
- keine unkontrollierte Freigabe interner Daten

Module müssen ihre API-Endpunkte dokumentieren und im System registrieren.

---

# 16 API für Content-, Layout- und Komponenten-System

Die internen Editoren und Verwaltungssysteme benötigen definierte Endpunkte.

Typische API-Aufgaben in diesem Bereich sind:

- Inhalte laden und speichern
- Komponentenlisten abrufen
- Layoutinformationen laden
- Komponenten-Metadaten verwalten
- Vorschauen generieren
- Editorzustände speichern

Diese API-Bereiche sind besonders wichtig, da sie direkt mit der täglichen Verwaltungsarbeit im System verbunden sind.

---

# 17 API für Hook-Manager und Systemwerkzeuge

Auch Systemwerkzeuge benötigen definierte API-Zugriffe.

Dazu gehören beispielsweise:

- Abruf verfügbarer Hooks
- Registrierung neuer Hooks
- Vorschau des benötigten Template-Codes
- Abruf der voraussichtlichen Einbindungsdatei
- Validierung von Hook-Definitionen

Da Hooks eine zentrale Erweiterungsstruktur in Chamy darstellen, sollte auch dieser Bereich API-seitig klar dokumentiert sein.

---

# 18 Marketplace-API

Der Marketplace nutzt eigene API-Endpunkte für mehrere Prozesse.

Dazu gehören:

- Abruf verfügbarer Erweiterungen
- Abruf von Updates
- Einreichung von Modulen und Themes
- Abruf von Moderationsstatus
- Abruf von Bewertungen und Kommentaren
- Verwaltung von Auto-Update-Einstellungen

Die Marketplace-API ist besonders sicherheitskritisch und muss daher strenger geprüft werden als gewöhnliche interne API-Aufrufe.

---

# 19 Externe Integrationen

Chamy kann über definierte externe APIs mit anderen Systemen verbunden werden.

Mögliche Integrationen sind:

- Apps
- externe Verwaltungsoberflächen
- Import- und Exportwerkzeuge
- Analyse- oder Reporting-Systeme
- Drittanbieterplattformen

Dabei muss klar geregelt sein:

- welche Daten extern sichtbar sind
- welche Aktionen extern erlaubt sind
- welche Authentifizierung verwendet wird
- welche Version der API angesprochen wird

---

# 20 Sicherheitsgrundsätze für APIs

Für alle API-Bereiche gelten zentrale Sicherheitsregeln.

## Minimale Rechte

Jeder Zugriff erhält nur die Rechte, die tatsächlich benötigt werden.

## Keine Umgehung interner Regeln

APIs dürfen nicht genutzt werden, um Theme-, Modul-, Sprach- oder Berechtigungsregeln zu umgehen.

## Strikte Validierung

Alle Eingaben müssen geprüft werden.

## Klare Trennung der API-Bereiche

Interne, externe, modulbezogene und Marketplace-Endpunkte müssen logisch getrennt bleiben.

## Sichere Fehlerausgabe

Fehlermeldungen dürfen keine sensiblen internen Details preisgeben.

---

# 21 Dokumentation und Entwicklerfreundlichkeit

Damit das API-System langfristig nutzbar bleibt, muss es sauber dokumentiert werden.

Dazu gehören:

- Beschreibung jedes API-Bereichs
- Beschreibung jedes wichtigen Endpunkts
- Parameterdefinitionen
- Antwortformate
- Fehlermöglichkeiten
- Rechteanforderungen
- Versionshinweise

Diese Dokumentation soll nicht nur externen Entwicklern helfen, sondern auch intern für Module, Editoren und Systemtools nutzbar sein.

---

# 22 Zusammenspiel mit anderen Systemen

Das API-System ist eng mit mehreren Kernbereichen von Chamy verbunden.

Wichtige Verbindungen bestehen zu:

- Kernel und Routing
- PermissionManager
- ModuleManager
- ThemeManager
- ComponentManager
- ContentManager
- MarketplaceManager
- HookManager

Damit bildet die API einen technischen Verbindungslayer zwischen mehreren Kernsystemen.

---

# 23 Zusammenfassung

Das API-System von Chamy ist ein zentraler Kommunikationslayer für interne und externe Schnittstellen.

Es verbindet Core, Module, Themes, Editoren und den Marketplace über klar definierte und kontrollierte Zugriffswege.

Durch Versionierung, Authentifizierung, Autorisierung, Dokumentation und Logging sorgt das API-System dafür, dass Erweiterungen und Integrationen stabil, nachvollziehbar und sicher umgesetzt werden können.

Die API ist damit nicht nur ein Zusatzsystem, sondern ein wesentlicher Bestandteil der langfristigen technischen Struktur von Chamy.

