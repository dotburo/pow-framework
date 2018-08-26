<?php namespace Pow\Bootstrap;

use Pow\Pow;
use Pow\Contracts\Bootstrap;
use Pow\Configuration\Repository;
use Pow\Configuration\ConfigureSite;
use Pow\Configuration\ConfigureAdmin;

/**
 * Load configuration files
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class LoadConfiguration implements Bootstrap
{
    /**
     * Current app instance
     *
     * @var Pow
     */
    private $app;

    /**
     * Path to config files
     *
     * @var string
     */
    private $path = '';

    /**
     * Config files as key, boolean for loaded-state as value
     *
     * @var array
     */
    private $files = [];

    /**
     * This tells Pow where we are, and allows use to load specific configurations
     *
     * @return void
     */
    private function cacheWpGlobals(): void
    {
        global $pagenow;

        $this->app->WP_SIDE = is_admin() ? 'admin' : 'site';
        $this->app->pagenow = $pagenow;
        $this->app->typenow = null;
    }

    /**
     * Load the config files
     *
     * @param Pow $app
     * @return bool
     */
    public function bootstrap(Pow $app): bool
    {
        $this->app = $app;

        $this->path = $app->getBasePath('config');

        $this->cacheWpGlobals();

        $app->instance('config', $config = new Repository());

        $this->files = $this->globFiles();

        $wp_side = $app->WP_SIDE;

        # Some files are loaded before the rest, or conditionally
        # according to WordPress' globals
        if ($file = $this->has('main'))
            $this->load($file);

        if ($file = $this->has('post-types'))
            $this->load($file, 'init');

        if ($file = $this->has($wp_side))
            $this->load($file);

        # Load the rest of the files
        foreach ($this->files as $file => $loaded) {
            if (!$loaded) $this->load($file);
        }

        # Apply the configuration for the current side
        if ($wp_side === 'site' && $this->app->pagenow !== 'wp-login.php') {
            new ConfigureSite($app);
        } else if ($wp_side === 'admin' && !doingPowAjax()) {
            new ConfigureAdmin($app);
        }

        return true;
    }

    /**
     * Get all the files in the config folder
     *
     * This excludes `/config/wordpress.php` as it is already loaded by `/public/wp-config.php`
     *
     * @return array
     */
    private function globFiles()
    {
        $files = glob("{$this->path}/*.php");
        $files = array_fill_keys($files, false);

        unset($files["{$this->path}/wordpress.php"]);

        return $files;
    }

    /**
     * Require a config file, storing the returned value in the app
     *
     * Note this will store ``1`` is the config file does not return anything.
     *
     * @param {string} $path
     * @return void
     */
    private function load($path, $defer = false): void
    {
        $this->files[$path] = true;
        $filename = pathinfo($path, PATHINFO_FILENAME);
        
        if (!$defer) {
            $this->app['config']->set($filename, require $path);
        } else {
            add_action($defer, function() use ($filename, $path){
                $this->app['config']->set($filename, require $path);
            }, 0);
        }
    }

    /**
     * Check is a file is present in the glob array
     *
     * @param $name
     * @return null|string
     */
    private function has(string $name)
    {
        $path = "{$this->path}/$name.php";

        return isset($this->files[$path]) ? $path : null;
    }
}
