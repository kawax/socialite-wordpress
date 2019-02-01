<?php

namespace Revolution\Socialite\WordPress;

use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class WordPressServiceProvider extends SocialiteServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Socialite::extend('wordpress', function ($app) {
            $config = $app['config']['services.wordpress'];

            return Socialite::buildProvider(WordPressProvider::class, $config);
        });
    }
}
