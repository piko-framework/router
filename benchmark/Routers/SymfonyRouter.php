<?php

declare(strict_types=1);

namespace bench\Routers;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use bench\AbstractRouter;

/**
 * @Groups({"symfony-router", "cached"})
 */
class SymfonyRouter extends AbstractRouter
{
    /**
     * @var UrlMatcher
     */
    protected $matcher;

    /**
     * @var Router
     */
    protected $router;
    /**
     * {@inheritdoc}
     */
    public function provideStaticRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/static0', 'result' => 'symfony::static'];

        yield 'Average Case' => ['route' => '/static499', 'result' => 'symfony::static'];

        yield 'Worst Case' => ['route' => '/static999', 'result' => 'symfony::static'];
    }

    /**
     * {@inheritdoc}
     */
    public function provideDynamicRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/dynamic0/1', 'result' => 'symfony::dynamic'];

        yield 'Average Case' => ['route' => '/dynamic499/1', 'result' => 'symfony::dynamic'];

        yield 'Worst Case' => ['route' => '/dynamic999/1','result' => 'symfony::dynamic'];
    }

    /**
     * {@inheritdoc}
     */
    public function createRouter(): void
    {
        $resource = static function (): RouteCollection {
            $collection = new RouteCollection();

            for ($i = 0; $i < 1000; $i++) {
                $collection->add('static_' . $i, new Route('/static' . $i, ['handler' => 'symfony::static']));
                $collection->add('dynamic_' . $i, new Route('/dynamic' . $i . '/{id}', ['handler' => 'symfony::dynamic']));
            }

            return $collection;
        };

        $this->router = new Router(new ClosureLoader(), $resource, ['cache_dir' => __DIR__ . '/../caches']);
    }

    /**
     * {@inheritdoc}
     */
    protected function runScenario(array $params): void
    {
        $result = $this->router->match($params['route']);
        assert($params['result'] === $result['handler']);
    }
}
