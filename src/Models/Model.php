<?php namespace Pow\Models;

/**
 * Base Model
 *
 * @property int ID
 *
 * @todo ORM; expand
 * @package Pow\Models
 * @version 0.0.0
 * @since 0.0.0
 */
class Model
{
    /**
     * WordPress ``post_type``
     *
     * @var string
     */
    private $type = 'post';

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * Model constructor
     *
     * @param array $attributes
     * @param string $post_type
     */
    public function __construct(array $attributes, string $post_type = null)
    {
        $this->setType($post_type);
    }

    /**
     * Format the model attributes
     *
     * @return $this
     */
    public function format()
    {
        $this->attributes['post_content'] = apply_filters('the_content', $this->attributes['post_content']);

        return $this;
    }

    /**
     * Get all of WP's postmeta data for the model
     *
     * This also fetches the permalink.
     *
     * @return $this
     */
    public function meta()
    {
        $meta = get_metadata('post', $this->attributes['ID']);

        $this->attributes['permalink'] = get_permalink($this->ID);

        if (!is_array($meta)) return $this;

        unset($meta['_edit_last'], $meta['_edit_lock']);

        foreach ($meta as $k => $v) $meta[$k] = $v[0];

        $this->attributes['meta'] = $meta;

        return $this;
    }

    /**
     * Get and set the featured image as an Attachment instance
     *
     * @return $this
     */
    public function thumbnail()
    {
        if (isset($this->attributes['_thumbnail_id'])) {
            $id = $this->attributes['_thumbnail_id'];
        }

        if (!isset($id) && isset($this->attributes['meta']['_thumbnail_id'])) {
            $id = $this->attributes['meta']['_thumbnail_id'];
        }

        if (!isset($id)) {
            $id = get_post_meta($this->ID, '_thumbnail_id', true);
        }

        $this->attributes['thumbnail'] = !empty($id) ? new Attachment($id) : null;

        return $this;
    }

    /**
     * Set the ``post_type`` of the model
     *
     * @param string $post_type
     */
    public function setType(string $post_type = null): void
    {
        $this->type = $post_type ?? basename(static::class);
    }

    /**
     * Get the ``post_type`` of the model
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add an attribute to the model
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value = null)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Return an attribute from the model
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?: null;
    }

    /**
     * Remove an attribute from the model
     *
     * @param string $key
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Check if an attribute exists
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
