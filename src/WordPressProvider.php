<?php

namespace Revolution\Socialite\WordPress;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class WordPressProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $url = Config::get('services.wordpress.host').'/authorize';

        return $this->buildAuthUrlFromBase($url, $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return Config::get('services.wordpress.host').'/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()
            ->get(Config::get('services.wordpress.api_me'), [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => (int) $user['ID'],
            'nickname' => Arr::get($user, 'display_name'),
            'name' => Arr::get($user, 'username', Arr::get($user, 'user_login')),
            'email' => Arr::get($user, 'email', Arr::get($user, 'user_email')),
            'avatar' => Arr::get($user, 'avatar_URL'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
