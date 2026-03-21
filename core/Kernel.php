<?php

declare(strict_types=1);

namespace Chamy\Core;

use Chamy\Core\Managers\ConfigManager;
use Chamy\Core\Managers\LanguageManager;
use Chamy\Core\Managers\EventManager;
use Chamy\Core\Managers\HookManager;
use Chamy\Core\Managers\PermissionManager;
use Chamy\Core\Managers\SessionManager;
use Chamy\Core\Managers\ModuleManager;
use Chamy\Core\Managers\ThemeManager;
use Chamy\Core\Managers\AssetManager;
use Chamy\Core\Managers\ContentTypeManager;
use Chamy\Core\Managers\ContentManager;
use Chamy\Core\Managers\StateManager;
use Chamy\Core\Managers\VersionManager;
use Chamy\Core\Managers\LayoutManager;
use Chamy\Core\Managers\ComponentManager;
use Chamy\Core\Managers\CacheManager;
use Chamy\Core\Managers\MarketplaceManager;
use Chamy\Core\Managers\TrashManager;
use Chamy\Core\Managers\AssetLibraryManager;
use Chamy\Core\Managers\MenuManager;
use Chamy\Core\Data\DataProviderInterface;
use Chamy\Core\Data\DataProviderFactory;
use Chamy\Core\Database\Connection;
use Chamy\Core\Routing\Router;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Errors\ErrorHandler;
use RuntimeException;

final class Kernel
{
    private static ?Kernel $instance = null;

    private ManagerRegistry $registry;
    private Connection $database;
    private DataProviderInterface $dataProvider;
    private Router $router;
    private ErrorHandler $errorHandler;
    private bool $booted = false;
    private string $basePath;

