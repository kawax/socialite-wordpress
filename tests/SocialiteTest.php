<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Mockery as m;
use Revolution\Socialite\WordPress\WordPressProvider;

class SocialiteTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testInstance()
    {
        $provider = Socialite::driver('wordpress');

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
