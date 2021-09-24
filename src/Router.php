<?php
/**
 * This file is part of Piko - Web micro framework
 *
 * @copyright 2019-2021 Sylvain PHILIP
 * @license LGPL-3.0; see LICENSE.txt
 * @link https://github.com/piko-framework/piko
 */
namespace piko;

/**
 * Base application router.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 */
class Router extends Component
{
    /**
     * Name-value pair uri to routes correspondance.
     *
     * Each name corresponds to a regular expression of the request uri.
     * Each value corresponds to a route replacement.
     *
     * eg. `'^/about$' => 'site/default/about'` means all requests corresponding to
     * '/about' will be treated in 'about' action in the 'defaut' controller of 'site' module.
     *
     * eg. `'^/(\w+)/(\w+)/(\w+)' => '$1/$2/$3'` means uri part 1 is the module id,
     * part 2, the controller id and part 3 the action id.
     *
     * Also route parameters could be given using pipe character after route.
     *
     * eg. `'^/user/(\d+)' => 'site/user/view|id=$1'` The router will populate `$_GET`
     * with 'id' = The user id in the uri.
     *
     * @var array
     * @see preg_replace()
     */
    public $routes = [];

    /**
     * Internal cache for routes uris
     * @var array
     */
    protected $cache = [];

    /**
     * Resolve the application route corresponding to the request uri.
     * The expected route scheme is : '{moduleId}/{controllerId}/{ationId}'
     *
     * @return string The route.
     */
    public function resolve()
    {
        $route = '';
        $uri = str_replace(Piko::getAlias('@web'), '', $_SERVER['REQUEST_URI']);

        if (($start = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $start);
        }

        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $uriPattern => $routePattern) {

            $matches = [];

            if (preg_match('`' . $uriPattern . '`', $uri, $matches)) {

                foreach ($matches as $i => $match) {
                    $routePattern = str_replace('$' . $i, $match, $routePattern);
                }

                $route = $routePattern;

                break;
            }
        }

        // Parse route request parameters
        if (($start = strpos($route, '|')) !== false) {
            $params = [];
            parse_str(substr($route, $start + 1), $params);

            foreach ($params as $k => $v) {
                $_GET[$k] = $v;
            }

            $route = substr($route, 0, $start);
        }

        return $route;
    }

    /**
     * Convert a route to an url.
     *
     * @param string $route The route given as '{moduleId}/{controllerId}/{ationId}'.
     * @param array $params Optional query parameters.
     * @param boolean $absolute Optional to have an absolute url.
     * @return string The url.
     */
    public function getUrl(string $route, array $params = [], $absolute = false)
    {
        $uri = '';
        $uriParams = '';
        $routeUris = $this->getRouteUris($route);
        ksort($params);

        foreach ($routeUris as $uriPattern => $queryStr) {

            $uri = str_replace(['^', '$'], '', $uriPattern);
            $uriParams = $queryStr;
            $query = [];
            parse_str($queryStr, $query);
            $replace = [];
            $continue = false;

            foreach ($query as $key => $val) {
                if (isset($params[$key]) && ($pos = strpos($val, '$')) !== false) {
                    /* When the query is dynamic.
                     * Ex: $params == ['alias' => 'page-2'] && $query == ['alias' => '$1']
                     */
                    $pos = (int) $val[$pos+1];
                    $replace[$pos] =  $params[$key];
                } elseif (!isset($params[$key]) || $params[$key] != $val) {
                    /* When the query is static.
                     * Ex: $params == ['alias' => 'page-2'] && $query == ['alias' => 'page-1']
                     */
                    $continue = true;
                    break;
                }
            }

            if (count($replace) > 0) {
                ksort($replace);

                $uri = preg_replace_callback('`\(.*?\)`', function ($matches) use (&$replace) {
                    return array_shift($replace);
                }, $uri);
            }

            if (!$continue) {
                break;
            }
        }

        if (count($params) > 0 && $uriParams === '') {
            $uri .= '/?' . http_build_query($params);
        }

        $this->trigger('afterBuildUri', [&$uri]);

        if ($absolute) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . Piko::getAlias('@web') . $uri;
        }

        return Piko::getAlias('@web') . $uri;
    }

    /**
     * Retrieve all the uris rattached to the route
     *
     * @param string $route
     * @return array
     */
    protected function getRouteUris(string $route): array
    {
        if (isset($this->cache[$route])) {
            return $this->cache[$route];
        }

        $this->cache[$route] = [];

        foreach ($this->routes as $uriPattern => $routePattern) {

            $strParams = '';

            // Remove query parameters from $routePattern
            if (($pos = strpos($routePattern, '|')) !== false) {
                $strParams = substr($routePattern, $pos + 1);
                $routePattern = substr($routePattern, 0, $pos);
            }

            // Looking for dynamic route
            if (strpos($routePattern, '$') !== false) {

                $routeParts = explode('/', $route);
                $routePatternParts = explode('/', $routePattern);
                $replace = [];

                foreach ($routePatternParts as $k => &$part) {

                    if (($pos = strpos($part, '$')) !== false && isset($routeParts[$k])) {
                        $replace[(int) $part[$pos+1]] = $routeParts[$k];
                        $part = $routeParts[$k];
                    }
                }

                ksort($replace);

                $uriPattern = preg_replace_callback('`\(.*?\)`', function ($matches) use (&$replace) {
                    return count($replace) ? array_shift($replace) : $matches[0];
                }, $uriPattern);

                $routePattern = implode('/', $routePatternParts);
            }

            if ($route == $routePattern) {
                $this->cache[$route][$uriPattern] = $strParams;
            }
        }

        return $this->cache[$route];
    }
}
