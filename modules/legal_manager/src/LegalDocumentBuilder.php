<?php

declare(strict_types=1);

/**
 * Legal Manager – Document Builder
 *
 * Baut vollständige Rechtstexte (HTML) aus einzelnen Blöcken zusammen
 * und ersetzt Stammdaten-Platzhalter.
 */

namespace LegalManager;

final class LegalDocumentBuilder
{
    private LegalService $service;

    public function __construct(LegalService $service)
    {
        $this->service = $service;
    }

    /**
     * Datenschutzseite aus aktiven Blöcken zusammensetzen.
     *
     * @param string $locale  Sprache
     * @param callable|null $t  Übersetzungsfunktion – erhält einen Schlüssel, gibt Text zurück
     */
    public function buildPrivacyPage(string $locale = 'de', ?callable $t = null): string
    {
        return $this->buildDocument('privacy', $locale, $t);
    }

    /**
     * Impressumsseite aus aktiven Blöcken zusammensetzen.
     */
    public function buildImprintPage(string $locale = 'de', ?callable $t = null): string
    {
        return $this->buildDocument('imprint', $locale, $t);
    }

    /* ---------------------------------------------------------------- */

    private function buildDocument(string $documentType, string $locale, ?callable $t): string
    {
        $blocks   = $this->service->getBlocks($documentType, $locale);
        $baseData = $this->service->getBaseData($locale);

        if (empty($blocks)) {
            return '';
        }

        $html = '<div class="legal-document legal-' . htmlspecialchars($documentType, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        foreach ($blocks as $block) {
            if ((int) ($block['is_active'] ?? 0) !== 1) {
                continue;
            }

            $title   = trim((string) ($block['title'] ?? ''));
            $content = trim((string) ($block['content'] ?? ''));
            $key     = trim((string) ($block['block_key'] ?? ''));

            // Titel: expliziter Blocktitel > i18n-Schlüssel > Block-Key als Fallback
            if ($title === '' && $t !== null && $key !== '') {
                $i18nKey = "legal.block_{$key}";
                $resolved = $t($i18nKey);
                if ($resolved !== $i18nKey) {
                    $title = $resolved;
                }
            }
            if ($title === '' && $key !== '') {
                $title = ucfirst(str_replace('_', ' ', $key));
            }

            // Platzhalter in Inhalt ersetzen
            $content = $this->replacePlaceholders($content, $baseData);

            $safeKey = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
            $html .= '<section class="legal-block" id="legal-block-' . $safeKey . '">' . "\n";
            if ($title !== '') {
                $html .= '  <h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>' . "\n";
            }
            if ($content !== '') {
                $html .= '  <div class="legal-block-content">' . "\n" . $content . "\n" . '  </div>' . "\n";
            }
            $html .= '</section>' . "\n";
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Ersetzt {{field_key}}-Platzhalter durch die zugehörigen Stammdaten.
     */
    private function replacePlaceholders(string $text, array $baseData): string
    {
        if ($text === '' || empty($baseData)) {
            return $text;
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $m) use ($baseData): string {
                $key = $m[1];
                return htmlspecialchars((string) ($baseData[$key] ?? $m[0]), ENT_QUOTES, 'UTF-8');
            },
            $text
        ) ?? $text;
    }

    /**
     * Erzeugt einen HTML-Snapshot des aktuellen Dokumentstands (für Versionierung).
     */
    public function buildSnapshot(string $documentType, string $locale = 'de', ?callable $t = null): string
    {
        $html = $this->buildDocument($documentType, $locale, $t);
        $meta = '<meta name="generator" content="Chamy Legal Manager">' . "\n";
        $meta .= '<meta name="document-type" content="' . htmlspecialchars($documentType, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $meta .= '<meta name="locale" content="' . htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $meta .= '<meta name="generated-at" content="' . date('Y-m-d H:i:s') . '">';
        return "<!-- Legal Manager Snapshot -->\n{$meta}\n{$html}";
    }
}
