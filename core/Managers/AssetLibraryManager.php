<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class AssetLibraryManager implements ManagerInterface
{
    private string $basePath;
    private string $storageDir;
    private string $storageFile;

    /** @var array{icon_sets: array<int, array<string, mixed>>, font_sets: array<int, array<string, mixed>>, icon_sources: array<int, array<string, mixed>>, source_templates: array<int, array<string, mixed>>} */
    private array $state = [
        'icon_sets' => [],
        'font_sets' => [],
        'icon_sources' => [],
        'source_templates' => [],
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->storageDir = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'assets';
        $this->storageFile = $this->storageDir . DIRECTORY_SEPARATOR . 'libraries.json';
    }

    public function getName(): string
    {
        return 'asset_library';
    }

    public function boot(): void
    {
        $this->load();
    }

    public function listIconSets(): array
    {
        return $this->state['icon_sets'];
    }

    public function listFontSets(): array
    {
        return $this->state['font_sets'];
    }

    public function getGoogleFontCatalog(string $query = ''): array
    {
        $result = $this->searchGoogleFonts([
            'q' => $query,
            'page' => 1,
            'per_page' => 120,
        ]);

        return is_array($result['items'] ?? null) ? $result['items'] : [];
    }

    public function searchGoogleFonts(array $params = []): array
    {
        $q = mb_strtolower(trim((string) ($params['q'] ?? '')));
        $style = strtolower(trim((string) ($params['style'] ?? '')));
        $category = trim((string) ($params['category'] ?? ''));
        $subcategory = trim((string) ($params['subcategory'] ?? ''));
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(5, min(50, (int) ($params['per_page'] ?? 10)));

        $catalog = $this->loadGoogleCatalogCached();
        $installedByFamily = $this->installedFontSetsByFamily();
        $taxonomy = $this->googleFontTaxonomy();

        $normalized = [];
        foreach ($catalog as $row) {
            if (!is_array($row)) {
                continue;
            }
            $family = trim((string) ($row['family'] ?? ''));
            if ($family === '') {
                continue;
            }

            $variants = array_values(array_filter(array_map(
                static fn(mixed $item): string => trim((string) $item),
                is_array($row['variants'] ?? null) ? $row['variants'] : []
            )));
            if ($variants === []) {
                $variants = ['regular'];
            }

            $styleTokens = $this->variantsToStyleTokens($variants);
            $classInfo = $this->classifyGoogleFont($family, (string) ($row['category'] ?? ''), $taxonomy);

            $installedSet = $installedByFamily[mb_strtolower($family)] ?? null;
            $normalized[] = [
                'family' => $family,
                'google_category' => (string) ($row['category'] ?? ''),
                'subsets' => is_array($row['subsets'] ?? null) ? array_values($row['subsets']) : [],
                'variants' => $variants,
                'styles' => $styleTokens,
                'version' => (string) ($row['version'] ?? ''),
                'lastModified' => (string) ($row['lastModified'] ?? ''),
                'category' => $classInfo['category'],
                'subcategory' => $classInfo['subcategory'],
                'installed' => is_array($installedSet),
                'installed_set' => $installedSet,
            ];
        }

        $baseFiltered = array_values(array_filter($normalized, function (array $font) use ($q, $style): bool {
            if ($q !== '' && !str_contains(mb_strtolower((string) ($font['family'] ?? '')), $q)) {
                return false;
            }
            if ($style !== '' && !$this->matchesStyleFilter($font, $style)) {
                return false;
            }
            return true;
        }));

        $categoryCounts = [];
        foreach ($baseFiltered as $font) {
            $cat = (string) ($font['category'] ?? '');
            if ($cat === '') {
                continue;
            }
            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
        }

        $filtered = array_values(array_filter($baseFiltered, static function (array $font) use ($category, $subcategory): bool {
            if ($category !== '' && (string) ($font['category'] ?? '') !== $category) {
                return false;
            }
            if ($subcategory !== '' && (string) ($font['subcategory'] ?? '') !== $subcategory) {
                return false;
            }
            return true;
        }));

        $subcategoryCounts = [];
        if ($category !== '') {
            foreach ($baseFiltered as $font) {
                if ((string) ($font['category'] ?? '') !== $category) {
                    continue;
                }
                $sub = (string) ($font['subcategory'] ?? 'All');
                $subcategoryCounts[$sub] = ($subcategoryCounts[$sub] ?? 0) + 1;
            }
        }

        usort($filtered, static fn(array $a, array $b): int => strcmp((string) ($a['family'] ?? ''), (string) ($b['family'] ?? '')));

        $total = count($filtered);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($filtered, $offset, $perPage);

        return [
            'items' => $slice,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'query' => [
                'q' => $q,
                'style' => $style,
                'category' => $category,
                'subcategory' => $subcategory,
            ],
            'categories' => array_map(static function (array $entry) use ($categoryCounts): array {
                $id = (string) ($entry['id'] ?? '');
                return [
                    'id' => $id,
                    'label' => (string) ($entry['label'] ?? $id),
                    'count' => (int) ($categoryCounts[$id] ?? 0),
                ];
            }, $taxonomy),
            'subcategories' => $category !== ''
                ? array_map(static function (string $sub) use ($subcategoryCounts): array {
                    return [
                        'id' => $sub,
                        'label' => $sub,
                        'count' => (int) ($subcategoryCounts[$sub] ?? 0),
                    ];
                }, $this->subcategoriesForCategory($taxonomy, $category))
                : [],
        ];
    }

    public function knownIconSources(): array
    {
        $sources = [];

        foreach ($this->defaultIconSources() as $source) {
            $url = trim((string) ($source['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $id = $this->slug((string) ($source['id'] ?? ($source['name'] ?? $url)));
            $key = $id !== '' ? $id : md5($url);
            $sources[$key] = [
                'id' => $key,
                'name' => (string) ($source['name'] ?? 'Icon Quelle'),
                'url' => $url,
                'status' => (string) ($source['status'] ?? 'known'),
                'template_id' => (string) ($source['template_id'] ?? ''),
                'package' => (string) ($source['package'] ?? ''),
                'latest_version' => (string) ($source['latest_version'] ?? ''),
                'last_checked' => (string) ($source['last_checked'] ?? ''),
            ];
        }
        
        return array_values($sources);
    }
    public function knownSourceTemplates(): array
    {
        $templates = [];

        foreach ($this->defaultSourceTemplates() as $tpl) {
            $id = $this->slug((string) ($tpl['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $templates[$id] = [
                'id' => $id,
                'name' => (string) ($tpl['name'] ?? $id),
                'type' => (string) ($tpl['type'] ?? 'generic'),
                'url_template' => (string) ($tpl['url_template'] ?? ''),
                'versions_api' => (string) ($tpl['versions_api'] ?? ''),
                'default_path' => (string) ($tpl['default_path'] ?? ''),
                'status' => (string) ($tpl['status'] ?? 'default'),
            ];
        }

        foreach ($this->state['source_templates'] as $tpl) {
            if (!is_array($tpl)) {
                continue;
            }
            $id = $this->slug((string) ($tpl['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $templates[$id] = [
                'id' => $id,
                'name' => (string) ($tpl['name'] ?? $id),
                'type' => (string) ($tpl['type'] ?? 'generic'),
                'url_template' => (string) ($tpl['url_template'] ?? ''),
                'versions_api' => (string) ($tpl['versions_api'] ?? ''),
                'default_path' => (string) ($tpl['default_path'] ?? ''),
                'status' => (string) ($tpl['status'] ?? 'custom'),
            ];
        }

        return array_values($templates);
    }

    public function addSourceTemplate(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $id = $this->slug((string) ($payload['id'] ?? $name));
        $type = trim((string) ($payload['type'] ?? 'generic'));
        $urlTemplate = trim((string) ($payload['url_template'] ?? ''));
        $versionsApi = trim((string) ($payload['versions_api'] ?? ''));
        $defaultPath = trim((string) ($payload['default_path'] ?? ''));

        if ($id === '') {
            return ['success' => false, 'message' => 'Template-ID fehlt.'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Template-Name fehlt.'];
        }
        if ($urlTemplate === '' || !str_contains($urlTemplate, '{package}') || !str_contains($urlTemplate, '{version}')) {
            return ['success' => false, 'message' => 'URL-Template muss {package} und {version} enthalten.'];
        }

        $record = [
            'id' => $id,
            'name' => $name,
            'type' => $type !== '' ? $type : 'generic',
            'url_template' => $urlTemplate,
            'versions_api' => $versionsApi,
            'default_path' => $defaultPath,
            'status' => 'custom',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $updated = false;
        foreach ($this->state['source_templates'] as $idx => $tpl) {
            if ((string) ($tpl['id'] ?? '') !== $id) {
                continue;
            }
            $record['created_at'] = (string) ($tpl['created_at'] ?? date('Y-m-d H:i:s'));
            $this->state['source_templates'][$idx] = $record;
            $updated = true;
            break;
        }

        if (!$updated) {
            $record['created_at'] = date('Y-m-d H:i:s');
            $this->state['source_templates'][] = $record;
        }

        usort($this->state['source_templates'], static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        $this->persist();

        return ['success' => true, 'message' => 'Template gespeichert.', 'template' => $record];
    }

    public function removeSourceTemplate(string $id): bool
    {
        $id = $this->slug($id);
        if ($id === '') {
            return false;
        }

        $before = count($this->state['source_templates']);
        $this->state['source_templates'] = array_values(array_filter(
            $this->state['source_templates'],
            static fn(array $tpl): bool => (string) ($tpl['id'] ?? '') !== $id
        ));

        if (count($this->state['source_templates']) === $before) {
            return false;
        }

        $this->persist();
        return true;
    }

    public function resolveIconSourceByTemplate(array $payload): array
    {
        $templateId = $this->slug((string) ($payload['template_id'] ?? ''));
        $query = trim((string) ($payload['query'] ?? ''));
        $preferredVersion = trim((string) ($payload['version'] ?? ''));
        $preferredPath = trim((string) ($payload['path'] ?? ''));

        if ($templateId === '' || $query === '') {
            return ['success' => false, 'message' => 'Template oder Suchwert fehlt.'];
        }

        $templates = [];
        foreach ($this->knownSourceTemplates() as $tpl) {
            if (!is_array($tpl)) {
                continue;
            }
            $templates[(string) ($tpl['id'] ?? '')] = $tpl;
        }

        $template = $templates[$templateId] ?? null;
        if (!is_array($template)) {
            return ['success' => false, 'message' => 'Template nicht gefunden.'];
        }

        $parsed = $this->extractPackageVersionPathFromQuery($query);
        $package = trim((string) ($parsed['package'] ?? $query));
        $detectedVersion = trim((string) ($parsed['version'] ?? ''));
        $path = $preferredPath !== '' ? $preferredPath : trim((string) ($parsed['path'] ?? (string) ($template['default_path'] ?? '')));

        if ($package === '') {
            return ['success' => false, 'message' => 'Paketname konnte nicht ermittelt werden.'];
        }

        $versions = $this->fetchTemplateVersions($template, $package);
        $latest = trim((string) ($versions['latest'] ?? ''));
        $list = is_array($versions['versions'] ?? null) ? $versions['versions'] : [];

        if ($list === []) {
            $fallback = $detectedVersion !== '' ? $detectedVersion : ($latest !== '' ? $latest : 'latest');
            $list = [$fallback];
            if ($latest === '') {
                $latest = $fallback;
            }
        }

        $selectedVersion = $preferredVersion !== '' ? $preferredVersion : ($detectedVersion !== '' ? $detectedVersion : ($latest !== '' ? $latest : (string) ($list[0] ?? 'latest')));
        if ($selectedVersion === '') {
            $selectedVersion = 'latest';
        }

        $versionItems = [];
        foreach ($list as $version) {
            $v = trim((string) $version);
            if ($v === '') {
                continue;
            }
            $versionItems[] = [
                'version' => $v,
                'is_latest' => $latest !== '' && $v === $latest,
                'is_recommended' => $latest !== '' && $v === $latest,
                'url' => $this->buildTemplateUrl($template, $package, $v, $path),
            ];
        }

        if ($versionItems === []) {
            return ['success' => false, 'message' => 'Keine Versionen gefunden.'];
        }

        $selectedUrl = '';
        foreach ($versionItems as $item) {
            if ((string) ($item['version'] ?? '') !== $selectedVersion) {
                continue;
            }
            $selectedUrl = (string) ($item['url'] ?? '');
            break;
        }
        if ($selectedUrl === '') {
            $selectedUrl = (string) ($versionItems[0]['url'] ?? '');
            $selectedVersion = (string) ($versionItems[0]['version'] ?? $selectedVersion);
        }

        $nameGuess = trim((string) ($parsed['name'] ?? ''));
        if ($nameGuess === '') {
            $base = basename(str_replace('\\', '/', $package));
            $nameGuess = str_replace(['-', '_'], ' ', $base);
            $nameGuess = ucwords(trim($nameGuess));
        }
        $setId = $this->slug(str_replace('/', '-', $package));

        $this->touchSourceVersionInfoByUrl($selectedUrl, $latest !== '' ? $latest : $selectedVersion, $templateId, $package);

        return [
            'success' => true,
            'message' => 'Quelle analysiert.',
            'template_id' => $templateId,
            'template' => $template,
            'query' => $query,
            'package' => $package,
            'path' => $path,
            'latest_version' => $latest !== '' ? $latest : $selectedVersion,
            'recommended_version' => $latest !== '' ? $latest : $selectedVersion,
            'selected_version' => $selectedVersion,
            'source_url' => $selectedUrl,
            'set_name' => $nameGuess,
            'set_id' => $setId,
            'versions' => $versionItems,
        ];
    }

    public function addIconSource(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $url = trim((string) ($payload['url'] ?? ''));
        $id = $this->slug((string) ($payload['id'] ?? ''));

        if ($url === '' || !$this->isAllowedUrl($url)) {
            return ['success' => false, 'message' => 'Ungültige oder nicht erlaubte URL.'];
        }

        if ($name === '') {
            $name = $id !== '' ? $id : 'Icon Quelle';
        }
        if ($id === '') {
            $id = $this->slug($name);
            if ($id === '') {
                $id = md5($url);
            }
        }

        $record = [
            'id' => $id,
            'name' => $name,
            'url' => $url,
            'status' => (string) ($payload['status'] ?? 'known'),
            'template_id' => (string) ($payload['template_id'] ?? ''),
            'package' => (string) ($payload['package'] ?? ''),
            'latest_version' => (string) ($payload['latest_version'] ?? ''),
            'last_checked' => (string) ($payload['last_checked'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $updated = false;
        foreach ($this->state['icon_sources'] as $idx => $source) {
            $sourceId = (string) ($source['id'] ?? '');
            $sourceUrl = trim((string) ($source['url'] ?? ''));
            if ($sourceId !== $id && $sourceUrl !== $url) {
                continue;
            }
            $record['created_at'] = (string) ($source['created_at'] ?? $record['created_at']);
            $this->state['icon_sources'][$idx] = $record;
            $updated = true;
            break;
        }

        if (!$updated) {
            $this->state['icon_sources'][] = $record;
        }

        usort($this->state['icon_sources'], static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        $this->persist();

        return ['success' => true, 'message' => 'Quelle gespeichert.', 'source' => $record];
    }

    public function removeIconSource(string $id): bool
    {
        $id = $this->slug($id);
        if ($id === '') {
            return false;
        }

        $before = count($this->state['icon_sources']);
        $this->state['icon_sources'] = array_values(array_filter(
            $this->state['icon_sources'],
            static fn(array $source): bool => (string) ($source['id'] ?? '') !== $id
        ));

        if (count($this->state['icon_sources']) === $before) {
            return false;
        }

        $this->persist();
        return true;
    }

    public function ensureIconSourceFromSet(array $set): void
    {
        $url = trim((string) ($set['source_url'] ?? ''));
        if ($url === '') {
            return;
        }

        $this->addIconSource([
            'id' => (string) ($set['id'] ?? ''),
            'name' => (string) ($set['name'] ?? 'Icon Quelle'),
            'url' => $url,
            'status' => 'installed',
        ]);
    }

    public function analyzeIconCss(string $url): array
    {
        $content = $this->downloadText($url);
        if ($content === null) {
            return ['success' => false, 'message' => 'Quelle konnte nicht geladen werden.'];
        }

        $icons = [];
        if (preg_match_all('/\\.(fa-[a-z0-9-]+|bi-[a-z0-9-]+|ti-[a-z0-9-]+|icon-[a-z0-9-]+):before/i', $content, $matches)) {
            $icons = array_values(array_unique(array_map('strtolower', $matches[1])));
        }

        $assets = $this->extractAssetUrls($content, $url);

        return [
            'success' => true,
            'message' => 'Analyse abgeschlossen.',
            'url' => $url,
            'icons' => array_slice($icons, 0, 500),
            'icon_count' => count($icons),
            'assets' => $assets,
        ];
    }

    public function analyzeFontCss(string $url): array
    {
        $content = $this->downloadText($url, ['User-Agent: Mozilla/5.0']);
        if ($content === null) {
            return ['success' => false, 'message' => 'Quelle konnte nicht geladen werden.'];
        }

        $families = [];
        if (preg_match_all('/font-family\s*:\s*(["\']?)([^;"\']+)\1\s*;/i', $content, $matches)) {
            foreach ($matches[2] as $family) {
                $value = trim((string) $family);
                if ($value === '') {
                    continue;
                }
                $families[] = $value;
            }
        }

        $assets = $this->extractAssetUrls($content, $url);

        return [
            'success' => true,
            'message' => 'Font-Analyse abgeschlossen.',
            'url' => $url,
            'families' => array_values(array_unique($families)),
            'asset_count' => count($assets),
            'assets' => $assets,
        ];
    }

    public function installIconSetFromUrl(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? 'Icon Set'));
        $sourceUrl = trim((string) ($payload['source_url'] ?? ''));
        $idInput = trim((string) ($payload['id'] ?? ''));
        $id = $this->slug($idInput !== '' ? $idInput : $name);

        if ($id === '' || $sourceUrl === '') {
            return ['success' => false, 'message' => 'ID oder URL fehlt.'];
        }

        $css = $this->downloadText($sourceUrl);
        if ($css === null) {
            return ['success' => false, 'message' => 'CSS konnte nicht geladen werden.'];
        }

        $setDir = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icon-sets' . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($setDir)) {
            @mkdir($setDir, 0755, true);
        }

        $localCssPath = $setDir . DIRECTORY_SEPARATOR . 'icons.css';
        $localCssWeb = '/assets/icon-sets/' . rawurlencode($id) . '/icons.css';

            $assets = $this->extractAssetUrls($css, $sourceUrl);
            $savedAssets = [];
            $replacementMap = [];
            foreach ($assets as $assetUrl) {
                $path = parse_url($assetUrl, PHP_URL_PATH) ?: '';
                $filename = basename($path ?: 'asset.bin');
                $filename = $this->safeFilename($filename);
                if ($filename === '') {
                    continue;
                }
                $binary = $this->downloadBinary($assetUrl);
                if ($binary === null) {
                    continue;
                }

                // Preserve a useful subdirectory (e.g. webfonts) if present in asset path
                $assetDirName = basename(dirname($path));
                $assetDir = $setDir . DIRECTORY_SEPARATOR . ($assetDirName !== '.' ? $assetDirName : '');
                if ($assetDirName !== '.' && $assetDirName !== '') {
                    @mkdir($assetDir, 0755, true);
                    $localPath = $assetDir . DIRECTORY_SEPARATOR . $filename;
                    $webLocal = '/assets/icon-sets/' . rawurlencode($id) . '/' . $assetDirName . '/' . $filename;
                    $replacement = './' . $assetDirName . '/' . $filename;
                } else {
                    $localPath = $setDir . DIRECTORY_SEPARATOR . $filename;
                    $webLocal = '/assets/icon-sets/' . rawurlencode($id) . '/' . $filename;
                    $replacement = './' . $filename;
                }

                @file_put_contents($localPath, $binary, LOCK_EX);
                $css = str_replace($assetUrl, $replacement, $css);
                $savedAssets[] = ['type' => 'binary', 'origin' => $assetUrl, 'local' => $webLocal];
                $replacementMap[$assetUrl] = $replacement;
            }

            // Replace any raw url(...) tokens (relative paths) by resolving them and mapping to replacements
            $css = $this->replaceAssetReferencesInCss($css, $sourceUrl, $replacementMap);

            @file_put_contents($localCssPath, $css, LOCK_EX);

        $record = [
            'id' => $id,
            'name' => $name,
            'source_url' => $sourceUrl,
            'local_css' => $localCssWeb,
            'assets' => $savedAssets,
            'areas' => $this->normalizeStringList($payload['areas'] ?? ['admin', 'frontend']),
            'allow_for' => $this->normalizeStringList($payload['allow_for'] ?? ['system', 'theme', 'mods']),
            'status' => (string) ($payload['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->upsertSet('icon_sets', $record);

        return ['success' => true, 'message' => 'Icon-Set installiert.', 'set' => $record];
    }

    public function installIconSetFromTemplate(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? 'Icon Set'));
        $templateId = $this->slug((string) ($payload['template_id'] ?? ''));
        $package = trim((string) ($payload['package'] ?? ''));
        $version = trim((string) ($payload['version'] ?? 'latest'));
        $idInput = trim((string) ($payload['id'] ?? ''));
        $id = $this->slug($idInput !== '' ? $idInput : $name);

        if ($templateId === '' || $package === '' || $id === '') {
            return ['success' => false, 'message' => 'Template, Paketname oder ID fehlt.'];
        }

        $templates = [];
        foreach ($this->knownSourceTemplates() as $tpl) {
            if (!is_array($tpl)) {
                continue;
            }
            $templates[(string) ($tpl['id'] ?? '')] = $tpl;
        }
        $template = $templates[$templateId] ?? null;
        if (!is_array($template)) {
            return ['success' => false, 'message' => 'Template nicht gefunden.'];
        }

        $setDir = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icon-sets' . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($setDir)) {
            @mkdir($setDir, 0755, true);
        }

        // Determine asset entries. Priority:
        // 1) payload-provided entries from UI (css/js/font lists)
        // 2) template-defined assets
        // 3) template default path as single css entry
        $entries = [];
        if (is_array($payload['asset_entries'] ?? null) && $payload['asset_entries'] !== []) {
            $entries = array_values(array_filter($payload['asset_entries'], static fn(mixed $e): bool => is_array($e)));
        } elseif (is_array($template['assets'] ?? null) && $template['assets'] !== []) {
            $entries = $template['assets'];
        } else {
            $entries = [
                ['type' => 'css', 'path' => (string) ($template['default_path'] ?? 'dist/icons.css')],
            ];
        }

        $savedAssets = [];
        foreach ($entries as $entry) {
            $etype = strtolower(trim((string) ($entry['type'] ?? 'css')));
            $epath = trim((string) ($entry['path'] ?? ($payload['path'] ?? '')));
            $entryUrlTemplate = trim((string) ($entry['url_template'] ?? ''));
            if ($epath === '') {
                $epath = (string) ($template['default_path'] ?? '');
            }

            if ($entryUrlTemplate !== '') {
                $url = str_replace(
                    ['{package}', '{version}', '{path}'],
                    [$package, $version, ltrim($epath, '/')],
                    $entryUrlTemplate
                );
            } elseif (preg_match('#^https?://#i', $epath)) {
                $url = str_replace(['{package}', '{version}'], [$package, $version], $epath);
            } else {
                $url = $this->buildTemplateUrl($template, $package, $version, $epath);
            }
            if ($url === '') {
                continue;
            }

            if ($etype === 'css' || $etype === 'js') {
                $text = $this->downloadText($url);
                if ($text === null) {
                    continue;
                }

                // download referenced assets inside CSS/JS
                $assets = $this->extractAssetUrls($text, $url);
                $replacementMap = [];
                foreach ($assets as $assetUrl) {
                    $path = parse_url($assetUrl, PHP_URL_PATH) ?: '';
                    $filename = basename($path ?: 'asset.bin');
                    $filename = $this->safeFilename($filename);
                    if ($filename === '') {
                        continue;
                    }
                    $binary = $this->downloadBinary($assetUrl);
                    if ($binary === null) {
                        continue;
                    }

                    // Preserve a useful subdirectory (e.g. webfonts) if present in asset path
                    $assetDirName = basename(dirname($path));
                    if ($assetDirName !== '.' && $assetDirName !== '') {
                        $assetDir = $setDir . DIRECTORY_SEPARATOR . $assetDirName;
                        @mkdir($assetDir, 0755, true);
                        $localPath = $assetDir . DIRECTORY_SEPARATOR . $filename;
                        $webLocal = '/assets/icon-sets/' . rawurlencode($id) . '/' . $assetDirName . '/' . $filename;
                        $replacement = './' . $assetDirName . '/' . $filename;
                    } else {
                        $localPath = $setDir . DIRECTORY_SEPARATOR . $filename;
                        $webLocal = '/assets/icon-sets/' . rawurlencode($id) . '/' . $filename;
                        $replacement = './' . $filename;
                    }

                    @file_put_contents($localPath, $binary, LOCK_EX);
                    $text = str_replace($assetUrl, $replacement, $text);
                    $savedAssets[] = ['type' => 'binary', 'origin' => $assetUrl, 'local' => $webLocal];
                    $replacementMap[$assetUrl] = $replacement;
                }

                // Replace any raw url(...) tokens by resolving them and mapping to replacements
                $text = $this->replaceAssetReferencesInCss($text, $url, $replacementMap);

                $localName = $this->safeFilename(basename($epath) ?: ($etype === 'css' ? 'icons.css' : 'script.js'));
                if ($localName === '') {
                    $localName = $etype === 'css' ? 'icons.css' : 'script.js';
                }
                @file_put_contents($setDir . DIRECTORY_SEPARATOR . $localName, $text, LOCK_EX);
                $savedAssets[] = ['type' => $etype, 'origin' => $url, 'local' => '/assets/icon-sets/' . rawurlencode($id) . '/' . $localName];
            } else {
                // binary asset (fonts, images, etc.)
                $bin = $this->downloadBinary($url);
                if ($bin === null) {
                    continue;
                }
                $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'asset.bin');
                $filename = $this->safeFilename($filename);
                if ($filename === '') {
                    $filename = 'asset.bin';
                }
                @file_put_contents($setDir . DIRECTORY_SEPARATOR . $filename, $bin, LOCK_EX);
                $savedAssets[] = ['type' => $etype, 'origin' => $url, 'local' => '/assets/icon-sets/' . rawurlencode($id) . '/' . $filename];
            }
        }

        // choose first css as local_css if present
        $localCssWeb = '';
        foreach ($savedAssets as $sa) {
            if (($sa['type'] ?? '') === 'css') {
                $localCssWeb = (string) ($sa['local'] ?? '');
                break;
            }
        }

        $record = [
            'id' => $id,
            'name' => $name,
            'source_template' => $templateId,
            'package' => $package,
            'version' => $version,
            'source_url' => $this->buildTemplateUrl($template, $package, $version, (string) ($template['default_path'] ?? '')),
            'local_css' => $localCssWeb,
            'assets' => $savedAssets,
            'areas' => $this->normalizeStringList($payload['areas'] ?? ['admin', 'frontend']),
            'allow_for' => $this->normalizeStringList($payload['allow_for'] ?? ['system', 'theme', 'mods']),
            'status' => (string) ($payload['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->upsertSet('icon_sets', $record);

        return ['success' => true, 'message' => 'Icon-Set aus Template installiert.', 'set' => $record];
    }

    public function installGoogleFont(string $family, array $styles, array $options = []): array
    {
        $family = trim($family);
        if ($family === '') {
            return ['success' => false, 'message' => 'Schriftfamilie fehlt.'];
        }

        $styles = array_values(array_filter(array_map(static fn($v): string => trim((string) $v), $styles)));
        if ($styles === []) {
            $styles = ['400'];
        }

        $id = 'google-' . $this->slug($family);
        $setDir = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'font-sets' . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($setDir)) {
            @mkdir($setDir, 0755, true);
        }

        $googleCssUrl = $this->buildGoogleCssUrl($family, $styles);
        $css = $this->downloadText($googleCssUrl, ['User-Agent: Mozilla/5.0']);
        if ($css === null) {
            return ['success' => false, 'message' => 'Google Fonts CSS konnte nicht geladen werden.'];
        }

        $assets = $this->extractAssetUrls($css, $googleCssUrl);
        $savedAssets = [];
        foreach ($assets as $assetUrl) {
            $filename = basename(parse_url($assetUrl, PHP_URL_PATH) ?: 'font.woff2');
            $filename = $this->safeFilename($filename);
            if ($filename === '') {
                continue;
            }
            $binary = $this->downloadBinary($assetUrl, ['User-Agent: Mozilla/5.0']);
            if ($binary === null) {
                continue;
            }
            @file_put_contents($setDir . DIRECTORY_SEPARATOR . $filename, $binary, LOCK_EX);
            $css = str_replace($assetUrl, './' . $filename, $css);
            $savedAssets[] = ['type' => 'font', 'origin' => $assetUrl, 'local' => '/assets/font-sets/' . rawurlencode($id) . '/' . $filename];
        }

        $localCssPath = $setDir . DIRECTORY_SEPARATOR . 'fonts.css';
        @file_put_contents($localCssPath, $css, LOCK_EX);

        $record = [
            'id' => $id,
            'name' => $family,
            'provider' => 'google-fonts',
            'styles' => $styles,
            'source_url' => $googleCssUrl,
            'local_css' => '/assets/font-sets/' . rawurlencode($id) . '/fonts.css',
            'assets' => $savedAssets,
            'areas' => $this->normalizeStringList($options['areas'] ?? ['admin', 'frontend']),
            'allow_for' => $this->normalizeStringList($options['allow_for'] ?? ['system', 'theme', 'mods']),
            'status' => (string) ($options['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->upsertSet('font_sets', $record);

        return ['success' => true, 'message' => 'Google Font lokal installiert.', 'set' => $record];
    }

    public function installFontSetFromUrl(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? 'Font Set'));
        $sourceUrl = trim((string) ($payload['source_url'] ?? ''));
        $idInput = trim((string) ($payload['id'] ?? ''));
        $id = $this->slug($idInput !== '' ? $idInput : $name);

        if ($id === '' || $sourceUrl === '') {
            return ['success' => false, 'message' => 'ID oder URL fehlt.'];
        }

        $css = $this->downloadText($sourceUrl);
        if ($css === null) {
            return ['success' => false, 'message' => 'Font CSS konnte nicht geladen werden.'];
        }

        $setDir = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'font-sets' . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($setDir)) {
            @mkdir($setDir, 0755, true);
        }

        $assets = $this->extractAssetUrls($css, $sourceUrl);
        $savedAssets = [];
        $replacementMap = [];
        foreach ($assets as $assetUrl) {
            $path = parse_url($assetUrl, PHP_URL_PATH) ?: '';
            $filename = basename($path ?: 'font.bin');
            $filename = $this->safeFilename($filename);
            if ($filename === '') {
                continue;
            }
            $binary = $this->downloadBinary($assetUrl);
            if ($binary === null) {
                continue;
            }

            $assetDirName = basename(dirname($path));
            if ($assetDirName !== '.' && $assetDirName !== '') {
                $assetDir = $setDir . DIRECTORY_SEPARATOR . $assetDirName;
                @mkdir($assetDir, 0755, true);
                $localPath = $assetDir . DIRECTORY_SEPARATOR . $filename;
                $replacement = './' . $assetDirName . '/' . $filename;
                $webLocal = '/assets/font-sets/' . rawurlencode($id) . '/' . $assetDirName . '/' . $filename;
            } else {
                $localPath = $setDir . DIRECTORY_SEPARATOR . $filename;
                $replacement = './' . $filename;
                $webLocal = '/assets/font-sets/' . rawurlencode($id) . '/' . $filename;
            }

            @file_put_contents($localPath, $binary, LOCK_EX);
            $css = str_replace($assetUrl, $replacement, $css);
            $savedAssets[] = ['type' => 'font', 'origin' => $assetUrl, 'local' => $webLocal];
            $replacementMap[$assetUrl] = $replacement;
        }

        $css = $this->replaceAssetReferencesInCss($css, $sourceUrl, $replacementMap);

        @file_put_contents($setDir . DIRECTORY_SEPARATOR . 'fonts.css', $css, LOCK_EX);

        $record = [
            'id' => $id,
            'name' => $name,
            'provider' => (string) ($payload['provider'] ?? 'custom'),
            'source_url' => $sourceUrl,
            'local_css' => '/assets/font-sets/' . rawurlencode($id) . '/fonts.css',
            'assets' => $savedAssets,
            'areas' => $this->normalizeStringList($payload['areas'] ?? ['admin', 'frontend']),
            'allow_for' => $this->normalizeStringList($payload['allow_for'] ?? ['system', 'theme', 'mods']),
            'status' => (string) ($payload['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->upsertSet('font_sets', $record);

        return ['success' => true, 'message' => 'Font-Set installiert.', 'set' => $record];
    }

    public function updateSet(string $type, string $id, array $changes): bool
    {
        $bucket = $type === 'fonts' ? 'font_sets' : 'icon_sets';
        foreach ($this->state[$bucket] as $idx => $set) {
            if ((string) ($set['id'] ?? '') !== $id) {
                continue;
            }
            $set['areas'] = $this->normalizeStringList($changes['areas'] ?? ($set['areas'] ?? []));
            $set['allow_for'] = $this->normalizeStringList($changes['allow_for'] ?? ($set['allow_for'] ?? []));
            $set['status'] = trim((string) ($changes['status'] ?? ($set['status'] ?? 'active')));
            $set['name'] = trim((string) ($changes['name'] ?? ($set['name'] ?? $id)));
            $set['updated_at'] = date('Y-m-d H:i:s');
            $this->state[$bucket][$idx] = $set;
            $this->persist();
            return true;
        }

        return false;
    }

    public function deleteSet(string $type, string $id): bool
    {
        $bucket = $type === 'fonts' ? 'font_sets' : 'icon_sets';
        $folder = $type === 'fonts' ? 'font-sets' : 'icon-sets';
        $this->state[$bucket] = array_values(array_filter(
            $this->state[$bucket],
            static fn(array $set): bool => (string) ($set['id'] ?? '') !== $id
        ));
        $this->persist();

        $targetDir = $this->basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $id;
        $this->removeDirectory($targetDir);

        return true;
    }

    public function exportSet(string $type, string $id): ?string
    {
        $bucket = $type === 'fonts' ? 'font_sets' : 'icon_sets';
        foreach ($this->state[$bucket] as $set) {
            if ((string) ($set['id'] ?? '') !== $id) {
                continue;
            }
            return json_encode([
                'schema' => 'chamy-asset-set-v1',
                'type' => $type,
                'set' => $set,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return null;
    }

    public function importSetJson(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !is_array($data['set'] ?? null)) {
            return ['success' => false, 'message' => 'Ungültiges Import-JSON.'];
        }

        $type = (string) ($data['type'] ?? 'icons');
        $set = $data['set'];
        $set['id'] = $this->slug((string) ($set['id'] ?? 'imported-set'));
        if ($set['id'] === '') {
            return ['success' => false, 'message' => 'Ungültige Set-ID.'];
        }
        $set['updated_at'] = date('Y-m-d H:i:s');
        if (!isset($set['created_at'])) {
            $set['created_at'] = date('Y-m-d H:i:s');
        }

        $bucket = $type === 'fonts' ? 'font_sets' : 'icon_sets';
        $this->upsertSet($bucket, $set);

        return ['success' => true, 'message' => 'Set importiert.', 'set' => $set];
    }

    private function upsertSet(string $bucket, array $record): void
    {
        $found = false;
        foreach ($this->state[$bucket] as $idx => $set) {
            if ((string) ($set['id'] ?? '') !== (string) ($record['id'] ?? '')) {
                continue;
            }
            $this->state[$bucket][$idx] = $record;
            $found = true;
            break;
        }

        if (!$found) {
            $this->state[$bucket][] = $record;
        }

        usort($this->state[$bucket], static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        $this->persist();
    }

    private function load(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        if (!is_file($this->storageFile)) {
            $this->persist();
            return;
        }

        $raw = @file_get_contents($this->storageFile);
        if ($raw === false || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return;
        }

        $this->state['icon_sets'] = array_values(array_filter($decoded['icon_sets'] ?? [], 'is_array'));
        $this->state['font_sets'] = array_values(array_filter($decoded['font_sets'] ?? [], 'is_array'));
        $this->state['icon_sources'] = array_values(array_filter($decoded['icon_sources'] ?? [], 'is_array'));
        $this->state['source_templates'] = array_values(array_filter($decoded['source_templates'] ?? [], 'is_array'));
    }

    private function fetchTemplateVersions(array $template, string $package): array
    {
        $latest = '';
        $versions = [];

        $type = strtolower((string) ($template['type'] ?? 'generic'));
        if ($type === 'npm' || str_contains((string) ($template['versions_api'] ?? ''), 'registry.npmjs.org')) {
            $meta = $this->fetchNpmPackageMeta($package);
            if (is_array($meta)) {
                $latest = trim((string) ($meta['dist-tags']['latest'] ?? ''));
                $verObj = $meta['versions'] ?? [];
                if (is_array($verObj)) {
                    $versions = array_keys($verObj);
                    usort($versions, fn(string $a, string $b): int => version_compare($b, $a));
                    $versions = array_values(array_slice($versions, 0, 20));
                }
            }
        }

        return ['latest' => $latest, 'versions' => $versions];
    }

    private function fetchNpmPackageMeta(string $package): ?array
    {
        $pkg = trim($package);
        if ($pkg === '') {
            return null;
        }

        $encoded = str_starts_with($pkg, '@') ? '@' . rawurlencode(substr($pkg, 1)) : rawurlencode($pkg);
        $url = 'https://registry.npmjs.org/' . $encoded;
        $raw = $this->downloadText($url, ['Accept: application/json']);
        if ($raw === null) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function extractPackageVersionPathFromQuery(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        if (!preg_match('#^https?://#i', $query)) {
            return ['package' => $query];
        }

        $urlParts = parse_url($query);
        $path = (string) ($urlParts['path'] ?? '');

        if (preg_match('#/npm/(@?[^@/]+(?:/[^@/]+)?)@([^/]+)/(.+)$#', $path, $m)) {
            return ['package' => urldecode($m[1]), 'version' => $m[2], 'path' => $m[3]];
        }
        if (preg_match('#/(@?[^@/]+(?:/[^@/]+)?)@([^/]+)/(.+)$#', $path, $m) && str_contains((string) ($urlParts['host'] ?? ''), 'unpkg.com')) {
            return ['package' => urldecode($m[1]), 'version' => $m[2], 'path' => $m[3]];
        }

        return ['package' => $query];
    }

    private function buildTemplateUrl(array $template, string $package, string $version, string $path = ''): string
    {
        $tpl = (string) ($template['url_template'] ?? '');
        $valuePath = trim($path);
        if ($valuePath === '') {
            $valuePath = (string) ($template['default_path'] ?? '');
        }
        $repl = [
            '{package}' => $package,
            '{version}' => $version,
            '{path}' => ltrim($valuePath, '/'),
        ];

        return str_replace(array_keys($repl), array_values($repl), $tpl);
    }

    private function touchSourceVersionInfoByUrl(string $url, string $latestVersion, string $templateId, string $package): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }

        foreach ($this->state['icon_sources'] as $idx => $source) {
            if (trim((string) ($source['url'] ?? '')) !== $url) {
                continue;
            }
            $source['latest_version'] = $latestVersion;
            $source['last_checked'] = date('Y-m-d H:i:s');
            if ($templateId !== '') {
                $source['template_id'] = $templateId;
            }
            if ($package !== '') {
                $source['package'] = $package;
            }
            $this->state['icon_sources'][$idx] = $source;
            $this->persist();
            return;
        }
    }

    private function persist(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }

        @file_put_contents($this->storageFile, json_encode($this->state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function downloadText(string $url, array $headers = []): ?string
    {
        $url = trim($url);
        if (!$this->isAllowedUrl($url)) {
            return null;
        }

        $attempts = 2;
        for ($i = 0; $i < $attempts; $i++) {
            $verifyPeer = $i === 0; // first try with verification, second try without
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 20,
                    'follow_location' => 1,
                    'header' => implode("\r\n", $headers),
                ],
                'ssl' => [
                    'verify_peer' => $verifyPeer,
                    'verify_peer_name' => $verifyPeer,
                ],
            ]);

            $content = @file_get_contents($url, false, $ctx);
            if ($content !== false) {
                return $content;
            }
        }

        return null;
    }

    private function downloadBinary(string $url, array $headers = []): ?string
    {
        return $this->downloadText($url, $headers);
    }

    private function isAllowedUrl(string $url): bool
    {
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        return true;
    }

    /** @return array<int, string> */
    private function extractAssetUrls(string $css, string $baseUrl): array
    {
        $urls = [];
        if (preg_match_all('/url\\(([^)]+)\\)/i', $css, $matches)) {
            foreach ($matches[1] as $raw) {
                $clean = trim((string) $raw, " \t\n\r\0\x0B\"'");
                if ($clean === '' || str_starts_with($clean, 'data:')) {
                    continue;
                }
                $resolved = $this->resolveUrl($baseUrl, $clean);
                if ($resolved !== null && $this->isAllowedUrl($resolved)) {
                    $urls[] = $resolved;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function replaceAssetReferencesInCss(string $css, string $baseUrl, array $replacementMap): string
    {
        if ($replacementMap === []) {
            return $css;
        }

        if (!preg_match_all('/url\(([^)]+)\)/i', $css, $matches)) {
            return $css;
        }

        foreach ($matches[1] as $raw) {
            $clean = trim((string) $raw, " \t\n\r\0\x0B\"'");
            if ($clean === '' || str_starts_with($clean, 'data:')) {
                continue;
            }
            $resolved = $this->resolveUrl($baseUrl, $clean);
            if ($resolved === null) {
                continue;
            }
            if (isset($replacementMap[$resolved])) {
                // replace the raw token inside the url(...) occurrence
                $search = $raw;
                $replace = $replacementMap[$resolved];
                $css = str_replace($search, $replace, $css);
            }
        }

        return $css;
    }

    private function resolveUrl(string $baseUrl, string $value): ?string
    {
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($value, '/')) {
            $path = $value;
        } else {
            $basePath = (string) ($parts['path'] ?? '/');
            $dir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
            if ($dir === '') {
                $dir = '/';
            }
            $path = ($dir === '/' ? '' : $dir) . '/' . ltrim($value, '/');
        }

        // Normalize the path to remove ./ and ../ segments
        $segments = explode('/', $path);
        $resolvedSegments = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($resolvedSegments);
                continue;
            }
            $resolvedSegments[] = $seg;
        }
        $normalizedPath = '/' . implode('/', $resolvedSegments);

        return $scheme . '://' . $host . $port . $normalizedPath;
    }

    /** @return array<int, string> */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }
        $items = array_values(array_unique(array_filter(array_map(
            static fn($item): string => trim((string) $item),
            $value
        ))));
        sort($items);
        return $items;
    }

    private function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]+/', '-', $value) ?? '';
        $value = trim($value, '-_');
        return substr($value, 0, 80);
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? '';
        return trim($name, '._');
    }

    private function buildGoogleCssUrl(string $family, array $styles): string
    {
        $familyParam = str_replace(' ', '+', $family);
        $weights = [];
        $italic = [];
        foreach ($styles as $style) {
            $s = strtolower(trim((string) $style));
            if (str_ends_with($s, 'italic')) {
                $w = trim(str_replace('italic', '', $s));
                $italic[] = $w !== '' ? $w : '400';
            } else {
                $weights[] = $s;
            }
        }
        $weights = array_values(array_unique(array_filter($weights)));
        $italic = array_values(array_unique(array_filter($italic)));
        sort($weights);
        sort($italic);

        if ($italic !== []) {
            $pairs = [];
            foreach ($weights as $w) {
                $pairs[] = '0,' . $w;
            }
            foreach ($italic as $w) {
                $pairs[] = '1,' . $w;
            }
            $familyParam .= ':ital,wght@' . implode(';', $pairs);
        } elseif ($weights !== []) {
            $familyParam .= ':wght@' . implode(';', $weights);
        }

        return 'https://fonts.googleapis.com/css2?family=' . rawurlencode($familyParam) . '&display=swap';
    }

    /** @return array<int, array<string, mixed>> */
    private function loadGoogleCatalogCached(): array
    {
        $cacheFile = $this->storageDir . DIRECTORY_SEPARATOR . 'google_fonts_catalog_cache.json';
        $ttl = 6 * 60 * 60;
        $now = time();

        $cached = null;
        if (is_file($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $cached = $decoded;
                }
            }
        }

        $cachedItems = is_array($cached['items'] ?? null) ? array_values(array_filter($cached['items'], 'is_array')) : [];
        $fetchedAt = (int) ($cached['fetched_at'] ?? 0);
        if ($cachedItems !== [] && $fetchedAt > 0 && ($now - $fetchedAt) < $ttl) {
            return $cachedItems;
        }

        $apiKey = $this->readGoogleFontsApiKey();
        if ($apiKey !== '') {
            $url = 'https://www.googleapis.com/webfonts/v1/webfonts?sort=alpha&key=' . rawurlencode($apiKey);
            $json = $this->downloadText($url, ['Accept: application/json', 'User-Agent: ChamyCMS/1.0']);
            if ($json !== null) {
                $decoded = json_decode($json, true);
                $items = is_array($decoded['items'] ?? null) ? array_values(array_filter($decoded['items'], 'is_array')) : [];
                if ($items !== []) {
                    if (!is_dir($this->storageDir)) {
                        @mkdir($this->storageDir, 0755, true);
                    }
                    @file_put_contents($cacheFile, json_encode([
                        'fetched_at' => $now,
                        'items' => $items,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
                    return $items;
                }
            }
        }

        if ($cachedItems !== []) {
            return $cachedItems;
        }

        // Fallback keeps UI usable if API key is not configured.
        return [
            ['family' => 'Inter', 'category' => 'sans-serif', 'variants' => ['100', '200', '300', 'regular', '500', '600', '700', '800', '900']],
            ['family' => 'Roboto', 'category' => 'sans-serif', 'variants' => ['100', '300', 'regular', '500', '700', '900']],
            ['family' => 'Open Sans', 'category' => 'sans-serif', 'variants' => ['300', 'regular', '500', '600', '700', '800']],
            ['family' => 'Merriweather', 'category' => 'serif', 'variants' => ['300', 'regular', '700', '900']],
            ['family' => 'Playfair Display', 'category' => 'serif', 'variants' => ['regular', '500', '600', '700']],
            ['family' => 'Fira Code', 'category' => 'monospace', 'variants' => ['300', 'regular', '500', '600', '700']],
        ];
    }

    private function readGoogleFontsApiKey(): string
    {
        $file = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . 'google_fonts_api_key';
        if (!is_file($file)) {
            return '';
        }
        return trim((string) @file_get_contents($file));
    }

    /** @return array<int, array{id:string,label:string,subcategories:array<int,string>}> */
    private function googleFontTaxonomy(): array
    {
        return [
            ['id' => 'Feeling', 'label' => 'Feeling', 'subcategories' => ['All', 'Calm', 'Playful', 'Elegant', 'Bold', 'Friendly']],
            ['id' => 'Appearance', 'label' => 'Appearance', 'subcategories' => ['All', 'Techno', 'Monospaced', 'Blobby', 'Marker', 'Art Deco', 'Art Nouveau', 'Distressed', 'Stencil', 'Woodtype', 'Medieval', 'Blackletter', 'Pixel', 'Not text', 'Tuscan', 'Wacky', 'Shaded', 'Inline']],
            ['id' => 'Calligraphy', 'label' => 'Calligraphy', 'subcategories' => ['All', 'Handwritten', 'Formal', 'Informal', 'Upright']],
            ['id' => 'Serif', 'label' => 'Serif', 'subcategories' => ['All', 'Transitional', 'Slab', 'Old Style', 'Modern', 'Humanist']],
            ['id' => 'Sans Serif', 'label' => 'Sans Serif', 'subcategories' => ['All', 'Neo Grotesque', 'Geometric', 'Humanist', 'Rounded']],
            ['id' => 'Technology', 'label' => 'Technology', 'subcategories' => ['All', 'Coding', 'Terminal', 'Sci-Fi', 'Display Tech']],
            ['id' => 'Seasonal', 'label' => 'Seasonal', 'subcategories' => ['All', 'Holiday', 'Winter', 'Summer', 'Halloween', 'Valentine']],
        ];
    }

    private function subcategoriesForCategory(array $taxonomy, string $category): array
    {
        foreach ($taxonomy as $entry) {
            if ((string) ($entry['id'] ?? '') !== $category) {
                continue;
            }
            return is_array($entry['subcategories'] ?? null) ? $entry['subcategories'] : ['All'];
        }
        return ['All'];
    }

    /** @return array{category:string,subcategory:string} */
    private function classifyGoogleFont(string $family, string $googleCategory, array $taxonomy): array
    {
        $name = mb_strtolower($family);
        $cat = strtolower(trim($googleCategory));

        $category = match ($cat) {
            'serif' => 'Serif',
            'sans-serif' => 'Sans Serif',
            'handwriting' => 'Calligraphy',
            'monospace' => 'Technology',
            'display' => 'Appearance',
            default => 'Appearance',
        };

        $subcategory = 'All';
        $keywordMap = [
            'Technology' => [
                'Monospaced' => ['mono', 'code'],
                'Terminal' => ['terminal', 'console'],
                'Sci-Fi' => ['sci', 'future', 'orbit', 'space'],
                'Display Tech' => ['tech', 'digital'],
            ],
            'Calligraphy' => [
                'Handwritten' => ['hand', 'script'],
                'Formal' => ['formal', 'classic'],
                'Informal' => ['casual', 'fun'],
                'Upright' => ['upright'],
            ],
            'Serif' => [
                'Slab' => ['slab'],
                'Old Style' => ['old', 'garamond', 'caslon'],
                'Modern' => ['modern', 'didot', 'bodoni'],
                'Humanist' => ['humanist'],
                'Transitional' => ['transitional', 'times', 'baskerville'],
            ],
            'Sans Serif' => [
                'Geometric' => ['geo', 'futura', 'montserrat', 'poppins'],
                'Humanist' => ['human', 'frutiger'],
                'Rounded' => ['round', 'nunito'],
                'Neo Grotesque' => ['grotesk', 'helvetica', 'inter', 'roboto'],
            ],
            'Appearance' => [
                'Techno' => ['tech', 'digital'],
                'Monospaced' => ['mono'],
                'Blobby' => ['blob', 'bubble'],
                'Marker' => ['marker'],
                'Art Deco' => ['deco'],
                'Art Nouveau' => ['nouveau'],
                'Distressed' => ['distress', 'rough'],
                'Stencil' => ['stencil'],
                'Woodtype' => ['wood'],
                'Medieval' => ['medieval', 'gothic'],
                'Blackletter' => ['blackletter', 'fraktur'],
                'Pixel' => ['pixel', '8bit'],
                'Tuscan' => ['tuscan'],
                'Wacky' => ['wacky', 'comic'],
                'Shaded' => ['shade', 'shadow'],
                'Inline' => ['inline'],
            ],
            'Seasonal' => [
                'Holiday' => ['holiday', 'xmas'],
                'Winter' => ['winter', 'snow'],
                'Summer' => ['summer', 'beach'],
                'Halloween' => ['halloween', 'spooky'],
                'Valentine' => ['valentine', 'love'],
            ],
            'Feeling' => [
                'Calm' => ['calm', 'soft'],
                'Playful' => ['play', 'fun'],
                'Elegant' => ['elegant', 'lux'],
                'Bold' => ['bold', 'impact'],
                'Friendly' => ['friendly', 'round'],
            ],
        ];

        foreach (($keywordMap[$category] ?? []) as $sub => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    $subcategory = $sub;
                    break 2;
                }
            }
        }

        $availableSubs = $this->subcategoriesForCategory($taxonomy, $category);
        if (!in_array($subcategory, $availableSubs, true)) {
            $subcategory = 'All';
        }

        return ['category' => $category, 'subcategory' => $subcategory];
    }

    /** @return array<int, string> */
    private function variantsToStyleTokens(array $variants): array
    {
        $styles = [];
        foreach ($variants as $variant) {
            $v = strtolower(trim((string) $variant));
            if ($v === '' || $v === 'regular') {
                $styles[] = '400';
                continue;
            }
            if ($v === 'italic') {
                $styles[] = 'italic';
                continue;
            }
            if (preg_match('/^([0-9]{3})italic$/', $v, $m)) {
                $styles[] = $m[1] . 'italic';
                $styles[] = $m[1];
                continue;
            }
            $styles[] = $v;
        }
        $styles = array_values(array_unique($styles));
        sort($styles);
        return $styles;
    }

    private function matchesStyleFilter(array $font, string $style): bool
    {
        $style = strtolower(trim($style));
        if ($style === '') {
            return true;
        }

        $variants = array_map(static fn(mixed $item): string => strtolower(trim((string) $item)), (array) ($font['variants'] ?? []));
        if ($style === 'italic') {
            foreach ($variants as $variant) {
                if ($variant === 'italic' || str_ends_with($variant, 'italic')) {
                    return true;
                }
            }
            return false;
        }

        foreach ($variants as $variant) {
            if ($variant === $style) {
                return true;
            }
            if ($variant === 'regular' && $style === '400') {
                return true;
            }
            if (preg_match('/^([0-9]{3})italic$/', $variant, $m) && $m[1] === $style) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array<string, mixed>> */
    private function installedFontSetsByFamily(): array
    {
        $map = [];
        foreach ($this->listFontSets() as $set) {
            if (!is_array($set)) {
                continue;
            }
            $family = trim((string) ($set['name'] ?? ''));
            if ($family === '') {
                continue;
            }
            $map[mb_strtolower($family)] = [
                'id' => (string) ($set['id'] ?? ''),
                'name' => $family,
                'provider' => (string) ($set['provider'] ?? ''),
                'local_css' => (string) ($set['local_css'] ?? ''),
                'styles' => is_array($set['styles'] ?? null) ? array_values($set['styles']) : [],
                'source_url' => (string) ($set['source_url'] ?? ''),
                'status' => (string) ($set['status'] ?? ''),
                'assets' => is_array($set['assets'] ?? null) ? $set['assets'] : [],
            ];
        }

        return $map;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }

    /** @return array<int, array<string, string>> */
    private function defaultIconSources(): array
    {
        return [
            ['id' => 'font-awesome-free', 'name' => 'Font Awesome Free', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', 'status' => 'known'],
            ['id' => 'bootstrap-icons', 'name' => 'Bootstrap Icons', 'url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', 'status' => 'known', 'template_id' => 'bootstrap-icons-default', 'package' => 'bootstrap-icons'],
            ['id' => 'tabler-icons-webfont', 'name' => 'Tabler Icons Webfont', 'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css', 'status' => 'known', 'template_id' => 'jsdelivr-npm', 'package' => '@tabler/icons-webfont', 'default_path' => 'dist/tabler-icons.min.css'],
            ['id' => 'heroicons', 'name' => 'Heroicons', 'url' => 'https://cdn.jsdelivr.net/npm/heroicons@2.0.18/dist/heroicons.css', 'status' => 'known'],
            ['id' => 'lucide', 'name' => 'Lucide Icons', 'url' => 'https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.css', 'status' => 'known', 'template_id' => 'jsdelivr-npm', 'package' => 'lucide', 'default_path' => 'dist/lucide.css'],
            ['id' => 'feather-icons', 'name' => 'Feather Icons', 'url' => 'https://cdn.jsdelivr.net/npm/feather-icons@latest/dist/feather.min.js', 'status' => 'known', 'template_id' => 'jsdelivr-npm', 'package' => 'feather-icons', 'default_path' => 'dist/feather.min.js'],
            ['id' => 'remix-icons', 'name' => 'Remix Icons', 'url' => 'https://cdn.jsdelivr.net/npm/remixicon@latest/fonts/remixicon.css', 'status' => 'known', 'template_id' => 'jsdelivr-npm', 'package' => 'remixicon', 'default_path' => 'fonts/remixicon.css'],
            ['id' => 'material-icons', 'name' => 'Material Icons', 'url' => 'https://fonts.googleapis.com/icon?family=Material+Icons', 'status' => 'known', 'template_id' => '', 'package' => 'material-design-icons'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function defaultSourceTemplates(): array
    {
        return [
            [
                'id' => 'jsdelivr-npm',
                'name' => 'jsDelivr (npm)',
                'type' => 'npm',
                'url_template' => 'https://cdn.jsdelivr.net/npm/{package}@{version}/{path}',
                'versions_api' => 'https://registry.npmjs.org/{package}',
                'default_path' => 'dist/icons.css',
                'status' => 'default',
            ],
            [
                'id' => 'unpkg-npm',
                'name' => 'unpkg (npm)',
                'type' => 'npm',
                'url_template' => 'https://unpkg.com/{package}@{version}/{path}',
                'versions_api' => 'https://registry.npmjs.org/{package}',
                'default_path' => 'dist/icons.css',
                'status' => 'default',
            ],
            [
                'id' => 'bootstrap-icons-default',
                'name' => 'Bootstrap Icons (Preset)',
                'type' => 'npm',
                'url_template' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@{version}/font/bootstrap-icons.min.css',
                'versions_api' => 'https://registry.npmjs.org/bootstrap-icons',
                'default_path' => 'font/bootstrap-icons.min.css',
                'status' => 'default',
            ],
        ];
    }
}
