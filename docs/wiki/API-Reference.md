# API Reference — Wichtige Endpunkte

Diese Seite dokumentiert einige zentrale Admin‑API Endpunkte. Nutze Postman oder `curl` zum Testen.

1) Google Fonts Status (Admin)
- URL: `GET /admin/api/google-fonts/status`
- Auth: Admin‑Session
- Antwort (Beispiel):

```json
{
  "ok": true,
  "items": [ ... ]
}
```

2) Google Fonts Key Check (Admin)
- URL: `POST /admin/api/google-fonts/check`
- Body: `google_api_key` (form)

3) Asset Install (Google Font)
- URL: `POST /admin/assets`
- Body fields: `asset_action=fonts.google_install`, `google_family`, `google_styles[]`

4) System Health
- URL: `GET /api/v1/system/health`
- Antwort: `{"ok": true, "status": "ok"}`

Allgemeine Hinweise
- Alle Admin‑API Endpunkte erwarten eine gültige Session (Admin) und CSRF Token. Nutze die Admin UI zur Authentifizierung oder übertrage Session‑Cookies in deinen Tools.
