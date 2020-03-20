<?php

/**
 * This file is part of WpAlgolia plugin.
 * (c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
 * @version 0.0.7
 */

namespace WpAlgolia;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class RegisterAbstract
{
    public $post_type;

    public $index_name_base;

    public $algolia_client;

    public $algolia_index;

    public $index_settings;

    public $locale;

    private $log;

    public function __construct($post_type, $index_name_base, $algolia_client, $index_settings = null)
    {
        // set class attributes
        $this->post_type = $post_type;
        $this->index_name_base = $index_name_base;
        $this->algolia_client = $algolia_client;
        $this->index_settings = $index_settings;
        $this->locale = null;

        // create logging
        $this->log = new Logger($index_name_base);
        $this->log->pushHandler(new StreamHandler(__DIR__."/debug-{$post_type}.log", Logger::DEBUG));

        // Core's WP actions
        add_action('wp_insert_post', array($this, 'save_post'), 10, 3);
        add_action("delete_post_{$this->post_type}", array($this, 'delete_post'), 10, 2);

        // add bulk action to post type
        add_filter("bulk_actions-edit-{$this->post_type}", array($this, 'register_bulk_update'), 10, 3);
        add_filter("handle_bulk_actions-edit-{$this->post_type}", array($this, 'handle_bulk_update'), 10, 3);

        // add extra column in admin
        add_filter("manage_{$this->post_type}_posts_columns", array($this, 'manage_admin_columns'), 10, 3);
        add_filter("manage_{$this->post_type}_posts_custom_column", array($this, 'manage_admin_column'), 10, 3);

        // Taxonomies action
        foreach ($this->index_settings['taxonomies'] as $key => $taxonomy) {
            add_action("edited_{$taxonomy}", array($this, 'update_posts'), 10, 2);
            add_action("delete_{$taxonomy}", array($this, 'update_posts'), 10, 2);
        }
    }

    public function get_post_type()
    {
        return $this->post_type;
    }

    public function save_post($post_ID, $post)
    {
        $this->log->info('Updating ? ' . print_r($post, true));

        if (wp_is_post_autosave($post)) {
            return;
        }

        if ($this->get_post_type() !== $post->post_type) {
            return;
        }

        if ('publish' !== $post->post_status) {
            $this->log->info('Removing record : '.$post_ID);
            $this->algolia_index($post_ID)->delete($post_ID, $post);

            return;
        }

        // should push to index ?
        if (false === $this->show_in_index($post_ID)) {
            $this->log->info('Removing record : '.$post_ID);
            $this->algolia_index($post_ID)->delete($post_ID, $post);

            return;
        }

        // pass all conditions, then save
        $this->log->info('Saving record : '.$post_ID);

        $this->algolia_index($post_ID)->save($post_ID, $post);
    }

    public function delete_post($postID, $post)
    {
        $this->algolia_index($post_ID)->delete($postID, $post);
    }

    public function register_bulk_update($bulk_actions)
    {
        $bulk_actions['wpalgolia_index_update'] = __('Send to Algolia index', 'wp_algolia');
        $bulk_actions['wpalgolia_index_delete'] = __('Remove from Algolia index', 'wp_algolia');

        return $bulk_actions;
    }

    public function handle_bulk_update($redirect_to, $doaction, $post_ids)
    {
        // print_r($post_ids);
        // echo $doaction;
        foreach ($post_ids as $post_ID) {
            $post = get_post($post_ID);
            if ($post) {
                switch ($doaction) {
                    case 'wpalgolia_index_update':
                        // $this->algolia_index($post_ID)->save($post_ID, $post);
                        if (true === $this->show_in_index($post_ID)) {
                            $this->algolia_index($post->ID)->save($post->ID, $post);
                        }
                        break;

                    case 'wpalgolia_index_delete':
                        $this->algolia_index($post_ID)->delete($post_ID);
                        break;
                }
            }
        }

        return $redirect_to;
    }

    public function manage_admin_columns($columns)
    {
        $columns['in_index'] = __('Algolia Index', 'wp_algolia');

        return $columns;
    }

    public function manage_admin_column($column, $post_ID)
    {
        if ('in_index' === $column) {
            $in_index = $this->algolia_index($post_ID)->record_exist($post_ID);
            echo $in_index ? '<span class="dashicons dashicons-yes-alt" style="color: #5468ff;"></span>' : '';
        }
    }

    public function update_posts()
    {
        $posts = get_posts(array(
            'post_type'   => $this->get_post_type(),
            'numberposts' => -1
        ));

        // update each posts found from current post type
        foreach ($posts as $key => $post) {
            $this->algolia_index($post->ID)->save($post->ID, $post);
        }
    }

    /**
     * Check if post has a bool 'hidden_flag_field' returning true
     * Any other value should prevent from sending object to Algolia.
     *
     * @param [type] $post_ID
     *
     * @return bool
     */
    public function show_in_index($post_ID)
    {
        // return ACF field value, defaults to true
        if (\function_exists('get_field')) {
            $value = get_field($this->index_settings['hidden_flag_field'], $post_ID);
            return $value ? $value : true;
        } else {
            return true;
        }
    }

    private function set_index_name($post_ID)
    {
        if (\function_exists('pll_get_post_language')) {
            $post_locale = pll_get_post_language($post_ID);
            $this->locale = $post_locale ? $post_locale : pll_default_language('slug');
        }

        return implode('_', array($this->index_name_base, $this->locale));
    }

    private function algolia_index($post_ID)
    {
        return new \WpAlgolia\AlgoliaIndex($this->set_index_name($post_ID), $this->algolia_client, $this->index_settings, $this->log, $this);
    }
}
