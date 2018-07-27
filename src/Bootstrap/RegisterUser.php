<?php namespace Pow\Bootstrap;

use Pow\Pow;
use Pow\Models\User;
use Pow\Contracts\Bootstrap;

/**
 * Bind the current user into the current Pow instance
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class RegisterUser implements Bootstrap
{
    /**
     * Load the config files
     *
     * @param Pow $app
     * @return bool
     */
    public function bootstrap(Pow $app): bool
    {
        global $current_user;

        $app->user = $current_user->exists() ? new User($current_user) : null;

        return true;
    }
}
