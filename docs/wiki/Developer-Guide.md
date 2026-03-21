# Developer Guide

Für Entwickler: Architektur‑Überblick, Coding‑Standards, Tests und Module anlegen.

Local Setup
- PHP-Version: 8.0+ empfohlen.
- Composer: `composer install` im Repo-Root.
- Node / pnpm: `pnpm install` für Frontend Assets (falls benötigt).

Running Locally
- Start PHP dev server: `php -S localhost:8080 -t public public/index.php`.
- Assets: `pnpm run dev`.
- DB: konfiguriere `config/database.php` für lokale DB und führe ggf. Migrations aus.

Coding Standards
- PSR‑12 für PHP
- Verwende Type Hints und `declare(strict_types=1)`

Tests
- Unit tests mit `phpunit` (siehe `phpunit.xml`): `vendor/bin/phpunit`.
- Integrationstests liegen in `tests/Integration`.

Static Analysis
- Empfehlung: PHPStan (level 5–7) oder Psalm. In CI: höhere Level (7+) verwenden.

Module erstellen (Kurzleitung)

1. Ordner anlegen: `modules/my_module/`
2. `module.php` erstellen: Registriere Routes, Permissions, Hooks
3. Templates in `modules/my_module/templates/`
4. Migrationen in `modules/my_module/migrations/`
5. Tests in `tests/Integration/` oder `tests/Unit/`

Beispiel: Minimal `module.php`

```php
<?php
// module.php
$kernel->modules()->register('my_module', function($kernel){
    $kernel->router()->get('/admin/my-module', [MyController::class, 'index']);
    $kernel->permissions()->definePermission('my_module.view', 'View My Module');
});
```

Pre-commit / Hooks
- Empfohlen: `pre-commit`-Hooks ausführen lassen für Linting, Tests und Composer Checks.

Debugging
- Verwende `storage/logs` und `php -l` für Syntaxchecks.
- Für Twig Fehler aktiviere `debug` in der Twig‑Konfiguration.
