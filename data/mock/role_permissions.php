<?php

return [
    // Admin -> all permissions
    ['role_id' => 1, 'permission_key' => 'users.manage'],
    ['role_id' => 1, 'permission_key' => 'roles.manage'],
    ['role_id' => 1, 'permission_key' => 'permissions.manage'],
    ['role_id' => 1, 'permission_key' => 'content.create'],
    ['role_id' => 1, 'permission_key' => 'content.edit'],
    ['role_id' => 1, 'permission_key' => 'content.publish'],
    ['role_id' => 1, 'permission_key' => 'system.manage'],
    ['role_id' => 1, 'permission_key' => 'system.mods'],
    ['role_id' => 1, 'permission_key' => 'system.themes'],

    // Editor -> content management
    ['role_id' => 2, 'permission_key' => 'content.create'],
    ['role_id' => 2, 'permission_key' => 'content.edit'],
    ['role_id' => 2, 'permission_key' => 'content.publish'],

    // Author -> create + edit own
    ['role_id' => 3, 'permission_key' => 'content.create'],
    ['role_id' => 3, 'permission_key' => 'content.edit'],
    // Mods role -> mods permission
    ['role_id' => 4, 'permission_key' => 'system.mods'],
    // Themes role -> themes permission
    ['role_id' => 5, 'permission_key' => 'system.themes'],
];
