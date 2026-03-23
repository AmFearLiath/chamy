# Elektro Keilitz – Migrationsdokumentation

## Übersicht

Migration der bestehenden **Astro-basierten** Website (F:\elektro-keilitz) in das **Chamy CMS** (F:\elektro-keilitz-neu) als Custom-Frontend-Theme.

---

## 1. Architektur

| Aspekt | Alt (Astro) | Neu (Chamy) |
|--------|-------------|-------------|
| SSG/SSR | Astro 5 + Tailwind | PHP 8.2 + Twig Templates |
| Styling | Tailwind CSS (utility-first) | Custom CSS (BEM `.ek-*` Klassen) |
| Content | Markdown Collections + JSON | MySQL + JSON-Datenfeld |
| Routing | Dateisystem-basiert | PHP-Router (routes/web.php) |
| i18n | Ordnerbasiert (de/en) | LanguageManager (languages/de/*.php) |

---

## 2. Erstellte Dateien

### Content-Type
- `system/content-types/service.content-type.php` – Leistungsseiten-Schema (22 Felder)

### Theme (themes/frontend/elektro-keilitz/)
- `theme.json` – Manifest
- `templates/base.twig` – Haupt-Layout (Dark Mode, Fonts, Meta)
- `templates/_partials/header.twig` – Sticky Header mit Mobilnavigation
- `templates/_partials/footer.twig` – 4-Spalten Footer
- `templates/home.twig` – Startseite (Hero-Slider, Services, Trust, Referenzen, CTA)
- `templates/service.twig` – Service-Detail (Hero, Zielgruppe, Umfang, Ablauf, FAQ, CTA)
- `templates/services.twig` – Leistungen-Übersicht
- `templates/contact.twig` – Kontaktformular + Kontaktdaten
- `templates/references.twig` – Referenzen-Seite
- `templates/page.twig` – Generische Inhaltsseite
- `templates/errors/404.twig` – Fehlerseite
- `templates/legal/frontend_imprint.twig` – Impressum (legal_manager Override)
- `templates/legal/frontend_privacy.twig` – Datenschutz (legal_manager Override)
- `templates/_partials/icons/*.svg.twig` – SVG-Icon Partials
- `assets/css/theme.css` – Komplettes Design-System (~1100 Zeilen)
- `assets/js/theme.js` – Mobile Menu, Smooth Scroll
- `assets/js/hero-slider.js` – Autoplay-Karussell mit Swipe/Keyboard

### Bilder (assets/images/)
- `logo.svg` – SVG-Logo (Blitz-Icon + Firmenname)
- `favicon.svg`, `favicon.ico`
- `slides/slide_1-5.png` – Hero-Slider Bilder
- `references/ref_01-06_*.png` – Referenzbilder
- `icons/services_icons_*.png` – Service-Icon-Sprites
- SVG-Illustrationen (hero-electrician, reference-*)

### Routen (routes/web.php)
```
GET  /                                → home
GET  /leistungen                      → servicesList
GET  /leistungen/{slug}               → serviceShow
GET  /kontakt                         → contact
POST /kontakt                         → contactSubmit
GET  /referenzen                      → references
GET  /impressum                       → (legal_manager Modul)
GET  /datenschutz                     → (legal_manager Modul)
```

### Controller (core/Controllers/FrontendController.php)
Erweitert um: `home()` (mit Services+Slides), `servicesList()`, `serviceShow()`, `contact()`, `contactSubmit()`, `references()`

### Übersetzungen
- `languages/de/frontend.php` – Alle Frontend-Schlüssel (nav.*, home.*, service.*, contact.*, footer.*, etc.)

### Scripte
- `scripts/import_keilitz_content.php` – Haupt-Import-Script (6 Services, 1 Snippet, 5 Seiten)
- `scripts/fix_theme_setting.php` – DB-Setting für Frontend-Theme
- `scripts/fix_seo_titles.php` – SEO-Titel-Bereinigung

---

## 3. Datenbank-Inhalte

| ID | Typ | Slug | Beschreibung |
|----|-----|------|-------------|
| 1 | service | elektroinstallation | Neubau, Sanierung, Modernisierung |
| 2 | service | photovoltaik | PV-Anlagen, Speicher, Energiemanagement |
| 3 | service | netzwerktechnik | LAN/WLAN, Patchfelder, Serverschrank |
| 4 | service | smart-home | Beleuchtung, Sicherheit, Steuerung |
| 5 | service | klimaanlagen-kaeltetechnik | Prüfung, Fehleranalyse, Instandsetzung |
| 6 | service | e-mobilitaet-ladetechnik | Wallbox, Lastmanagement, Abnahme |
| 7 | snippet | hero-slides | 5 Hero-Slides (JSON im body-Feld) |
| 8 | page | startseite | Homepage-Meta |
| 9 | page | kontakt | Kontaktseite |
| 10 | page | referenzen | Referenzseite |
| 11 | page | impressum | Platzhalter |
| 12 | page | datenschutz | Platzhalter |

---

## 4. Design-System

### Farbschema (CSS Custom Properties)
- `--ek-bg`: #07111f (Hintergrund)
- `--ek-surface`: #102742 (Kartenoberflächen)
- `--ek-primary`: #e5ba54 (Gold/Primär)
- `--ek-accent`: #4ea0ff (Blau/Akzent)
- `--ek-text`: #edf3fc (Textfarbe)
- `--ek-muted`: #a9bad2 (Gedämpft)

### Typografie
- Font: Inter (Google Fonts, 400-800)
- Heading-Skala: 3rem → 1.25rem

### Besonderheiten
- Dark Mode als Standard mit CSS-Variable-System
- Hero-Slider mit Fade-Transitions und Autoplay (6s)
- Responsive Breakpoints: 1024px, 768px, 480px
- BEM-Namenskonvention: `.ek-*`

---

## 5. Konfiguration

### .env Änderungen
```
APP_NAME="Elektro Keilitz"
FRONTEND_THEME=elektro-keilitz
```

### DB Settings (chamy_settings)
```sql
UPDATE chamy_settings SET value = 'elektro-keilitz' WHERE `group` = 'theme' AND `key` = 'frontend_theme';
```

---

## 6. Offene Punkte

- [ ] Echte Firmenadresse, Telefonnummer und E-Mail-Adresse einpflegen
- [ ] Impressum und Datenschutz juristisch finalisieren (aktuell Platzhalter, werden vom legal_manager verwaltet)
- [ ] Referenzprojekte mit echten Projektdaten befüllen
- [ ] Englische Übersetzung aller Inhalte (EN-Locale) ergänzen
- [ ] SSL-Zertifikat und Produktions-Deployment konfigurieren
- [ ] Meta-OG-Images für Social Sharing hinterlegen
