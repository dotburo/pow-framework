<?php namespace Pow\Controllers;

use Pow\Pow;
use WP_Query;
use Pow\Models\Model;
use Pow\Helpers\Collection;
use Pow\Contracts\Controller;

/**
 * Base Controller
 *
 * @todo expand
 * @package Pow\Controllers
 * @version 0.0.0
 * @since 0.0.0
 */
abstract class ControllerAbstract implements Controller
{
    /**
     * Current Pow instance
     *
     * @var Pow
     */
    protected $app;

    /**
     * Controller instantiation
     *
     * @param Pow $app
     */
    public function __construct(Pow $app)
    {
        $this->app = $app;
    }

    /**
     * @todo rename ?
     * @param Collection|Model $models
     * @param Wp_Query $wp_query
     * @return mixed
     */
    abstract public function render($models, $wp_query);

    /**
     * @todo
     * @param WP_Query $query
     */
    public function pre_get_posts(WP_Query $query)
    {

    }

    /**
     * Render the default 404 template
     *
     * @return void
     */
    public function renderNotFound(): void
    {
        $this->app->view('partials.404');
    }
}
