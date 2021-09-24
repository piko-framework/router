<?php
namespace tests;

use PHPUnit\Framework\TestCase;

use piko\Piko;
use piko\Router;

class RouterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $router = new Router([
            'routes' => [
                '^/$' => 'test/test/index',
                '^/user/(\d+)' => 'user/default/view|id=$1',
                '^/portfolio/(\w+)/(\d+)' => 'portfolio/default/view|alias=$1&category=$2',
                '^/page-1' => 'page/default/view|alias=page-1',
                '^/page-2' => 'page/default/view|alias=page-2',
                '^/([\w-]+)$' => 'page/default/view|alias=$1',
                '^/api/even' => 'api/event/index',
                '^/blog/year/(\d+)$' => 'site/index/index|filter=year&slug=$1',
                '^/blog/category/(\w+)$' => 'site/index/index|filter=category&slug=$1',
                '^/admin/shop/(\w+)/(\w+)/(\d+)' => 'shop/admin/$1/$2|id=$3',
                '^/admin/(\w+)/(\w+)/(\d+)' => '$1/admin/$2|id=$3',
                '^/events/(\w+)/(\w+)/(\d+)' => 'events/$2/$1|id=$3',
                '^/(\w+)/(\w+)/(\w+)$' => '$1/$2/$3',
                '^/(\w+)/(\w+)/(\w+)/(\w+)$' => '$1/$2/$3/$4',
                '^/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)$' => '$1/$2/$3/$4/$5',
            ]
        ]);

        Piko::set('router', $router);
    }

    public static function tearDownAfterClass(): void
    {
       Piko::reset();
    }

    public function testResolve()
    {
        /* @var $router \piko\Router */
        $router = Piko::get('router');

        $bases = ['', '/subdir'];

        foreach ($bases as $base) {
            Piko::setAlias('@web', $base);
            $_SERVER['REQUEST_URI'] = $base . '/';
            $this->assertEquals('test/test/index', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/user/10';
            $this->assertEquals('user/default/view', $router->resolve());
            $this->assertEquals(10, $_GET['id']);

            $_SERVER['REQUEST_URI'] = $base . '/portfolio/toto/5';
            $this->assertEquals('portfolio/default/view', $router->resolve());
            $this->assertEquals(5, $_GET['category']);
            $this->assertEquals('toto', $_GET['alias']);

            $_SERVER['REQUEST_URI'] = $base . '/page-1';
            $this->assertEquals('page/default/view', $router->resolve());
            $this->assertEquals('page-1', $_GET['alias']);

            $_SERVER['REQUEST_URI'] = $base . '/page-2';
            $this->assertEquals('page/default/view', $router->resolve());
            $this->assertEquals('page-2', $_GET['alias']);

            $_SERVER['REQUEST_URI'] = $base . '/page-3';
            $this->assertEquals('page/default/view', $router->resolve());
            $this->assertEquals('page-3', $_GET['alias']);

            $_SERVER['REQUEST_URI'] = $base . '/blog/default/index?filter=perso';
            $this->assertEquals('blog/default/index', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/admin/user/edit/5';
            $this->assertEquals('user/admin/edit', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/admin/shop/products/edit/5';
            $this->assertEquals('shop/admin/products/edit', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/api/event';
            $this->assertEquals('api/event/index', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/events/edit/user/15';
            $this->assertEquals('events/user/edit', $router->resolve());
            $this->assertEquals('15', $_GET['id']);

            $_SERVER['REQUEST_URI'] = $base . '/blog/year/2023';
            $this->assertEquals('site/index/index', $router->resolve());
            $this->assertEquals('year', $_GET['filter']);
            $this->assertEquals('2023', $_GET['slug']);

            $_SERVER['REQUEST_URI'] = $base . '/blog/category/tutu';
            $this->assertEquals('site/index/index', $router->resolve());
            $this->assertEquals('category', $_GET['filter']);
            $this->assertEquals('tutu', $_GET['slug']);

            $_SERVER['REQUEST_URI'] = $base . '/test/sub/test/index';
            $this->assertEquals('test/sub/test/index', $router->resolve());

            $_SERVER['REQUEST_URI'] = $base . '/test/sub/til/test/index';
            $this->assertEquals('test/sub/til/test/index', $router->resolve());
        }
    }

    public function testGetUrl()
    {
        /* @var $router \piko\Router */
        $router = Piko::get('router');

        $bases = ['', '/subdir'];

        foreach ($bases as $base) {
            Piko::setAlias('@web', $base);

            // '^/$' => 'test/test/index'
            $this->assertEquals($base . '/', $router->getUrl('test/test/index'));

            // '^/user/(\d+)' => 'user/default/view|id=$1'
            $this->assertEquals($base . '/user/2',  $router->getUrl('user/default/view', ['id' => 2]));

            // '^/portfolio/(\w+)/(\d+)' => 'portfolio/default/view|alias=$1&category=$2'
            $this->assertEquals($base . '/portfolio/toto/5', $router->getUrl(
                'portfolio/default/view',
                ['category' => 5, 'alias' => 'toto']
            ));

            // '^/page-1' => 'page/default/view|alias=page-1'
            $this->assertEquals($base . '/page-1',  $router->getUrl('page/default/view', ['alias' => 'page-1']));

            // '^/page-2' => 'page/default/view|alias=page-2',
            $this->assertEquals($base . '/page-2',  $router->getUrl('page/default/view', ['alias' => 'page-2']));

            // '^/([\w-]+)$' => 'page/default/view|alias=$1',
            $this->assertEquals($base . '/page-3',  $router->getUrl('page/default/view', ['alias' => 'page-3']));

            // '^/(\w+)/(\w+)/(\w+)' => '$1/$2/$3'
            $this->assertEquals($base . '/blog/default/index', $router->getUrl('blog/default/index'));

            // '^/(\w+)/(\w+)/(\w+)' => '$1/$2/$3'
            $this->assertEquals($base . '/blog/default/view/?id=2', $router->getUrl('blog/default/view', ['id' => 2]));

            // '^/admin/(\w+)/(\w+)/(\d+)' => '$1/admin/$2|id=$3'
            $this->assertEquals($base . '/admin/user/edit/5', $router->getUrl('user/admin/edit', ['id' => 5]));

            // '^/admin/shop/(\w+)/(\w+)/(\d+)' => 'shop/admin/$1/$2|id=$3'
            $this->assertEquals($base . '/admin/shop/products/edit/5', $router->getUrl('shop/admin/products/edit', ['id' => 5]));
            $this->assertEquals($base . '/admin/shop/orders/delete/5', $router->getUrl('shop/admin/orders/delete', ['id' => 5]));
            $this->assertEquals($base . '/admin/shop/coupons/edit/10', $router->getUrl('shop/admin/coupons/edit', ['id' => 10]));

            // '^/events/(\w+)/(\w+)/(\d+)' => 'events/$2/$1|id=$3'
            $this->assertEquals($base . '/events/edit/user/5', $router->getUrl('events/user/edit', ['id' => 5]));

            // '^/blog/year/(\d+)$' => 'site/index/index|filter=year&slug=$1'
            $this->assertEquals($base . '/blog/year/2021', $router->getUrl('site/index/index', ['slug' => '2021', 'filter' => 'year']));

            // '^/blog/category/(\d+)$' => 'site/index/index|filter=category&slug=$1'
            $this->assertEquals($base . '/blog/category/test', $router->getUrl('site/index/index', ['slug' => 'test', 'filter' => 'category']));

            // '^/(\w+)/(\w+)/(\w+)' => '$1/$2/$3'
            $this->assertEquals($base . '/site/index/index/?slug=test&unknown=category', $router->getUrl('site/index/index', ['slug' => 'test', 'unknown' => 'category']));

            // '^/(\w+)/(\w+)/(\w+)/(\w+)' => '$1/$2/$3/$4'
            $this->assertEquals($base . '/test/sub/test/index', $router->getUrl('test/sub/test/index'));

            // '^/(\w+)/(\w+)/(\w+)/(\w+)' => '$1/$2/$3/$4'
            $this->assertEquals($base . '/test/sub/test/index/?id=5', $router->getUrl('test/sub/test/index', ['id' => 5]));

            // '^/(\w+)/(\w+)/(\w+)/(\w+)/(\w+)' => '$1/$2/$3/$4/$5'
            $this->assertEquals($base . '/test/sub/til/test/index', $router->getUrl('test/sub/til/test/index'));
        }
    }

    public function testGetAbsoluteUrl()
    {
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'www.sphilip.com';
        Piko::setAlias('@web', '');

        /* @var $router \piko\Router */
        $router = Piko::get('router');

        // '^/user/(\d+)' => 'user/default/view|id=$1'
        $this->assertEquals('https://www.sphilip.com/user/2',  $router->getUrl('user/default/view', ['id' => 2], true));
    }
}
