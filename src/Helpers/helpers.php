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

if (!function_exists('app')) {
    /**
     * Return the current Pow instance
     *
     * @param string $abstract
     * @param array $parameters
     * @return \Pow\Pow
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return \Pow\Pow::getInstance();
        }

        return \Pow\Pow::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('renamePostType')) {
    /**
     * Return the current Pow instance
     *
     * @param string $post_type
     * @return string
     */
    function renamePostType(string $post_type): string
    {
        $app = \Pow\Pow::getInstance();
        return $post_type;
    }
}

if (!function_exists('doingPowAjax')) {
    /**
     * Check if current request is a Pow specific XHR request
     *
     * This is for the old fashion of making ajax calls. These calls require an ``action``
     * parameter to be intercepted by WordPress. Within Pow the value of the parameter needs
     * to have the form of ``controller@method`` to be recognized.
     *
     * @return array|null
     */
    function doingPowAjax()
    {
        if (!defined('DOING_AJAX')) return null;

        $action = $_REQUEST['action'] ?? '';

        $controllerAtMethod = (array)preg_split('/@/', (string)$action, 2, PREG_SPLIT_NO_EMPTY);

        return count($controllerAtMethod) === 2 ? $controllerAtMethod : null;
    }
}

if (!function_exists('modelClassExists')) {
    /**
     * Check if a model exists in the ``App`` namespace
     *
     * @param string $post_type
     * @param string $default
     * @return string|null
     */
    function modelClassExists(string $post_type, string $default = null)
    {
        $name = sanitize_key($post_type);
        $name = preg_split('/[-_]/', $name, null, PREG_SPLIT_NO_EMPTY);
        $name = array_map('ucfirst', (array)$name);
        $name = implode('', $name);

        $name = "App\\{$name}";

        if (!class_exists($name)) return $default;

        return $name;
    }
}

if (!function_exists('splitPriority')) {
    /**
     * Split priority int from a concatenated ``action_name:int`` string
     *
     * @param string $hook
     * @return array
     */
    function splitPriority(string $hook): array
    {
        if (strpos($hook, ':') === false) return [$hook, 10];

        return explode(':', $hook);
    }
}

if (!function_exists('removeMetaBox')) {
    /**
     * Remove a meta box from an admin edit screen
     *
     * @param string $postType
     * @param string|array $names
     * @param string $side
     * @return void
     */
    function removeMetaBoxes(string $postType, $names, string $side = 'normal')
    {
        add_action('admin_head', function () use ($postType, $names, $side) {
            if (is_array($names)) {
                array_walk($names, function ($name) use ($postType, $side) {
                    remove_meta_box($name, $postType, $side);
                });
            } else {
                remove_meta_box($names, $postType, $side);
            }
        }, PHP_INT_MAX, 0);
    }
}
