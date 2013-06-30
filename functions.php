<?php

//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('error_log', '/path/to/my/php.log');
ini_set('log_errors', 'On');      // log to file (yes)
ini_set('display_errors', 'Off'); // log to screen (no)

require_once( 'deps/bk1-wp-utils/bk1-wp-utils.php' );
//require_once( 'deps/SEOstats/src/seostats.php' );
require_once( 'deps/wp-less/wp-less.php' );

bk1_debug::state_set('off');
bk1_debug::print_always_set('off');

// Check how many categorized resources there are for a source on resource save. If there are zero, the source is labeled as not pertinent and viceversa
add_action('pods_api_post_edit_pod_item_resources', function ($pieces, $is_new, $id){

	$source_id	= pods('resources', $id)->find()->field('source.id');

	$source		= pods('sources', $source_id);

	$total 		= pods('resources')->find(array('where' => array('status' => 2, 'source.id' => $source_id)))->total_found();

	bk1_debug::log('Auto labeling of sources based on relative resources status');

	if ($total > 0):
		$source->save('is_pertinent', true);
	else:
		$source->save('is_pertinent', false);
	endif;

}, 10, 3);


// Redirect user to wp-admin
/*
add_action( 'init', function () {
	add_action( 'template_redirect', function(){
		if(!is_admin()) wp_redirect();
	}, 100);
}, 100 );
*/

add_filter( 'login_redirect', function($redirect_to, $request, $user){
	//return admin_url().'index.php';
}, 10, 3);

/* Css and javascript includes */

add_action( 'admin_init', function() {
	global $pagenow;

	//if (in_array($pagenow, array('index.php', 'admin.php'))){
		wp_enqueue_style( 'opgb-admin-style', get_stylesheet_directory_uri().'/style/admin-style.less' );
		wp_enqueue_style( 'font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' );
		wp_enqueue_script('opbg_admin', get_stylesheet_directory_uri().'/js/admin.js', array('jquery'), false, true);
	//}
});


/* Some aspect customizations */

// Clean the Dashboard
function opbg_dashboard_setup()
{
    // Globalize the metaboxes array, this holds all the widgets for wp-admin
    global $wp_meta_boxes;

	unset($wp_meta_boxes['dashboard']);

	wp_add_dashboard_widget('dashboard_summary', 'Summary', function(){

	$resources = (object)opbg_get_resource_summary();

	?>
	<div class="bootstrap-wpadmin">
		<section class="fetch row-fluid">
			<div class="fetch-button span6">
				<button data-loading-text="Loading" data-nonce="<?php echo wp_create_nonce('resource_fetch_nonce') ?>" id="resources-fetch" class="btn btn-primary btn-block">Fetch new Resources</i></button>
			</div>
			<div class="fetched-results span6 closable-message-wrapper">Push Fetch to scan the feeds for new resources!</div>
		</section>
		<hr>
		<section class="status row-fluid">
			<div class="status-table span6">
				<div class="row-fluid">
					<div class="grid-dummy-first span12"></div>
					<div class="status-header span6">Status:</div>
					<div class="status-view-toggle span6">
						<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_print_status_values">
							<button data-toggle-option="#" class="btn btn-mini active">#</button>
							<button data-toggle-option="%" class="btn btn-mini">%</button>
						</div>
					</div>
					<div class="separator span12"></div>
					<div class="status-new key span6">New:</div><div class="status-new value span6" data-status-value="<?php echo $resources->new ?>"></div>
					<div class="status-categorized key span6">Categorized:</div><div class="status-categorized value span6" data-status-value="<?php echo $resources->categorized ?>"></div>
					<div class="status-excluded key span6">Excluded:</div><div class="status-excluded value span6" data-status-value="<?php echo $resources->excluded ?>"></div>
					<div class="separator span12"></div>
					<div class="status-total key span6">Total:</div><div class="status-total value span6" data-status-value="<?php echo $resources->total ?>"></div>
				</div>
			</div>
			<div class="status-graph span6">

			</div>
		</section>
	</div>
	<?php });
}

add_action('wp_dashboard_setup', 'opbg_dashboard_setup', 100 );

function opbg_admin_logo()
{
    echo '<style type="text/css">#header-logo { background-image: url('.get_stylesheet_directory_uri().'/images/opbg_alpha.png) !important; }</style>';
}
add_action('admin_head', 'opbg_admin_logo');

