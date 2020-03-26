<?php

/**
 * This file is part of WpAlgolia plugin.
 * (c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
 * @version 0.0.7
 */

namespace WpAlgolia\Register;

use Carbon\Carbon;
use WpAlgolia\RegisterAbstract as WpAlgoliaRegisterAbstract;
use WpAlgolia\RegisterInterface as WpAlgoliaRegisterInterface;

class Events extends WpAlgoliaRegisterAbstract implements WpAlgoliaRegisterInterface
{
    public $searchable_fields = array('post_title');

    public $acf_fields = array('date', 'date_end', 'time_start', 'time_end', 'location', 'address', 'link');

    public $taxonomies = array('regions', 'event_types');

    public function __construct($post_type, $index_name, $algolia_client)
    {
        $index_config = array(
            'acf_fields'        => $this->acf_fields,
            'taxonomies'        => $this->taxonomies,
            'post_type'         => $post_type,
            'hidden_flag_field' => 'search_hidden',
            'config'            => array(
                'searchableAttributes'  => $this->searchableAttributes(),
                'customRanking'         => array('asc(timestamp)'),
                'attributesForFaceting' => array(),
                'queryLanguages'        => array('fr', 'en'),
            ),
            array(
               'forwardToReplicas' => true,
            ),
        );

        parent::__construct($post_type, $index_name, $algolia_client, $index_config);
    }

    public function searchableAttributes()
    {
        return array_merge($this->searchable_fields, $this->acf_fields, $this->taxonomies);
    }

    // implement any special data handling for post type here
    public function extraFields($data, $postID, $log) {

      // set locale from post
      $locale = pll_get_post_language($postID);

      // get date
      $date = get_field('date', $postID, false);

      // get end date
      $date_end = get_field('date_end', $postID, false);

      // parse dates with Carbon lib
      $parsed_date = Carbon::parse($date);
      $parsed_date_end = $date_end ? Carbon::parse($date_end) : null;

      // send day, month and year as seperate field value to index
      $data['day'] = $parsed_date->locale($locale)->isoFormat('D');
      $data['day_end'] = $parsed_date_end ? $parsed_date_end->locale($locale)->isoFormat('D') : null;
      $data['month'] = ucfirst($parsed_date->locale($locale)->isoFormat('MMMM'));
      $data['month_end'] = $parsed_date_end ? ucfirst($parsed_date->locale($locale)->isoFormat('MMMM')) : null;
      $data['year'] = $parsed_date->locale($locale)->isoFormat('YYYY');
      // convert php timestamp from epoch to milliseconds
      $data['timestamp'] = $parsed_date->getTimestamp() * 1000;

      // set permalink as formatted url value
      $coordinates = get_field('coordinates', $postID);
      if ($coordinates) {
          $data['lat'] = $coordinates['lat'];
          $data['lng'] = $coordinates['lng'];
          $data['city'] = $coordinates['city'];
          $data['state'] = $coordinates['state'];
          $data['country'] = $coordinates['country'];
          // $data['post_code'] = $coordinates['post_code'];
      }

      // set permalink as formatted url value
      $link_to_permalink = get_field('link_to_permalink', $postID);
      if ($link_to_permalink) {
          $permalink = get_permalink($postID);
          $data['formated_url'] = "href='{$permalink}'";
      }

      return $data;
    }

}
