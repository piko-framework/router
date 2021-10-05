<?php

declare(strict_types=1);

namespace bench\Routers;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\cachedDispatcher;
use bench\AbstractRouter;

/**
 * @Groups({"fastroute", "cached"})
 */
class FastRoute extends AbstractRouter
{
    protected Dispatcher $dispatcher;

    /**
     * {@inheritDoc}
     */
    public function createRouter(): void
    {
        $this->dispatcher = cachedDispatcher(function(RouteCollector $r) {

            for ($i = 0; $i < 1000; $i ++) {
                $r->addRoute('GET', '/static' . $i, 'fastroute::static');
                $r->addRoute('GET', '/dynamic' . $i . '/{id:\d+}', 'fastroute::dynamic');
            }
        },[
        'cacheFile' => __DIR__ . '/../caches/fastroute.cache',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function provideStaticRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/static0', 'result' => 'fastroute::static'];

        yield 'Average Case' => ['route' => '/static499', 'result' => 'fastroute::static'];

        yield 'Worst Case' => ['route' => '/static999', 'result' => 'fastroute::static'];
    }

    /**
     * {@inheritdoc}
     */
    public function provideDynamicRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/dynamic0/1', 'result' => 'fastroute::dynamic'];

        yield 'Average Case' => ['route' => '/dynamic499/1', 'result' => 'fastroute::dynamic'];

        yield 'Worst Case' => ['route' => '/dynamic999/1','result' => 'fastroute::dynamic'];
    }

    /**
     * {@inheritdoc}
     */
    protected function runScenario(array $params): void
    {
        $routeInfo = $this->dispatcher->dispatch('GET', $params['route']);
        assert($params['result'] === $routeInfo[1]);
    }
}
