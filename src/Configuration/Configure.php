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
class Configure
{
    const INT_MAX = PHP_INT_MAX;

    /**
     * @var Pow
     */
    protected $app;

    /**
     * @var Repository
     */
    protected $settings;

    /**
     * Cherry pick config options and call related methods
     *
     * @param Pow $app
     */
    public function __construct(Pow $app)
    {
        $this->app = $app;

        $this->settings = $app['config'];

        $this->setPaths();

        add_action('init', [$this, 'init'], PHP_INT_MAX, 0);
        add_action('after_setup_theme', [$this, 'after_theme_setup'], PHP_INT_MAX, 0);
    }

    private function setPaths()
    {
        $wp_side = $this->app->WP_SIDE;

        $appURL = $this->settings->get('APP_URL');
        $appDIR = $this->app->getBasePath();

        $this->settings->set([
            'url' => $appURL,
            'url_home' => $appURL,
            'url_css' => "$appURL/build/$wp_side.min.css",
            'url_js' => "$appURL/build/$wp_side.min.js",
            'url_admin' => admin_url(),
            'dir' => $appDIR,
            'dir_lang' => $appDIR . DIRECTORY_SEPARATOR . 'languages',
            'dir_upload' => wp_upload_dir(),
        ]);
    }

    /**
     * Hook handler
     *
     * @event init
     * @return void
     */
    public function init(): void
    {
        $postTypeConfig = $this->settings->get('post-types');

        if (!empty($postTypeConfig['rename'])) {
            $this->renameDefaultContentTypes($postTypeConfig['rename']);
        }
    }

    /**
     * Hook handler
     *
     * @event after_theme_setup
     * @return void
     */
    public function after_theme_setup(): void
    {
        if ($this->settings->has('text_domain')) {
            load_textdomain($this->settings->get('text_domain'), $this->settings->dir('lang'));
        }

        if ($this->settings->has($this->app->WP_SIDE . '.theme_support')) {
            $this->addThemeSupport();
        }
    }

    /**
     * Rename default post types
     *
     * @see http://codex.wordpress.org/Function_Reference/register_taxonomy
     * @see http://core.trac.wordpress.org/browser/branches/3.0/wp-includes/taxonomy.php#L350
     * @param array $renames
     * @return void
     */
    public function renameDefaultContentTypes(array $renames): void
    {
        global $wp_post_types, $wp_taxonomies;

        $renames = array_filter($renames);

        foreach ($renames as $old => $param) {

            if (isset($wp_post_types[$old])) {

                $labels = &$wp_post_types[$old]->labels;
                $labels = (object)array_merge((array)$labels, $param['labels']);
                $wp_post_types[$old]->menu_icon = isset($param['menu_icon'])
                    ? $param['menu_icon']
                    : $wp_post_types[$old]->menu_icon;

            } elseif (isset($wp_taxonomies[$old])) {

                $labels = &$wp_taxonomies[$old]->labels;
                $labels = (object)array_merge((array)$labels, $param['labels']);
                $wp_taxonomies[$old]->label = $param['label'];
            }
        }
    }

    /**
     * Enable theme features depending on the current side (admin|public)
     *
     * @see https://developer.wordpress.org/reference/functions/add_theme_support/
     * @return void
     */
    protected function addThemeSupport(): void
    {
        $support = $this->settings->get($this->app->WP_SIDE . '.theme_support');

        array_walk($support, function ($args, $feature) {
            if ($args === true)
                add_theme_support($feature);
            elseif (is_array($args))
                add_theme_support($feature, ...$args);
        });
    }

    /**
     * Adapt or remove toolbar items
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     * @return array
     */
    public function admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): array
    {
        $config = array_merge([
            'view-site-submenu' => false, 'logo' => false, 'howdy' => null, 'search' => true
        ], $this->settings->get('main.toolbar'));

        # Remove WP's logo
        if (!$config['logo']) {
            $wp_admin_bar->remove_node('wp-logo');
        }

        # Replace or remove howdy/username

        $greet = $wp_admin_bar->get_node('my-account')->title;

        if (empty($config['username'])) {
            $greet = substr($greet, strpos($greet, '<img'));
        } else {
            $greet = substr($greet, strpos($greet, '<span '));
        }

        $wp_admin_bar->add_node([
            'id' => 'my-account',
            'title' => (!empty($config['howdy']) ? $config['howdy'] : '') . $greet
        ]);

        return $config;
    }
}
