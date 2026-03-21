# Security & Operations

Guidelines für sichere Betriebs‑ und Entwicklungsabläufe.

Geheimnisse
- Speichere Secrets niemals im Repo. Verwende `storage/secrets/` lokal/auf dem Server.
- In CI: nutze GitHub Secrets oder ein Vault (HashiCorp Vault, Azure Key Vault).

Vulnerability Disclosure
- Melde Sicherheitsprobleme privat an den Projektinhaber (siehe `SECURITY.md`).

Dependency Management
- Nutze Dependabot und `composer audit` in CI.

Static Analysis / SAST
- Füge GitHub CodeQL als Workflow hinzu, um Security Issues frühzeitig zu finden.

Backups & Restore
- Backups für `storage/` und Datenbank regelmäßig prüfen und testen.

