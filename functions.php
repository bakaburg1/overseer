<?php

//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('error_log', '/path/to/my/php.log');
//ini_set('log_errors', 'On');      // log to file (yes)
ini_set('display_errors', 'Off'); // log to screen (no)

require_once( 'prometheus/prometheus.php' );
require_once( 'bk1-wp-utils/bk1-wp-utils.php');

add_filter('pods_api_pre_save_pod_item_sources', 'opbg_sanitize_host_url', 10, 3);

function opbg_sanitize_host_url($pieces, $is_new, $id){

	if (!array_key_exists('value', $pieces['fields']['url'])){
		return  $pieces;
	}

	$url = $pieces['fields']['url']['value'];

	$url_parsed = parse_url($url);

	$pieces['fields']['url']['value'] = $url_parsed['host'];

	//bk1_debug($url_parsed['host']);

	return  $pieces;
}

function opbg_fetch_new_resources(){

	/* Setup */
	$entries_per_page	= 1000;

	$url_query			= '?'.http_build_query(array('n' => $entries_per_page));

	$topics 			= pods('topics', array('limit' => -1));

	$sources 			= pods('sources');

	$resources 			= pods('prove');

	$new_results 		= array();

	/* Topics Loop */
	if ($topics->total() > 0):
		while ($topics->fetch()):
			$feeds = $topics->field('feeds');

			$topic_id = $topics->id();

			$new_results[$topic_id] = 0;

			/* Feeds Loop */
			foreach($feeds as $feed):

				$feed 			= (object)$feed;

				$feed_id 		= $feed->id;

				$feed_content   = file_get_contents($feed->url.$url_query);

				$xml			= simplexml_load_string($feed_content);

				if ($xml):

					/* Entries Loop */
					foreach($xml->entry as $entry):

						/* Exit foreach if entry older than last check */

						$entry_pub_time = date_create($entry->published);

						if ($entry_pub_time < date_create($feed->last_check)):
							break;
						endif;

						/* Fetching monodimensional data from entry */

						$entry_data = array();

						$entry_url = array();

						// If no url is present go to next entry
						if (!preg_match('/&q=(.*)&ct/', $entry->link->attributes()->href, $entry_url)){
							continue;
						}

						$entry_data['pub_time'] = $entry_pub_time->format('Y-m-d H:i:s');

						$entry_data['url'] = urldecode($entry_url[1]);

						$entry_data['title'] = $entry->title;

						$entry_data['feeds'] = $feed_id;

						$entry_data['topics'] = $topic_id;

						/* Checking if resource and source are already existing */
						$parsed_url = parse_url($entry_data['url']);

						$sources->find(array('limit' => 1, 'where' => array('t.url' => $parsed_url['host'])));

						// If source already exist, check if resource exist
						if ($sources->total() > 0):
							$resources->find(array('limit' => 1, 'where' => array('t.resource_url' => $entry_data['url'])));

							$source_id = $sources->id();

							// If resource already exist, check if it had this topic already. If not add it
							if ($resources->total() > 0):

								$resource_topics = $resources->field('topics.id') ? $resources->field('topics.id') : array();

								if (!in_array($topic_id, $resource_topics)):
									$resource_topics[] = $topic_id;
									$resources->save('topic', $resource_topics);
								endif;

								$resource_feeds = $resources->field('feeds.id') ? $resources->field('feeds.id') : array();

								if (!in_array($feed_id, $resource_feeds)):
									$resource_feeds[] = $feed_id;
									$resources->save('feeds', $resource_feeds);
								endif;

								continue;
							endif;
						else:
							// If source doesn't exist, create a new one
							$source_id = $sources->add(array('url' => $parsed_url['host']));
						endif;

						/* Save new entry */

						$entry_data['sources'] = $source_id;

						$resources->add($entry_data);

						$last_update = $entry_data['pub_time'];

						$new_results[$topic_id]++;
					endforeach;

					pods('feeds', $feed_id)->save('last_check', $last_update);
				endif;
			endforeach;
		endwhile;

		$new_results['total'] = 0;

		foreach ($new_results as $topic_results):
			$new_results['total_new'] += $topic_results;
		endforeach;

		header( "Content-Type: application/json" );

		echo json_encode($new_results);

		exit;
	endif;
}


?>