function opbg_login_logo() { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo get_stylesheet_directory_uri().'/style/login.css' ?>" media="all" />
<?php }
add_action( 'login_enqueue_scripts', 'opbg_login_logo' );

function opbg_login_logo_url() {
    return wp_login_url( admin_url() );
}
add_filter( 'login_headerurl', 'opbg_login_logo_url' );

function opbg_login_logo_url_title() {
    return '';
}
add_filter( 'login_headertitle', 'opbg_login_logo_url_title' );

function opbg_sanitize_host_url($url){

	$url_parsed = parse_url($url);

	bk1_debug::log('getting host url');
	bk1_debug::log($url_parsed);

	return @isset($url_parsed['host']) ? $url_parsed['host'] : $url_parsed['path'];
}

// Get a summary of resurces for every topic
function opbg_get_resource_summary($sorted_by = false ){

	$resources 	= pods('resources');

	$response 	= array();

	if ($sorted_by != false):
		$taxonomy = pods($sorted_by, array('limit' => -1));

		$taxonomy_field = $sorted_by.'.id';

		if ($taxonomy->total() > 0):
			while($taxonomy->fetch()):
				$stats = array();

				$stats['total'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id()) ) )->total_found();

				$stats['new'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 1) ) )->total_found();

				$stats['categorized'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 2) ) )->total_found();

				$stats['excluded'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 0) ) )->total_found();

				$response[$taxonomy->field('name')] = $stats;

			endwhile;
		endif;
	else:
		$response['total'] = $resources->find()->total_found();

		$response['new'] = $resources->find(array('where' => array('status' => 1) ) )->total_found();

		$response['categorized'] = $resources->find(array('where' => array('status' => 2) ) )->total_found();

		$response['excluded'] = $resources->find(array('where' => array('status' => 0) ) )->total_found();
	endif;

	return $response;
}

/* Checking if resource and source are already existing */
function opbg_is_resource_existing($resource_url, $topic_id, $feed_id){
	$sources	= pods('sources');

	$resources	= pods('resources');

	$host		= opbg_sanitize_host_url($resource_url);

	$sources->find(array('limit' => 1, 'where' => array('t.url' => $host) ) )->fetch();

	// If source already exist
	if ($sources->exists()):

		bk1_debug::log('source already exists');
		//bk1_debug::log($sources->row());

		// Check if resource exist
		$resources->find(array('limit' => 1, 'where' => array('t.url' => $resource_url) ) )->fetch();

		// If resource already exist, add topic and feed to it and exit
		if ($resources->exists()):

			$recatd = false;

			bk1_debug::log('resource already exists');
			//bk1_debug::log($resources->field('feeds.id'));

			//bk1_debug::log($topic_id);
			//bk1_debug::log($resources->field('topics.id'));
			$resource_topics = $resources->field('topics.id');
			if (is_array($resource_topics)){
				if (!in_array($topic_id, $resource_topics)){
					bk1_debug::log('saving resource under another topic');
					$resources->add_to('topics', $topic_id);
					$recatd = true;
				}
			}

			//bk1_debug::log($resources->field('feeds.id'));

			$resource_feeds = $resources->field('feeds.id');
			if (is_array($resource_feeds)){
				if (!in_array($feed_id, $resource_feeds)){
					bk1_debug::log('saving resource under another feed');
					$resources->add_to('feeds', $feed_id);
					$recatd = true;
				}
			}


			$source_topics = $sources->field('topics.id');
			if (is_array($source_topics)){
				if (!in_array($topic_id, $source_topics)){
					bk1_debug::log('saving source under another topic');
					$sources->add_to('topics', $topic_id);
					$recatd = true;
				}
			}

			return $recatd;
		endif;

		bk1_debug::log('resource is new');

		// Returns id of an already existing source
		$source_id = $sources->id();

	else:
		bk1_debug::log('source doesn\'t exist, creating a new one');
		// If source doesn't exist, create a new one
		$source_id = $sources->add(array('url' => $host, 'topics' => $topic_id, 'is_pertinent' => 0) );
		bk1_debug::log('source id: '.$source_id);
		//bk1_debug::log(pods('sources')->fetch($source_id));
	endif;

	return $source_id;
}

