<?php

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script
     *
     * @param  mixed $args
     * @return void
     */
    function dd(...$args): void
    {
        foreach ($args as $x) var_dump(...$x);

        die(1);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable or falls back to a default
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        return $value !== false ? $value : $default;
    }
}
