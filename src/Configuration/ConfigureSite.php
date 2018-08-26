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
class ConfigureSite extends Configure
{
    /**
     * @inheritdoc
     * @param Pow $app
     */
    public function __construct(Pow $app)
    {
        parent::__construct($app);

        if (!empty($this->settings->has('main.toolbar')))
            add_action('admin_bar_menu', [$this, 'admin_bar_menu'], static::INT_MAX, 1);

        $config = $this->settings->get('site');

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

        # Can hide the toolbar even if user chose to display it
        show_admin_bar(!$config['force_hide_toolbar']);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        # If we do not use themes, this action hook needs to be called manually,
        # so it triggers subsequent action for displaying the toolbar and outputting wp_head things.
        if (!WP_USE_THEMES) {
            do_action('template_redirect');
        }
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

    /**
     * @inheritdoc
     */
    public function admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): array
    {
        $config = parent::admin_bar_menu($wp_admin_bar);

        # Remove search item
        if (!$config['search']) {
            $wp_admin_bar->remove_node('search');
        }

        if (!WP_USE_THEMES) {
            $wp_admin_bar->remove_node('themes');
        }

        return $config;
    }
}
