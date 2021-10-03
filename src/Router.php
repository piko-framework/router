<?php

/**
 * This file is part of Piko Router
 *
 * @copyright 2019-2021 Sylvain PHILIP
 * @license LGPL-3.0-or-later; see LICENSE.txt
 * @link https://github.com/piko-framework/router
 */

declare(strict_types=1);

namespace piko;

use piko\router\Match;
use piko\router\RadixTrie;

/**
 * Router class.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 */
class Router extends Component
{
    /**
     * Base uri
     *
     * The base uri is the base part of the request uri which shouldn't be parsed.
     * Example for the uri /home/blog/page : if the $baseUri is /home, the router will parse /blog/page
     *
     * @var string
     */
    public $baseUri = '';

    /**
     * Http protocol used (http/https)
     *
     * @var string
     */
    public $protocol;

    /**
     * Http host
     *
     * @var string
     */
    public $host;

    /**
     * Internal cache for routes uris
     *
     * @var array[]
     */
    protected $cache = [];

    /**
     * The radix trie storage utility
     *
     * @var RadixTrie
     */
    protected $radix;

    /**
     * Name-value pair route to handler correspondance.
     * Each key corresponds to a route. Each value corresponds to a route handler.
     * Routes and handlers can contain parameters. Ex:
     * `'/user/:id' => 'usercontroller/viewAction'`
     *
     * @var string[]
     */
    protected $routes = [];

    /**
     * Name-value pair route to handler correspondance.
     * This contains only routes with non params.
     *
     * @var string[]
     */
    protected $staticRoutes = [];

    /**
     * Name-value pair route to handler correspondance.
     * This contains only routes composed with params. Ex:
     * `'/:controller/:action' => ':controller/:action'`
     *
     * @var string[]
     */
    protected $fullyDynamicRoutes = [];

    public function __construct(array $config = [])
    {
        $this->radix = new RadixTrie();

        if (isset($config['routes']) && is_array($config['routes'])) {
            foreach ($config['routes'] as $route => $handler) {
                $this->addRoute($route, $handler);
            }

            unset($config['routes']);
        }

        parent::__construct($config);
    }

    protected function init(): void
    {
        if ($this->protocol === null) {
            $this->protocol = $_SERVER['REQUEST_SCHEME'];
        }

        if ($this->host === null) {
            $this->host = $_SERVER['HTTP_HOST'];
        }
    }

    /**
     * @param string $route
     * @param mixed $handler
     */
    public function addRoute(string $route, $handler): void
    {
        $this->routes[$route] = $handler;

        if (strpos($route, ':') === false) {
            $this->staticRoutes[$route] = $handler;
            return;
        }

        $parts = explode('/', trim($route, '/'));
        $countParams = 0;

        foreach ($parts as $part) {
            if (strpos($part, ':') === 0) {
                $countParams++;
            }
        }

        if ($countParams === count($parts)) {
            $this->fullyDynamicRoutes[$route] = $handler;
            return;
        }

        $this->radix->insert($route, $handler);
    }

    /**
     * Parse the route to get its corresponding handler and parameters.

     * @param string $route
     *
     * @return Match The route Match.
     */
    public function resolve(string $route): Match
    {
        $route = str_replace($this->baseUri, '', $route);
        $query = [];

        if (($start = strpos($route, '?')) !== false) {
            $queryStr = substr($route, $start + 1);
            parse_str($queryStr, $query);
            $route = substr($route, 0, $start);
        }

        $route = '/' . trim($route, '/');

        if (isset($this->staticRoutes[$route])) {

            $match = new Match();
            $match->found = true;
            $match->handler = $this->staticRoutes[$route];

            return $match;
        }

        $match = $this->radix->search($route);

        if (!$match->found) {
            $match = $this->findFullyDynamicRoute($route);
        }

        if (count($query)) {
            foreach ($query as $key => $value) {
                if (!isset($match->params[$key])) {
                    $match->params[$key] = $value;
                }
            }
        }

        return $match;
    }

    protected function findFullyDynamicRoute(string $route): Match
    {
        $match = new Match();
        $route = trim($route, '/');
        $routeParts = explode('/', $route);

        foreach ($this->fullyDynamicRoutes as $path => $handler) {

            $path = trim($path, '/');
            $pathParts = explode('/', $path);

            if (count($pathParts) == count($routeParts)) {

                foreach ($pathParts as $i => $part) {
                    $pos = strpos($part, ':');
                    $paramName = substr($part, $pos + 1);
                    $match->found = true;
                    $match->handler = $handler;
                    $match->params[$paramName] = $routeParts[$i];
                }

                break;
            }
        }

        if ($match->found && is_string($match->handler)) {
            foreach ($match->params as $key => $value) {
                $match->handler = str_replace(':' . $key, $value, $match->handler);
            }
        }

        return $match;
    }

    /**
     * Convert an handler to its corresponding route url (reverse routing).
     *
     * @param string $handler
     * @param string[] $params Optional query parameters.
     * @param boolean $absolute Optional to get an absolute url.
     * @return string The corresponding url.
     */
    public function getUrl(string $handler, array $params = [], $absolute = false)
    {
        $routes = $this->gethandlerRoutes($handler);
        $uri = '';

        foreach ($routes as $route) {

            $uri = $route;

            if (!count($params)) {
                break;
            }

            $routeMatch = false;
            $routeParams = [];

            foreach ($params as $key => $value) {
                $routeMatch = strpos($route, ':' . $key) !== false;

                if (!$routeMatch) {
                    break;
                }

                $routeParams[] = $key;
                $uri = str_replace(':' . $key, $value, $uri);
            }

            if ($routeMatch) {

                foreach ($routeParams as $key) {
                    unset($params[$key]);
                }

                break;
            }
        }

        if (count($params)) {
            $uri .= '/?' . http_build_query($params);
        }

        $this->trigger('afterBuildUri', [&$uri]);

        return ($absolute) ? $this->protocol . '://' . $this->host . $this->baseUri . $uri : $this->baseUri . $uri;
    }

    /**
     * Retrieve all routes attached to the handler
     *
     * @param string $handler
     * @return string[]
     */
    protected function gethandlerRoutes(string $handler): array
    {
        if (isset($this->cache[$handler])) {
            return $this->cache[$handler];
        }

        $this->cache[$handler] = [];

        foreach ($this->routes as $route => $handlerPattern) {

            // Looking for dynamic handler to populate route params
            while (is_string($handlerPattern) && ($pos = strpos($handlerPattern, ':')) !== false) {

                $param = substr($handlerPattern, $pos + 1);
                $value = substr($handler, $pos);

                if (($pos = strpos($param, '/')) !== false) {
                    $param = substr($param, 0, $pos);
                }

                if (($pos = strpos($value, '/')) !== false) {
                    $value = substr($value, 0, $pos);
                }

                $handlerPattern = str_replace(':' . $param, $value, $handlerPattern);
                $route = str_replace(':' . $param, $value, $route);
            }

            if ($handler == $handlerPattern) {
                $this->cache[$handler][] = $route;
            }
        }

        return $this->cache[$handler];
    }
}
