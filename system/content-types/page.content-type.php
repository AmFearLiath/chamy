<?php

return [
    'id'                    => 'page',
    'label'                 => 'Seite',
    'description'           => 'Standard-Seiteninhalt',
    'source'                => 'system',
    'version'               => '1.0.0',
    'group'                 => 'pages',
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
        'excerpt' => [
            'type'         => 'textarea',
            'label'        => 'Einleitung',
            'required'     => false,
            'translatable' => true,
        ],
        'body' => [
            'type'         => 'richtext',
            'label'        => 'Inhalt',
            'required'     => false,
            'translatable' => true,
            'searchable'   => true,
        ],
        'seo_title' => [
            'type'         => 'text',
            'label'        => 'SEO-Titel',
            'required'     => false,
            'translatable' => true,
            'max_length'   => 70,
        ],
        'seo_description' => [
            'type'         => 'textarea',
            'label'        => 'SEO-Beschreibung',
            'required'     => false,
            'translatable' => true,
            'max_length'   => 160,
        ],
        'layout' => [
            'type'         => 'select',
            'label'        => 'Layout',
            'required'     => false,
            'default'      => 'default',
        ],
    ],
];
