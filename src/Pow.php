<?php namespace Pow;

use Pow\Bootstrap\LoadEnvironmentVariables;

/**
 * Core application class
 *
 * @package Pow
 * @version 0.0.0
 * @since 0.0.0
 */
class Pow {

    /**
     * The base path for the app
     *
     * @var string
     */
    protected $basePath;

    /**
     * Instantiate Pow
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        new LoadEnvironmentVariables($this->basePath);
    }
}
