<?php namespace Pow\Configuration;

use Dotenv\Dotenv;

/**
 * Load environment variables
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class LoadEnvironment
{
    /**
     * Filename of the environment file
     *
     * @var string
     */
    const FILE = '.env';

    /**
     * Instantiate DotEnv and WordPress globals
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->dotEnv($basePath);

        $this->wpGlobals($basePath);
    }

    /**
     * Instantiate DotEnv
     *
     * @param string $basePath
     * @return void
     */
    private function dotEnv(string $basePath): void
    {
        try {
            (new Dotenv($basePath, static::FILE))->load();
        } catch (InvalidPathException $e) {
            //
        } catch (InvalidFileException $e) {
            die('The environment file is invalid: ' . $e->getMessage());
        }
    }

    /**
     * Load the WordPress globals
     *
     * @param string $basePath
     * @return void
     */
    private function wpGlobals(string $basePath): void
    {
        require_once($basePath . '/config/wordpress.php');
    }
}