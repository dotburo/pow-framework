<?php namespace Pow;

use Pow\Models\User;
use Illuminate\Container\Container;
use Pow\Bootstrap\LoadConfiguration;

/**
 * Core application class
 *
 * @property string WP_SIDE
 * @property string pagenow
 * @property string typenow
 *
 * @package Pow
 * @version 0.0.0
 * @since 0.0.0
 */
class Pow extends Container
{
    /**
     * The current Pow instance
     *
     * @var static
     */
    protected static $instance;

    /**
     * The current Pow instance
     *
     * @var static
     */
    protected $basePath;

    /**
     * Prevent bootstrap classes to be called multiple times
     *
     * @var array
     */
    protected $isBootstrapped = [];

    /**
     * The selected controller for current request
     *
     * @see Pow\Bootstrap\BindController
     * @var Pow\Controllers\ControllerAbstract
     */
    public $controller;

    /**
     * If logged in, the current user
     *
     * @var User|null
     */
    public $user;

    /**
     * Will be true once the wp_loaded action is fired
     * 
     * @var bool
     */
    private $wp_loaded = false;

    /**
     * @var Pow\Helpers\Collection|Pow\Models\Model;Model
     */
    public $wp_query_result;

    /**
     * @var \WP_Query
     */
    public $wp_query;

    /**
     * Return the current app instance
     *
     * @return Pow
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Instantiate Pow
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        static::$instance = $this;
    }

    /**
     * Action hook handler bound in ``bootstrap-pow.php`` mu-plugin
     * 
     * @param string $action
     * @param array|null $arguments
     */
    public function bootstrap(string $action, ?array $arguments = null): void
    {
        if ($this->wp_loaded || isset($this->isBootstrapped[$action])) return;

        if ($action === 'muplugins_loaded') {
            (new LoadConfiguration())->bootstrap($this);
        }

        $classes = $this['config']->get("main.bootstrap.$action");

        if (!empty($classes)) {
            array_walk($classes, function(string $class) use ($arguments) {
                if (class_exists($class)) {
                    $bootstrap = new $class($this, ...$arguments);
                    $bootstrap->bootstrap($this);
                }
            });
        }

        if ($action === 'wp_loaded') {
            $this->wp_loaded = true;
        }
    }

    /**
     * Get the templating engine instance and build the view
     *
     * @param string $template
     * @param array $data
     * @param bool $echo
     * @return mixed
     */
    public function view(string $template, array $data = [], bool $echo = true)
    {
        $factory = $this->resolve('view');

        $config = $this->resolve('config')->all();

        $rendered = $factory->make($template, $data, $config)->render();

        if ($echo) {
            echo $rendered;
            return;
        }

        return $rendered;
    }

    /**
     * Run the developer's app
     *
     * By now Pow is set up, the controller is bound to the query and
     * the posts are fetched from the database.
     *
     * @return void
     */
    public function run(): void
    {
        add_action('wp', function () {
            if (method_exists($this->controller, 'render')) {
                $this->controller->render($this->wp_query_result, $this->wp_query);
            }
        }, PHP_INT_MAX, 0);
    }

    /**
     * Return the applications base path
     *
     * @param string $append
     * @return string
     */
    public function getBasePath(string $append = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $append;
    }
}
