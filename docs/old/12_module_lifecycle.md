# Modul Upgrade / Rollback Strategie

Dieses Dokument beschreibt die minimalen Regeln und einen sicheren Ablauf fuer Upgrade- und Rollback-Operationen von Modulen in Chamy.

Ziele:
- sichere, reproduzierbare Upgrades
- schnelle, zuverlässige Rollbacks bei Problemen
- nachvollziehbare Backups und Migrationsschritte

Kernprinzipien:
- Nicht-destruktiv: vor jeder Änderung wird ein Backup angelegt.
- Idempotent: wiederholte Upgrades auf dieselbe Version führen nicht zu Seiteneffekten.
- Trennung: Module liefern Migrationsskripte, Core führt sie kontrolliert aus.
- Prüfbar: Validierung von Manifests und SemVer-Constraints vor Ausführung.

Empfohlener Ablauf (Upgrade):
1. Validieren: Manifest lesen und Abhängigkeiten prüfen.
2. Backup: Modulordner und relevante DB-States sichern (Backup-Archiv in `storage/backups`).
3. Dry-Run: Migrationsskripte im Trockenmodus prüfen (wenn unterstützt).
4. Anwenden: Paket entpacken / Dateien überschreiben, Migrationsskripte mit Transaktion ausführen.
5. Verifizieren: Smoke-Tests (Routen, Templates, einfache API-Calls) ausführen.
6. Markieren: neue Version in Manifest persistieren und Audit-Event schreiben.

Rollback (bei Fehlern):
1. Stoppen: aktive Prozesse/Jobs anhalten falls nötig.
2. Restore: Backup-Archiv zurückspielen (Dateien + ggf. DB-Backup wiederherstellen).
3. Verifizieren: Smoke-Tests erneut ausführen.
4. Audit: Rollback-Ereignis protokollieren.

Implementationshinweise:
- Module sollten optionale `migrations/` Skripte bereitstellen, möglichst als einzelne, reversable Schritte.
- Migrationen, die irreversible DB-Änderungen durchführen, müssen ein entsprechendes DB-Backup erzeugen.
- Module-Pakete sollten versionierte Archive bereitstellen: `packages/<id>-<version>.zip`.

Werkzeuge:
- Ein CLI-Skript `scripts/module_lifecycle.php` (Dry-Run zuerst, dann mit `--confirm` ausführen) implementiert Backup/Restore/Version-Simulation.

Beispielbefehle:
```
php scripts/module_lifecycle.php --action=upgrade --module=contact_form --to=1.1.0 --dry-run
php scripts/module_lifecycle.php --action=upgrade --module=contact_form --to=1.1.0 --confirm
php scripts/module_lifecycle.php --action=rollback --module=contact_form --confirm
```

Weiteres:
- CI sollte `Dry-Run`-Checks automatisiert ausführen und bei Erfolg die eigentlichen Upgrades in Rollouts steuern.
- Dokumentation fuer Modul-Entwickler: wie Migrationsskripte zu schreiben sind und welche Audit-Events erwartet werden.

DB-Backup-Unterstützung:

- Das Lifecycle-Tool unterstützt jetzt optionales DB-Backup vor dem Upgrade/Rollback. Es versucht, DB-Zugangsdaten aus `config/database.php` oder aus Umgebungsvariablen zu lesen (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`).
- Wenn `mysqldump` verfügbar ist, wird ein SQL-Dump in `storage/backups/` geschrieben; fehlt `mysqldump`, erfolgt eine Warnung und der Prozess kann optional abbrechen.
- In CI/Headless-Umgebungen kann das DB-Backup übersprungen werden; dann muss mindestens ein Dateisystem-Backup vorhanden sein.

Empfohlene Flags für das CLI-Tool:
```
--db-backup        # versuche ein DB-Dump zu erstellen
--apply-migrations  # führe PHP-Migrationsskripte unter modules/<id>/migrations aus (nur mit --confirm)
```

