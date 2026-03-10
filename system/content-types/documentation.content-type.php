<?php

return [
    'id'                    => 'documentation',
    'label'                 => 'Dokumentation',
    'label_plural'          => 'Dokumentationen',
    'description'           => 'Strukturierter Dokumentationseintrag',
    'source'                => 'system',
    'version'               => '1.0.0',
    'group'                 => 'docs',
    'icon'                  => '📖',
    'is_translatable'       => true,
    'is_revisionable'       => true,
    'is_publicly_queryable' => true,
    'fields' => [
        'title' => [
            'type'     => 'text',
            'label'    => 'Titel',
            'required' => true,
            'translatable' => true,
            'searchable'   => true,
            'max_length'   => 255,
        ],
        'slug' => [
            'type'     => 'slug',
            'label'    => 'Slug',
            'required' => true,
            'unique'   => true,
        ],
        'category' => [
            'type'    => 'select',
            'label'   => 'Kategorie',
            'options' => [
                'getting-started' => 'Erste Schritte',
                'guides'          => 'Anleitungen',
                'api'             => 'API-Referenz',
                'modules'         => 'Module',
                'themes'          => 'Themes',
                'advanced'        => 'Fortgeschritten',
            ],
            'required' => true,
        ],
        'sort_order' => [
            'type'    => 'text',
            'label'   => 'Sortierung',
            'required' => false,
        ],
        'excerpt' => [
            'type'  => 'textarea',
            'label' => 'Kurzfassung',
        ],
        'body' => [
            'type'     => 'richtext',
            'label'    => 'Inhalt',
            'required' => true,
            'translatable' => true,
        ],
        'seo_title' => [
            'type'      => 'text',
            'label'     => 'SEO Titel',
            'max_length'=> 70,
        ],
        'seo_description' => [
            'type'      => 'textarea',
            'label'     => 'SEO Beschreibung',
            'max_length'=> 160,
        ],
    ],
];
