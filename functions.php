<?php

//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('error_log', '/path/to/my/php.log');
//ini_set('log_errors', 'On');      // log to file (yes)
ini_set('display_errors', 'Off'); // log to screen (no)

require_once( 'prometheus/prometheus.php' );
require_once( 'bk1-wp-utils/bk1-wp-utils.php');

function opbg_sanitize_host_url($url){

	$url_parsed = parse_url($url);

	return $url_parsed['host'];
}

// Sanitize Sources pod items
add_filter('pods_api_pre_save_pod_item_sources', function($pieces, $is_new, $id){
	if (!array_key_exists('value', $pieces['fields']['url'])){
		return  $pieces;
	}
	$host = opbg_sanitize_host_url($pieces['fields']['url']['value']);

	$pieces['fields']['url']['value'] = $host;

	return  $pieces;

}, 10, 3);

// Check how many categorized resources there are for a source on resource save. If there are zero, the source is labeled as not pertinent and viceversa
add_action('pods_api_post_edit_pod_item_resources', function ($pieces, $is_new, $id){

	$source_id	= $pieces['fields']['source']['value'];

	$source		= pods('sources', $source_id);

	$total 		= pods('resources')->find(['where' => ['status' => 2, 'source.id' => $source_id]])->total();

	if ($total > 0):
		$source->save('is_pertinent', true);
	else:
		$source->save('is_pertinent', false);
	endif;

}, 10, 3);

// Redirect user to wp-admin
add_action( 'init', function () {
	add_action( 'template_redirect', function(){
		if(!is_admin()) wp_redirect(admin_url());
	}, 10);
}, 10 );

add_filter('pods_admin_ui_fields_topics', function($fields, $pod, $item){
	return $fields;
}, 10, 3);

/* Css and javascript includes */

add_action( 'admin_init', function() {
       wp_enqueue_style( 'opgb-admin-style', get_stylesheet_directory_uri().'/style/admin-style.less' );
	   wp_enqueue_style( 'font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' );
});


/* Some aspect customizations */

// Clean the Dashboard
function opbg_dashboard_setup()
{
    // Globalize the metaboxes array, this holds all the widgets for wp-admin
    global $wp_meta_boxes;

    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);

	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	//unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);

	//bk1_debug($wp_meta_boxes);

	wp_add_dashboard_widget('summary_dashboard_widget', 'Summary', function(){

		$resources = pods('resources');

		$total_resources = $resources->find()->total_found();

		$new_resources = $resources->find(['where' => ['status' => 1]])->total_found();

		$cat_resources = $resources->find(['where' => ['status' => 2]])->total_found();

		$excluded_resources = $resources->find(['where' => ['status' => 0]])->total_found();

	?>
	<div class="bootstrap-wpadmin">
		<section class="fetch row-fluid">
			<div class="fetch-button span6">
				<button id="resources-fetch" class="btn btn-primary">Fetch new Resources <i class="icon-refresh"></i></button>
			</div>
			<div class="fetch-results span6">
				<p></p>
			</div>
		</section>
		<hr>
		<section class="status row-fluid">
			<div class="status-table span6">
				<div class="row-fluid">
					<div class="status-header span12">Status:</div>
					<div class="separator span12"></div>
					<div class="status-new key span6">New:</div><div class="status-new value span6"><?php echo $new_resources ?></div>
					<div class="status-cat key span6">Categorized:</div><div class="status-cat value span6"><?php echo $cat_resources ?></div>
					<div class="status-excluded key span6">Excluded:</div><div class="status-excluded value span6"><?php echo $excluded_resources ?></div>
					<div class="separator span12"></div>
					<div class="status-total key span6">Total:</div><div class="status-total key span6"><?php echo $total_resources ?></div>
				</div>
			</div>
			<div class="status-graph span6">

			</div>
		</section>
	</div>
	<?php });
}

add_action('wp_dashboard_setup', 'opbg_dashboard_setup' );

function opbg_admin_logo()
{
    echo '<style type="text/css">#header-logo { background-image: url('.get_stylesheet_directory_uri().'/images/opbg_alpha.png) !important; }</style>';
}
add_action('admin_head', 'opbg_admin_logo');

function opbg_login_logo() { ?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(<?php echo get_stylesheet_directory_uri() ?>/images/opbg_alpha.png);
            padding-bottom: 30px;
			background-size: 149px 89px;
        }
    </style>
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

// Get a summary of resurces for every topic

function opbg_get_topic_summary(){
	$resources 	= pods('resources');

	$topics 	= pods('topics', array('limit' => -1));

	$response	= array();

	if ($topics->total() > 0):
		while($topics->fetch()):
			$stats = array();

			$stats['total'] = $resources->find(array('where' => array('topics.id' => $topics->id())))->total_found();

			$stats['new'] = $resources->find(array('where' => array('topics.id' => $topics->id(), 'status' => 1)))->total_found();

			$stats['categorized'] = $resources->find(array('where' => array('topics.id' => $topics->id(), 'status' => 2)))->total_found();

			$stats['not_pertinent'] = $resources->find(array('where' => array('topics.id' => $topics->id(), 'status' => false)))->total_found();
		endwhile;
	endif;
}

/* Checking if resource and source are already existing */
function opbg_is_resource_existing($resource_url, $topic_id, $feed_id){
	$sources	= pods('sources');

	$resources	= pods('resources');

	$host		= opbg_sanitize_host_url($resource_url);

	$sources->find(array('limit' => 1, 'where' => array('t.url' => $host)));

	// If source already exist
	if ($sources->total() > 0):
		// Check if resource exist
		$resources->find(array('limit' => 1, 'where' => array('t.resource_url' => $resource_url)));

		// If resource already exist, add topic and feed to it and exit
		if ($resources->total() > 0):

			$resources->add_to('topic', $topic_id);

			$resources->add_to('feeds', $feed_id);

			return false;
		endif;

		// Returns id of an already existing source
		$source_id = $sources->id();

	else:
		// If source doesn't exist, create a new one
		$source_id = $sources->add(['url' => $host, is_pertinent => 0]);
	endif;

	return $source_id;
}

function opbg_fetch_new_resources(){

	/* Setup */
	$entries_per_page	= 1000;

	$url_query			= '?'.http_build_query(array('n' => $entries_per_page));

	$topics 			= pods('topics', array('limit' => -1));

	$sources 			= pods('sources');

	$resources 			= pods('prove');

	$new_results 		= 0;

	/* Topics Loop */
	if ($topics->total() > 0):
		while ($topics->fetch()):
			$feeds = $topics->field('feeds');

			$topic_id = $topics->id();

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

						$resources_url = urldecode($entry_url[1]);

						$entry_data['pub_time'] = $entry_pub_time->format('Y-m-d H:i:s');

						$entry_data['url'] = urldecode($entry_url[1]);

						$entry_data['title'] = $entry->title;

						$entry_data['feeds'] = $feed_id;

						$entry_data['topics'] = $topic_id;

						$entry_data['status'] = 0;

						$source_id = opbg_is_resource_existing($entry_data['url'], $topic_id, $feed_id);

						if ($source_id === false):
							continue;
						endif;

						/* Save new entry */

						$entry_data['sources'] = $source_id;

						$resources->add($entry_data);

						$last_update = $entry_data['pub_time'];

						$new_results++;
					endforeach;

					pods('feeds', $feed_id)->save('last_check', $last_update);
				endif;
			endforeach;
		endwhile;

		header( "Content-Type: application/json" );

		echo json_encode($new_results);

		exit;
	endif;
}


?>
