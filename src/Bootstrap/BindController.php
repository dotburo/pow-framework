<?php namespace Pow\Bootstrap;

use WP;
use Pow\Pow;
use WP_Query;
use Pow\Contracts\Bootstrap;
use Pow\Controllers\ControllerAbstract;

/**
 * Decides which controller should be loaded
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class BindController implements Bootstrap
{
    /**
     * Name for the default controller
     *
     * @var string
     */
    const DEFAULT_CONTROLLER_NAME = 'default';

    /**
     * Current app instance
     *
     * @var Pow
     */
    private $app;

    /**
     * Decide which controller should be loaded
     *
     * @param Pow $app
     * @return bool
     * @throws BootstrapException
     */
    public function bootstrap(Pow $app): bool
    {
        $this->app = $app;

        if ($app->pagenow === 'wp-login.php') {
            return $this->loadLoginController();
        }

        if ($app->WP_SIDE === 'site') {
            $this->bindSiteController();
            $this->catchMissingController();
            return true;
        }

        if ($controllerAndMethod = doingPowAjax()) {
            return $this->bindAjaxController($controllerAndMethod);
        }

        if ($app->WP_SIDE === 'admin') {
            $this->bindAdminController();
            return true;
        }

        return false;
    }

    /**
     * Attempt to instantiate a controller withe a given name
     *
     * @param string $name
     * @param string $prefix
     * @return false|ControllerAbstract
     */
    private function instantiateController(string $name, string $prefix = '')
    {
        if ($controller = $this->controllerExists($name, $prefix)) {
            return $this->app->controller = new $controller($this->app);
        }

        return false;
    }

    /**
     * Attempt to instantiate a controller from the query variable object for WP_Query
     *
     * This is currently only used for the main query on page load.
     *
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts
     * @return void
     */
    private function bindSiteController(): void
    {
        add_action('pre_get_posts', function (WP_Query &$wp_query) {
            if (!$wp_query->is_main_query()) {
                return;
            }

            $name = $this->selectSiteControllerName($wp_query, static::DEFAULT_CONTROLLER_NAME);

            $subNamespace = !$wp_query->is_feed() ? '' : 'RSS';

            $controller = $this->controllerExists($name, $subNamespace);

            if ($controller) {

                $this->app->controller = new $controller($this->app);

                # This allows the user to interact with the query variables
                # before the database request is performed.
                $this->app->controller->pre_get_posts($wp_query);
            }

        }, PHP_INT_MAX, 1);
    }

    /**
     * A fail-save in case no controller was selected before
     *
     * Binds to the ``wp`` event and tries to instantiate the default controller,
     * it also call ``renderNotFound`` method on the current controller in case WP
     * could not query the requested content type in the database.
     *
     * @throws BootstrapException
     * @return void
     */
    private function catchMissingController(): void
    {
        add_action('wp', function (WP &$wp) {

            $controller = $this->app->controller;

            if (!$controller) {
                $controller = $this->controllerExists(static::DEFAULT_CONTROLLER_NAME);
                $controller = $this->app->controller = new $controller($this->app);
            }

            if (!$controller) {
                throw new BootstrapException('Missing controller.');
            }

            if (is_404() && method_exists($controller, 'renderNotFound')) {
                $controller->renderNotFound();
            }

        }, PHP_INT_MAX, 1);
    }

    /**
     * Return the name of the type of content being requested
     *
     * @see https://codex.wordpress.org/Conditional_Tags
     * @param WP_Query $wp_query
     * @param string $default
     * @return string
     */
    private function selectSiteControllerName(WP_Query $wp_query, string $default): string
    {
        if ($wp_query->is_feed()) {

            $default_type = !$wp_query->is_comment_feed() ? 'post' : 'comment';

            # The 2nd parameter of the getter is a fallback in case no post_type is found
            # this seems to always be the case for the default 'post' post type...
            $post_type = $wp_query->get('post_type', $default_type);

            return renamePostType($post_type);
        }

        if ($wp_query->is_home())
            return renamePostType('post');

        if ($wp_query->is_page())
            return 'page';

        if ($wp_query->is_search())
            return 'search';

        if ($wp_query->is_single() || $wp_query->is_archive()) {
            $post_type = $wp_query->get('post_type', 'post');
            return renamePostType($post_type);
        }

        return $default;
    }

    /**
     * Bind a controller to an admin screen
     *
     * @todo handle custom pages, cf. pow-profile
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/current_screen
     * @return void
     */
    private function bindAdminController(): void
    {
        add_action('current_screen', function($scr) {

            if ($scr->base === 'dashboard') {
                return $this->instantiateController('dashboard', 'Admin');
            }

            if (!empty($scr->post_type)) {
                $this->app->typenow = $scr->post_type;
                return $this->instantiateController($scr->post_type);
            }

            if (in_array($scr->base, ['edit-tags', 'term'])) {
                $this->app->typenow = $scr->post_type;
                return $this->instantiateController($scr->taxonomy);
            }

            if (in_array($scr->base, ['users', 'user'])) {
                return $this->instantiateController('user', 'Admin');
            }

            if (in_array($scr->base, ['profile', 'users_page_pow-profile'])) {
                return $this->instantiateController('user');
            }

            if ($scr->base === 'edit-comments') {
                return $this->instantiateController('comments', 'Admin');
            }

            return $scr;

        }, PHP_INT_MAX, 1);
    }

    /**
     * If it exists, instantiate the controller class and call its method
     *
     * This is the 'old' way of answering XHR requests, where possible one
     * should use the built-in REST API instead.
     * Both the controller class name and the method need to be provided
     * in the ``action`` parameter, for example ``action=post@all`` for the
     * ``method`` on the ``PostController`` class.
     *
     * @see doing_pow_ajax()
     * @param array $controllerAndMethod
     * @return bool
     */
    private function bindAjaxController(array $controllerAndMethod): bool
    {
        $controllerName = $controllerAndMethod[0];
        $method = $controllerAndMethod[1];

        if (!$this->instantiateController($controllerName)) {
            return false;
        }

        $callMethod = function () use ($method) {
            if (method_exists($this->app->controller, $method)) {
                $this->app->controller->{$method}();
            }
        };

        if ($this->app->user) {
            add_action("wp_ajax_$controllerName@$method", $callMethod);
        } else {
            add_action("wp_ajax_nopriv_$controllerName@$method", $callMethod);
        }

        return true;
    }

    /**
     * If it exists, instantiate ``\App\Controllers\LoginController``
     *
     * This is a bit of an edge-case, but with it we can avoid loading any other,
     * admin or site, controller.
     *
     * @return bool
     */
    private function loadLoginController(): bool
    {
        if ($controller = $this->controllerExists('login')) {
            $this->app->controller = new $controller($this->app);
            return true;
        }

        return false;
    }

    /**
     * Check if a controller class exists under ``php/Controllers/``
     *
     * @param string $name
     * @param string $subNamespace
     * @return string|null
     */
    private function controllerExists(string $name, string $subNamespace = '')
    {
        $name = sanitize_key($name);
        $name = str_replace('controller', '', $name);
        $name = preg_split('/[-_]/', $name, null, PREG_SPLIT_NO_EMPTY);
        $name = array_map('ucfirst', (array)$name);
        $name = implode('', $name);

        $subNamespace = !empty($subNamespace) ? "\\$subNamespace}" : '';

        $name = "App\Controllers{$subNamespace}\\{$name}Controller";

        if (class_exists($name)) return $name;

        return null;
    }
}
