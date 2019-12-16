<?php

namespace Tests;

use Laravel\Socialite\SocialiteServiceProvider;
use Revolution\Socialite\WordPress\WordPressServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SocialiteServiceProvider::class,
            WordPressServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            //
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set(
            'services.wordpress',
            [
                'client_id'     => 'test',
                'client_secret' => 'test',
                'redirect'      => 'http://localhost',
            ]
        );
    }
}
