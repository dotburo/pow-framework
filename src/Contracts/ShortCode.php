<?php namespace Pow\Contracts;

/**
 * Interface shortcode
 *
 * @package Pow\Contracts
 */
interface ShortCode
{
    public static function handle(array $attr = []);
}
