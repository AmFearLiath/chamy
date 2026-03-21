# 08 Modul-System und Marketplace

## 8.1 Modulgrundlagen

Module werden aus `modules/*` erkannt. Voraussetzung:
- gueltiges `manifest.json` mit `id`

`ModuleManager` entdeckt Module, markiert im MVP alle installierten Module als aktiv und bootet sie.

## 8.2 Modul-Bootprozess

Pro aktivem Modul:
1. Sprachdateien (`languages/<locale>.php`) laden
2. optionale Content-Types (`content-types/*.php`) registrieren
3. Entry-Datei laden (default `module.php`)
4. wenn Rueckgabe callable ist: callable mit Kernel ausfuehren
5. Event `module.booted` dispatchen

## 8.3 Beispielmodul `contact_form`

Pfad: `modules/contact_form/`

Implementiert:
- Frontend Route `GET/POST /contact`
- CSRF-Pruefung beim Submit
- Speicherung in DB-Tabelle `contact_messages`
- Adminliste unter `/admin/contact-messages`

## 8.4 Lifecycle-Operationen

Vorhanden im `ModuleManager`:
- `activate(moduleId)`
- `deactivate(moduleId)`
- `isInstalled/isActive`

Wichtig:
- Der aktuelle Bootmodus setzt im MVP alle entdeckten Module aktiv.

## 8.5 Modul-UI im Admin

Routen vorhanden fuer:
- Moduluebersicht
- Marketplace-Seiten
- Manager-Detailseiten
- Toggle/Uninstall
- Modulkonfiguration
- SDK-Seite

Controller: `AdminController` (Methoden `modules*`).

## 8.6 MarketplaceManager (Ist-Zustand)

`core/Managers/MarketplaceManager.php` ist als MVP implementiert.

Vorhanden:
- Kataloghaltung in-memory
- Suche im Katalog
- Installation aus lokalem ZIP (`installFromZip`)
- Deinstallation per Verzeichnisloeschung
- Manifest-Validierung

Nicht ausgebaut:
- echte Remote-Katalog-Synchronisation als Standardpfad
- vollstaendige Moderations- und Review-Pipeline im Core

## 8.7 Theme-Marketplace im Admin

Der AdminController hat bereits Marketplace-Ansichten fuer Themes (inkl. Katalogdaten/Filter in Controller-Logik), jedoch ist das kein vollstaendig externer Marketplace-Client mit durchgaengigem Remote-Lifecycle.

## 8.8 Sicherheitsaspekte bei Modulen

- Modulcode kann eigene Routen registrieren.
- Zugriffsschutz muss im Modulcode selbst korrekt beruecksichtigt werden (z. B. Admin-Auth-Check).
- CSRF bei Formularaktionen ist im Modul explizit umzusetzen.

## 8.9 Aktuelle Grenzen

- keine differenzierte Aktivierungspersistenz pro Modul im Standardpfad (MVP-Active-All)
- Marketplace primar lokal/mock-nah

## 8.10 Fazit

Das Modulsystem ist technisch tragfaehig und lauffaehig. Der Marketplace-Bereich ist vorhanden, aber in Teilen noch als MVP zu verstehen.
