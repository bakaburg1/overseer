<?php

require_once( 'prometheus/prometheus.php' );
require_once( 'bk1-wp-utils/bk1-wp-utils.php');

function opbg_fetch_new_resources(){
	$new_resources = 0;

	$feed_xml = false;

	$entries_per_page = 1000;

	$topics = pods('topics', array('limit' => -1));

	if ($topics->total() > 0):
		while ($topics->fetch()):
			$feed_urls = $topics->field('feeds.url');
			$feed_urls = array_map('trim', $feed_urls);

			foreach($feed_urls as $feed_url):

				$not_last_page = true;

				$url_query = '?'.http_build_query(array('n' => $entries_per_page));

				while($not_last_page):

					$feed   = file_get_contents($feed_url.$url_query);

					$xml	= simplexml_load_string($feed);

					if ($xml):
						
					endif;
				endwhile;
			endforeach;
		endwhile;
	endif;
}


?>
