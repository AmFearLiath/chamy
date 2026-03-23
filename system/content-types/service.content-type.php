<?php

/**
 * Content Type: Service (Leistung)
 *
 * Abbildung der Leistungsseiten für die Elektro-Keilitz-Website.
 * Jeder Service hat strukturierte Abschnitte: Hero, Zielgruppe, Leistungsumfang,
 * Ablauf, FAQ und CTA – jeweils zweisprachig (DE/EN).
 */
return [
    'id'                    => 'service',
    'label'                 => 'Leistung',
    'label_plural'          => 'Leistungen',
    'description'           => 'Strukturierte Leistungsseite mit Hero, Zielgruppe, Scope, Ablauf, FAQ und CTA',
    'source'                => 'system',
    'version'               => '1.0.0',
    'group'                 => 'pages',
    'icon'                  => '⚡',
    'is_translatable'       => true,
    'is_revisionable'       => true,
    'is_publicly_queryable' => true,
    'fields' => [
        'title' => [
            'type'         => 'text',
            'label'        => 'Titel',
            'required'     => true,
            'translatable' => true,
            'searchable'   => true,
            'max_length'   => 255,
        ],
        'slug' => [
            'type'         => 'slug',
            'label'        => 'Slug',
            'required'     => true,
            'unique'       => true,
            'translatable' => true,
        ],
        'sort_order' => [
            'type'    => 'number',
            'label'   => 'Reihenfolge',
            'default' => 0,
        ],
        'icon' => [
            'type'  => 'text',
            'label' => 'Icon-Dateiname (z.B. elektro.svg)',
        ],
        'micro_info' => [
            'type'         => 'text',
            'label'        => 'Kurzinfo (z.B. Neubau • Sanierung • Modernisierung)',
            'translatable' => true,
        ],
        'hero_heading' => [
            'type'         => 'text',
            'label'        => 'Hero Überschrift',
            'required'     => true,
            'translatable' => true,
        ],
        'hero_subheading' => [
            'type'         => 'textarea',
            'label'        => 'Hero Untertitel',
            'translatable' => true,
        ],
        'hero_bullets' => [
            'type'         => 'json',
            'label'        => 'Hero Aufzählung (JSON-Array)',
            'translatable' => true,
        ],
        'target_heading' => [
            'type'         => 'text',
            'label'        => 'Zielgruppen-Überschrift',
            'translatable' => true,
        ],
        'target_items' => [
            'type'         => 'json',
            'label'        => 'Zielgruppen-Liste (JSON-Array)',
            'translatable' => true,
        ],
        'scope_heading' => [
            'type'         => 'text',
            'label'        => 'Leistungsumfang Überschrift',
            'translatable' => true,
        ],
        'scope_items' => [
            'type'         => 'json',
            'label'        => 'Leistungsumfang (JSON-Array mit title/description)',
            'translatable' => true,
        ],
        'process_heading' => [
            'type'         => 'text',
            'label'        => 'Ablauf Überschrift',
            'translatable' => true,
        ],
        'process_steps' => [
            'type'         => 'json',
            'label'        => 'Ablauf-Schritte (JSON-Array mit title/description)',
            'translatable' => true,
        ],
        'faq_heading' => [
            'type'         => 'text',
            'label'        => 'FAQ Überschrift',
            'translatable' => true,
        ],
        'faq_items' => [
            'type'         => 'json',
            'label'        => 'FAQ (JSON-Array mit question/answer)',
            'translatable' => true,
        ],
        'cta_heading' => [
            'type'         => 'text',
            'label'        => 'CTA Überschrift',
            'translatable' => true,
        ],
        'cta_text' => [
            'type'         => 'textarea',
            'label'        => 'CTA Text',
            'translatable' => true,
        ],
        'cta_primary_label' => [
            'type'         => 'text',
            'label'        => 'CTA Button Label',
            'translatable' => true,
        ],
        'cta_primary_href' => [
            'type'  => 'text',
            'label' => 'CTA Button Link',
        ],
        'seo_title' => [
            'type'         => 'text',
            'label'        => 'SEO-Titel',
            'translatable' => true,
            'max_length'   => 70,
        ],
        'seo_description' => [
            'type'         => 'textarea',
            'label'        => 'SEO-Beschreibung',
            'translatable' => true,
            'max_length'   => 160,
        ],
    ],
];
