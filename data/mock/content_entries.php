<?php

// ═══════════════════════════════════════════════════════════
//  Chamy CMS – Marketing-Website Mock Content
// ═══════════════════════════════════════════════════════════

$editorStartseite = [
    'root' => [
        'type' => 'root',
        'children' => [
            // ── Announcement Bar ──
            [
                'type' => 'snippet',
                'definition' => 'announcement_bar',
                'props' => [
                    'text' => '🚀 Chamy CMS v1.0 ist da!',
                    'link_url' => '/artikel/chamy-cms-v1-veroeffentlicht',
                    'link_label' => 'Release Notes lesen →',
                    'style' => 'accent',
                ],
                'children' => [],
            ],
            // ── Hero Banner ──
            [
                'type' => 'component',
                'definition' => 'hero_banner',
                'props' => [
                    'badge' => 'Open Source CMS',
                    'title' => 'Inhalte verwalten war noch nie so schnell.',
                    'subtitle' => 'Chamy ist das modulare PHP CMS für Entwickler und Redakteure – mit Visual Editor, Theme-System, REST-API und einem Admin-Interface, das Spaß macht.',
                    'primary_cta_label' => 'Jetzt kostenlos starten',
                    'primary_cta_url' => '#pricing',
                    'secondary_cta_label' => 'Features entdecken',
                    'secondary_cta_url' => '/seite/features',
                    'bg_style' => 'gradient',
                ],
                'children' => [],
            ],
            // ── Logo Cloud / Tech Stack ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'md', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'component',
                        'definition' => 'logo_cloud',
                        'props' => [
                            'title' => 'Gebaut mit bewährter Technologie',
                            'logos' => [
                                ['name' => 'PHP 8.2+', 'icon' => '🐘'],
                                ['name' => 'Twig', 'icon' => '🌿'],
                                ['name' => 'SQLite / MySQL', 'icon' => '🗄️'],
                                ['name' => 'REST API', 'icon' => '🔌'],
                                ['name' => 'Composer', 'icon' => '📦'],
                                ['name' => 'Vite', 'icon' => '⚡'],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
            // ── Key Features Section ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'default', 'anchor_id' => 'features'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => [
                            'badge' => 'Features',
                            'title' => 'Alles, was ein modernes CMS braucht',
                            'subtitle' => 'Von der Inhaltserstellung bis zum Deployment – Chamy deckt den gesamten Workflow ab.',
                            'level' => 'h2',
                            'align' => 'center',
                            'size' => 'xl',
                        ],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '✏️',
                                    'title' => 'Visual Editor',
                                    'text' => 'Drag & Drop Editor mit Echtzeit-Vorschau. Blöcke, Komponenten und Layouts – alles visuell anordnen.',
                                    'style' => 'glass',
                                    'link_url' => '/seite/features',
                                    'link_label' => 'Mehr erfahren →',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '🎨',
                                    'title' => 'Theme-System',
                                    'text' => 'Twig-basierte Themes mit Parent-Theme-Support, Hot Reload und CSS Custom Properties.',
                                    'style' => 'glass',
                                    'link_url' => '/seite/features',
                                    'link_label' => 'Mehr erfahren →',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '🧩',
                                    'title' => 'Modul-System',
                                    'text' => 'Erweitern Sie Chamy mit Modulen: Hooks, eigene Routen, Templates und Migrationen inklusive.',
                                    'style' => 'glass',
                                    'link_url' => '/seite/features',
                                    'link_label' => 'Mehr erfahren →',
                                ],
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '🔌',
                                    'title' => 'REST API',
                                    'text' => 'Headless-fähige REST API mit Token-Auth, Pagination und vollständiger CRUD-Unterstützung.',
                                    'style' => 'glass',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '🛡️',
                                    'title' => 'Sicherheit',
                                    'text' => 'RBAC-Berechtigungssystem, CSRF-Schutz, Input-Sanitization und sichere Session-Verwaltung.',
                                    'style' => 'glass',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'feature_card',
                                'props' => [
                                    'icon' => '🌍',
                                    'title' => 'Mehrsprachigkeit',
                                    'text' => 'Vollständiges i18n-System mit Locale-Support, Sprachdateien und automatischer Erkennung.',
                                    'style' => 'glass',
                                ],
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            // ── Stats Section ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'accent', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'layout',
                        'definition' => 'four_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            [
                                'type' => 'component',
                                'definition' => 'stats_counter',
                                'props' => ['value' => '5', 'label' => 'Content-Types', 'suffix' => '+'],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'stats_counter',
                                'props' => ['value' => '18', 'label' => 'Editor-Blöcke', 'suffix' => '+'],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'stats_counter',
                                'props' => ['value' => '100', 'label' => 'API-Endpunkte', 'suffix' => '%', 'prefix' => ''],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'stats_counter',
                                'props' => ['value' => '0', 'label' => 'Lizenzkosten', 'prefix' => '€'],
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            // ── Code Example Section ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'layout',
                        'definition' => 'two_columns',
                        'props' => ['ratio' => '50-50', 'gap' => 'xl', 'align' => 'center'],
                        'children' => [
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    [
                                        'type' => 'block',
                                        'definition' => 'marketing_heading',
                                        'props' => [
                                            'badge' => 'Developer Experience',
                                            'title' => 'Einfach zu erweitern',
                                            'subtitle' => 'Saubere PHP-Architektur mit Dependency Injection, Event-System und klarer Verzeichnisstruktur.',
                                            'level' => 'h2',
                                            'align' => 'left',
                                            'size' => 'lg',
                                        ],
                                        'children' => [],
                                    ],
                                    [
                                        'type' => 'block',
                                        'definition' => 'icon_text',
                                        'props' => ['icon' => '⚡', 'title' => 'Schneller Setup', 'text' => 'composer create-project & los gehts – in unter 2 Minuten.'],
                                        'children' => [],
                                    ],
                                    [
                                        'type' => 'block',
                                        'definition' => 'icon_text',
                                        'props' => ['icon' => '🔧', 'title' => 'CLI-Tools', 'text' => 'Migrationen, Cache, Importe – alles über die Kommandozeile.'],
                                        'children' => [],
                                    ],
                                    [
                                        'type' => 'block',
                                        'definition' => 'icon_text',
                                        'props' => ['icon' => '📖', 'title' => 'Volle Dokumentation', 'text' => 'Umfangreiche Docs mit Beispielen für jeden Bereich.'],
                                        'children' => [],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    [
                                        'type' => 'block',
                                        'definition' => 'code_block',
                                        'props' => [
                                            'language' => 'php',
                                            'filename' => 'modules/my-plugin/Plugin.php',
                                            'code' => "<?php\n\nclass MyPlugin extends Module\n{\n    public function boot(): void\n    {\n        \$this->hook('content.saved',\n            fn(\$entry) => Cache::flush()\n        );\n\n        \$this->route('GET', '/api/custom',\n            [CustomController::class, 'index']\n        );\n    }\n}",
                                        ],
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // ── Testimonials Section ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => [
                            'title' => 'Was unsere Nutzer sagen',
                            'subtitle' => 'Feedback von Entwicklern und Content-Teams weltweit.',
                            'level' => 'h2',
                            'align' => 'center',
                            'size' => 'lg',
                        ],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            [
                                'type' => 'component',
                                'definition' => 'testimonial_card',
                                'props' => [
                                    'quote' => 'Endlich ein CMS, das sich anfühlt wie es 2025 sein sollte. Der Visual Editor ist genial.',
                                    'author' => 'Sarah Weber',
                                    'role' => 'Frontend Lead',
                                    'company' => 'DigitalWerk GmbH',
                                    'rating' => '5',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'testimonial_card',
                                'props' => [
                                    'quote' => 'Die API-first-Architektur hat uns den Umstieg von WordPress unglaublich leicht gemacht.',
                                    'author' => 'Markus Schneider',
                                    'role' => 'CTO',
                                    'company' => 'TechStart AG',
                                    'rating' => '5',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'testimonial_card',
                                'props' => [
                                    'quote' => 'Module schreiben geht so schnell, dass ich nach einer Woche schon drei Plugins im Marketplace hatte.',
                                    'author' => 'Julia Hoffmann',
                                    'role' => 'Full-Stack Dev',
                                    'company' => 'Freelancer',
                                    'rating' => '5',
                                ],
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            // ── CTA Banner ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'narrow'],
                'children' => [
                    [
                        'type' => 'component',
                        'definition' => 'cta_banner',
                        'props' => [
                            'title' => 'Bereit, Chamy auszuprobieren?',
                            'text' => 'Starte jetzt kostenlos – keine Kreditkarte nötig. Community-Edition für immer gratis.',
                            'cta_label' => 'Kostenlos herunterladen',
                            'cta_url' => '#pricing',
                            'style' => 'gradient',
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ],
];

$editorFeatures = [
    'root' => [
        'type' => 'root',
        'children' => [
            // ── Hero ──
            [
                'type' => 'component',
                'definition' => 'hero_banner',
                'props' => [
                    'badge' => 'Feature-Überblick',
                    'title' => 'Gebaut für das moderne Web',
                    'subtitle' => 'Jedes Feature in Chamy wurde mit Blick auf Developer Experience, Performance und Erweiterbarkeit designt.',
                    'primary_cta_label' => 'Pricing ansehen',
                    'primary_cta_url' => '/seite/pricing',
                    'secondary_cta_label' => 'Dokumentation',
                    'secondary_cta_url' => '#docs',
                    'bg_style' => 'gradient',
                ],
                'children' => [],
            ],
            // ── Visual Editor ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['badge' => 'Editor', 'title' => 'Der Visual Content Editor', 'subtitle' => 'Erstellen Sie komplexe Layouts ohne eine Zeile Code. Blöcke, Komponenten und Snippets – alles per Drag & Drop.', 'level' => 'h2', 'align' => 'center', 'size' => 'xl'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'two_columns',
                        'props' => ['ratio' => '50-50', 'gap' => 'xl', 'align' => 'center'],
                        'children' => [
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '🧱', 'title' => '4 Layout-Typen', 'text' => 'Section, Container, Grid, Columns – flexibel verschachtelbar.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '📦', 'title' => '18+ Blöcke', 'text' => 'Text, Bilder, Buttons, Code, Statistiken, Testimonials und mehr.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '🔄', 'title' => 'Echtzeit-Vorschau', 'text' => 'Sehen Sie sofort, wie Ihre Seite aussieht – im integrierten Preview-Panel.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '💾', 'title' => 'Import & Export', 'text' => 'Editor-Definitionen als JSON exportieren und auf anderen Instanzen importieren.'], 'children' => []],
                                ],
                            ],
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    [
                                        'type' => 'block',
                                        'definition' => 'code_block',
                                        'props' => [
                                            'language' => 'json',
                                            'filename' => 'editor-tree.json',
                                            'code' => "{\n  \"root\": {\n    \"type\": \"root\",\n    \"children\": [\n      {\n        \"type\": \"component\",\n        \"definition\": \"hero_banner\",\n        \"props\": {\n          \"title\": \"Hello World\",\n          \"bg_style\": \"gradient\"\n        }\n      }\n    ]\n  }\n}",
                                        ],
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // ── Theme System ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['badge' => 'Themes', 'title' => 'Leistungsstarkes Theme-System', 'subtitle' => 'Twig-Templates, Parent-Theme-Support, Hot Reload – erstellen Sie Themes, die sich genau an Ihre Marke anpassen.', 'level' => 'h2', 'align' => 'center', 'size' => 'xl'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'sm'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🌿', 'title' => 'Twig Templates', 'text' => 'Industriestandard-Template-Engine mit Vererbung, Includes und Custom Filters.', 'style' => 'glass'], 'children' => []],
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🎭', 'title' => 'Admin + Frontend', 'text' => 'Getrennte Theme-Umgebungen für Admin und Frontend mit eigenem Asset-System.', 'style' => 'glass'], 'children' => []],
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🔗', 'title' => 'Parent-Themes', 'text' => 'Child-Themes erben von Parent-Themes – überschreiben Sie nur, was Sie ändern wollen.', 'style' => 'glass'], 'children' => []],
                        ],
                    ],
                ],
            ],
            // ── Comparison Table ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['title' => 'Chamy vs. Andere CMS', 'level' => 'h2', 'align' => 'center', 'size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'component',
                        'definition' => 'comparison_table',
                        'props' => [
                            'columns' => ['Feature', 'Chamy', 'WordPress', 'Strapi'],
                            'rows' => [
                                ['Visual Editor', '✅', '✅ (Gutenberg)', '❌'],
                                ['REST API', '✅', '✅', '✅'],
                                ['PHP nativ', '✅', '✅', '❌ (Node.js)'],
                                ['Theme-System', '✅', '✅', '❌'],
                                ['Modul-System', '✅', '✅ (Plugins)', '✅'],
                                ['SQLite Support', '✅', '❌', '✅'],
                                ['Strict Types', '✅', '❌', 'N/A'],
                                ['Twig Templates', '✅', '❌ (PHP)', '❌'],
                                ['Zero-Config Start', '✅', '❌', '❌'],
                            ],
                            'highlight_column' => 1,
                        ],
                        'children' => [],
                    ],
                ],
            ],
            // ── API & Security ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'layout',
                        'definition' => 'two_columns',
                        'props' => ['ratio' => '50-50', 'gap' => 'xl', 'align' => 'stretch'],
                        'children' => [
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'md', 'max_width' => 'full'],
                                'children' => [
                                    ['type' => 'block', 'definition' => 'marketing_heading', 'props' => ['badge' => 'API', 'title' => 'Headless-Ready REST API', 'level' => 'h2', 'align' => 'left', 'size' => 'lg'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'rich_text', 'props' => ['content' => '<p>Nutzen Sie Chamy als Headless CMS und verbinden Sie es mit Ihrem React-, Vue- oder Svelte-Frontend. Authentifizierung via API-Token, Pagination und Filtering inklusive.</p>'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'code_block', 'props' => ['language' => 'bash', 'code' => "curl -H \"Authorization: Bearer <token>\" \\\n     https://example.com/api/v1/content/articles\n\n# Response: { data: [...], meta: { total: 42 } }"], 'children' => []],
                                ],
                            ],
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'md', 'max_width' => 'full'],
                                'children' => [
                                    ['type' => 'block', 'definition' => 'marketing_heading', 'props' => ['badge' => 'Security', 'title' => 'Sicherheit von Anfang an', 'level' => 'h2', 'align' => 'left', 'size' => 'lg'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '🔐', 'title' => 'RBAC', 'text' => 'Rollenbasiertes Berechtigungssystem mit feingranularen Permissions.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '🛡️', 'title' => 'CSRF & XSS Schutz', 'text' => 'Automatische Token-Validierung und Output-Escaping.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '🔑', 'title' => 'Sichere Sessions', 'text' => 'Verschlüsselte Session-Daten mit konfigurierbarem Lifetime.'], 'children' => []],
                                    ['type' => 'block', 'definition' => 'icon_text', 'props' => ['icon' => '📝', 'title' => 'Audit Logging', 'text' => 'Alle Admin-Aktionen werden protokolliert und sind nachvollziehbar.'], 'children' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // ── CTA ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'accent', 'padding' => 'xl', 'max_width' => 'narrow'],
                'children' => [
                    [
                        'type' => 'component',
                        'definition' => 'cta_banner',
                        'props' => [
                            'title' => 'Überzeugt? Starte jetzt.',
                            'text' => 'Die Community-Edition ist für immer kostenlos. Wählen Sie den Plan, der zu Ihnen passt.',
                            'cta_label' => 'Pricing ansehen',
                            'cta_url' => '/seite/pricing',
                            'style' => 'gradient',
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ],
];

$editorPricing = [
    'root' => [
        'type' => 'root',
        'children' => [
            [
                'type' => 'component',
                'definition' => 'hero_banner',
                'props' => [
                    'badge' => 'Pricing',
                    'title' => 'Einfache, faire Preise',
                    'subtitle' => 'Open Source im Kern, professioneller Support wenn Sie ihn brauchen. Keine versteckten Kosten.',
                    'bg_style' => 'gradient',
                ],
                'children' => [],
            ],
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'default', 'anchor_id' => 'pricing'],
                'children' => [
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            [
                                'type' => 'component',
                                'definition' => 'pricing_card',
                                'props' => [
                                    'plan_name' => 'Community',
                                    'price' => '€0',
                                    'period' => ' /für immer',
                                    'features' => ['Unbegrenzte Seiten & Artikel', 'Visual Editor', 'Theme-System', '3 Content-Types', 'Community Support', 'REST API'],
                                    'cta_label' => 'Kostenlos starten',
                                    'cta_url' => '#',
                                    'highlighted' => 'no',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'pricing_card',
                                'props' => [
                                    'plan_name' => 'Professional',
                                    'price' => '€29',
                                    'period' => '/Monat',
                                    'features' => ['Alles aus Community', 'Unbegrenzte Content-Types', 'Premium Themes', 'Modul-Marketplace', 'Prioritäts-Support', 'Automatische Backups', 'Custom Domains'],
                                    'cta_label' => 'Professional wählen',
                                    'cta_url' => '#',
                                    'highlighted' => 'yes',
                                    'badge' => 'Beliebt',
                                ],
                                'children' => [],
                            ],
                            [
                                'type' => 'component',
                                'definition' => 'pricing_card',
                                'props' => [
                                    'plan_name' => 'Enterprise',
                                    'price' => '€99',
                                    'period' => '/Monat',
                                    'features' => ['Alles aus Professional', 'SSO / SAML Integration', 'Dedizierter Support', 'SLA Garantie', 'Custom Development', 'On-Premise Option', 'Audit & Compliance'],
                                    'cta_label' => 'Kontakt aufnehmen',
                                    'cta_url' => '#',
                                    'highlighted' => 'no',
                                ],
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            // ── FAQ ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['title' => 'Häufig gestellte Fragen', 'level' => 'h2', 'align' => 'center', 'size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'component',
                        'definition' => 'faq_accordion',
                        'props' => [
                            'items' => [
                                ['q' => 'Kann ich Chamy kostenlos nutzen?', 'a' => 'Ja! Die Community-Edition ist Open Source und komplett kostenlos – ohne Einschränkungen bei Seiten oder Artikeln.'],
                                ['q' => 'Welche PHP-Version wird benötigt?', 'a' => 'Chamy benötigt mindestens PHP 8.2. Wir empfehlen PHP 8.3 für die beste Performance.'],
                                ['q' => 'Kann ich von WordPress migrieren?', 'a' => 'Ja, wir bieten ein Import-Tool für WordPress-Exports. Die meisten Inhalte können automatisch übernommen werden.'],
                                ['q' => 'Gibt es eine Hosting-Empfehlung?', 'a' => 'Chamy läuft auf jedem PHP-fähigen Hosting. Für die beste Performance empfehlen wir VPS oder Cloud-Hosting mit PHP-FPM.'],
                                ['q' => 'Kann ich Chamy als Headless CMS nutzen?', 'a' => 'Absolut! Die REST API ist vollständig und ermöglicht die Nutzung mit jedem Frontend-Framework.'],
                                ['q' => 'Wie funktioniert der Support?', 'a' => 'Community-Support via GitHub Issues und Discussions. Professional und Enterprise erhalten priorisierten E-Mail-Support.'],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
            // ── CTA ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'narrow'],
                'children' => [
                    [
                        'type' => 'component',
                        'definition' => 'cta_banner',
                        'props' => [
                            'title' => 'Starten Sie noch heute',
                            'text' => 'Keine Kreditkarte nötig. In unter 2 Minuten einsatzbereit.',
                            'cta_label' => 'Jetzt starten',
                            'cta_url' => '#',
                            'style' => 'gradient',
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ],
];

$editorAbout = [
    'root' => [
        'type' => 'root',
        'children' => [
            [
                'type' => 'component',
                'definition' => 'hero_banner',
                'props' => [
                    'badge' => 'Über Chamy',
                    'title' => 'Open Source, mit Leidenschaft gebaut',
                    'subtitle' => 'Chamy entstand aus der Überzeugung, dass Content Management einfacher, schneller und entwicklerfreundlicher sein muss.',
                    'bg_style' => 'gradient',
                ],
                'children' => [],
            ],
            // ── Mission ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'narrow'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['title' => 'Unsere Mission', 'level' => 'h2', 'align' => 'center', 'size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'rich_text',
                        'props' => [
                            'content' => '<p>Wir glauben daran, dass großartige Software offen sein sollte. Chamy CMS ist ein Community-Projekt, das die besten Ideen aus der PHP-Welt vereint: saubere Architektur, moderne Standards und eine unermüdliche Fokussierung auf Developer Experience.</p><p>Unser Ziel ist einfach: Das CMS bauen, das wir selbst immer nutzen wollten. Eines, das schnell einzurichten ist, Spaß beim Erweitern macht und Redakteure nicht vor Rätsel stellt.</p>',
                            'align' => 'center',
                        ],
                        'children' => [],
                    ],
                ],
            ],
            // ── Values ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'accent', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['title' => 'Werte, die uns antreiben', 'level' => 'h2', 'align' => 'center', 'size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'sm'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'three_columns',
                        'props' => ['gap' => 'lg'],
                        'children' => [
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🌟', 'title' => 'Open Source First', 'text' => 'Der gesamte Kern ist MIT-lizenziert. Keine Vendor-Lock-ins, keine versteckten Abhängigkeiten.', 'style' => 'glass'], 'children' => []],
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🏗️', 'title' => 'Quality Code', 'text' => 'Strict Types, PHPStan Level 6, automatisierte Tests. Wir nehmen Code-Qualität ernst.', 'style' => 'glass'], 'children' => []],
                            ['type' => 'component', 'definition' => 'feature_card', 'props' => ['icon' => '🤝', 'title' => 'Community Driven', 'text' => 'Jeder kann beitragen. Wir reviewen PRs schnell und schätzen jedes Feedback.', 'style' => 'glass'], 'children' => []],
                        ],
                    ],
                ],
            ],
            // ── Tech Stack ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'dark', 'padding' => 'xl', 'max_width' => 'default'],
                'children' => [
                    [
                        'type' => 'block',
                        'definition' => 'marketing_heading',
                        'props' => ['title' => 'Unser Tech Stack', 'level' => 'h2', 'align' => 'center', 'size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'md'],
                        'children' => [],
                    ],
                    [
                        'type' => 'snippet',
                        'definition' => 'badge_row',
                        'props' => [
                            'align' => 'center',
                            'badges' => [
                                ['label' => 'PHP 8.2+', 'color' => 'accent'],
                                ['label' => 'Twig 3', 'color' => 'purple'],
                                ['label' => 'SQLite', 'color' => 'success'],
                                ['label' => 'MySQL', 'color' => 'success'],
                                ['label' => 'Composer', 'color' => 'accent'],
                                ['label' => 'PHPUnit', 'color' => 'pink'],
                                ['label' => 'Vite', 'color' => 'purple'],
                                ['label' => 'REST', 'color' => 'accent'],
                            ],
                        ],
                        'children' => [],
                    ],
                    [
                        'type' => 'block',
                        'definition' => 'marketing_spacer',
                        'props' => ['size' => 'lg'],
                        'children' => [],
                    ],
                    [
                        'type' => 'layout',
                        'definition' => 'two_columns',
                        'props' => ['ratio' => '50-50', 'gap' => 'lg', 'align' => 'stretch'],
                        'children' => [
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '🐘', 'name' => 'PHP', 'version' => '8.2+', 'description' => 'Strict Types, Named Arguments, Fibers.'], 'children' => []],
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '🌿', 'name' => 'Twig', 'version' => '3.x', 'description' => 'Industriestandard Template Engine.'], 'children' => []],
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '🗄️', 'name' => 'SQLite / MySQL', 'version' => '', 'description' => 'Flexible Datenbank-Optionen.'], 'children' => []],
                                ],
                            ],
                            [
                                'type' => 'layout',
                                'definition' => 'marketing_section',
                                'props' => ['bg_style' => 'default', 'padding' => 'sm', 'max_width' => 'full'],
                                'children' => [
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '📦', 'name' => 'Composer', 'version' => '', 'description' => 'PSR-4 Autoloading & Package Management.'], 'children' => []],
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '🧪', 'name' => 'PHPUnit', 'version' => '10.x', 'description' => 'Automatisierte Unit & Integration Tests.'], 'children' => []],
                                    ['type' => 'snippet', 'definition' => 'tech_stack_item', 'props' => ['icon' => '⚡', 'name' => 'Vite', 'version' => '5.x', 'description' => 'Frontend Asset Bundling & HMR.'], 'children' => []],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // ── CTA ──
            [
                'type' => 'layout',
                'definition' => 'marketing_section',
                'props' => ['bg_style' => 'default', 'padding' => 'xl', 'max_width' => 'narrow'],
                'children' => [
                    [
                        'type' => 'component',
                        'definition' => 'cta_banner',
                        'props' => [
                            'title' => 'Werde Teil der Community',
                            'text' => 'Chamy wächst mit seiner Community. Starte jetzt und werde Teil von etwas Großem.',
                            'cta_label' => 'Auf GitHub ansehen',
                            'cta_url' => '#',
                            'style' => 'gradient',
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ],
];

return [
    // ──── Marketing Pages ────
    [
        'id'           => 1,
        'uuid'         => 'p1a2b3c4-d5e6-4f7a-8b9c-0d1e2f3a4b5c',
        'content_type' => 'page',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 2,
        'data'         => json_encode([
            'title'           => 'Startseite',
            'slug'            => 'startseite',
            'excerpt'         => 'Chamy CMS – Das modulare PHP Content-Management-System für das moderne Web.',
            'body'            => '',
            'editor_data'     => $editorStartseite,
            'seo_title'       => 'Chamy CMS – Modulares Open-Source Content Management',
            'seo_description' => 'Chamy ist das modulare PHP CMS für Entwickler und Redakteure. Visual Editor, Theme-System, REST API und mehr.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-01 00:00:00',
        'updated_at'   => '2025-01-20 10:00:00',
    ],
    [
        'id'           => 2,
        'uuid'         => 'p2b3c4d5-e6f7-4a8b-9c0d-1e2f3a4b5c6d',
        'content_type' => 'page',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 2,
        'data'         => json_encode([
            'title'           => 'Features',
            'slug'            => 'features',
            'excerpt'         => 'Alle Features von Chamy CMS im Überblick.',
            'body'            => '',
            'editor_data'     => $editorFeatures,
            'seo_title'       => 'Features – Chamy CMS',
            'seo_description' => 'Visual Editor, Theme-System, REST API, Modul-System – entdecken Sie alle Features von Chamy CMS.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-02 10:00:00',
        'updated_at'   => '2025-01-20 10:00:00',
    ],
    [
        'id'           => 3,
        'uuid'         => 'p3c4d5e6-f7a8-4b9c-0d1e-2f3a4b5c6d7e',
        'content_type' => 'page',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'           => 'Pricing',
            'slug'            => 'pricing',
            'excerpt'         => 'Einfache, faire Preise für Chamy CMS.',
            'body'            => '',
            'editor_data'     => $editorPricing,
            'seo_title'       => 'Pricing – Chamy CMS',
            'seo_description' => 'Community-Edition kostenlos, Professional ab €29/Monat. Wählen Sie den Plan, der zu Ihnen passt.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-05 14:00:00',
        'updated_at'   => '2025-01-20 10:00:00',
    ],
    [
        'id'           => 4,
        'uuid'         => 'p4d5e6f7-a8b9-4c0d-1e2f-3a4b5c6d7e8f',
        'content_type' => 'page',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'           => 'Über uns',
            'slug'            => 'about',
            'excerpt'         => 'Das Team und die Mission hinter Chamy CMS.',
            'body'            => '',
            'editor_data'     => $editorAbout,
            'seo_title'       => 'Über uns – Chamy CMS',
            'seo_description' => 'Lernen Sie das Open-Source-Projekt und die Community hinter Chamy CMS kennen.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-03 09:00:00',
        'updated_at'   => '2025-01-20 10:00:00',
    ],
    [
        'id'           => 11,
        'uuid'         => 'p11aabb-cc00-4d1e-9f3a-4b5c6d7e8f9a',
        'content_type' => 'page',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'       => 'Datenschutz',
            'slug'        => 'datenschutz',
            'excerpt'     => 'Datenschutzerklärung des Chamy CMS.',
            'body'        => '<h2>Datenschutzerklärung</h2><p>Der Schutz Ihrer persönlichen Daten ist uns ein besonderes Anliegen. Wir verarbeiten Ihre Daten daher ausschließlich auf Grundlage der gesetzlichen Bestimmungen (DSGVO, TKG 2003).</p><h3>Cookies</h3><p>Unsere Website verwendet sogenannte Cookies. Dabei handelt es sich um kleine Textdateien, die mit Hilfe des Browsers auf Ihrem Endgerät abgelegt werden.</p>',
            'seo_title'   => 'Datenschutz – Chamy CMS',
            'seo_description' => 'Datenschutzerklärung des Chamy CMS.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-03 09:00:00',
        'updated_at'   => '2025-01-03 09:00:00',
    ],

    // ──── Blog Articles ────
    [
        'id'           => 5,
        'uuid'         => 'a5e6f7a8-b9c0-4d1e-2f3a-4b5c6d7e8f9a',
        'content_type' => 'article',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'       => 'Chamy CMS v1.0 veröffentlicht',
            'slug'        => 'chamy-cms-v1-veroeffentlicht',
            'teaser'      => 'Nach monatelanger Entwicklung bietet Chamy v1.0 ein vollständiges Modul- und Theme-System, eine durchdachte Admin-Oberfläche und eine leistungsstarke API.',
            'body'        => '<p>Wir freuen uns, die erste stabile Version von Chamy CMS bekannt zu geben! Nach monatelanger Entwicklung bietet Chamy v1.0 ein vollständiges Modul- und Theme-System, eine durchdachte Admin-Oberfläche und eine leistungsstarke API.</p><h3>Highlights</h3><ul><li>Modulares Kernel-System mit Manager-Registry</li><li>Neon-Dark Admin-Theme mit Light-Mode</li><li>RESTful API mit Token-Authentifizierung</li><li>Marketplace für Module und Themes</li></ul><p>Chamy wurde von Grund auf in modernem PHP 8.2+ geschrieben und nutzt strikt getypte Klassen, Named Arguments und die neuesten Sprachfeatures. Der Visual Editor ermöglicht es, Seiten aus vorgefertigten Blocks und Komponenten zusammenzubauen – ganz ohne Programmierkenntnisse.</p>',
            'author'      => 'Chamy Team',
            'category'    => 'release',
            'seo_title'   => 'Chamy CMS v1.0 veröffentlicht',
            'seo_description' => 'Die erste stabile Version von Chamy CMS ist da. Visual Editor, Theme-System, REST API und mehr.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-10 08:00:00',
        'updated_at'   => '2025-01-10 08:00:00',
    ],
    [
        'id'           => 6,
        'uuid'         => 'a6f7a8b9-c0d1-4e2f-3a4b-5c6d7e8f9a0b',
        'content_type' => 'article',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 3,
        'data'         => json_encode([
            'title'       => 'Ein eigenes Theme erstellen',
            'slug'        => 'eigenes-theme-erstellen',
            'teaser'      => 'Themes in Chamy basieren auf dem leistungsstarken Twig-Template-System. Lernen Sie, wie Sie Ihr eigenes Theme entwickeln.',
            'body'        => '<p>Themes in Chamy basieren auf dem leistungsstarken Twig-Template-System. In diesem Tutorial zeigen wir Ihnen Schritt für Schritt, wie Sie ein eigenes Frontend-Theme erstellen.</p><h3>Voraussetzungen</h3><p>Grundkenntnisse in HTML, CSS und Twig sind hilfreich. Chamy-Themes folgen einer klaren Verzeichnisstruktur mit einer theme.json als Ausgangspunkt.</p><h3>Verzeichnisstruktur</h3><p>Erstellen Sie einen neuen Ordner unter <code>themes/frontend/mein-theme/</code> und legen Sie folgende Dateien an:</p><ul><li><code>theme.json</code> – Manifest mit Meta-Informationen</li><li><code>templates/base.twig</code> – Basis-Layout</li><li><code>templates/home.twig</code> – Startseite</li><li><code>assets/css/frontend.css</code> – Styles</li></ul><p>Chamy erkennt Ihr Theme automatisch und bietet es im Admin-Bereich zur Auswahl an.</p>',
            'author'      => 'Max Mustermann',
            'category'    => 'tutorial',
            'seo_title'   => 'Theme-Entwicklung für Chamy CMS',
            'seo_description' => 'Lernen Sie, wie Sie ein eigenes Twig-Theme für Chamy CMS erstellen.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 2,
        'updated_by'   => 2,
        'created_at'   => '2025-01-11 14:00:00',
        'updated_at'   => '2025-01-13 10:30:00',
    ],
    [
        'id'           => 7,
        'uuid'         => 'a7a8b9c0-d1e2-4f3a-4b5c-6d7e8f9a0b1c',
        'content_type' => 'article',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'       => 'Module entwickeln – Ein Leitfaden',
            'slug'        => 'module-entwickeln-leitfaden',
            'teaser'      => 'Das Modulsystem ist das Herzstück von Chamy CMS. Erfahren Sie, wie Sie eigene Module bauen.',
            'body'        => '<p>Das Modulsystem ist das Herzstück von Chamy CMS. Module erweitern die Funktionalität, ohne den Kern zu verändern. Jedes Modul besitzt eine manifest.json, eine Hauptdatei und kann eigene Routen, Hooks, Templates und Migrationen mitbringen.</p><h3>Das Hook-System</h3><p>Über Hooks können Module sich an verschiedenen Stellen im System einklinken – zum Beispiel im Dashboard, in der Navigation oder bei Content-Events.</p><h3>Erste Schritte</h3><p>Erstellen Sie unter <code>modules/mein-modul/</code> eine <code>manifest.json</code> und eine <code>Module.php</code>. Das System erkennt und lädt Ihr Modul automatisch.</p>',
            'author'      => 'Max Mustermann',
            'category'    => 'tutorial',
            'seo_title'   => 'Module für Chamy CMS entwickeln',
            'seo_description' => 'So erstellen Sie eigene Module für das Chamy CMS.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 2,
        'updated_by'   => 2,
        'created_at'   => '2025-01-12 09:00:00',
        'updated_at'   => '2025-01-12 09:00:00',
    ],
    [
        'id'           => 8,
        'uuid'         => 'a8b9c0d1-e2f3-4a4b-5c6d-7e8f9a0b1c2d',
        'content_type' => 'article',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 2,
        'data'         => json_encode([
            'title'       => 'Performance-Optimierung in Chamy',
            'slug'        => 'performance-optimierung',
            'teaser'      => 'Chamy ist von Grund auf auf Geschwindigkeit ausgelegt. Lernen Sie die besten Optimierungsstrategien.',
            'body'        => '<p>Chamy ist von Grund auf auf Geschwindigkeit ausgelegt. In diesem Artikel zeigen wir Best Practices für die Optimierung Ihrer Chamy-Installation.</p><h3>Caching</h3><p>Aktivieren Sie das integrierte Caching-System über <code>config/cache.php</code>. Chamy unterstützt File-Cache und Redis-Adapter.</p><h3>Template-Kompilierung</h3><p>Im Produktionsmodus kompiliert Twig die Templates einmalig, was nachfolgende Requests erheblich beschleunigt.</p><h3>Datenbank-Optimierung</h3><p>Nutzen Sie die Indexierung für häufig abgefragte Felder und aktivieren Sie Query-Caching für wiederkehrende Abfragen.</p>',
            'author'      => 'Chamy Team',
            'category'    => 'development',
            'seo_title'   => 'Performance-Tipps für Chamy CMS',
            'seo_description' => 'Best Practices für eine schnelle Chamy CMS Installation.',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-14 16:00:00',
        'updated_at'   => '2025-01-20 12:00:00',
    ],

    // ──── Snippets ────
    [
        'id'           => 9,
        'uuid'         => 's9c0d1e2-f3a4-4b5c-6d7e-8f9a0b1c2d3e',
        'content_type' => 'snippet',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'    => 'Footer-Hinweis',
            'slug'     => 'footer-hinweis',
            'body'     => '&copy; 2025 Chamy CMS. Alle Rechte vorbehalten.',
            'position' => 'footer',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-01 00:00:00',
        'updated_at'   => '2025-01-01 00:00:00',
    ],
    [
        'id'           => 10,
        'uuid'         => 's1d1e2f3-a4b5-4c6d-7e8f-9a0b1c2d3e4f',
        'content_type' => 'snippet',
        'locale'       => 'de',
        'status'       => 'published',
        'version'      => 1,
        'data'         => json_encode([
            'title'    => 'Wartungs-Banner',
            'slug'     => 'wartungs-banner',
            'body'     => 'Geplante Wartungsarbeiten am 20. Januar 2025 zwischen 02:00 und 04:00 Uhr.',
            'position' => 'header',
        ], JSON_UNESCAPED_UNICODE),
        'created_by'   => 1,
        'updated_by'   => 1,
        'created_at'   => '2025-01-08 10:00:00',
        'updated_at'   => '2025-01-08 10:00:00',
    ],
];