function opbg_fetch_new_resources(){

	$time_start = time();

	set_time_limit(0);

	bk1_debug::log('ajax called');

	if ( !wp_verify_nonce( $_REQUEST['nonce'], "resource_fetch_nonce")) {
      exit("No naughty business please");
	}

	bk1_debug::log('nonce verified');

	/* Setup */
	$entries_per_page	= 1000;

	$url_query			= '?'.http_build_query(array('n' => $entries_per_page));

	$topics 			= pods('topics', array('limit' => -1));

	$sources 			= pods('sources');

	$resources 			= pods('resources');

	$new_results 		= 0;

	$new_resources		= 0;

	$duplicated			= 0;

	$recatd				= 0;

	/* Topics Loop */
	if ($topics->total() > 0):
		while ($topics->fetch()):
			//bk1_debug::log('topic: '.$topics->field('name'));
			$feeds = $topics->field('feeds');

			$topic_id = $topics->id();

			if (is_array($feeds)):

				/* Feeds Loop */
				foreach($feeds as $feed):

					$feed 			= (object)$feed;

					bk1_debug::log('feed: '.$feed->query);

					$feed_id 		= $feed->id;

					$feed_content   = file_get_contents($feed->url.$url_query);

					$xml			= simplexml_load_string($feed_content);

					if ($xml):

						bk1_debug::log('entered xml');

						/* Entries Loop */
						foreach($xml->entry as $entry):

							bk1_debug::log('processing single entry');
							//bk1_debug::log($entry);

							/* Exit foreach if entry older than last check */

							$entry_pub_time = date_create($entry->published);

							if ($entry_pub_time <= date_create($feed->last_check)):
								bk1_debug::log('entry too old, breaking out of the feed');
								break;
							endif;

							/* Fetching monodimensional data from entry */

							$entry_data = array();

							$entry_url = array();

							// If no url is present go to next entry
							if (!preg_match('/&q=(.*)&ct/', $entry->link->attributes()->href, $entry_url)){
								bk1_debug::log('entry doesn\'t have normal url');
								continue;
							}

							$resources_url = urldecode($entry_url[1]);

							$entry_data['pub_time'] = $entry_pub_time->format('Y-m-d H:i:s');

							$entry_data['url'] = urldecode($entry_url[1]);

							$entry_data['title'] = (string)$entry->title;

							$entry_data['feeds'] = (int)$feed_id;

							$entry_data['topics'] = (int)$topic_id;

							$entry_data['status'] = 1;

							$source_id = opbg_is_resource_existing($entry_data['url'], $topic_id, $feed_id);

							// Is duplicate, skip entry
							if (!is_numeric($source_id)):
								bk1_debug::log('Duplicated entry, not saving!');
								$duplicated++;
								if($source_id === true){
									$recatd++;
								}
								continue;
							endif;

							/* Save new entry */

							$entry_data['source'] = $source_id;

							bk1_debug::log('saving the resource');
							bk1_debug::log($entry_data);
							$resources->add($entry_data);

							$last_update = $entry_data['pub_time'];

							$new_resources++;
						endforeach;

						$last_check = date_create($xml->updated)->format('Y-m-d H:i:s');

						bk1_debug::log('upgrading feed last check timestamp: '.$last_check);
						pods('feeds', $feed_id)->save('last_check', $last_check);
					else:
						bk1_debug::log('We got problems with the xml');
					endif;
				endforeach;
			endif;
		endwhile;

		header( "Content-Type: application/json" );

		$total_time = time() - $time_start;

		$total_time = (object)array('s' => $total_time % 60, 'm' => (int)($total_time / 60) % 60, 'h' => (int)($total_time / 3600));

		$total_time = ($total_time->h > 0 ? $total_time->h.'h ': '').($total_time->m > 0 ? $total_time->m.'m ': '').$total_time->s.'s';

		$response = array('success' => true, 'new_results' => $new_resources, 'summary' => opbg_get_resource_summary(), 'duplicated' => $duplicated, 'recatd' => $recatd, 'duration' => $total_time);

		echo json_encode($response);

		bk1_debug::log_print();

		die();
	endif;
}

add_action('wp_ajax_fetch_new_resources', 'opbg_fetch_new_resources');


?>
