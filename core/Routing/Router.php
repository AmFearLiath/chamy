<?php

declare(strict_types=1);

namespace Chamy\Core\Routing;

use Chamy\Core\Kernel;
use Chamy\Core\Http\Request;
use Chamy\Core\Http\Response;
use Chamy\Core\Http\Middleware\MiddlewareInterface;

final class Router
{
    private Kernel $kernel;

    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $namedRoutes = [];

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function get(string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        return $this->addRoute('GET', $pattern, $handler, $name, $middleware);
    }

    public function post(string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        return $this->addRoute('POST', $pattern, $handler, $name, $middleware);
    }

    public function put(string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        return $this->addRoute('PUT', $pattern, $handler, $name, $middleware);
    }

    public function delete(string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $pattern, $handler, $name, $middleware);
    }

    public function any(string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        return $this->addRoute('ANY', $pattern, $handler, $name, $middleware);
    }

    public function addRoute(string $method, string $pattern, mixed $handler, string $name = '', array $middleware = []): Route
    {
        $route = new Route(
            method: strtoupper($method),
            pattern: $pattern,
            handler: $handler,
            name: $name,
            middleware: $middleware
        );

        $this->routes[] = $route;

        if ($name !== '') {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Fire hook before routing
        $this->kernel->hooks()->fire('router.before_dispatch', ['method' => $method, 'path' => $path]);

        foreach ($this->routes as $route) {
            $params = $route->matches($method, $path);

            if ($params !== null) {
                $request->setRouteParams($params);
                return $this->executeRoute($route, $request);
            }
        }

        // 404
        if ($request->expectsJson() || str_starts_with($path, '/api/')) {
            return Response::notFound();
        }

        return $this->render404($request);
    }

    public function getRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function url(string $name, array $params = []): string
    {
        $route = $this->getRoute($name);

        if ($route === null) {
            return '#';
        }

        $url = $route->pattern;

        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', (string) $value, $url);
        }

        return $url;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    // ------------------------------------------------------------------

    private function executeRoute(Route $route, Request $request): Response
    {
        // Build middleware pipeline
        $coreHandler = function (Request $req) use ($route): Response {
            return $this->invokeHandler($route, $req);
        };

        $pipeline = $coreHandler;

        foreach (array_reverse($route->middleware) as $mw) {
            $prev = $pipeline;
            $pipeline = function (Request $req) use ($mw, $prev): Response {
                $instance = is_string($mw) ? new $mw() : $mw;
                if ($instance instanceof MiddlewareInterface) {
                    return $instance->handle($req, $prev);
                }
                return $prev($req);
            };
        }

        return $pipeline($request);
    }

    private function invokeHandler(Route $route, Request $request): Response
    {
        $handler = $route->handler;

        // Callable
        if (is_callable($handler)) {
            $result = $handler($request, $this->kernel);

            if ($result instanceof Response) {
                return $result;
            }

            if (is_string($result)) {
                return Response::html($result);
            }

            if (is_array($result)) {
                return Response::apiSuccess($result);
            }

            return Response::html((string) $result);
        }

        // [ControllerClass, method]
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;

            if (is_string($class)) {
                $controller = new $class($this->kernel);
                $result = $controller->$method($request);

                if ($result instanceof Response) {
                    return $result;
                }

                return is_array($result) ? Response::apiSuccess($result) : Response::html((string) $result);
            }
        }

        return Response::apiError('handler_error', 'Invalid route handler.', 500);
    }

    private function render404(Request $request): Response
    {
        try {
            $html = $this->kernel->themes()->render('errors/404.twig', [
                'app_locale' => $this->kernel->config()->get('APP_LOCALE', 'de'),
            ]);
            return Response::html($html, 404);
        } catch (\Throwable) {
            $lang = $this->kernel->lang();
            $html = '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">';
            $html .= '<title>404 – ' . $lang->t('system.not_found') . '</title>';
            $html .= '</head><body>';
            $html .= '<h1>404</h1><p>' . htmlspecialchars($lang->t('system.page_not_found'), ENT_QUOTES, 'UTF-8') . '</p>';
            $html .= '</body></html>';
            return Response::html($html, 404);
        }
    }
}
