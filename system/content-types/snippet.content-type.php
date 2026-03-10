<?php

return [
    'id'                    => 'snippet',
    'label'                 => 'Snippet',
    'label_plural'          => 'Snippets',
    'description'           => 'Wiederverwendbarer Inhaltsblock (Header, Footer, Banner, etc.)',
    'source'                => 'system',
    'version'               => '1.0.0',
    'group'                 => 'components',
    'icon'                  => '🧩',
    'is_translatable'       => true,
    'is_revisionable'       => true,
    'is_publicly_queryable' => false,
    'fields' => [
        'title' => [
            'type'     => 'text',
            'label'    => 'Bezeichnung',
            'required' => true,
            'searchable' => true,
        ],
        'slug' => [
            'type'     => 'slug',
            'label'    => 'Slug / Identifier',
            'required' => true,
            'unique'   => true,
        ],
        'location' => [
            'type'    => 'select',
            'label'   => 'Einbindungsort',
            'options' => [
                'header'  => 'Header',
                'footer'  => 'Footer',
                'sidebar' => 'Sidebar',
                'banner'  => 'Banner',
                'custom'  => 'Benutzerdefiniert',
            ],
        ],
        'body' => [
            'type'     => 'richtext',
            'label'    => 'Inhalt',
            'required' => true,
            'translatable' => true,
        ],
    ],
];
