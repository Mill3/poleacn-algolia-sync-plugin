<?php

/*
 * This file is part of WpAlgolia library.
 * (c) Raymond Rutjes for Algolia <raymond.rutjes@algolia.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WpAlgolia;

use WP_CLI;
use WP_CLI_Command;

class Commands extends WP_CLI_Command
{
    /**
     * @var Main
     */
    private $instance;

    /**
     * @param InMemoryIndexRepository $indexRepository
     */
    public function __construct(Main $instance)
    {
        $this->instance = $instance;
    }

    private function get_post_type_instance($indexName) {
        try {
            return $this->instance->registered_post_types[$indexName];
        } catch (\Throwable $th) {
            return null;
        }
    }


     /**
     * Push all records to Algolia for a given index.
     *
     * ## OPTIONS
     *
     * <indexName>
     * : The key of the index.
     *
     *
     * ## EXAMPLES
     *
     *     wp algolia settings articles
     *
     * @when before_wp_load
     *
     * @param mixed $args
     * @param mixed $assoc_args
     */
    public function settings($args, $assoc_args) {
        list($indexName) = $args;

        // get registered post type
        $indexInstance = $this->get_post_type_instance($indexName);

        if( !$indexInstance ) {
            WP_CLI::error(sprintf("Index for post type '%s' is not a registered index.", $indexName));
            return;
        }

        // run reindex
        $indexInstance->cli_set_settings();
    }

    /**
     * List all available index.
     *
     *
     * ## EXAMPLES
     *
     *     wp algolia list
     *
     * @when before_wp_load
     *
     */

    public function list() {
        foreach ($this->instance->registered_post_types as $key => $registered_post_type) {
            WP_CLI::line($registered_post_type->get_post_type());
        }
    }

    /**
     * Push all records to Algolia for a given index.
     *
     * ## OPTIONS
     *
     * <indexName>
     * : The key of the index.
     *
     * [--clear]
     * : Clear all existing records prior to pushing the records.
     *
     * [--batch=<batch>]
     * : Number of items to push to Algolia at the same time.
     * ---
     * default: 1000
     * ---
     *
     *
     * ## EXAMPLES
     *
     *     wp algolia re-index articles
     *
     * @when before_wp_load
     *
     * @param mixed $args
     * @param mixed $assoc_args
     */
    public function reIndex($args, $assoc_args)
    {
        list($indexName) = $args;

        // get registered post type
        $indexInstance = $this->get_post_type_instance($indexName);

        if( !$indexInstance ) {
            WP_CLI::error(sprintf("Index for post type '%s' is not a registered index.", $indexName));
            return;
        }

        // run reindex
        $indexInstance->cli_reindex();

        // $perPage = (int) $assoc_args['batch'];
        // if ($perPage <= 0) {
        //     throw new \InvalidArgumentException('The "--batch" option should be greater than 0.');
        // }
        // $clear = (bool) $assoc_args['clear'];
        // $index = $this->indexRepository->get($indexName);
        // if ($clear) {
        //     WP_CLI::line(sprintf(__('About to clear index %s...', 'algolia'), $index->getName()));
        //     $index->clear();
        //     WP_CLI::success(sprintf(__('Correctly cleared index "%s".', 'algolia'), $index->getName()));
        // }
        // WP_CLI::line(sprintf(__('About to push the settings for index %s...', 'algolia'), $index->getName()));
        // $index->pushSettings();
        // WP_CLI::success(sprintf(__('Correctly pushed settings to the index "%s".', 'algolia'), $index->getName()));

        // WP_CLI::line(__('About to push all records to Algolia. Please be patient...', 'algolia'));

        // $start = microtime(true);

        // $totalPages = $index->getRecordsProvider()->getTotalPagesCount($perPage);
        // $progress = WP_CLI\Utils\make_progress_bar(__('Pushing records to Algolia', 'algolia'), $totalPages);

        // $totalRecordsCount = $index->reIndex(false, $perPage, function () use ($progress) {
        //     $progress->tick();
        // });

        // $progress->finish();

        // $elapsed = microtime(true) - $start;

        // WP_CLI::success(sprintf(__('%d records pushed to Algolia in %d seconds!', 'algolia'), $totalRecordsCount, $elapsed));
    }

    /**
     * Push the settings for an index to Algolia.
     *
     * ## OPTIONS
     *
     * <indexName>
     * : The key of the index.
     *
     *
     * ## EXAMPLES
     *
     *     wp algolia pushSettings articles
     *
     * @when before_wp_load
     *
     * @param mixed $args
     * @param mixed $assoc_args
     */
    public function pushSettings($args, $assoc_args)
    {
        list($indexName) = $args;
        // $index = $this->indexRepository->get($indexName);
        // WP_CLI::line(sprintf(__('About to push the settings for index %s...', 'algolia'), $index->getName()));
        // $index->pushSettings();
        // WP_CLI::success(sprintf(__('Correctly pushed settings to the index "%s".', 'algolia'), $index->getName()));
    }
}