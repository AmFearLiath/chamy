<?php

return [
    'id'                    => 'media_entry',
    'label'                 => 'Media-Eintrag',
    'label_plural'          => 'Media-Einträge',
    'description'           => 'Verwaltung von Mediendateien (Bilder, Videos, Dokumente)',
    'source'                => 'system',
    'version'               => '1.0.0',
    'group'                 => 'media',
    'icon'                  => '🖼️',
    'is_translatable'       => false,
    'is_revisionable'       => false,
    'is_publicly_queryable' => true,
    'fields' => [
        'title' => [
            'type'     => 'text',
            'label'    => 'Titel',
            'required' => true,
            'searchable' => true,
        ],
        'slug' => [
            'type'     => 'slug',
            'label'    => 'Slug',
            'required' => true,
            'unique'   => true,
        ],
        'file_path' => [
            'type'     => 'text',
            'label'    => 'Dateipfad',
            'required' => true,
        ],
        'file_name' => [
            'type'  => 'text',
            'label' => 'Dateiname',
        ],
        'mime_type' => [
            'type'  => 'text',
            'label' => 'MIME-Typ',
        ],
        'file_size' => [
            'type'  => 'number',
            'label' => 'Dateigröße (Bytes)',
        ],
        'alt_text' => [
            'type'  => 'text',
            'label' => 'Alternativer Text',
            'translatable' => true,
        ],
        'caption' => [
            'type'  => 'textarea',
            'label' => 'Bildunterschrift',
            'translatable' => true,
        ],
        'media_type' => [
            'type'    => 'select',
            'label'   => 'Medientyp',
            'options' => [
                'image'    => 'Bild',
                'video'    => 'Video',
                'audio'    => 'Audio',
                'document' => 'Dokument',
                'other'    => 'Sonstiges',
            ],
        ],
    ],
];
