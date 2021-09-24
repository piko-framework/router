# Piko Router

[![build](https://github.com/piko-framework/router/actions/workflows/php.yml/badge.svg)](https://github.com/piko-framework/router/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/piko-framework/router/badge.svg?branch=main)](https://coveralls.io/github/piko-framework/router?branch=main)

This router map request uris to user defined routes.

# Installation

It's recommended that you use Composer to install Piko Router.

```bash
composer require piko/router
```

# Usage

This is a basic example:

```php
use piko\Router;

$router = new Router([
     // Uri mapping to route
    'routes' => [
        '^/$' => 'home',
        '^/user/(\d+)' => 'user/view|id=$1'
    ]
]);

$_SERVER['REQUEST_URI'] = '/';

$route = $router->resolve(); // home

$_SERVER['REQUEST_URI'] = '/user/10';

$route = $router->resolve(); // user/view
$id = $_GET['id']; // 10

// Then, you can use the route to dispatch an action in your code
// ...

// The router can generate url from route
echo $router->getUrl('home'); // /
echo $router->getUrl('user/view', ['id' => 3]); // /user/3
```

Dynamic routing:

```php
use piko\Router;

$router = new Router([
    'routes' => [
        '^/admin/(\w+)/(\w+)/(\d+)' => '$1/admin/$2|id=$3',
    ]
]);

$_SERVER['REQUEST_URI'] = '/admin/user/edit/3';

echo $router->resolve(); // user/admin/edit
echo $_GET['id']; // 3

echo $router->getUrl('blog/admin/edit', ['id' => 10]); // /admin/blog/edit/3

```

Advanced example: [See RouterTest.php](tests/RouterTest.php)

