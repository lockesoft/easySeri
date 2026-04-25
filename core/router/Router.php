<?php

class Router
{
    private $routes = [];
    private $basePath = '';

    public function __construct($basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute($method, $path, $handler)
    {
        $this->routes[strtoupper($method)][$this->normalize($path)] = $handler;
    }

    public function dispatch($uri, $method = 'GET')
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!$path) {
            $path = '/';
        }

        if ($this->basePath !== '' && substr($path, 0, strlen($this->basePath)) === $this->basePath) {
            $path = substr($path, strlen($this->basePath));
        }

        $path = $this->normalize($path);
        $method = strtoupper($method);

        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
            return;
        }

        http_response_code(404);
        echo "<h1>404</h1><p>Ruta no encontrada</p>";
    }

    private function normalize($path)
    {
        $path = '/' . trim($path, '/');

        if ($path === '//') {
            return '/';
        }

        return $path;
    }
}