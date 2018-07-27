<?php namespace Pow\Contracts;

use Pow\Pow;

/**
 * Bootstrapping interface
 *
 * @package Pow\Contract
 * @version 0.0.0
 * @since 0.0.0
 */
interface Bootstrap
{
    /**
     * @param Pow $app
     * @return bool
     */
    public function bootstrap(Pow $app): bool;
}