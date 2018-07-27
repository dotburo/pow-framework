<?php namespace Pow\Bootstrap;

use Pow\Pow;
use WP_Post;
use WP_Query;
use Pow\Models\Model;
use Pow\Helpers\Collection;
use Pow\Contracts\Bootstrap;

/**
 * Intercept the results of the main query and augment its models
 *
 * @package Pow\Bootstrap
 * @version 0.0.0
 * @since 0.0.0
 */
class ModelQueryResults implements Bootstrap
{
    /**
     * Current Pow instance
     *
     * @var Pow
     */
    protected $app;

    /**
     * Bootstrap the given application
     *
     * @param Pow $app
     * @return true
     */
    public function bootstrap(Pow $app): bool
    {
        $this->app = $app;

        return add_action('the_posts', [$this, 'parse'], PHP_INT_MAX, 2);
    }

    /**
     * Make Pow models out of WP_Post ones
     *
     * @param array $posts
     * @param WP_Query $wp_query
     * @return array|Collection
     */
    public function parse(array $posts, WP_Query $wp_query)
    {
        $count = count($posts);

        if (!$wp_query->is_main_query() || !$count) {
            return $posts;
        }

        $post_type = $wp_query->get('post_type', 'post');

        $model = modelClassExists($post_type, Model::class);

        $models = array_map(function (WP_Post $post) use ($model, $post_type) {
            return new $model($post->to_array(), $post->post_type);
        }, $posts);

        if ($count === 1) {
            $models = array_shift($models);
        } else {
            $models = new Collection($models);
        }

        $this->app->wp_query_result = $models;
        $this->app->wp_query = $wp_query;

        return $posts;
    }
}
