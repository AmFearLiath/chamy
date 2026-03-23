<?php

/**
 * Elektro Keilitz – Content-Import-Script
 *
 * Überträgt alle Inhalte der alten Elektro-Keilitz-Webseite in Chamy:
 * - 6 Leistungsseiten (Service Content Type)
 * - Hero-Slides (als Snippet)
 * - Standardseiten (Kontakt, Impressum, Datenschutz, Referenzen, Startseite)
 *
 * Nutzung: php scripts/import_keilitz_content.php
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);
require_once $basePath . '/vendor/autoload.php';

use Chamy\Core\Bootstrap;

$kernel = Bootstrap::init($basePath);

$db     = $kernel->db();
$prefix = $db->getPrefix();
$table  = $prefix . 'content_entries';
$userId = 1; // Admin-Benutzer
$now    = date('Y-m-d H:i:s');

echo "═══ Elektro Keilitz – Content Import ═══\n\n";

/**
 * Hilfsfunktion: Content Entry einfügen
 */
function insertContent(PDO $pdo, string $table, string $type, string $locale, array $data, int $userId, string $now, string $status = 'published'): int
{
    $uuid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0xffff),
        (random_int(0, 0x0fff) | 0x4000),
        (random_int(0, 0x3fff) | 0x8000),
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
    );

    $stmt = $pdo->prepare("INSERT INTO {$table} (uuid, content_type, locale, status, version, data, created_by, updated_by, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $uuid,
        $type,
        $locale,
        $status,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $userId,
        $userId,
        $status === 'published' ? $now : null,
        $now,
        $now,
    ]);

    return (int) $pdo->lastInsertId();
}

$pdo = $db->getPdo();

// Prüfe ob bereits Imports existieren, um Duplikate zu vermeiden
$existingCount = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
if ($existingCount > 0) {
    echo "⚠ Es existieren bereits {$existingCount} Einträge.\n";
    echo "  Lösche bestehende Einträge...\n";
    $pdo->exec("DELETE FROM {$table}");
    echo "  ✓ Bestehende Einträge gelöscht.\n\n";
}

// ═══════════════════════════════════════════════════════════════
// 1. SERVICES (Leistungen)
// ═══════════════════════════════════════════════════════════════

echo "1. Services importieren...\n";

