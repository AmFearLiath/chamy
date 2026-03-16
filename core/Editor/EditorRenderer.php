<?php

declare(strict_types=1);

namespace Chamy\Core\Editor;

use Chamy\Core\Kernel;

/**
 * Renders editor content for preview purposes.
 *
 * Two modes:
 *  1. Basic HTML rendering (fallback – always available)
 *  2. Theme-aware rendering via Twig (when frontend theme has editor templates)
 */
final class EditorRenderer
{
    public function __construct(private readonly Kernel $kernel) {}

    /**
     * Render the full editor tree as preview HTML.
     */
    public function render(array $editorData, string $contentType = 'page'): string
    {
        $root = $editorData['root'] ?? null;
        if (!$root) {
            return '<div class="editor-preview-empty">Kein Inhalt vorhanden</div>';
        }

        $bodyHtml = $this->renderNode($root);

        // Wrap in a minimal HTML document with frontend theme styles
        $themeManager = $this->kernel->themes();
        $themePath = $themeManager->getFrontendThemePath();
        $cssPath = '';
        if (file_exists($themePath . '/assets/css/style.css')) {
            $cssPath = '/themes/frontend/' . $themeManager->getFrontendThemeId() . '/assets/css/style.css';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorschau</title>
    {$this->renderPreviewStyles()}
    {$this->renderThemeCss($cssPath)}
</head>
<body class="editor-preview-body">
    <div class="editor-preview-wrapper">
        {$bodyHtml}
    </div>
</body>
</html>
HTML;
    }

    private function renderNode(array $node): string
    {
        $type = $node['type'] ?? 'unknown';
        $definition = $node['definition'] ?? '';
        $props = $node['props'] ?? [];
        $children = $node['children'] ?? [];

        $childrenHtml = '';
        foreach ($children as $child) {
            $childrenHtml .= $this->renderNode($child);
        }

        return match ($type) {
            'root' => '<div class="preview-root">' . $childrenHtml . '</div>',
            'layout' => $this->renderLayout($definition, $props, $childrenHtml),
            'block' => $this->renderBlock($definition, $props),
            'component' => $this->renderComponent($definition, $props, $childrenHtml),
            'snippet' => $this->renderSnippet($definition, $props),
            default => '<div class="preview-unknown">[' . $this->esc($type) . ']</div>',
        };
    }

    private function renderLayout(string $definition, array $props, string $childrenHtml): string
    {
        $classes = ['preview-layout', 'preview-layout--' . $this->esc($definition)];
        $styles = [];

        if (($props['_padding'] ?? 'none') !== 'none') {
            $padMap = ['sm' => '16px', 'md' => '32px', 'lg' => '48px', 'xl' => '64px'];
            $styles[] = 'padding:' . ($padMap[$props['_padding']] ?? '0');
        }
        if (($props['background'] ?? 'none') !== 'none') {
            $bgMap = ['light' => '#f8f9fa', 'dark' => '#1a1a2e', 'accent' => '#2c2c54'];
            $styles[] = 'background:' . ($bgMap[$props['background']] ?? 'transparent');
        }

        $styleAttr = !empty($styles) ? ' style="' . implode(';', $styles) . '"' : '';

        return match ($definition) {
            'section' => '<section class="' . implode(' ', $classes) . '"' . $styleAttr . '>' . $childrenHtml . '</section>',
            'container' => '<div class="' . implode(' ', $classes) . ' preview-container"' . $styleAttr . '>' . $childrenHtml . '</div>',
            'grid' => $this->renderGrid($props, $childrenHtml, $styleAttr),
            'columns' => $this->renderColumns($props, $childrenHtml, $styleAttr),
            default => '<div class="' . implode(' ', $classes) . '"' . $styleAttr . '>' . $childrenHtml . '</div>',
        };
    }

    private function renderGrid(array $props, string $childrenHtml, string $styleAttr): string
    {
        $cols = (int) ($props['columns'] ?? 2);
        $gapMap = ['none' => '0', 'sm' => '12px', 'md' => '24px', 'lg' => '40px'];
        $gap = $gapMap[$props['gap'] ?? 'md'] ?? '24px';

        return '<div class="preview-layout preview-layout--grid" style="display:grid;grid-template-columns:repeat(' . $cols . ',1fr);gap:' . $gap . '"' . $styleAttr . '>' . $childrenHtml . '</div>';
    }

    private function renderColumns(array $props, string $childrenHtml, string $styleAttr): string
    {
        $ratioMap = [
            '1:1' => '1fr 1fr',
            '1:2' => '1fr 2fr',
            '2:1' => '2fr 1fr',
            '1:1:1' => '1fr 1fr 1fr',
        ];
        $template = $ratioMap[$props['ratio'] ?? '1:1'] ?? '1fr 1fr';

        return '<div class="preview-layout preview-layout--columns" style="display:grid;grid-template-columns:' . $template . ';gap:24px"' . $styleAttr . '>' . $childrenHtml . '</div>';
    }

    private function renderBlock(string $definition, array $props): string
    {
        return match ($definition) {
            'text' => '<div class="preview-block preview-text" style="text-align:' . $this->esc($props['alignment'] ?? 'left') . '">' . ($props['content'] ?? '') . '</div>',
            'heading' => $this->renderHeading($props),
            'image' => $this->renderImage($props),
            'video' => $this->renderVideo($props),
            'button' => $this->renderButton($props),
            'spacer' => $this->renderSpacer($props),
            'divider' => '<hr class="preview-divider" style="border-style:' . $this->esc($props['style'] ?? 'solid') . '">',
            default => '<div class="preview-block">[' . $this->esc($definition) . ']</div>',
        };
    }

    private function renderHeading(array $props): string
    {
        $level = $props['level'] ?? 'h2';
        $tag = in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? $level : 'h2';
        $align = $this->esc($props['alignment'] ?? 'left');
        return '<' . $tag . ' class="preview-heading" style="text-align:' . $align . '">' . $this->esc($props['text'] ?? '') . '</' . $tag . '>';
    }

    private function renderImage(array $props): string
    {
        $src = $this->esc($props['src'] ?? '');
        if (!$src) {
            return '<div class="preview-image-placeholder">Bild</div>';
        }
        $alt = $this->esc($props['alt'] ?? '');
        $caption = $props['caption'] ?? '';
        $html = '<figure class="preview-image"><img src="' . $src . '" alt="' . $alt . '" style="max-width:100%">';
        if ($caption) {
            $html .= '<figcaption>' . $this->esc($caption) . '</figcaption>';
        }
        return $html . '</figure>';
    }

    private function renderVideo(array $props): string
    {
        $url = $this->esc($props['url'] ?? '');
        if (!$url) {
            return '<div class="preview-video-placeholder">Video</div>';
        }
        return '<div class="preview-video"><a href="' . $url . '">' . $url . '</a></div>';
    }

    private function renderButton(array $props): string
    {
        $label = $this->esc($props['label'] ?? 'Button');
        $url = $this->esc($props['url'] ?? '#');
        $variant = $props['variant'] ?? 'primary';
        $size = $props['size'] ?? 'md';
        $sizeMap = ['sm' => '8px 16px', 'md' => '10px 24px', 'lg' => '14px 32px'];
        $padding = $sizeMap[$size] ?? '10px 24px';

        return '<div class="preview-button-wrapper" style="text-align:center"><a class="preview-button preview-button--' . $this->esc($variant) . '" href="' . $url . '" style="display:inline-block;padding:' . $padding . ';text-decoration:none">' . $label . '</a></div>';
    }

    private function renderSpacer(array $props): string
    {
        $heightMap = ['sm' => '16px', 'md' => '32px', 'lg' => '64px', 'xl' => '96px'];
        $h = $heightMap[$props['height'] ?? 'md'] ?? '32px';
        return '<div class="preview-spacer" style="height:' . $h . '"></div>';
    }

    private function renderComponent(string $definition, array $props, string $childrenHtml): string
    {
        return match ($definition) {
            'hero' => $this->renderHero($props),
            'cta' => $this->renderCta($props),
            default => '<div class="preview-component preview-component--' . $this->esc($definition) . '">' . $childrenHtml . '</div>',
        };
    }

    private function renderHero(array $props): string
    {
        $title = $this->esc($props['title'] ?? '');
        $subtitle = $this->esc($props['subtitle'] ?? '');
        $align = $this->esc($props['alignment'] ?? 'center');
        $bgImage = $props['backgroundImage'] ?? '';
        $bgStyle = $bgImage ? 'background-image:url(' . $this->esc($bgImage) . ');background-size:cover;background-position:center;' : '';

        $html = '<div class="preview-hero" style="text-align:' . $align . ';padding:60px 24px;' . $bgStyle . '">';
        $html .= '<h1>' . $title . '</h1>';
        if ($subtitle) $html .= '<p style="font-size:18px;opacity:0.8">' . $subtitle . '</p>';
        if (!empty($props['ctaLabel'])) {
            $html .= '<a href="' . $this->esc($props['ctaUrl'] ?? '#') . '" style="display:inline-block;padding:12px 28px;margin-top:20px">' . $this->esc($props['ctaLabel']) . '</a>';
        }
        return $html . '</div>';
    }

    private function renderCta(array $props): string
    {
        $title = $this->esc($props['title'] ?? '');
        $text = $this->esc($props['text'] ?? $props['description'] ?? '');

        $html = '<div class="preview-cta" style="text-align:center;padding:40px 24px">';
        $html .= '<h2>' . $title . '</h2>';
        if ($text) $html .= '<p>' . $text . '</p>';
        if (!empty($props['buttonLabel'])) {
            $html .= '<a href="' . $this->esc($props['buttonUrl'] ?? '#') . '" style="display:inline-block;padding:10px 24px;margin-top:16px">' . $this->esc($props['buttonLabel']) . '</a>';
        }
        return $html . '</div>';
    }

    private function renderSnippet(string $definition, array $props): string
    {
        return match ($definition) {
            'infobox' => $this->renderInfobox($props),
            'notice' => '<div class="preview-notice preview-notice--' . $this->esc($props['variant'] ?? 'default') . '">' . $this->esc($props['text'] ?? '') . '</div>',
            'contact_short' => $this->renderContactShort($props),
            default => '<div class="preview-snippet">[' . $this->esc($definition) . ']</div>',
        };
    }

    private function renderInfobox(array $props): string
    {
        $type = $this->esc($props['type'] ?? 'info');
        $title = $this->esc($props['title'] ?? '');
        $text = $props['text'] ?? $props['content'] ?? '';

        $html = '<div class="preview-infobox preview-infobox--' . $type . '" style="padding:16px;border-left:4px solid;margin:12px 0">';
        if ($title) $html .= '<strong>' . $title . '</strong><br>';
        $html .= $this->esc($text);
        return $html . '</div>';
    }

    private function renderContactShort(array $props): string
    {
        $parts = [];
        if (!empty($props['name'])) $parts[] = '<strong>' . $this->esc($props['name']) . '</strong>';
        if (!empty($props['email'])) $parts[] = $this->esc($props['email']);
        if (!empty($props['phone'])) $parts[] = $this->esc($props['phone']);
        return '<div class="preview-contact-short">' . implode(' · ', $parts) . '</div>';
    }

    private function renderPreviewStyles(): string
    {
        return <<<CSS
<style>
    body.editor-preview-body { margin:0; padding:0; font-family:Inter,-apple-system,system-ui,sans-serif; color:#222; background:#fff; }
    .editor-preview-wrapper { max-width:1200px; margin:0 auto; padding:24px; }
    .preview-container { max-width:960px; margin:0 auto; }
    .preview-heading { margin:0.8em 0 0.4em; }
    .preview-text { line-height:1.7; margin-bottom:1em; }
    .preview-image img { border-radius:4px; }
    .preview-image-placeholder, .preview-video-placeholder { padding:40px; text-align:center; background:#f0f0f0; border-radius:4px; color:#888; }
    .preview-divider { border:none; border-top-width:1px; margin:24px 0; color:#ddd; }
    .preview-button { background:#2563eb; color:#fff; border-radius:6px; font-weight:600; }
    .preview-button--secondary { background:#6b7280; }
    .preview-button--ghost { background:transparent; border:1px solid #2563eb; color:#2563eb; }
    .preview-hero { background:#1a1a2e; color:#fff; border-radius:8px; }
    .preview-cta { background:#f8f9fa; border-radius:8px; }
    .preview-infobox { border-radius:4px; background:#f0f8ff; }
    .preview-infobox--warning { background:#fff8e1; border-color:#ffc107; }
    .preview-infobox--success { background:#e8f5e9; border-color:#4caf50; }
    .preview-infobox--error { background:#ffebee; border-color:#f44336; }
    .preview-notice { padding:12px 16px; background:#f5f5f5; border-radius:4px; font-size:14px; }
    .preview-notice--important { background:#fff3e0; font-weight:600; }
    .preview-contact-short { padding:12px; font-size:14px; }
    .preview-empty { padding:40px; text-align:center; color:#888; }
</style>
CSS;
    }

    private function renderThemeCss(string $cssPath): string
    {
        if (!$cssPath) {
            return '';
        }
        return '<link rel="stylesheet" href="' . $this->esc($cssPath) . '">';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // ═══════════════════════════════════════════════════════════
    //  Frontend rendering – produces CSS-class-based HTML
    //  fragments for use inside the frontend theme templates.
    // ═══════════════════════════════════════════════════════════

    /**
     * Render editor data as frontend-ready HTML (no document wrapper).
     */
    public function renderFrontendHtml(array $editorData): string
    {
        $root = $editorData['root'] ?? null;
        if (!$root) {
            return '';
        }
        return $this->fe($root);
    }

    private function fe(array $node): string
    {
        $type       = $node['type'] ?? 'unknown';
        $definition = $node['definition'] ?? '';
        $props      = $node['props'] ?? [];
        $children   = $node['children'] ?? [];

        $childrenHtml = '';
        foreach ($children as $child) {
            $childrenHtml .= $this->fe($child);
        }

        return match ($type) {
            'root'      => $childrenHtml,
            'layout'    => $this->feLayout($definition, $props, $childrenHtml),
            'block'     => $this->feBlock($definition, $props),
            'component' => $this->feComponent($definition, $props, $childrenHtml),
            'snippet'   => $this->feSnippet($definition, $props),
            default     => $childrenHtml,
        };
    }

    // ─── Frontend Layouts ─────────────────────────────────────

    private function feLayout(string $def, array $p, string $ch): string
    {
        return match ($def) {
            'marketing_section' => $this->feMkSection($p, $ch),
            'two_columns'       => $this->feColumns($p, $ch, 2),
            'three_columns'     => $this->feColumns($p, $ch, 3),
            'four_columns'      => $this->feColumns($p, $ch, 4),
            'section'           => '<section class="chamy-section chamy-section--default"><div class="chamy-container">' . $ch . '</div></section>',
            'container'         => '<div class="chamy-container">' . $ch . '</div>',
            'grid'              => '<div class="chamy-grid chamy-grid--' . (int)($p['columns'] ?? 2) . '">' . $ch . '</div>',
            'columns'           => $this->feColumns($p, $ch, 2),
            default             => '<div class="chamy-layout">' . $ch . '</div>',
        };
    }

    private function feMkSection(array $p, string $ch): string
    {
        $bg      = $this->esc($p['bg_style'] ?? 'default');
        $pad     = $this->esc($p['padding'] ?? 'lg');
        $maxW    = $this->esc($p['max_width'] ?? 'default');
        $anchor  = !empty($p['anchor_id']) ? ' id="' . $this->esc($p['anchor_id']) . '"' : '';
        $bgImg   = !empty($p['bg_image']) ? ' style="background-image:url(\'' . $this->esc($p['bg_image']) . '\')"' : '';

        return '<section class="chamy-section chamy-section--' . $bg . ' chamy-pad--' . $pad . '"' . $anchor . $bgImg . '>'
             . '<div class="chamy-container chamy-container--' . $maxW . '">' . $ch . '</div></section>';
    }

    private function feColumns(array $p, string $ch, int $cols): string
    {
        $ratio   = $this->esc($p['ratio'] ?? ($cols . '-equal'));
        $gap     = $this->esc($p['gap'] ?? 'lg');
        $align   = $this->esc($p['align'] ?? 'center');
        $reverse = ($p['reverse_mobile'] ?? 'normal') === 'reverse' ? ' chamy-cols--reverse' : '';

        return '<div class="chamy-cols chamy-cols--' . $cols . ' chamy-cols-ratio--' . $ratio . ' chamy-gap--' . $gap . ' chamy-align--' . $align . $reverse . '">'
             . $ch . '</div>';
    }

    // ─── Frontend Blocks ──────────────────────────────────────

    private function feBlock(string $def, array $p): string
    {
        return match ($def) {
            'rich_text'         => $this->feRichText($p),
            'marketing_heading' => $this->feMkHeading($p),
            'marketing_image'   => $this->feMkImage($p),
            'marketing_button'  => $this->feMkButton($p),
            'marketing_spacer'  => '<div class="chamy-spacer chamy-spacer--' . $this->esc($p['size'] ?? 'md') . '"></div>',
            'code_block'        => $this->feCodeBlock($p),
            'icon_text'         => $this->feIconText($p),
            'button_group'      => $this->feButtonGroup($p),
            'text'              => '<div class="chamy-text">' . ($p['content'] ?? '') . '</div>',
            'heading'           => $this->feSimpleHeading($p),
            'image'             => $this->feMkImage($p),
            'button'            => $this->feMkButton($p),
            'spacer'            => '<div class="chamy-spacer chamy-spacer--' . $this->esc($p['height'] ?? $p['size'] ?? 'md') . '"></div>',
            'divider'           => '<hr class="chamy-divider">',
            default             => '',
        };
    }

    private function feRichText(array $p): string
    {
        $align = $this->esc($p['align'] ?? 'left');
        return '<div class="chamy-text chamy-text--' . $align . '">' . ($p['content'] ?? '') . '</div>';
    }

    private function feMkHeading(array $p): string
    {
        $tag   = in_array($p['level'] ?? 'h2', ['h1','h2','h3'], true) ? $p['level'] : 'h2';
        $align = $this->esc($p['align'] ?? 'center');
        $size  = $this->esc($p['size'] ?? 'lg');
        $badge = !empty($p['badge']) ? '<span class="chamy-badge">' . $this->esc($p['badge']) . '</span>' : '';
        $sub   = !empty($p['subtitle']) ? '<p class="chamy-subtitle">' . $this->esc($p['subtitle']) . '</p>' : '';

        return '<div class="chamy-heading chamy-heading--' . $size . ' chamy-text--' . $align . '">'
             . $badge
             . '<' . $tag . ' class="chamy-heading__title">' . $this->esc($p['title'] ?? '') . '</' . $tag . '>'
             . $sub . '</div>';
    }

    private function feSimpleHeading(array $p): string
    {
        $tag = in_array($p['level'] ?? 'h2', ['h1','h2','h3','h4','h5','h6'], true) ? $p['level'] : 'h2';
        return '<' . $tag . ' class="chamy-heading__title">' . $this->esc($p['text'] ?? $p['title'] ?? '') . '</' . $tag . '>';
    }

    private function feMkImage(array $p): string
    {
        $src = $this->esc($p['src'] ?? '');
        if (!$src) return '';
        $alt     = $this->esc($p['alt'] ?? '');
        $style   = $this->esc($p['style'] ?? 'rounded');
        $maxW    = $this->esc($p['max_width'] ?? 'full');
        $caption = $p['caption'] ?? '';

        $html = '<figure class="chamy-image chamy-image--' . $style . ' chamy-image-w--' . $maxW . '">'
              . '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
        if ($caption) {
            $html .= '<figcaption>' . $this->esc($caption) . '</figcaption>';
        }
        return $html . '</figure>';
    }

    private function feMkButton(array $p): string
    {
        $label  = $this->esc($p['label'] ?? 'Button');
        $url    = $this->esc($p['url'] ?? '#');
        $style  = $this->esc($p['style'] ?? $p['variant'] ?? 'primary');
        $size   = $this->esc($p['size'] ?? 'lg');
        $target = ($p['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';

        return '<div class="chamy-btn-wrap"><a class="chamy-btn chamy-btn--' . $style . ' chamy-btn--' . $size . '" href="' . $url . '"' . $target . '>' . $label . '</a></div>';
    }

    private function feCodeBlock(array $p): string
    {
        $code = $this->esc($p['code'] ?? '');
        $lang = $this->esc($p['language'] ?? '');
        $file = !empty($p['filename']) ? '<div class="chamy-code__filename">' . $this->esc($p['filename']) . '</div>' : '';

        return '<div class="chamy-code" data-lang="' . $lang . '">' . $file . '<pre><code>' . $code . '</code></pre></div>';
    }

    private function feIconText(array $p): string
    {
        return '<div class="chamy-icon-text">'
             . '<span class="chamy-icon-text__icon">' . $this->esc($p['icon'] ?? '') . '</span>'
             . '<div><strong class="chamy-icon-text__title">' . $this->esc($p['title'] ?? '') . '</strong>'
             . (!empty($p['text']) ? '<p>' . $this->esc($p['text']) . '</p>' : '')
             . '</div></div>';
    }

    private function feButtonGroup(array $p): string
    {
        $buttons = $p['buttons'] ?? [];
        if (is_string($buttons)) $buttons = json_decode($buttons, true) ?? [];
        $align = $this->esc($p['align'] ?? 'center');

        $html = '<div class="chamy-btn-group chamy-text--' . $align . '">';
        foreach ($buttons as $b) {
            $style = $this->esc($b['style'] ?? 'primary');
            $html .= '<a class="chamy-btn chamy-btn--' . $style . ' chamy-btn--lg" href="' . $this->esc($b['url'] ?? '#') . '">' . $this->esc($b['label'] ?? '') . '</a>';
        }
        return $html . '</div>';
    }

    // ─── Frontend Components ──────────────────────────────────

    private function feComponent(string $def, array $p, string $ch): string
    {
        return match ($def) {
            'hero_banner'      => $this->feHeroBanner($p),
            'feature_card'     => $this->feFeatureCard($p),
            'stats_counter'    => $this->feStatsCounter($p),
            'testimonial_card' => $this->feTestimonial($p),
            'pricing_card'     => $this->fePricingCard($p),
            'logo_cloud'       => $this->feLogoCloud($p),
            'faq_accordion'    => $this->feFaqAccordion($p),
            'cta_banner'       => $this->feCtaBanner($p),
            'comparison_table' => $this->feComparisonTable($p),
            'hero'             => $this->feHeroBanner($p),
            'cta'              => $this->feCtaBanner($p),
            default             => '<div class="chamy-component">' . $ch . '</div>',
        };
    }

    private function feHeroBanner(array $p): string
    {
        $bg    = $this->esc($p['bg_style'] ?? 'gradient');
        $badge = !empty($p['badge']) ? '<span class="chamy-badge">' . $this->esc($p['badge']) . '</span>' : '';
        $img   = !empty($p['image']) ? '<div class="chamy-hero__visual"><img src="' . $this->esc($p['image']) . '" alt="" loading="lazy"></div>' : '';

        $btns = '';
        if (!empty($p['primary_cta_label'])) {
            $btns .= '<a class="chamy-btn chamy-btn--primary chamy-btn--xl" href="' . $this->esc($p['primary_cta_url'] ?? '#') . '">' . $this->esc($p['primary_cta_label']) . '</a>';
        }
        if (!empty($p['secondary_cta_label'])) {
            $btns .= '<a class="chamy-btn chamy-btn--outline chamy-btn--xl" href="' . $this->esc($p['secondary_cta_url'] ?? '#') . '">' . $this->esc($p['secondary_cta_label']) . '</a>';
        }
        $btnsWrap = $btns ? '<div class="chamy-hero__actions">' . $btns . '</div>' : '';

        return '<section class="chamy-hero chamy-hero--' . $bg . '">'
             . '<div class="chamy-container">'
             . '<div class="chamy-hero__content">'
             . $badge
             . '<h1 class="chamy-hero__title">' . $this->esc($p['title'] ?? '') . '</h1>'
             . (!empty($p['subtitle']) ? '<p class="chamy-hero__subtitle">' . $this->esc($p['subtitle']) . '</p>' : '')
             . $btnsWrap
             . '</div>'
             . $img
             . '</div></section>';
    }

    private function feFeatureCard(array $p): string
    {
        $style = $this->esc($p['style'] ?? 'glass');
        $link  = '';
        if (!empty($p['link_url'])) {
            $link = '<a class="chamy-feature__link" href="' . $this->esc($p['link_url']) . '">' . $this->esc($p['link_label'] ?? 'Mehr erfahren →') . '</a>';
        }

        return '<div class="chamy-feature chamy-feature--' . $style . '">'
             . '<div class="chamy-feature__icon">' . $this->esc($p['icon'] ?? '') . '</div>'
             . '<h3 class="chamy-feature__title">' . $this->esc($p['title'] ?? '') . '</h3>'
             . '<p class="chamy-feature__text">' . $this->esc($p['text'] ?? '') . '</p>'
             . $link . '</div>';
    }

    private function feStatsCounter(array $p): string
    {
        $prefix = $this->esc($p['prefix'] ?? '');
        $value  = $this->esc($p['value'] ?? '0');
        $suffix = $this->esc($p['suffix'] ?? '');

        return '<div class="chamy-stat">'
             . '<div class="chamy-stat__value">' . $prefix . $value . $suffix . '</div>'
             . '<div class="chamy-stat__label">' . $this->esc($p['label'] ?? '') . '</div>'
             . '</div>';
    }

    private function feTestimonial(array $p): string
    {
        $stars = '';
        $rating = (int)($p['rating'] ?? 5);
        if ($rating > 0) {
            $stars = '<div class="chamy-testimonial__stars">' . str_repeat('★', $rating) . '</div>';
        }

        return '<div class="chamy-testimonial">'
             . $stars
             . '<blockquote class="chamy-testimonial__quote">' . $this->esc($p['quote'] ?? '') . '</blockquote>'
             . '<div class="chamy-testimonial__author">'
             . '<strong>' . $this->esc($p['author'] ?? '') . '</strong>'
             . (!empty($p['role']) ? '<span>' . $this->esc($p['role']) . (!empty($p['company']) ? ', ' . $this->esc($p['company']) : '') . '</span>' : '')
             . '</div></div>';
    }

    private function fePricingCard(array $p): string
    {
        $hl    = ($p['highlighted'] ?? 'no') === 'yes' ? ' chamy-pricing--highlighted' : '';
        $badge = !empty($p['badge']) ? '<span class="chamy-pricing__badge">' . $this->esc($p['badge']) . '</span>' : '';
        $features = $p['features'] ?? [];
        if (is_string($features)) $features = json_decode($features, true) ?? [];

        $listHtml = '';
        foreach ($features as $f) {
            $text = is_string($f) ? $f : ($f['text'] ?? $f['label'] ?? '');
            $listHtml .= '<li>' . $this->esc($text) . '</li>';
        }

        return '<div class="chamy-pricing' . $hl . '">'
             . $badge
             . '<h3 class="chamy-pricing__plan">' . $this->esc($p['plan_name'] ?? '') . '</h3>'
             . '<div class="chamy-pricing__price">' . $this->esc($p['price'] ?? '') . '<span>' . $this->esc($p['period'] ?? '') . '</span></div>'
             . '<ul class="chamy-pricing__features">' . $listHtml . '</ul>'
             . '<a class="chamy-btn chamy-btn--primary chamy-btn--lg chamy-pricing__cta" href="' . $this->esc($p['cta_url'] ?? '#') . '">' . $this->esc($p['cta_label'] ?? 'Auswählen') . '</a>'
             . '</div>';
    }

    private function feLogoCloud(array $p): string
    {
        $title = !empty($p['title']) ? '<p class="chamy-logos__title">' . $this->esc($p['title']) . '</p>' : '';
        $logos = $p['logos'] ?? [];
        if (is_string($logos)) $logos = json_decode($logos, true) ?? [];

        $items = '';
        foreach ($logos as $l) {
            $name = is_string($l) ? $l : ($l['name'] ?? $l['label'] ?? '');
            $icon = is_string($l) ? '' : ($l['icon'] ?? '');
            $items .= '<span class="chamy-logos__item">' . ($icon ? $this->esc($icon) . ' ' : '') . $this->esc($name) . '</span>';
        }

        return '<div class="chamy-logos">' . $title . '<div class="chamy-logos__grid">' . $items . '</div></div>';
    }

    private function feFaqAccordion(array $p): string
    {
        $items = $p['items'] ?? [];
        if (is_string($items)) $items = json_decode($items, true) ?? [];

        $html = '<div class="chamy-faq">';
        foreach ($items as $item) {
            $q = $this->esc($item['q'] ?? '');
            $a = $this->esc($item['a'] ?? '');
            $html .= '<details class="chamy-faq__item"><summary>' . $q . '</summary><div class="chamy-faq__answer">' . $a . '</div></details>';
        }
        return $html . '</div>';
    }

    private function feCtaBanner(array $p): string
    {
        $style = $this->esc($p['style'] ?? 'gradient');
        return '<div class="chamy-cta chamy-cta--' . $style . '">'
             . '<h2 class="chamy-cta__title">' . $this->esc($p['title'] ?? '') . '</h2>'
             . (!empty($p['text']) ? '<p class="chamy-cta__text">' . $this->esc($p['text'] ?? $p['description'] ?? '') . '</p>' : '')
             . '<a class="chamy-btn chamy-btn--primary chamy-btn--xl" href="' . $this->esc($p['cta_url'] ?? $p['buttonUrl'] ?? '#') . '">'
             . $this->esc($p['cta_label'] ?? $p['buttonLabel'] ?? 'Jetzt starten') . '</a>'
             . '</div>';
    }

    private function feComparisonTable(array $p): string
    {
        $columns = $p['columns'] ?? [];
        $rows    = $p['rows'] ?? [];
        if (is_string($columns)) $columns = json_decode($columns, true) ?? [];
        if (is_string($rows)) $rows = json_decode($rows, true) ?? [];

        $html = '<div class="chamy-comparison"><table><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . $this->esc(is_string($col) ? $col : ($col['label'] ?? '')) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $cells = is_array($row) ? $row : [];
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $val = is_string($cell) ? $cell : ($cell['value'] ?? '');
                if ($val === 'true' || $val === '✓') $val = '<span class="chamy-check">✓</span>';
                elseif ($val === 'false' || $val === '✗') $val = '<span class="chamy-cross">✗</span>';
                else $val = $this->esc($val);
                $html .= '<td>' . $val . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div>';
    }

    // ─── Frontend Snippets ────────────────────────────────────

    private function feSnippet(string $def, array $p): string
    {
        return match ($def) {
            'badge_row'        => $this->feBadgeRow($p),
            'tech_stack_item'  => $this->feTechStack($p),
            'divider_with_text'=> $this->feDividerText($p),
            'announcement_bar' => $this->feAnnouncementBar($p),
            'infobox'          => '<div class="chamy-infobox chamy-infobox--' . $this->esc($p['type'] ?? 'info') . '">'
                                . (!empty($p['title']) ? '<strong>' . $this->esc($p['title']) . '</strong> ' : '')
                                . $this->esc($p['text'] ?? $p['content'] ?? '') . '</div>',
            'notice'           => '<div class="chamy-notice">' . $this->esc($p['text'] ?? '') . '</div>',
            default            => '',
        };
    }

    private function feBadgeRow(array $p): string
    {
        $badges = $p['badges'] ?? [];
        if (is_string($badges)) $badges = json_decode($badges, true) ?? [];
        $align = $this->esc($p['align'] ?? 'center');

        $html = '<div class="chamy-badges chamy-text--' . $align . '">';
        foreach ($badges as $b) {
            $label = $this->esc(is_string($b) ? $b : ($b['label'] ?? ''));
            $color = $this->esc(is_string($b) ? 'accent' : ($b['color'] ?? 'accent'));
            $html .= '<span class="chamy-badge chamy-badge--' . $color . '">' . $label . '</span>';
        }
        return $html . '</div>';
    }

    private function feTechStack(array $p): string
    {
        return '<div class="chamy-tech">'
             . '<span class="chamy-tech__icon">' . $this->esc($p['icon'] ?? '') . '</span>'
             . '<div>'
             . '<strong>' . $this->esc($p['name'] ?? '') . '</strong>'
             . (!empty($p['version']) ? ' <span class="chamy-tech__version">' . $this->esc($p['version']) . '</span>' : '')
             . (!empty($p['description']) ? '<p class="chamy-tech__desc">' . $this->esc($p['description']) . '</p>' : '')
             . '</div></div>';
    }

    private function feDividerText(array $p): string
    {
        $text = $p['text'] ?? $p['icon'] ?? '✦';
        return '<div class="chamy-divider-text"><span>' . $this->esc($text) . '</span></div>';
    }

    private function feAnnouncementBar(array $p): string
    {
        $style = $this->esc($p['style'] ?? 'accent');
        $link  = !empty($p['link_url']) ? ' <a href="' . $this->esc($p['link_url']) . '">' . $this->esc($p['link_label'] ?? 'Mehr →') . '</a>' : '';
        return '<div class="chamy-announce chamy-announce--' . $style . '">' . $this->esc($p['text'] ?? '') . $link . '</div>';
    }
}
