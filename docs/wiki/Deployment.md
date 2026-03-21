# Deployment & Docker

Empfehlung: Verwende für Entwicklung und Tests eine `docker-compose` Umgebung mit PHP‑FPM, Nginx und einer DB (MySQL/Postgres).

Beispiel (Kurz):

```yaml
version: '3.8'
services:
	php:
		image: php:8.1-fpm
		volumes:
			- ./:/var/www/html
		working_dir: /var/www/html
	web:
		image: nginx:stable
		ports:
			- "8080:80"
		volumes:
			- ./:/var/www/html:ro
			- ./deploy/nginx.conf:/etc/nginx/conf.d/default.conf:ro
	db:
		image: mariadb:10.6
		environment:
			MYSQL_ROOT_PASSWORD: example
			MYSQL_DATABASE: chamy
```

Healthcheck
- Nutze `GET /api/v1/system/health` in CI/Monitoring, Beispiel‑status `{"ok":true}`.

Migrationen & Release
- Vor Release: `php chamy migrate` ausführen, Assets builden, Cache leeren.

