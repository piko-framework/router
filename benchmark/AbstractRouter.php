<?php
namespace bench;

/**
 * @Revs(1000)
 * @Iterations(5)
 */
abstract class AbstractRouter
{
    protected $loopIteration = 1000;
    protected $avg = 0;
    protected $worst = 0;

    public function __construct()
    {
        if (getenv('LOOP_ITERATIONS', true)) {
            $this->loopIteration = (int) getenv('LOOP_ITERATIONS', true);
        }

        $this->avg = $this->loopIteration / 2;
        $this->worst = $this->loopIteration - 1;
    }

    abstract public function createRouter(): void;

    /** @param array<string,mixed> $params */
    abstract protected function runScenario(array $params): void;

    /** @return \Generator<string,array<string,mixed>> */
    abstract public function provideStaticRoutes(): iterable;

    /** @return \Generator<string,array<string,mixed>> */
    abstract public function provideDynamicRoutes(): iterable;

    /**
     * @BeforeMethods("createRouter")
     * @ParamProviders("provideStaticRoutes")
     *
     * @param array<string,mixed> $params
     */
    public function benchStaticRoutes(array $params): void
    {
        $this->runScenario($params);
    }

    /**
     * @BeforeMethods("createRouter")
     * @ParamProviders("provideDynamicRoutes")
     *
     * @param array<string,mixed> $params
     */
    public function benchDynamicRoutes(array $params): void
    {
        $this->runScenario($params);
    }
}