$services = [
    // ─── Elektroinstallation ───
    [
        'title'           => 'Elektroinstallation',
        'slug'            => 'elektroinstallation',
        'sort_order'      => 1,
        'icon'            => 'elektro.svg',
        'micro_info'      => 'Neubau • Sanierung • Modernisierung',
        'hero_heading'    => 'Elektroinstallation, die heute und morgen funktioniert.',
        'hero_subheading' => 'Von der Planung über die Absicherung bis zur finalen Messung: Wir bauen elektrische Anlagen so, dass sie verständlich, sicher und erweiterbar bleiben.',
        'hero_bullets'    => json_encode([
            'Planung nach Nutzung, Lasten und Reserven',
            'Normgerechte Schutzkonzepte und Messprotokolle',
            'Strukturierte Verteilung: nachvollziehbar beschriftet',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Privatkunden im Neubau oder bei Kernsanierung',
            'Hausbesitzer, die ihre Anlage erweitern oder modernisieren',
            'Familien, die Sicherheit und klare Bedienbarkeit priorisieren',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Planung & Stromkreis-Design', 'description' => 'Raumweise Planung mit Leistungsreserven, sinnvoller Aufteilung und sauberer Dokumentation.'],
            ['title' => 'Verteilungen & Schutz', 'description' => 'Fehlerstromschutz, Leitungsschutz und Überspannungsschutz passend zur Anlage und Nutzung.'],
            ['title' => 'Installation & Inbetriebnahme', 'description' => 'Präzise Ausführung, Beschriftung, Messungen und Übergabe mit Protokollen.'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Erstaufnahme', 'description' => 'Wir klären Anforderungen, Bestand, Zukunftspläne und sensible Bereiche (z. B. Küche, Homeoffice).'],
            ['title' => 'Planung & Angebot', 'description' => 'Sie erhalten eine klare Leistungsbeschreibung mit Varianten, Budgetrahmen und Zeitplan.'],
            ['title' => 'Umsetzung', 'description' => 'Installation in definierten Etappen – mit sauberem Baustellenablauf und Transparenz.'],
            ['title' => 'Prüfung & Übergabe', 'description' => 'Messungen, Protokolle, Einweisung und eine Anlage, die nachvollziehbar beschriftet ist.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Welche Unterlagen bekomme ich?', 'answer' => 'Eine strukturierte Dokumentation (Stromkreisplan/Belegung, Messprotokolle und relevante Herstellerdaten).'],
            ['question' => 'Kann man später Smart Home oder PV nachrüsten?', 'answer' => 'Ja – wir planen Reserven und Schnittstellen so, dass Erweiterungen ohne Architekturbruch möglich sind.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Elektroinstallation anfragen',
        'cta_text'          => 'Beschreiben Sie kurz Ihr Projekt (Neubau/Sanierung, Fläche, besondere Verbraucher). Wir melden uns mit einer klaren nächsten Empfehlung.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'Elektroinstallation',
        'seo_description'   => 'Sichere und normgerechte Elektroinstallation für Neubau, Sanierung und Erweiterung – mit sauberer Dokumentation.',
    ],

    // ─── Photovoltaik ───
    [
        'title'           => 'Photovoltaik',
        'slug'            => 'photovoltaik',
        'sort_order'      => 2,
        'icon'            => 'photovoltaik.svg',
        'micro_info'      => 'PV-Anlagen • Speicher • Energiemanagement',
        'hero_heading'    => 'Photovoltaik – Energie erzeugen, sinnvoll nutzen.',
        'hero_subheading' => 'Wir verbinden Dach, Wechselrichter und Hausnetz so, dass die Anlage sicher läuft und Eigenverbrauch intelligent möglich wird.',
        'hero_bullets'    => json_encode([
            'Auslegung nach Dach, Verbrauch und Zielen',
            'Sichere DC/AC-Installation mit Überspannungsschutz',
            'Vorbereitung für Speicher, Wärmepumpe und Wallbox',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Eigenheimbesitzer mit Fokus auf Eigenverbrauch',
            'Modernisierungen mit gleichzeitiger Elektro-Optimierung',
            'Haushalte, die E‑Mobilität oder Speicher planen',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Technische Auslegung', 'description' => 'Modulbelegung, Stringplanung, Wechselrichterauswahl und Abstimmung auf Verbrauchsprofile.'],
            ['title' => 'Elektrische Integration', 'description' => 'Einspeisepunkt, Absicherung, Messkonzept und saubere Einbindung in die Verteilung.'],
            ['title' => 'Betrieb & Transparenz', 'description' => 'Monitoring-Setup, verständliche Einweisung und Dokumentation für langfristig sicheren Betrieb.'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Vor-Ort-Check', 'description' => 'Dachbewertung, Verschattungsanalyse und Bestandsprüfung.'],
            ['title' => 'Planung & Angebot', 'description' => 'Detaillierte Auslegung mit Ertragsberechnung und Kostenplan.'],
            ['title' => 'Installation', 'description' => 'Montage, Verkabelung und Anschluss an die Hausverteilung.'],
            ['title' => 'Inbetriebnahme', 'description' => 'Prüfung, Netzanmeldung, Monitoring-Setup und Einweisung.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Lohnt sich ein Speicher immer?', 'answer' => 'Nicht immer. Wir bewerten Ziele, Verbrauch und Lastgänge und empfehlen transparent die passende Variante.'],
            ['question' => 'Kann ich später eine Wallbox integrieren?', 'answer' => 'Ja. Wir berücksichtigen Lastmanagement und Reserven, damit die Erweiterung technisch sauber bleibt.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Photovoltaik anfragen',
        'cta_text'          => 'Beschreiben Sie Ihr Vorhaben (Dachfläche, Verbrauch, Ziele). Wir nehmen Kontakt auf.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'Photovoltaik',
        'seo_description'   => 'PV-Anlagen mit sauberer Auslegung, sicherer AC/DC-Installation und klarer Übergabe – inkl. Vorbereitung für Speicher & Wallbox.',
    ],

    // ─── Netzwerktechnik ───
    [
        'title'           => 'Netzwerktechnik',
        'slug'            => 'netzwerktechnik',
        'sort_order'      => 3,
        'icon'            => 'netzwerk.svg',
        'micro_info'      => 'LAN/WLAN • Patchfelder • Serverschrank',
        'hero_heading'    => 'Netzwerktechnik, die einfach stabil ist.',
        'hero_subheading' => 'Wir bauen Heimnetze, die zuverlässig funktionieren: strukturierte Leitungen, saubere Verteilung und WLAN dort, wo Sie es brauchen.',
        'hero_bullets'    => json_encode([
            'Strukturierte Cat-Verkabelung mit Messprotokollen',
            'WLAN-Planung mit sauberen Access-Point-Positionen',
            'Patchfeld/Rack: klar beschriftet und erweiterbar',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Homeoffice-Setups mit hohen Stabilitätsansprüchen',
            'Familien mit vielen Endgeräten und Streaming',
            'Smart-Home-Projekte, die ein stabiles Backbone brauchen',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Verkabelung & Dosen', 'description' => 'Leitungswege, Datendosen, Patchpanel – sauber dokumentiert und gemessen.'],
            ['title' => 'Switch/Router/WLAN', 'description' => 'Auswahl und Konfiguration passender Geräte inklusive VLAN/WLAN-Segmentierung nach Bedarf.'],
            ['title' => 'Struktur & Ordnung', 'description' => 'Rack-Aufbau, Beschriftung, Kabelmanagement und eine Anlage, die Sie verstehen.'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Bedarf & Grundriss', 'description' => 'Analyse der Anforderungen und Planung anhand des Gebäudegrundrisses.'],
            ['title' => 'Konzept', 'description' => 'Detailplanung mit Komponentenauswahl und Kostenübersicht.'],
            ['title' => 'Umsetzung', 'description' => 'Verkabelung, Montage und Konfiguration aller Netzwerkkomponenten.'],
            ['title' => 'Übergabe', 'description' => 'Messprotokolle, Dokumentation und Einweisung in das fertige Netzwerk.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Reicht WLAN nicht aus?', 'answer' => 'WLAN ist wichtig, aber strukturierte Verkabelung ist die stabile Basis – besonders für TV, Homeoffice und AP-Backhaul.'],
            ['question' => 'Kann man das später erweitern?', 'answer' => 'Ja. Mit Reserven im Rack und klarer Struktur bleiben Erweiterungen sauber.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Netzwerktechnik anfragen',
        'cta_text'          => 'Beschreiben Sie Ihren Bedarf (Anzahl Geräte, Grundriss, gewünschte Funktionen). Wir planen Ihr stabiles Netz.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'Netzwerktechnik',
        'seo_description'   => 'Stabiles Heimnetz mit strukturierter Verkabelung, WLAN-Konzept und sauberem Rack – ideal für Homeoffice, Streaming und Smart Home.',
    ],

    // ─── Smart Home ───
    [
        'title'           => 'Smart Home',
        'slug'            => 'smart-home',
        'sort_order'      => 4,
        'icon'            => 'smarthome.svg',
        'micro_info'      => 'Beleuchtung • Sicherheit • Steuerung',
        'hero_heading'    => 'Smart Home – intelligent, unaufdringlich, robust.',
        'hero_subheading' => 'Wir planen Smart Home so, dass es zuverlässig funktioniert, auch wenn einzelne Komponenten ausgetauscht werden – mit klarer Struktur und Dokumentation.',
        'hero_bullets'    => json_encode([
            'Licht- und Szenensteuerung mit sauberer Logik',
            'Beschattung, Präsenz, Sicherheit – sinnvoll verknüpft',
            'Erweiterbar ohne „Wildwuchs"',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Neubau/Sanierung mit Anspruch an Komfort und Effizienz',
            'Haushalte, die Energieflüsse besser steuern möchten',
            'Kunden, die einfache Bedienung statt App-Chaos wollen',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Konzept & Architektur', 'description' => 'Gewerkeübergreifende Planung: Sensorik, Aktorik, Logik, Bedienkonzept.'],
            ['title' => 'Installation & Inbetriebnahme', 'description' => 'Saubere Verdrahtung, Parametrierung und nachvollziehbare Szenen/Automationen.'],
            ['title' => 'Übergabe & Dokumentation', 'description' => 'Klar dokumentierte Funktionen, Einweisung und eine Basis, die wartbar bleibt.'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Anforderungsworkshop', 'description' => 'Gemeinsame Analyse Ihres Alltags, Ihrer Wünsche und technischen Rahmenbedingungen.'],
            ['title' => 'Systemdesign', 'description' => 'Auswahl der Plattform, Komponentenplanung und Bedienkonzept.'],
            ['title' => 'Umsetzung', 'description' => 'Installation, Konfiguration und Test aller Smart-Home-Funktionen.'],
            ['title' => 'Optimierung', 'description' => 'Feinjustierung der Szenen und Automationen nach Ihrem Feedback.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Muss alles smart sein?', 'answer' => 'Nein. Wir automatisieren nur dort, wo es echten Nutzen bringt – der Rest bleibt bewusst klassisch.'],
            ['question' => 'Was passiert bei Internet-Ausfall?', 'answer' => 'Ein robustes System funktioniert im Kern lokal weiter; Cloud-Funktionen sind optional.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Smart Home anfragen',
        'cta_text'          => 'Erzählen Sie uns, was Sie sich wünschen. Wir beraten Sie ehrlich und praxisnah.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'Smart Home',
        'seo_description'   => 'Smarte Funktionen, die im Alltag helfen: Licht, Beschattung, Heizung und Energie – geplant als System, nicht als Gadget.',
    ],

    // ─── Klimaanlagen / Kältetechnik ───
    [
        'title'           => 'Klimaanlagen / Kältetechnik',
        'slug'            => 'klimaanlagen-kaeltetechnik',
        'sort_order'      => 5,
        'icon'            => 'service.svg',
        'micro_info'      => 'Prüfung • Fehleranalyse • Instandsetzung',
        'hero_heading'    => 'Klimatisierung – komfortabel, effizient, sicher integriert.',
        'hero_subheading' => 'Wir kümmern uns um die elektrische Seite Ihrer Klima-/Kälteanlage – passend abgesichert, sauber integriert und nachvollziehbar dokumentiert.',
        'hero_bullets'    => json_encode([
            'Saubere Zuleitungen, Absicherung und Schutzkonzepte',
            'Vorbereitung für smarte Steuerung und Zeitprogramme',
            'Integration ohne "Provisorien" in der Verteilung',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Eigenheime mit steigenden Komfort- und Effizienzansprüchen',
            'Modernisierungen mit gleichzeitiger Elektro-Optimierung',
            'Kunden, die eine saubere, wartbare Installation wollen',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Elektrische Vorbereitung', 'description' => 'Leitungswege, Lasten, Absicherung und passende Schutzmaßnahmen.'],
            ['title' => 'Integration & Lastmanagement', 'description' => 'Einbindung in die Hausinstallation – auch in Kombination mit PV, Speicher oder Wärmepumpe.'],
            ['title' => 'Dokumentation', 'description' => 'Beschriftung, Stromkreiszuordnung und klare Übergabe der elektrischen Unterlagen.'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Check vor Ort', 'description' => 'Aufnahme des Bestands, Prüfung der Hausanschlussreserven.'],
            ['title' => 'Planung', 'description' => 'Leitungsführung, Absicherungskonzept und Integration in die Verteilung.'],
            ['title' => 'Umsetzung', 'description' => 'Installation, Anschluss und Test aller elektrischen Komponenten.'],
            ['title' => 'Übergabe', 'description' => 'Dokumentation, Beschriftung und Einweisung.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Kann man Klima mit PV sinnvoll kombinieren?', 'answer' => 'Ja. Gerade im Sommer passt PV-Erzeugung gut zur Kühl-Last. Wir planen die elektrische Integration entsprechend.'],
            ['question' => 'Muss die Verteilung angepasst werden?', 'answer' => 'Je nach Bestand ja. Ziel ist eine sichere, normgerechte Absicherung ohne Provisorien.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Klimatechnik anfragen',
        'cta_text'          => 'Beschreiben Sie Ihr Vorhaben. Wir beraten Sie zur elektrischen Integration.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'Klimaanlagen / Kältetechnik',
        'seo_description'   => 'Klimatisierung mit sauberer elektrischer Einbindung: Zuleitung, Absicherung, Kondensatmanagement und sichere Inbetriebnahme.',
    ],

    // ─── E-Mobilität / Ladetechnik ───
    [
        'title'           => 'E‑Mobilität / Ladetechnik',
        'slug'            => 'e-mobilitaet-ladetechnik',
        'sort_order'      => 6,
        'icon'            => 'emobilitaet.svg',
        'micro_info'      => 'Wallbox • Lastmanagement • Abnahme',
        'hero_heading'    => 'Wallbox & Ladetechnik – sicher, schnell, zukunftsfähig.',
        'hero_subheading' => 'Wir planen die Ladeinfrastruktur so, dass sie zu Ihrem Hausanschluss passt – mit Lastmanagement und sauberer Dokumentation.',
        'hero_bullets'    => json_encode([
            'Absicherung und Leitungsauslegung nach Norm',
            'Lastmanagement bei mehreren Verbrauchern',
            'Vorbereitung für PV-Überschussladen',
        ], JSON_UNESCAPED_UNICODE),
        'target_heading'  => 'Für wen ist das ideal?',
        'target_items'    => json_encode([
            'Eigenheimbesitzer mit E‑Auto oder Plug‑in‑Hybrid',
            'Haushalte mit PV oder geplantem PV-Ausbau',
            'Familien mit perspektivisch zwei Fahrzeugen',
        ], JSON_UNESCAPED_UNICODE),
        'scope_heading'   => 'Leistungsumfang',
        'scope_items'     => json_encode([
            ['title' => 'Hausanschluss & Verteilung', 'description' => 'Prüfung der Leistungsreserven, Schutzkonzepte und saubere Einbindung in die Verteilung.'],
            ['title' => 'Installation & Konfiguration', 'description' => 'Leitungswege, Montage, Inbetriebnahme und – falls sinnvoll – Lastmanagement.'],
            ['title' => 'PV-Integration', 'description' => 'Vorbereitung und Konfiguration für PV-Überschussladen (systemabhängig).'],
        ], JSON_UNESCAPED_UNICODE),
        'process_heading' => 'Ablauf',
        'process_steps'   => json_encode([
            ['title' => 'Vorprüfung', 'description' => 'Prüfung des Hausanschlusses, Reserven und nötiger Anpassungen.'],
            ['title' => 'Angebot', 'description' => 'Detailliertes Angebot mit Leitungsweg, Wallbox-Empfehlung und Zeitplan.'],
            ['title' => 'Installation', 'description' => 'Montage, Verkabelung und Inbetriebnahme der Ladestation.'],
            ['title' => 'Einweisung', 'description' => 'Funktionserklärung, Dokumentation und erste Ladung.'],
        ], JSON_UNESCAPED_UNICODE),
        'faq_heading'     => 'Häufige Fragen',
        'faq_items'       => json_encode([
            ['question' => 'Reicht mein Hausanschluss für eine Wallbox?', 'answer' => 'Häufig ja, aber nicht immer. Wir prüfen Reserven und empfehlen die passende Ladeleistung und ggf. Lastmanagement.'],
            ['question' => 'Kann ich später eine zweite Wallbox nachrüsten?', 'answer' => 'Ja – wenn Struktur und Reserven von Anfang an mitgedacht werden.'],
        ], JSON_UNESCAPED_UNICODE),
        'cta_heading'       => 'Wallbox & Ladetechnik anfragen',
        'cta_text'          => 'Erzählen Sie uns von Ihrem Fahrzeug und Ihren Ladeanforderungen – wir kümmern uns um den Rest.',
        'cta_primary_label' => 'Angebot anfragen',
        'cta_primary_href'  => 'mailto:kontakt@elektro-keilitz.de',
        'seo_title'         => 'E‑Mobilität / Ladetechnik',
        'seo_description'   => 'Wallbox-Installationen mit sauberer Planung: Absicherung, Lastmanagement, Zählerplatz und Vorbereitung für PV-Überschussladen.',
    ],
];

foreach ($services as $service) {
    $id = insertContent($pdo, $table, 'service', 'de', $service, $userId, $now);
    echo "  ✓ Service: {$service['title']} (ID: {$id})\n";
}

// ═══════════════════════════════════════════════════════════════
// 2. HERO SLIDES (als Snippet)
// ═══════════════════════════════════════════════════════════════

echo "\n2. Hero-Slides importieren...\n";

$heroSlides = [
    [
        'badge'     => 'Elektro-Meisterbetrieb',
        'heading'   => 'Elektro-Meisterbetrieb für moderne und sichere Lösungen',
        'subheading' => 'Privat • Gewerbe • Industrie – fachgerecht geplant, sauber umgesetzt und zuverlässig betreut.',
        'image'     => '/themes/frontend/elektro-keilitz/assets/images/slides/slide_1.png',
        'theme'     => 'balanced',
        'cta_primary'   => ['label' => 'Jetzt Kontakt aufnehmen', 'href' => '/kontakt'],
        'cta_secondary' => ['label' => 'Leistungen ansehen', 'href' => '/#leistungen'],
    ],
    [
        'badge'     => 'Elektroinstallation',
        'heading'   => 'Präzise Elektroinstallation für Neubau und Sanierung',
        'subheading' => 'Normgerechte Planung, saubere Ausführung und transparente Dokumentation für Ihr Projekt.',
        'image'     => '/themes/frontend/elektro-keilitz/assets/images/slides/slide_2.png',
        'theme'     => 'blue-heavy',
        'cta_primary'   => ['label' => 'Mehr erfahren', 'href' => '/leistungen/elektroinstallation'],
        'cta_secondary' => ['label' => 'Kontakt', 'href' => '/kontakt'],
    ],
    [
        'badge'     => 'Smart Home',
        'heading'   => 'Intelligente Gebäudeautomation mit klarer Bedienung',
        'subheading' => 'Von Licht und Beschattung bis Sicherheit und Energiefluss – alles zentral steuerbar.',
        'image'     => '/themes/frontend/elektro-keilitz/assets/images/slides/slide_3.png',
        'theme'     => 'blue-dominant',
        'cta_primary'   => ['label' => 'Mehr erfahren', 'href' => '/leistungen/smart-home'],
        'cta_secondary' => ['label' => 'Alle Leistungen', 'href' => '/#leistungen'],
    ],
    [
        'badge'     => 'Photovoltaik',
        'heading'   => 'Photovoltaik und Speicher wirtschaftlich umgesetzt',
        'subheading' => 'Mehr Unabhängigkeit durch passende PV-Systeme, abgestimmt auf Verbrauch und Gebäudestruktur.',
        'image'     => '/themes/frontend/elektro-keilitz/assets/images/slides/slide_4.png',
        'theme'     => 'gold-dominant',
        'cta_primary'   => ['label' => 'Mehr erfahren', 'href' => '/leistungen/photovoltaik'],
        'cta_secondary' => ['label' => 'Kontakt', 'href' => '/kontakt'],
    ],
    [
        'badge'     => 'E-Mobilität',
        'heading'   => 'Wallbox und Ladeinfrastruktur für morgen',
        'subheading' => 'Sichere Ladepunkte für Einfamilienhaus, Mehrparteienobjekt und gewerblichen Fuhrpark.',
        'image'     => '/themes/frontend/elektro-keilitz/assets/images/slides/slide_5.png',
        'theme'     => 'blue-subtle-gold',
        'cta_primary'   => ['label' => 'Mehr erfahren', 'href' => '/leistungen/e-mobilitaet-ladetechnik'],
        'cta_secondary' => ['label' => 'Beratung buchen', 'href' => '/kontakt'],
    ],
];

$slidesData = [
    'title'    => 'Hero-Slides',
    'slug'     => 'hero-slides',
    'location' => 'custom',
    'body'     => json_encode($heroSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
];

$id = insertContent($pdo, $table, 'snippet', 'de', $slidesData, $userId, $now);
echo "  ✓ Hero-Slides Snippet (ID: {$id})\n";

// ═══════════════════════════════════════════════════════════════
// 3. STANDARDSEITEN
// ═══════════════════════════════════════════════════════════════

echo "\n3. Standardseiten importieren...\n";

$pages = [
    // Startseite
    [
        'title'           => 'Startseite',
        'slug'            => 'startseite',
        'excerpt'         => 'Elektro-Meisterbetrieb für Privat, Gewerbe und Industrie.',
        'body'            => '',
        'seo_title'       => 'Elektro Keilitz – Meisterbetrieb für moderne Elektrotechnik',
        'seo_description' => 'Elektro-Meisterbetrieb für Privat, Gewerbe und Industrie: Elektroinstallation, Smart Home, Photovoltaik, Netzwerke und E-Mobilität.',
        'layout'          => 'home',
    ],

    // Kontakt
    [
        'title'           => 'Kontakt',
        'slug'            => 'kontakt',
        'excerpt'         => 'Schreiben Sie uns kurz, was Sie vorhaben – wir antworten strukturiert und verbindlich.',
        'body'            => '',
        'seo_title'       => 'Kontakt – Elektro Keilitz',
        'seo_description' => 'Kontaktieren Sie Elektro Keilitz – wir melden uns mit klaren nächsten Schritten.',
        'layout'          => 'contact',
    ],

    // Referenzen
    [
        'title'           => 'Referenzen',
        'slug'            => 'referenzen',
        'excerpt'         => 'Ob Neubau, Sanierung oder Modernisierung – hier finden Sie Beispiele unserer fachgerechten Elektroarbeiten.',
        'body'            => '<h2>Referenzen in Vorbereitung</h2><p>Diese Seite wird derzeit mit Referenzprojekten befüllt.</p><p><strong>Geplante Inhalte:</strong></p><ul><li>Fotos und Beschreibungen abgeschlossener Projekte</li><li>Kundenstimmen und Bewertungen</li><li>Projekt-Details zu verschiedenen Leistungsbereichen</li></ul><p>Haben Sie Interesse an einer Beratung? <a href="/kontakt">Kontaktieren Sie uns</a></p>',
        'seo_title'       => 'Referenzen – Elektro Keilitz',
        'seo_description' => 'Beispiele unserer erfolgreich umgesetzten Elektroprojekte: Elektroinstallation, Photovoltaik, Smart Home und mehr.',
        'layout'          => 'references',
    ],

    // Impressum
    [
        'title'           => 'Impressum',
        'slug'            => 'impressum',
        'badge'           => 'Rechtliches',
        'excerpt'         => 'Diese Seite ist ein Platzhalter und wird nach juristischer Prüfung befüllt.',
        'body'            => '<h2>Inhalt folgt</h2><p>Platzhalter: Impressum wird hier ergänzt.</p>',
        'seo_title'       => 'Impressum – Elektro Keilitz',
        'seo_description' => 'Rechtliche Angaben (Platzhalter) – wird juristisch finalisiert.',
        'layout'          => 'default',
    ],

    // Datenschutz
    [
        'title'           => 'Datenschutz',
        'slug'            => 'datenschutz',
        'badge'           => 'Rechtliches',
        'excerpt'         => 'Diese Seite ist ein Platzhalter und wird nach juristischer Prüfung befüllt.',
        'body'            => '<h2>Inhalt folgt</h2><p>Platzhalter: Datenschutzhinweise werden hier ergänzt.</p>',
        'seo_title'       => 'Datenschutz – Elektro Keilitz',
        'seo_description' => 'Datenschutzhinweise (Platzhalter) – wird juristisch finalisiert.',
        'layout'          => 'default',
    ],
];

foreach ($pages as $page) {
    $id = insertContent($pdo, $table, 'page', 'de', $page, $userId, $now);
    echo "  ✓ Seite: {$page['title']} (ID: {$id})\n";
}

echo "\n═══ Import abgeschlossen ═══\n";

// Zusammenfassung
$totalServices = count($services);
$totalPages    = count($pages);
$totalEntries  = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();

echo "\nZusammenfassung:\n";
echo "  - {$totalServices} Leistungsseiten importiert\n";
echo "  - 1 Hero-Slides Snippet importiert\n";
echo "  - {$totalPages} Standardseiten importiert\n";
echo "  - {$totalEntries} Einträge insgesamt in der Datenbank\n";
echo "\n✓ Fertig.\n";
