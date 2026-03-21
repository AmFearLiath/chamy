# 10 Sicherheit und Berechtigungen

## 10.1 Authentifizierung im Admin

Authentifizierungsfluss:
- Login ueber `/admin/login`
- Validierung in `AdminController::loginSubmit`
- Session-Werte setzen (`user_id`, Rollenkontext)
- Session-Regeneration zur Härtung gegen Session Fixation

Logout loescht Sessionkontext.

## 10.2 Session-Haertung

`SessionManager` setzt Cookie-Parameter:
- `httponly=true`
- `samesite=Lax`
- `secure` bei HTTPS
- konfigurierbare Lebensdauer

## 10.3 CSRF-Schutz

- Tokenbereitstellung via `getCsrfToken()`
- Validierung via `verifyCsrfToken()`
- Twig-Helper: `csrf_token()`, `csrf_field()`

Admin-POST-Flows (z. B. Settings) validieren CSRF vor Aktion.

## 10.4 Berechtigungsmodell

`PermissionManager` kombiniert:
- registrierte Rechte
- Role-Permission-Zuweisung
- userCan(...) fuer effektive Entscheidung

Besonderheit:
- Rolle `admin` wird implizit als allmaechtig behandelt (`roleHas`).

## 10.5 Rollen und Rechte in der Praxis

Standardrollen:
- `admin`
- `editor`
- `viewer`

Typische Rechtegruppen:
- `admin.*`
- `content.*`
- `system.*`
- `users.manage`, `roles.manage`, `permissions.manage`

## 10.6 API-Sicherheit

### CORS
`CorsMiddleware` setzt:
- `Access-Control-Allow-Origin`
- Preflight fuer `OPTIONS`

### API-Auth
`ApiAuthMiddleware` prueft:
1. Bearer-Token
2. `X-Api-Key`
3. Session-Fallback (Browser/Admin)

## 10.7 Wichtige Inkonsistenz (IST)

Die Middleware greift auf Tabelle `api_keys` zu, waehrend Migrationen `api_tokens` erstellen.

Damit ist Token-Auth auf DB-Ebene aktuell inkonsistent und sollte korrigiert werden.

## 10.8 Eingabevalidierung

Validierung ist verteilt:
- Controller-seitig fuer Formaktionen
- API-Controller fuer Payload-Mindestanforderungen
- typbezogene Logik ueber Content-Type-Definitionen

## 10.9 Dateisystembezogene Schutzmechanismen

- Dotfile-Block im Front Controller
- Installationssperre via `storage/install.lock`
- Theme-Deinstallation verschiebt in Trash statt sofortiger irreversibler Loeschung

## 10.10 Empfehlungen fuer den Betrieb

- API-Tokenfluss mit Tabellenstruktur harmonisieren.
- Rechte im Produktivbetrieb explizit aus DB-Mapping verwalten.
- CSRF auf modulseitigen Formularen konsequent durchziehen.
- Secrets nur in `storage/secrets` und nicht im Repository halten.

## 10.11 Fazit

Chamy hat bereits eine solide Sicherheitsbasis (Session, CSRF, Rollen/Rechte, CORS), besitzt aber im API-Token-Bereich eine konkrete technische Inkonsistenz, die vor produktivem API-Einsatz beseitigt werden sollte.
