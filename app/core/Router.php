<?php
declare(strict_types=1);

namespace Koonect\Core;

/**
 * Router — Routeur HTTP simple et rapide.
 * Supporte les routes statiques et les paramètres dynamiques (:param).
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private ?string $prefix = null;
    private array $groupMiddlewares = [];

    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousPrefix      = $this->prefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->prefix            = ($this->prefix ?? '') . $prefix;
        $this->groupMiddlewares  = array_merge($this->groupMiddlewares, $middlewares);

        $callback($this);

        $this->prefix           = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        $fullPath   = ($this->prefix ?? '') . $path;
        $pattern    = $this->compilePath($fullPath);
        $allMiddles = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[] = [
            'method'      => $method,
            'path'        => $fullPath,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $allMiddles,
        ];
    }

    private function compilePath(string $path): string
    {
        // :param → named capture group
        $pattern = preg_replace('#:([a-zA-Z_][a-zA-Z0-9_]*)#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#u';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        // Support _method spoofing pour PUT/DELETE via formulaires HTML
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper($_POST['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'])) {
                $method = $spoofed;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire les paramètres nommés
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // Exécuter les middlewares dans l'ordre
                $this->runMiddlewares($route['middlewares'], $request, function () use ($route, $request) {
                    $this->callHandler($route['handler'], $request);
                });
                return;
            }
        }

        // 404
        Response::setStatusCode(404);
        View::render('errors/404');
    }

    private function runMiddlewares(array $middlewares, Request $request, callable $final): void
    {
        if (empty($middlewares)) {
            $final();
            return;
        }

        $middleware = array_shift($middlewares);
        $instance   = new $middleware();
        $instance->handle($request, function () use ($middlewares, $request, $final) {
            $this->runMiddlewares($middlewares, $request, $final);
        });
    }

    private function callHandler(callable|array $handler, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request);
            return;
        }

        // [ControllerClass::class, 'method']
        [$class, $method] = $handler;
        $controller = new $class();
        $controller->$method($request);
    }
}
