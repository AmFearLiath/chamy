<?php

declare(strict_types=1);

/**
 * Admin Routes – Registered by Kernel after boot.
 *
 * Expects $router to be an instance of \Chamy\Core\Routing\Router.
 */

use Chamy\Core\Controllers\AdminController;

// Auth
$router->get('/admin/login', [AdminController::class, 'loginForm'], 'admin.login');
$router->post('/admin/login', [AdminController::class, 'loginSubmit'], 'admin.login.submit');
$router->get('/admin/logout', [AdminController::class, 'logout'], 'admin.logout');

// Dashboard
$router->get('/admin', [AdminController::class, 'dashboard'], 'admin.dashboard');

// Content CRUD
$router->get('/admin/content/{type}', [AdminController::class, 'contentList'], 'admin.content.list');
$router->get('/admin/content/{type}/create', [AdminController::class, 'contentCreate'], 'admin.content.create');
$router->post('/admin/content/{type}/store', [AdminController::class, 'contentStore'], 'admin.content.store');
$router->get('/admin/content/{type}/{id}/edit', [AdminController::class, 'contentEdit'], 'admin.content.edit');
$router->post('/admin/content/{type}/{id}/update', [AdminController::class, 'contentUpdate'], 'admin.content.update');
$router->post('/admin/content/{type}/{id}/delete', [AdminController::class, 'contentDelete'], 'admin.content.delete');

// Users
$router->get('/admin/users', [AdminController::class, 'usersList'], 'admin.users');
$router->get('/admin/users/create', [AdminController::class, 'userCreate'], 'admin.users.create');
$router->post('/admin/users/store', [AdminController::class, 'userStore'], 'admin.users.store');
$router->get('/admin/users/{id}/edit', [AdminController::class, 'userEdit'], 'admin.users.edit');
$router->post('/admin/users/{id}/update', [AdminController::class, 'userUpdate'], 'admin.users.update');
$router->post('/admin/users/{id}/delete', [AdminController::class, 'userDelete'], 'admin.users.delete');
$router->post('/admin/users/{id}/toggle-status', [AdminController::class, 'userToggleStatus'], 'admin.users.toggle_status');

// Roles
$router->get('/admin/roles/create', [AdminController::class, 'roleCreate'], 'admin.roles.create');
$router->post('/admin/roles/store', [AdminController::class, 'roleStore'], 'admin.roles.store');
$router->get('/admin/roles/{id}/edit', [AdminController::class, 'roleEdit'], 'admin.roles.edit');
$router->post('/admin/roles/{id}/update', [AdminController::class, 'roleUpdate'], 'admin.roles.update');
$router->post('/admin/roles/{id}/delete', [AdminController::class, 'roleDelete'], 'admin.roles.delete');

// Permissions
$router->get('/admin/permissions/create', [AdminController::class, 'permissionCreate'], 'admin.permissions.create');
$router->post('/admin/permissions/store', [AdminController::class, 'permissionStore'], 'admin.permissions.store');
$router->get('/admin/permissions/{id}/edit', [AdminController::class, 'permissionEdit'], 'admin.permissions.edit');
$router->post('/admin/permissions/{id}/update', [AdminController::class, 'permissionUpdate'], 'admin.permissions.update');
$router->post('/admin/permissions/{id}/delete', [AdminController::class, 'permissionDelete'], 'admin.permissions.delete');

// Settings
$router->get('/admin/settings', [AdminController::class, 'settingsPage'], 'admin.settings');
$router->post('/admin/settings', [AdminController::class, 'settingsUpdate'], 'admin.settings.update');

// Global Trash
$router->get('/admin/trash', [AdminController::class, 'trashPage'], 'admin.trash');
$router->post('/admin/trash/{id}/restore', [AdminController::class, 'trashRestore'], 'admin.trash.restore');
$router->post('/admin/trash/{id}/purge', [AdminController::class, 'trashPurge'], 'admin.trash.purge');

// Modules
$router->get('/admin/modules', [AdminController::class, 'modulesList'], 'admin.modules');

// Profile
$router->get('/admin/profile', [AdminController::class, 'profilePage'], 'admin.profile');
$router->post('/admin/profile', [AdminController::class, 'profileUpdate'], 'admin.profile.update');

// Themes
$router->get('/admin/themes', [AdminController::class, 'themesList'], 'admin.themes');
$router->get('/admin/themes/installed', [AdminController::class, 'themesInstalled'], 'admin.themes.installed');
$router->get('/admin/themes/installed/{area}/{id}', [AdminController::class, 'themesInstalledDetail'], 'admin.themes.installed.detail');
$router->post('/admin/themes/installed/{area}/{id}/update', [AdminController::class, 'themesInstalledDetailUpdate'], 'admin.themes.installed.detail.update');
$router->post('/admin/themes/installed/{area}/{id}/create-child', [AdminController::class, 'themesInstalledCreateChild'], 'admin.themes.installed.create_child');
// Quick actions for installed themes (POST)
$router->post('/admin/themes/installed/{area}/{id}/activate', [AdminController::class, 'themesActivate'], 'admin.themes.installed.activate');
$router->post('/admin/themes/installed/{area}/{id}/toggle-status', [AdminController::class, 'themesToggleStatus'], 'admin.themes.installed.toggle_status');
$router->post('/admin/themes/installed/{area}/{id}/uninstall', [AdminController::class, 'themesUninstall'], 'admin.themes.installed.uninstall');
$router->get('/admin/themes/config', [AdminController::class, 'themesConfigPage'], 'admin.themes.config');
$router->post('/admin/themes/config', [AdminController::class, 'themesConfigUpdate'], 'admin.themes.config.update');
$router->get('/admin/themes/marketplace', [AdminController::class, 'themesMarketplace'], 'admin.themes.marketplace');
$router->get('/admin/themes/marketplace/config', [AdminController::class, 'themesMarketplaceConfigPage'], 'admin.themes.marketplace.config');
$router->post('/admin/themes/marketplace/config', [AdminController::class, 'themesMarketplaceConfigUpdate'], 'admin.themes.marketplace.config.update');
$router->get('/admin/themes/marketplace/{id}', [AdminController::class, 'themesMarketplaceDetail'], 'admin.themes.marketplace.detail');
