<?php namespace Pow\Helpers\ShortCodes;

use Pow\Contracts\ShortCode;

/**
 * Email obfuscation short code handler
 *
 * @package Pow\Helpers\ShortCodes
 * @version 0.0.0
 * @since 0.0.0
 */
class ObfuscateEmail implements ShortCode
{
    public static function handle(array $attr = [])
    {
        $attr = shortcode_atts(['email' => ''], $attr);

        return obfuscateEmail($attr['email']);
    }
}
