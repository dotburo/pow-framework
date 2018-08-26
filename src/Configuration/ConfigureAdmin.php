<?php namespace Pow\Configuration;

use Pow\Pow;

class ConfigureAdmin extends Configure
{
    /**
     * @var array
     */
    protected $css = [];

    /**
     * @inheritdoc
     */
    public function __construct(Pow $app)
    {
        parent::__construct($app);

        if ($this->settings->has('main.toolbar'))
            add_action('admin_bar_menu', [$this, 'admin_bar_menu'], static::INT_MAX, 1);

        $config = $this->settings->get('admin');

        if (!empty($config['enqueue']))
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], static::INT_MAX, 0);

        if (!empty($config['disable-pages']))
            $this->disablePagesByURI($config['disable-pages']);

        if (!empty($config['footer']))
            $this->setFooter($config['footer']);

        add_action('admin_head', [$this, 'admin_head'], static::INT_MAX, 0);
        add_action('admin_notices', [$this, 'admin_notices'], static::INT_MAX, 0);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        # Configure post types
        if ($this->settings->has('post-types.support')) {

            $conf = $this->settings->get('post-types.support');

            # Attachment configuration has too many edge cases compared to other post types
            if (!empty($conf['attachment'])) {
                $this->setAttachmentSupport($conf['attachment']);
                unset($conf['attachment']);
            }

            $this->setPostTypesSupport($conf);
        }

        # Hide post type screens from the admin
        if ($this->settings->has('admin.disable-pages'))
            $this->disablePostTypeScreens($this->settings->get('admin.disable-pages'));
    }

    /**
     * Set or change supported features of all post types except attachments
     *
     * @see config/post-types.php
     * @see https://codex.wordpress.org/Function_Reference/add_post_type_support
     *
     * @param array[] $config Array of arrays indexed by post type name
     * @return void
     */
    private function setPostTypesSupport(array $config): void
    {
        foreach ($config as $postType => $supports) {
            foreach ($supports as $feature => $toggle) {

                if (!$toggle) {
                    if ($feature !== 'slug') {
                        remove_post_type_support($postType, $feature);
                    } else {
                        # The slug meta box cannot be removed with ``remove_post_type_support``.
                        removeMetaBoxes($postType, 'slugdiv');
                    }
                } else {
                    add_post_type_support($postType, $feature);
                }
            }
        }
    }

    /**
     * Set or change supported features of all post types except attachments
     *
     * @see config/post-types.php
     * @see https://codex.wordpress.org/Function_Reference/add_post_type_support
     *
     * @param array $config
     * @return void
     */
    private function setAttachmentSupport(array $config): void
    {
        # Plain post type support removal
        foreach (array_intersect_key($config, array_flip(['author', 'comments', 'title'])) as $feature => $toggle) {
            if (!$toggle) remove_post_type_support('attachment', $feature);
        }

        # The slug meta box cannot be removed with ``remove_post_type_support``.
        if (!($config['slug'] ?? true)) {
            removeMetaBoxes('attachment', 'slugdiv');
        }

        # Disable single pages, ie. all links leading to the ``single-attachment.php`` template or its fallback(s)
        if (!($config['single'])) {
            $this->disableAttachmentSingle();
        }

        # Following media view sections are can neither be removed through ``remove_post_type_support``
        # nor through ``remove_meta_box``. They will merely be hidden through css echo in ``admin_head``;

        # Hide the description field
        if (!($config['description'] ?? true)) {
            $this->css[] = '#wp-attachment_content-wrap,#post-body-content label[for=attachment_content],'
                . '.attachment-details .setting[data-setting="description"]{display:none}';
        }
        # Hide the alternate text field
        if (!($config['alt-text'] ?? true)) {
            $this->css[] = '.wp_attachment_details p:nth-child(2),'
                . '.attachment-details .setting[data-setting="alt"]{display:none}';
        }
        # Hide the caption field
        if (!($config['caption'] ?? true)) {
            $this->css[] = '.wp_attachment_details p:first-of-type,'
                . '.attachment-details .setting[data-setting="caption"]{display:none}';
        }
        # Hide 'attachment display settings' in post modal
        if (!($config['display-settings'] ?? true)) {
            $this->css[] = '.attachment-display-settings{display:none}';
        }
    }

    /**
     * Disable single pages for attachment
     *
     * This is mainly to cope with WP inconsistencies, making sure all admin links
     * leading to the ``single-attachment.php`` template or its fallback(s) are removed.
     *
     * @todo fix WP bugs
     * @return void
     */
    private function disableAttachmentSingle(): void
    {
        # Hides the permalink in the attachment edit and
        # the "View Attachment Page" link in the toolbar when on a attachment edit screen
        # (In earlier WPs the latter had to be done with ``$wp_admin_bar->remove_node``).
        $post_param = get_post_type_object('attachment');
        $post_param->public = false;

        # WP Bug: 'View attachment page' is not removed through ``$post_param->public``
        $this->css[] = '.attachment-details .actions a.view-attachment{display:none}';

        # WP Bug: 'View' row action is not removed through ``$post_param->public``
        add_filter('media_row_actions', function ($actions, $post) {
            if ($post->post_type === 'attachment') unset($actions['view']);
            return $actions;
        }, static::INT_MAX, 2);
    }

    /**
     * Do things upon the ``admin_head`` action
     *
     * This outputs all css rules push to the ``$css`` property of this class;
     *
     * @return void
     */
    public function admin_head(): void
    {
        $screen = get_current_screen();

        # Hide update notice for non-admins
        if (!current_user_can('manage_options'))
            remove_action('admin_notices', 'update_nag', 3);

        # Always hide default help sidebar
        $screen->set_help_sidebar('');

        # Echo out all css bound through this class
        if (!empty($this->css)) {
            echo '<style>', implode('', $this->css), '</style>';
        }
    }

    /**
     * Hide update notification for everyone except with 'manage_options' cap;
     * page access permission error, when GET param "PowAccessError" is set.
     */
    public function admin_notices()
    {
        if (isset($_GET["PowAccessError"])) {
            echo "<div class='error'><p>Nope</p></div>";
        }
    }

    /**
     * @inheritdoc
     */
    public function admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): array
    {
        $config = parent::admin_bar_menu($wp_admin_bar);

        # Always set target _blank on the site (sub)menu items, that way
        # the user can easily switch between admin and site browser tabs.

        /** @var array|object $node */
        $node = $wp_admin_bar->get_node('site-name');
        $node->meta['target'] = '_blank';
        $wp_admin_bar->add_node($node);

        if (!$config['view-site-submenu']) {
            $wp_admin_bar->remove_node('view-site');
        } else {
            $node = $wp_admin_bar->get_node('view-site');
            $node->meta['target'] = '_blank';
            $wp_admin_bar->add_node($node);
        }

        return $config;
    }

    /**
     * Set footer left and right text (HTML)
     *
     * @internal string $left
     * @internal string $right
     * @internal boolean $show-version
     *
     * @param array $config
     * @return void
     */
    private function setFooter(array $config): void
    {
        $config = array_merge([
            'left' => null, 'right' => null, 'show-version' => false
        ], $config);

        if (!is_null($config['left'])) {
            add_filter('admin_footer_text', function () use ($config) {
                return $config['left'];
            }, static::INT_MAX);
        }

        add_filter('update_footer', function ($version) use ($config) {
            return (!is_null($config['right']) ? $config['right'] : '') . ($config['show-version'] ? $version : '');
        }, static::INT_MAX, 1);
    }

    /**
     * Hide and prevent access to admin pages given URIs
     *
     * @param array $remove
     * @return void
     */
    private function disablePagesByURI(array $remove): void
    {
        add_action('admin_menu', function () use ($remove) {

            foreach ($remove as $uri) {

                if (count($splits = preg_split('/:/', $uri, 2, PREG_SPLIT_NO_EMPTY)) === 2) {
                    if (remove_submenu_page($splits[0], $splits[1])) $test = $splits[1];
                } else {
                    if (remove_menu_page($splits[0])) $test = $splits[0];
                }

                if (isset($test) && (strpos($_SERVER['REQUEST_URI'], $test) !== false)) {
                    wp_redirect($this->settings->url('admin', 'index.php?PowAccessError'));
                    exit;
                }
            }
        });
    }

    /**
     * Hide a post type from the admin
     *
     * All items in the admin config file under ``disable-pages`` which have
     * no '.php' extension will be considered here as post type names.
     *
     * @todo fix WP bug: get_post_type_object('attachment')->show_in_admin_bar = false has no effect
     * @param array $disabledPages
     * @return void
     */
    private function disablePostTypeScreens(array $disabledPages): void
    {
        $pages = array_filter($disabledPages, function ($pageURI) {
            return strpos($pageURI, '.php') === false;
        });

        if ($pages === []) return;

        foreach ($pages as $postType) {
            $post_param = get_post_type_object($postType);
            $post_param->show_in_admin_bar = false;
            $post_param->show_in_menu = false;
        }

        # Redirect if on one of the removed pages
        add_action('current_screen', function ($screen) use ($pages) {
            if (in_array($screen->post_type, $pages) || (in_array('media', $pages) && $screen->id === 'upload')) {
                wp_redirect($this->settings->url('admin', 'index.php?PowAccessError'));
                exit;
            }
        });
    }

    /**
     * Enqueue css & js through a manifest
     *
     * @return void
     */
    public function admin_enqueue_scripts(): void
    {
        $url = $this->settings->url();
        $manifest = $this->settings->get('admin.enqueue');

        if (is_string($manifest)) {
            $manifest = $this->settings->dir('public', $manifest);
            if (file_exists($manifest)) {
                $manifest = (array)json_decode(file_get_contents($manifest), true);
            } else {
                return;
            }
        }

        foreach ($manifest as $k => $path) {
            if (strpos($path, '.js')) {
                wp_enqueue_script("pow-$k", "$url$path", [], false, true);
            } else {
                wp_enqueue_style("pow-$k", "$url$path", [], false);
            }
        }
    }
}