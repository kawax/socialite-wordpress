<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User;
use Mockery as m;
use Revolution\Socialite\WordPress\WordPressProvider;

class WordPressProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_redirect_generates_correct_url()
    {
        $request = Request::create('foo');
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->expects('put')->once();

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');
        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $url = $response->getTargetUrl();
        $this->assertStringStartsWith('http://localhost/authorize', $url);
        $this->assertStringContainsString('client_id=client_id', $url);
        $this->assertStringContainsString('redirect_uri=redirect', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function test_get_auth_url_method()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getAuthUrl');
        $method->setAccessible(true);

        $authUrl = $method->invoke($provider, 'test_state');

        $this->assertStringStartsWith('http://localhost/authorize', $authUrl);
        $this->assertStringContainsString('state=test_state', $authUrl);
        $this->assertStringContainsString('client_id=client_id', $authUrl);
        $this->assertStringContainsString('redirect_uri=redirect', $authUrl);
        $this->assertStringContainsString('response_type=code', $authUrl);
    }

    public function test_get_token_url_method()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getTokenUrl');
        $method->setAccessible(true);

        $tokenUrl = $method->invoke($provider);

        $this->assertEquals('http://localhost/token', $tokenUrl);
    }

    public function test_user_retrieval_with_mocked_http_client()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->expects('pull')->once()->with('state')->andReturn(str_repeat('A', 40));

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect_uri');

        $tokenResponse = new Response(200, [], json_encode([
            'access_token' => 'access_token_123',
            'refresh_token' => 'refresh_token_456',
            'expires_in' => 3600,
        ]));

        $userResponse = new Response(200, [], json_encode([
            'ID' => 12345,
            'display_name' => 'John Doe',
            'username' => 'johndoe',
            'user_login' => 'johndoe',
            'email' => 'john@example.com',
            'user_email' => 'john@example.com',
            'avatar_URL' => 'https://example.com/avatar.jpg',
        ]));

        $mock = new MockHandler([$tokenResponse, $userResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider->setHttpClient($client);

        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('johndoe', $user->getName());
        $this->assertEquals('John Doe', $user->getNickname());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('https://example.com/avatar.jpg', $user->getAvatar());
        $this->assertEquals('access_token_123', $user->token);
        $this->assertEquals('refresh_token_456', $user->refreshToken);
        $this->assertEquals(3600, $user->expiresIn);
    }

    public function test_user_retrieval_with_missing_optional_fields()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('B', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->expects('pull')->once()->with('state')->andReturn(str_repeat('B', 40));

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect_uri');

        $tokenResponse = new Response(200, [], json_encode([
            'access_token' => 'access_token_456',
            'expires_in' => 7200,
        ]));

        $userResponse = new Response(200, [], json_encode([
            'ID' => 67890,
        ]));

        $mock = new MockHandler([$tokenResponse, $userResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider->setHttpClient($client);

        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(67890, $user->getId());
        $this->assertEquals('', $user->getName());
        $this->assertEquals('', $user->getNickname());
        $this->assertEquals('', $user->getEmail());
        $this->assertEquals('', $user->getAvatar());
        $this->assertEquals('access_token_456', $user->token);
        $this->assertNull($user->refreshToken);
        $this->assertEquals(7200, $user->expiresIn);
    }

    public function test_user_retrieval_with_partial_data()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('C', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->expects('pull')->once()->with('state')->andReturn(str_repeat('C', 40));

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect_uri');

        $tokenResponse = new Response(200, [], json_encode([
            'access_token' => 'access_token_789',
            'refresh_token' => 'refresh_token_789',
            'expires_in' => 1800,
        ]));

        $userResponse = new Response(200, [], json_encode([
            'ID' => 11111,
            'display_name' => 'Jane Smith',
            'user_login' => 'janesmith',
        ]));

        $mock = new MockHandler([$tokenResponse, $userResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider->setHttpClient($client);

        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(11111, $user->getId());
        $this->assertEquals('janesmith', $user->getName());
        $this->assertEquals('Jane Smith', $user->getNickname());
        $this->assertEquals('', $user->getEmail());
        $this->assertEquals('', $user->getAvatar());
    }

    public function test_user_profile_request_uses_bearer_token()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('D', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->expects('pull')->once()->with('state')->andReturn(str_repeat('D', 40));

        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect_uri');

        $tokenResponse = new Response(200, [], json_encode([
            'access_token' => 'bearer_token_test',
            'expires_in' => 3600,
        ]));

        $userResponse = new Response(200, [], json_encode([
            'ID' => 22222,
            'username' => 'testuser',
            'user_email' => 'test@wordpress.com',
        ]));

        $mock = new MockHandler([$tokenResponse, $userResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider->setHttpClient($client);

        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(22222, $user->getId());
        $this->assertEquals('testuser', $user->getName());
        $this->assertEquals('test@wordpress.com', $user->getEmail());
        $this->assertEquals('bearer_token_test', $user->token);
    }

    public function test_map_user_to_object_with_all_fields()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('mapUserToObject');
        $method->setAccessible(true);

        $userData = [
            'ID' => 12345,
            'display_name' => 'John Doe',
            'username' => 'johndoe',
            'user_login' => 'johndoe_login',
            'email' => 'john@example.com',
            'user_email' => 'john_alt@example.com',
            'avatar_URL' => 'https://example.com/avatar.jpg',
        ];

        $user = $method->invoke($provider, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('johndoe', $user->getName());
        $this->assertEquals('John Doe', $user->getNickname());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('https://example.com/avatar.jpg', $user->getAvatar());
    }

    public function test_map_user_to_object_with_fallback_fields()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('mapUserToObject');
        $method->setAccessible(true);

        $userData = [
            'ID' => 67890,
            'user_login' => 'fallback_user',
            'user_email' => 'fallback@example.com',
        ];

        $user = $method->invoke($provider, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(67890, $user->getId());
        $this->assertEquals('fallback_user', $user->getName());
        $this->assertEquals('fallback@example.com', $user->getEmail());
        $this->assertEquals('', $user->getNickname());
        $this->assertEquals('', $user->getAvatar());
    }

    public function test_get_user_by_token_method()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $userResponse = new Response(200, [], json_encode([
            'ID' => 33333,
            'display_name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'avatar_URL' => 'https://example.com/test-avatar.jpg',
        ]));

        $mock = new MockHandler([$userResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider->setHttpClient($client);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getUserByToken');
        $method->setAccessible(true);

        $userData = $method->invoke($provider, 'test_token');

        $this->assertIsArray($userData);
        $this->assertEquals(33333, $userData['ID']);
        $this->assertEquals('Test User', $userData['display_name']);
        $this->assertEquals('testuser', $userData['username']);
        $this->assertEquals('test@example.com', $userData['email']);
        $this->assertEquals('https://example.com/test-avatar.jpg', $userData['avatar_URL']);
    }

    public function test_map_user_to_object_with_minimal_data()
    {
        $request = Request::create('foo');
        $provider = new WordPressProvider($request, 'client_id', 'client_secret', 'redirect');

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('mapUserToObject');
        $method->setAccessible(true);

        $userData = [
            'ID' => 99999,
        ];

        $user = $method->invoke($provider, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(99999, $user->getId());
        $this->assertEquals('', $user->getName());
        $this->assertEquals('', $user->getNickname());
        $this->assertEquals('', $user->getEmail());
        $this->assertEquals('', $user->getAvatar());
    }
}
