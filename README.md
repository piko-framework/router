# Piko Router

[![build](https://github.com/piko-framework/router/actions/workflows/php.yml/badge.svg)](https://github.com/piko-framework/router/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/piko-framework/router/badge.svg?branch=main)](https://coveralls.io/github/piko-framework/router?branch=main)

A lightning fast router using a [radix trie](https://en.wikipedia.org/wiki/Radix_tree) to store dynamic routes.

This router maps routes to user defined handlers. It can do the reverse operation (reverse routing)

# Installation

It's recommended that you use Composer to install Piko Router.

```bash
composer require piko/router
```

# Usage

A basic example:

```php
use piko\Router;

$router = new Router();
$router->addRoute('/', 'homeView');
$router->addRoute('/user/:id', 'userView');

$match = $router->resolve('/');
echo $match->handler // homeView

$match = $router->resolve('/user/10'); // user/view
echo $match->handler // userView
echo $match->params['id'] // 10

// Use of the $match->handler to dispatch an action
// ...

// Reverse routing
echo $router->getUrl('homeView'); // /
echo $router->getUrl('userView', ['id' => 3]); // /user/3
```

Dynamic handlers:

```php
use piko\Router;

$router = new Router();
$router->addRoute('/admin/:module/:action', ':module/admin/:action');

$match = $router->resolve('/admin/user/edit/3'); 

echo $match->handler; // user/admin/edit
echo $router->getUrl('blog/admin/edit', ['id' => 10]); // /admin/blog/edit/3

```

Advanced usage: [See RouterTest.php](tests/RouterTest.php)

