<?php namespace Pow\Configuration;

use Pow\Pow;

/**
 * Handle the public facing configuration parameters
 *
 * @package Pow\Configuration
 * @see /config/site.php
 * @version 0.0.0
 * @since 0.0.0
 */
class ConfigureSite
{
    /**
     * Cherry pick config options and call related methods
     *
     * @param Pow $app
     */
    public function __construct(Pow $app)
    {
        $config = $app['config']->get('site');

        if (!empty($config['theme_support']))
            $this->addThemeSupport($config['theme_support']);

        if (!($config['emoji'] ?? false))
            $this->disableEmoji();

        if (!($config['oEmbed'] ?? false))
            $this->disableOEmbed();

        if (!empty($config['enable_actions']))
            $this->disableActions($config['enable_actions'], 'wp_head');

        if (!empty($config['enable_filters']))
            $this->disableFilters($config['enable_filters']);

        if (!empty($config['shortcodes']))
            $this->addShortCodes($config['shortcodes']);
    }

    /**
     * Remove all emoji related action and filter handlers
     *
     * @return void
     */
    private function disableEmoji(): void
    {
        //remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }

    /**
     * Remove all oEmbed related action and filter handlers
     *
     * @return void
     */
    private function disableOEmbed(): void
    {
        # Remove the REST API endpoint
        remove_action('rest_api_init', 'wp_oembed_register_route');

        # Turn off oEmbed auto discovery. Don't filter oEmbed results
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

        # Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        # oEmbed host javascript
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }

    /**
     * Enable theme support
     *
     * @see https://developer.wordpress.org/reference/functions/add_theme_support/
     * @param array $support
     * @return void
     */
    public function addThemeSupport(array $support): void
    {
        add_action('after_theme_setup', function() use ($support) {
            array_walk($support, function ($args, $feature) {
                if ($args === true)
                    add_theme_support($feature);
                elseif (is_array($args))
                    add_theme_support($feature, ...$args);
            });
        }, PHP_INT_MAX);
    }

    /**
     * Disables wp_head action hook handlers
     *
     * @param array $actions
     * @param string $hook
     * @return void
     */
    private function disableActions(array $actions, string $hook): void
    {
        array_walk($actions, function (bool $enable, string $name) use ($hook) {

            $namePriority = splitPriority($name);

            if (!$enable) remove_action($hook, $namePriority[0], (int)$namePriority[1]);
        });
    }

    /**
     * Disable public facing filters
     *
     * @param array $filters
     */
    private function disableFilters(array $filters): void
    {
        array_walk($filters, function (bool $enable, string $name) {
            if (!$enable) add_filter($name, '__return_false');
        });
    }

    /**
     * Add shortcode handlers
     *
     * @param array $codes
     * @return void
     */
    public function addShortCodes(array $codes): void
    {
        array_walk($codes, function (string $class, string $name) {
            if (class_exists($class)) add_shortcode($name, [$class, 'handle']);
        });
    }
}
