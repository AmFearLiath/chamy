# Kontaktformular-Modul

Ein Beispiel-Modul für Chamy CMS, das zeigt, wie Module aufgebaut werden.

## Struktur

```
modules/contact_form/
├── manifest.json              # Modul-Manifest (Pflicht)
├── module.php                 # Einstiegspunkt (Pflicht)
├── README.md
├── languages/
│   ├── de/contact_form.php    # Deutsche Übersetzungen
│   └── en/contact_form.php    # Englische Übersetzungen
└── migrations/
    └── 001_create_contact_messages_table.php
```

## Features

- Frontend-Kontaktformular unter `/contact`
- CSRF-Schutz
- Validierung (Name, E-Mail, Nachricht)
- Speicherung in der Datenbank
- Admin-Übersicht unter `/admin/contact-messages`
- Mehrsprachig (DE/EN)

## Installation

1. Ordner in `modules/` ablegen
2. Migration ausführen: `php chamy migrate`
3. Modul ist automatisch aktiv wenn `manifest.json` korrekt ist

## Eigene Module erstellen

1. Neuen Ordner unter `modules/` anlegen
2. `manifest.json` erstellen mit id, name, version, entry
3. `module.php` als Einstiegspunkt erstellen
4. Optional: `languages/`, `content-types/`, `migrations/`
