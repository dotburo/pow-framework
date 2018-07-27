<?php namespace Pow\Bootstrap;

use Pow\Pow;
use Pow\Contracts\Bootstrap;

/**
 * Register Service Providers
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class RegisterProviders implements Bootstrap
{
    /**
     * Bootstrap the given application
     *
     * @param Pow $app
     * @return bool
     */
    public function bootstrap(Pow $app): bool
    {
        $providers = $app['config']->get('main.providers') ?? [];

        array_walk($providers, function(string $provider) use ($app) {

            $provider = new $provider($app);

            $provider->register();
        });

        return true;
    }
}
