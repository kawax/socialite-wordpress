<?php

namespace Tests;

use Laravel\Socialite\SocialiteServiceProvider;
use Revolution\Socialite\WordPress\WordPressServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            WordPressServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            //
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('services.wordpress',
            [
                'host' => 'http://localhost',
                'api_me' => 'http://localhost/me/',
                'client_id' => 'test',
                'client_secret' => 'test',
                'redirect' => 'http://localhost',
            ],
        );

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
