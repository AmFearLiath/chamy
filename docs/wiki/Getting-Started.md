# Getting Started

Kurzanleitung zum lokalen Starten von Chamy.

1. Systemanforderungen
- PHP 8.x
- Composer
- Node / pnpm
- A running database (MySQL/Postgres supported)

2. Installation

```
composer install
cp .env.example .env
php chamy migrate
pnpm run dev
php -S localhost:8080 -t public public/index.php
```

3. Admin Login
- Default admin wird während Installation erstellt oder per `scripts/import_roles_permissions.php` vorbereitet.
