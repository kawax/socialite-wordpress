<?php

namespace Tests;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Mockery as m;
use Revolution\Socialite\WordPress\WordPressProvider;

class SocialiteTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function test_instance()
    {
        $provider = Socialite::driver('wordpress');

        $this->assertInstanceOf(WordPressProvider::class, $provider);
    }

    public function test_redirect()
    {
        $request = Request::create('foo');
        $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
        $session->shouldReceive('put')->once();

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');
        $response = $provider->redirect();

        $this->assertStringStartsWith('http://localhost', $response->getTargetUrl());
    }
}
