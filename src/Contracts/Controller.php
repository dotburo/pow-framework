<?php namespace Pow\Contracts;

use Pow\Pow;

/**
 * Controller contract
 *
 * @package Pow\Contracts
 * @version 0.0.0
 * @since 0.0.0
 */
interface Controller
{
    public function __construct(Pow $app);

    public function render($models, $wp_query);
}
