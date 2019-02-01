<?php

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;

use Illuminate\Http\Request;
use Laravel\Socialite\SocialiteManager;
use Illuminate\Support\Facades\Config;

use Revolution\Socialite\WordPress\WordPressProvider;

class SocialiteTest extends TestCase
{
    /**
     * @var SocialiteManager
     */
    protected $socialite;

    public function setUp()
    {
        parent::setUp();

        $app = ['request' => Request::create('foo')];

        $this->socialite = new SocialiteManager($app);

        $this->socialite->extend('wordpress', function ($app) {
            return $this->socialite->buildProvider(WordPressProvider::class, [
                'client_id'     => 'test',
                'client_secret' => 'test',
                'redirect'      => 'https://localhost',
            ]);
        });
    }

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $provider = $this->socialite->driver('wordpress');

        $this->assertInstanceOf(WordPressProvider::class, $provider);
    }

    public function testRedirect()
    {
        $request = Request::create('foo');
        $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
        $session->shouldReceive('put')->once();

        Config::shouldReceive('get')->once()->with('services.wordpress.host')->andReturn('http://localhost');

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');
        $response = $provider->redirect();

        $this->assertStringStartsWith('http://localhost', $response->getTargetUrl());
    }
}
