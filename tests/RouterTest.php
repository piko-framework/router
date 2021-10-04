<?php
use PHPUnit\Framework\TestCase;
use piko\Router;

class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    private $router;

    protected function setUp(): void
    {
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'www.sphilip.com';

        $this->router = new Router([
            'routes' => [
                '/' => 'test/test/index',
                '/home' => 'test/test/index',
                '/user/:id' => 'user/default/view',
                '/user/add' => 'user/admin/add',
                '/partner/:name' => 150,
                '/gallery/:ref' => function($params) { return $params['ref']; },
                '/portfolio/:alias/:category' => 'portfolio/default/view',
                '/admin/:module/:action' => ':module/admin/:action',
                '/:page' => 'page/default/view',
                '/:category/:page' => 'page/default/view',
                '/:module/:controller/:action' => ':module/:controller/:action',
            ]
        ]);
    }

    public function testResolve()
    {
        $bases = ['', '/subdir'];

        foreach ($bases as $base) {
            $this->router->baseUri = $base;

            // Test static routes
            $match = $this->router->resolve($base . '/');
            $this->assertTrue($match->found);
            $this->assertEquals('test/test/index', $match->handler);

            $match = $this->router->resolve($base . '/user/add');
            $this->assertEquals('user/admin/add', $match->handler);

            // Test dynamic routes
            $match = $this->router->resolve($base . '/user/10');
            $this->assertEquals('user/default/view', $match->handler);
            $this->assertEquals('10', $match->params['id']);

            $match = $this->router->resolve($base . '/portfolio/toto/5');
            $this->assertEquals('portfolio/default/view', $match->handler);
            $this->assertEquals('5', $match->params['category']);
            $this->assertEquals('toto', $match->params['alias']);

            $match = $this->router->resolve($base . '/admin/shop/edit?id=14');
            $this->assertEquals('shop/admin/edit', $match->handler);
            $this->assertEquals('14', $match->params['id']);

            // Test with numeric handler
            $match = $this->router->resolve($base . '/partner/toto');
            $this->assertEquals(150, $match->handler);
            $this->assertEquals('toto', $match->params['name']);

            // Test with callable handler
            $match = $this->router->resolve($base . '/gallery/toto');
            $this->assertEquals('toto', call_user_func($match->handler, $match->params));

            // Test fully dynamic routes
            $match = $this->router->resolve($base . '/page-1');
            $this->assertEquals('page/default/view', $match->handler);
            $this->assertEquals('page-1', $match->params['page']);

            $match = $this->router->resolve($base . '/events/admin/add');
            $this->assertEquals('events/admin/add', $match->handler);
            $this->assertEquals('events', $match->params['module']);
            $this->assertEquals('admin', $match->params['controller']);
            $this->assertEquals('add', $match->params['action']);
        }

        // Change already declared route
        $this->router->addRoute('/user/:id', 'site/user/view');
        $match = $this->router->resolve('/user/11');
        $this->assertEquals('site/user/view', $match->handler);
        $this->assertEquals('11', $match->params['id']);

        // Test with route folowwed by param
        $this->router->addRoute('/use:id', 'site/use');
        $match = $this->router->resolve('/use20');
        $this->assertEquals('site/use', $match->handler);
        $this->assertEquals('20', $match->params['id']);

        // Test with same prefix
        $this->router->addRoute('/use:i', 'site/use');
        $match = $this->router->resolve('/use20');
        $this->assertEquals('site/use', $match->handler);
        $this->assertEquals('20', $match->params['i']);
    }

    public function  testDuplicateParam()
    {
        $this->router->addRoute('/user/:alias', 'user/default/view');
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Cannot determine param for the route parts: user/:id, user/:alias');
        $this->router->resolve('/user/john');
    }

    public function testReverseRouting()
    {
        $bases = ['', '/subdir'];

        foreach ($bases as $base) {
            $this->router->baseUri = $base;

            // '/' => 'test/test/index'
            $this->assertEquals($base . '/', $this->router->getUrl('test/test/index'));

            // '/user/:id' => 'user/default/view'
            $this->assertEquals($base . '/user/2',  $this->router->getUrl('user/default/view', ['id' => 2]));

            // '/user/add' => 'user/admin/add'
            $this->assertEquals($base . '/user/add', $this->router->getUrl('user/admin/add'));

            // '/portfolio/:alias/:category' => 'portfolio/default/view'
            $this->assertEquals($base . '/portfolio/toto/5', $this->router->getUrl(
                'portfolio/default/view',
                ['category' => 5, 'alias' => 'toto']
            ));

            // '/admin/:module/:action' => ':module/admin/:action'
            $this->assertEquals($base . '/admin/blog/index', $this->router->getUrl('blog/admin/index'));
            $this->assertEquals($base . '/admin/events/add', $this->router->getUrl('events/admin/add'));

            // '/:page => 'page/default/view'
            $this->assertEquals($base . '/page-1',  $this->router->getUrl('page/default/view', ['page' => 'page-1']));
            $this->assertEquals($base . '/news/page-2',  $this->router->getUrl('page/default/view', [
                'page' => 'page-2',
                'category' => 'news',
            ]));

            $this->assertEquals($base . '/page-2/?ref=test',  $this->router->getUrl('page/default/view', [
                'page' => 'page-2',
                'ref' => 'test',
            ]));

            // '/:module/:controller/:action' => ':module/:controller/:action'
            $this->assertEquals($base . '/events/index/view', $this->router->getUrl('events/index/view'));
        }
    }

    public function testGetAbsoluteUrl()
    {
        $this->router->baseUri = '';

        // '/user/:id' => 'user/default/view'
        $this->assertEquals(
            'https://www.sphilip.com/user/2',
            $this->router->getUrl('user/default/view', ['id' => 2], true)
        );
    }
}
