<?php namespace Pow\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;

/**
 * Load environment variables
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class LoadEnvironmentVariables
{
    /**
     * LoadEnvironmentVariables constructor.
     *
     * @param string $path
     * @param string $file
     */
    public function __construct(string $path, string $file = '.env')
    {
        try {
            (new Dotenv($path, $file))->load();
        } catch (InvalidPathException $e) {
            //
        } catch (InvalidFileException $e) {
            die('The environment file is invalid: ' . $e->getMessage());
        }
    }
}