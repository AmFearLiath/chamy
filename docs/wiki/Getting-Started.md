# Getting Started — Schnellstart

Diese Seite beschreibt die schnellsten Schritte, um Chamy lokal zum Laufen zu bringen.

Voraussetzungen
- PHP 8.0 oder neuer
- Composer
- Node.js + pnpm
- MySQL / MariaDB oder PostgreSQL
- Git

1) Repository klonen

```bash
git clone https://github.com/AmFearLiath/chamy.git
cd chamy
```

2) Abhängigkeiten installieren

```bash
composer install --no-interaction --optimize-autoloader
pnpm install
```

3) Umgebungsdatei anlegen

```bash
cp .env.example .env
# Passe DB‑Zugang, APP_URL, etc. in .env an
```

4) Datenbankmigrationen

```bash
php chamy migrate
```

5) Assets & Dev Server

```bash
pnpm run dev   # startet JS/CSS Watch
php -S localhost:8080 -t public public/index.php
```

6) Erstzugriff (Admin)
- Während der Installation wird in der Regel ein Admin‑Benutzer angelegt oder siehe `scripts/import_roles_permissions.php` zur Vorbereitung.

Tipps
- Nutze `docker-compose.dev.yml` (siehe Deployment) für eine reproduzierbare Dev‑Umgebung mit DB und PHP‑FPM.
- Nutze `php -S` nur für Entwicklung, nicht für Produktion.

