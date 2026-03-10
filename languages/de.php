<?php

return [
    'admin' => [
        'nav' => [
            'main' => 'Hauptmenü',
            'content' => 'Inhalte',
            'system' => 'System',
        ],
        'dashboard' => 'Dashboard',
        'modules' => 'Module',
        'themes'  => 'Themes',
        'users'   => 'Benutzer',
        'settings'=> 'Einstellungen',
        'clear_cache' => 'Cache leeren',
        'logout' => 'Abmelden',
        'profile' => 'Profil',
        'toggle_theme' => 'Design wechseln',
        'manage_profile' => 'Dein Account und Einstellungen',
        'content_created' => 'Inhalt wurde erstellt.',
        'content_saved' => 'Inhalt wurde gespeichert.',
        'content_deleted' => 'Inhalt wurde gelöscht.',

        // Common labels
        'id'           => 'ID',
        'name'         => 'Name',
        'key'          => 'Key',
        'description'  => 'Beschreibung',
        'group'        => 'Gruppe',
        'email'        => 'E-Mail',
        'username'     => 'Benutzername',
        'display_name' => 'Anzeigename',
        'password'     => 'Passwort',
        'password_edit'=> 'Passwort (leer lassen = unverändert)',
        'role'         => 'Rolle',
        'status'       => 'Status',
        'actions'      => 'Aktionen',
        'last_login'   => 'Letzter Login',
        'last_seen'    => 'Zuletzt eingeloggt:',
        'created_at'   => 'Erstellt',
        'save'         => 'Speichern',
        'create'       => 'erstellen',
        'edit'         => 'Bearbeiten',
        'delete'       => 'Löschen',
        'cancel'       => 'Abbrechen',
        'back'         => 'Zurück',
        'yes'          => 'Ja',
        'no'           => 'Nein',
        'active'       => 'Aktiv',
        'inactive'     => 'Inaktiv',
        'total'        => 'gesamt',
        'installed'    => 'installiert',
        'no_description'=> 'Keine Beschreibung',
        'danger_zone'  => 'Gefahrenzone',
        'confirm_delete'=> 'Wirklich löschen?',
        'settings_group'=> 'Einstellungen',
        'save_settings' => 'Einstellungen speichern',
        'required'     => 'Pflichtfeld',
        'login'        => 'Anmelden',
        'title'        => 'Titel',
        'slug'         => 'Slug',
        'state'        => 'Status',
        'created'      => 'Erstellt',
        'updated'      => 'Aktualisiert',
        'entries'      => 'Einträge',
        'no_entries'   => 'Keine Einträge',
        'create_first' => 'Ersten Eintrag erstellen',
        'page'         => 'Seite',
        'prev'         => 'Zurück',
        'next'         => 'Weiter',
        'content_fields'=> 'Inhaltsfelder',
        'current_state' => 'Aktueller Status',
        'versions'     => 'Versionen',
        'system_info'  => 'System-Info',
        'locale'       => 'Sprache',
        'quick_actions' => 'Schnellzugriff',
        'no_title'     => 'Ohne Titel',

        // Users page
        'new_user'          => '+ Neuer Benutzer',
        'edit_user'         => 'Benutzer bearbeiten',
        'new_user_title'    => 'Neuer Benutzer',
        'create_user'       => 'Erstellen',
        'delete_user'       => 'Benutzer löschen',
        'confirm_delete_user'=> 'Benutzer wirklich löschen?',
        'no_users'          => 'Keine Benutzer vorhanden.',
        'user_settings'     => 'Einstellungen',

        // Tabs
        'tab_users'       => 'Benutzer',
        'tab_roles'       => 'Rollen',
        'tab_permissions' => 'Berechtigungen',

        // Roles
        'create_role'     => '+ Rolle erstellen',
        'no_roles'        => 'Keine Rollen vorhanden.',
        'permissions_info'=> 'Berechtigungen können Rollen zugewiesen werden.',
        'no_permissions'  => 'Keine Berechtigungen vorhanden.',

        // Modules
        'no_modules'      => 'Keine Module installiert',
        'no_modules_hint' => 'Lege ein Modul unter <code>modules/</code> mit einer <code>manifest.json</code> ab.',

        // Themes
        'admin_themes'     => 'Admin-Themes',
        'frontend_themes'  => 'Frontend-Themes',
        'no_admin_themes'  => 'Keine Admin-Themes gefunden.',
        'no_frontend_themes'=> 'Keine Frontend-Themes gefunden.',

        // Settings
        'no_settings'      => 'Keine Einstellungen vorhanden.',

        // Error pages
        'error_403_title' => 'Zugriff verweigert',
        'error_403_text'  => 'Du hast keine Berechtigung, auf diese Ressource zuzugreifen.',
        'error_404_title' => 'Seite nicht gefunden',
        'error_404_text'  => 'Die angeforderte Seite existiert nicht oder wurde verschoben.',
        'error_500_title' => 'Interner Serverfehler',
        'error_500_text'  => 'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuche es später erneut.',
        'back_to_home'    => 'Zur Startseite',
    ],

    'content_types' => [
        'draft'     => 'Entwurf',
        'review'    => 'In Prüfung',
        'published' => 'Veröffentlicht',
        'archived'  => 'Archiviert',
    ],

    'validation' => [
        'login_failed' => 'Benutzername oder Passwort falsch.',
    ],

    'system' => [
        'not_found' => 'Nicht gefunden',
        'page_not_found' => 'Die Seite wurde nicht gefunden.',
    ],
];
