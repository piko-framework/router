<?php

declare(strict_types=1);

namespace bench\Routers;

use bench\AbstractRouter;
use piko\Router;

/**
 * @Groups({"piko-router", "raw"})
 */
class PikoRouter extends AbstractRouter
{
    protected Router $router;

    public function createRouter(): void
    {
        $this->router = new Router();

        for ($i = 0; $i < $this->loopIteration; $i ++) {
            $this->router->addRoute('/static' . $i, 'piko::static');
            $this->router->addRoute('/dynamic' . $i . '/:id', 'piko::dynamic');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provideStaticRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/static0', 'result' => 'piko::static'];

        yield 'Average Case' => ['route' => '/static' . $this->avg, 'result' => 'piko::static'];

        yield 'Worst Case' => ['route' => '/static' . $this->worst, 'result' => 'piko::static'];
    }

    /**
     * {@inheritdoc}
     */
    public function provideDynamicRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/dynamic0/1', 'result' => 'piko::dynamic'];

        yield 'Average Case' => ['route' => '/dynamic' . $this->avg . '/1', 'result' => 'piko::dynamic'];

        yield 'Worst Case' => ['route' => '/dynamic' . $this->worst . '/1','result' => 'piko::dynamic'];
    }

    /**
     * {@inheritdoc}
     */
    protected function runScenario(array $params): void
    {
        $match = $this->router->resolve($params['route']);

        assert($params['result'] === $match->handler);
    }
}