    private function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->registry = new ManagerRegistry();
    }

    public static function create(string $basePath): self
    {
        if (self::$instance !== null) {
            throw new RuntimeException('Kernel has already been created.');
        }

        self::$instance = new self($basePath);
        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Kernel has not been created yet.');
        }

        return self::$instance;
    }

    /**
     * @internal Only for testing purposes
     */
    public static function destroy(): void
    {
        self::$instance = null;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->initErrorHandler();
        $this->initConfig();
        $this->initCache();
        $this->initLanguage();
        $this->initDatabase();
        $this->initDataProvider();
        $this->initEvent();
        $this->initHook();
        $this->initSession();
        $this->initPermission();
        $this->initRouter();
        $this->initAsset();
        $this->initModule();
        $this->initTheme();
        $this->initContentType();
        $this->initContent();
        $this->initState();
        $this->initVersion();
        $this->initLayout();
        $this->initComponent();
        $this->initMarketplace();
        $this->initTrash();
        $this->initAssetLibrary();
        $this->initMenu();

        $this->registry->bootAll();
        $this->registerRoutes();
        $this->booted = true;
    }

    public function handle(Request $request): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this->router->dispatch($request);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function path(string ...$segments): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
    }

    public function getRegistry(): ManagerRegistry
    {
        return $this->registry;
    }

    public function getDatabase(): Connection
    {
        return $this->database;
    }

    public function db(): Connection
    {
        return $this->database;
    }

    public function data(): DataProviderInterface
    {
        return $this->dataProvider;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function config(): ConfigManager
    {
        /** @var ConfigManager $manager */
        $manager = $this->registry->get('config');
        return $manager;
    }

    public function lang(): LanguageManager
    {
        /** @var LanguageManager $manager */
        $manager = $this->registry->get('language');
        return $manager;
    }

    public function events(): EventManager
    {
        /** @var EventManager $manager */
        $manager = $this->registry->get('event');
        return $manager;
    }

    public function hooks(): HookManager
    {
        /** @var HookManager $manager */
        $manager = $this->registry->get('hook');
        return $manager;
    }

    public function permissions(): PermissionManager
    {
        /** @var PermissionManager $manager */
        $manager = $this->registry->get('permission');
        return $manager;
    }

    public function session(): SessionManager
    {
        /** @var SessionManager $manager */
        $manager = $this->registry->get('session');
        return $manager;
    }

    public function modules(): ModuleManager
    {
        /** @var ModuleManager $manager */
        $manager = $this->registry->get('module');
        return $manager;
    }

    public function themes(): ThemeManager
    {
        /** @var ThemeManager $manager */
        $manager = $this->registry->get('theme');
        return $manager;
    }

    public function assets(): AssetManager
    {
        /** @var AssetManager $manager */
        $manager = $this->registry->get('asset');
        return $manager;
    }

    public function contentTypes(): ContentTypeManager
    {
        /** @var ContentTypeManager $manager */
        $manager = $this->registry->get('content_type');
        return $manager;
    }

    public function content(): ContentManager
    {
        /** @var ContentManager $manager */
        $manager = $this->registry->get('content');
        return $manager;
    }

    public function states(): StateManager
    {
        /** @var StateManager $manager */
        $manager = $this->registry->get('state');
        return $manager;
    }

    public function versions(): VersionManager
    {
        /** @var VersionManager $manager */
        $manager = $this->registry->get('version');
        return $manager;
    }

    public function layouts(): LayoutManager
    {
        /** @var LayoutManager $manager */
        $manager = $this->registry->get('layout');
        return $manager;
    }

    public function components(): ComponentManager
    {
        /** @var ComponentManager $manager */
        $manager = $this->registry->get('component');
        return $manager;
    }

    public function marketplace(): MarketplaceManager
    {
        /** @var MarketplaceManager $manager */
        $manager = $this->registry->get('marketplace');
        return $manager;
    }

    public function trash(): TrashManager
    {
        /** @var TrashManager $manager */
        $manager = $this->registry->get('trash');
        return $manager;
    }

    public function assetLibrary(): AssetLibraryManager
    {
        /** @var AssetLibraryManager $manager */
        $manager = $this->registry->get('asset_library');
        return $manager;
    }

    public function menus(): MenuManager
    {
        /** @var MenuManager $manager */
        $manager = $this->registry->get('menu');
        return $manager;
    }

    // ------------------------------------------------------------------
    // Initialization methods
    // ------------------------------------------------------------------

    private function initErrorHandler(): void
    {
        $this->errorHandler = new ErrorHandler($this->basePath);
        $this->errorHandler->register();
    }

    private function initConfig(): void
    {
        $manager = new ConfigManager($this->basePath);
        $this->registry->register('config', $manager);
    }

    private function initCache(): void
    {
        $config = $this->config();
        $manager = new CacheManager(
            $this->path('storage', 'cache'),
            $config->get('CACHE_DRIVER', 'file'),
            $config->get('CACHE_PREFIX', 'chamy_cache_')
        );
        $this->registry->register('cache', $manager);
    }

    private function initLanguage(): void
    {
        $config = $this->config();
        $manager = new LanguageManager(
            $this->path('languages'),
            $config->get('APP_LOCALE', 'de'),
            $config->get('APP_FALLBACK_LOCALE', 'en')
        );
        $this->registry->register('language', $manager);
    }

    private function initDatabase(): void
    {
        $config = $this->config();
        $this->database = new Connection(
            driver: $config->get('DB_DRIVER', 'mysql'),
            host: $config->get('DB_HOST', '127.0.0.1'),
            port: (int) $config->get('DB_PORT', 3306),
            database: $config->get('DB_DATABASE', 'chamy'),
            username: $config->get('DB_USERNAME', 'root'),
            password: $config->get('DB_PASSWORD', ''),
            charset: $config->get('DB_CHARSET', 'utf8mb4'),
            collation: $config->get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            prefix: $config->get('DB_PREFIX', 'chamy_')
        );
    }

    private function initDataProvider(): void
    {
        $source = $this->config()->get('DATA_SOURCE', 'mock');
        $this->dataProvider = DataProviderFactory::create($source, $this->basePath, $this->database);
    }

    private function initEvent(): void
    {
        $manager = new EventManager();
        $this->registry->register('event', $manager);
    }

    private function initHook(): void
    {
        /** @var EventManager $events */
        $events = $this->registry->get('event');
        $manager = new HookManager($events);
        $this->registry->register('hook', $manager);
    }

    private function initSession(): void
    {
        $config = $this->config();
        $manager = new SessionManager(
            $config->get('SESSION_NAME', 'chamy_session'),
            (int) $config->get('SESSION_LIFETIME', 120)
        );
        $this->registry->register('session', $manager);
    }

    private function initPermission(): void
    {
        $manager = new PermissionManager($this->database);
        $this->registry->register('permission', $manager);
    }

    private function initRouter(): void
    {
        $this->router = new Router($this);
    }

    private function initAsset(): void
    {
        $manager = new AssetManager($this->path('public', 'assets'));
        $this->registry->register('asset', $manager);
    }

    private function initModule(): void
    {
        $manager = new ModuleManager($this);
        $this->registry->register('module', $manager);
    }

    private function initTheme(): void
    {
        $config = $this->config();

        // Prefer persisted settings from the settings table, then fall back to env/config.
        $adminTheme = (string) $config->get('ADMIN_THEME', 'default');
        $frontendTheme = (string) $config->get('FRONTEND_THEME', 'default');

        try {
            $allSettings = $this->dataProvider->getSettings();
            $themeSettings = $allSettings['theme'] ?? $allSettings['appearance'] ?? [];
            if (is_array($themeSettings)) {
                foreach ($themeSettings as $setting) {
                    if (!is_array($setting)) {
                        continue;
                    }
                    $key = (string) ($setting['key'] ?? '');
                    $value = trim((string) ($setting['value'] ?? ''));
                    if ($key === 'admin_theme' && $value !== '') {
                        $adminTheme = $value;
                    }
                    if ($key === 'frontend_theme' && $value !== '') {
                        $frontendTheme = $value;
                    }
                }
            }
        } catch (\Throwable) {
            // Best effort: boot must not fail when settings cannot be read.
        }

        $manager = new ThemeManager(
            $this,
            $adminTheme,
            $frontendTheme
        );
        $this->registry->register('theme', $manager);
    }

    private function initContentType(): void
    {
        $manager = new ContentTypeManager($this);
        $this->registry->register('content_type', $manager);
    }

    private function initContent(): void
    {
        $manager = new ContentManager($this);
        $this->registry->register('content', $manager);
    }

    private function initState(): void
    {
        $manager = new StateManager();
        $this->registry->register('state', $manager);
    }

    private function initVersion(): void
    {
        $manager = new VersionManager($this->database);
        $this->registry->register('version', $manager);
    }

    private function initLayout(): void
    {
        $manager = new LayoutManager($this);
        $this->registry->register('layout', $manager);
    }

    private function initComponent(): void
    {
        $manager = new ComponentManager($this);
        $this->registry->register('component', $manager);
    }

    private function initMarketplace(): void
    {
        $manager = new MarketplaceManager($this);
        $this->registry->register('marketplace', $manager);
    }

    private function initTrash(): void
    {
        $manager = new TrashManager($this->basePath);
        $this->registry->register('trash', $manager);
    }

    private function initAssetLibrary(): void
    {
        $manager = new AssetLibraryManager($this->basePath);
        $this->registry->register('asset_library', $manager);
    }

    private function initMenu(): void
    {
        $locale = $this->config()->get('APP_LOCALE', 'de');
        $manager = new MenuManager($this->database, $locale);
        $this->registry->register('menu', $manager);
    }

    private function registerRoutes(): void
    {
        $router = $this->router;

        $webRouteFile = $this->path('routes', 'web.php');
        if (file_exists($webRouteFile)) {
            require $webRouteFile;
        }

        $routeFile = $this->path('routes', 'admin.php');
        if (file_exists($routeFile)) {
            require $routeFile;
        }

        $apiRouteFile = $this->path('routes', 'api.php');
        if (file_exists($apiRouteFile)) {
            require $apiRouteFile;
        }

        $this->hooks()->fire('routes.registered', ['router' => $router]);
    }
}
