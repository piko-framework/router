<?php
namespace tests\Benchmark;

use tests\AbstractRouter;
use piko\Router;
use function assert;

/**
 * @Groups({"piko-router", "raw"})
 */
class PikoRouterBench extends AbstractRouter
{
    protected Router $router;

    public function createRouter(): void
    {
        $this->router = new Router();

        for ($i = 0; $i < 1000; $i ++) {
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

        yield 'Average Case' => ['route' => '/static499', 'result' => 'piko::static'];

        yield 'Worst Case' => ['route' => '/static999', 'result' => 'piko::static'];
    }

    /**
     * {@inheritdoc}
     */
    public function provideDynamicRoutes(): iterable
    {
        yield 'Best Case' => ['route' => '/dynamic0/1', 'result' => 'piko::dynamic'];

        yield 'Average Case' => ['route' => '/dynamic499/1', 'result' => 'piko::dynamic'];

        yield 'Worst Case' => ['route' => '/dynamic999/1','result' => 'piko::dynamic'];
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
