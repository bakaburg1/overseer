<?php

/**** SETUP ****/

/*
ini_set('error_reporting', E_ALL & ~E_NOTICE);
define('WP_DEBUG', true);
if (WP_DEBUG) {
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
}
*/

ini_set('log_errors', 'off');      // log to file (yes)
ini_set('display_errors', 'off'); // log to screen (no)

require_once( 'deps/bk1-wp-utils/bk1-wp-utils.php' );
//require_once( 'deps/SEOstats/src/seostats.php' );
//require_once( 'deps/wp-less/wp-less.php' );

bk1_debug::state_set('off');
bk1_debug::print_always_set('off');

add_action( 'init', function(){
	delete_option('sampling_threshold');
	delete_option('are_new_resources');
});

/**** UTILITIES ****/

function opbg_sanitize_host_url($url){

	$url_parsed = parse_url($url);

	bk1_debug::log('getting host url');
	bk1_debug::log($url_parsed);

	return @isset($url_parsed['host']) ? $url_parsed['host'] : $url_parsed['path'];
}

function is_pods_detail_page($pods = ''){
	global $pagenow;

	$page_query = parse_url(pods_current_url());

	bk1_debug::log($page_query);

	$parsed_page_query = wp_parse_args($page_query['query']);

	return $pagenow === 'admin.php' AND strpos($parsed_page_query['page'], 'pods-manage-'.$pods) !== false AND @$parsed_page_query['action'] === 'edit';
}

function is_pods_list_page($pods = ''){
	global $pagenow;

	$page_query = parse_url(pods_current_url());

	$parsed_page_query = wp_parse_args($page_query['query']);

	return $pagenow === 'admin.php' AND strpos($parsed_page_query['page'], 'pods-manage-'.$pods) !== false AND @$parsed_page_query['action'] !== 'edit';
}

function get_current_admin_page_pod(){
	global $pagenow;

	$page_query = parse_url(pods_current_url());

	$parsed_page_query = wp_parse_args($page_query['query']);

	return str_replace('pods-manage-', '', $parsed_page_query['page']);
}

function opbg_log_database_status($action){
	$new_line = array(
		'action_performed'      => $action,
		'date'                  => date('r'),
		'fetching_status'       => get_option( 'resources_fetching_status', false),
		'sampling_status'      	=> get_option( 'resource_filtering_status', false),
		'sampling_threshold'	=> pods('opbg_database_settings')->field('sampling_threshold'),
		'sampled_in_resources'	=> get_option( 'sampled_in_resources', 0),
		'sampled_out_resources'	=> get_option( 'sampled_out_resources', 0),
		'arrived_ifttt_posts'   => get_option( 'arrived_ifttt_posts', 0),
		'resources_status'      => opbg_get_resource_summary()
	);

	if (get_option('opbg_database_log', false) !== false){
		$log = get_option('opbg_database_log');

		$log[] = $new_line;

		//bk1_debug::log($log);

		update_option( 'opbg_database_log', $log );
	}
	else {
		add_option( 'opbg_database_log', array($new_line) );
	}
}

function opbg_parse_page_content($url) {

	$error = true;
	$parser_api_url = 'http://ftr.fivefilters.org/makefulltextfeed.php?url='.urlencode($url);
	$i = 0;

	while ($error === true AND $i < 4) {
		$response = fetch_feed( $parser_api_url );

		if ( !is_wp_error( $response ) ) {
			$error = false;
		}
	}

	if ($error){
		bk1_debug::log('Fivefilters not reachable.');
		bk1_debug::log($response);
		//opbg_add_excluded_resource($item, 5);
		return false;
	}

	$content = $response->get_items()[0]->get_description();

	if (preg_match("/\[unable to retrieve full-text content\]/im", $content) > 0) return false;

	bk1_debug::log('Page main content fetched.');

	return $content;
}

function opbg_get_whole_page($url) {

	$error = true;
	$i = 0;

	while ($error === true AND $i < 4) {
		$response = wp_remote_post($url);

		if ( !is_wp_error( $response ) ) {
		   $error = false;
		}
	}

	if ($error){
		bk1_debug::log('Site not reacheable.');
		bk1_debug::log($response);
		//opbg_add_excluded_resource($item, 5);
		return false;
	}

	bk1_debug::log('Whole page fetched.');

	return wp_remote_retrieve_body($response);
}

function opbg_load_resource_body($item)
{
	$url = $item['url'];
	$response = false;

	//var_dump(preg_match_all("/forum|community|answer|consulti/i", $url));

	if (preg_match_all("/forum|community|answer|consulti/i", $url) === 0) $response = opbg_parse_page_content($url);
	else  $content = opbg_get_whole_page($url);

	if ($response === false) $content = opbg_get_whole_page($url);

	// Super Trim! ©
	if( $content !== false) $content = preg_replace("/^\s+|\s+$|(?<=\s)\s+/", "", $content);

	//bk1_debug::log($content);

	return $content;
}


function opbg_keywords_to_query($string)
{
	// Super Trim! ©
	$string = preg_replace("/^\s+|\s+$|(?<=\s)\s+/", "", $string);

	if (preg_replace("/\s*/", '', $string) == false) return false;

	bk1_debug::log($string);

	$pieces = preg_split("/,(?!\d+})\s+/", $string);

	$result = array();

	foreach ($pieces as $keyword) {

		$type = 'single';

		$pattern = array();

		$keyword = trim($keyword);

		if (preg_replace("/\s*/", '', $keyword) == false) continue;

		// Check if kywords are multiple and if are quoted or not
		if (preg_match_all("/\w+/", preg_replace("/[^\w\s]/m", '', $keyword)) > 1) {
			if (preg_match_all("/^\"|\"$/", $keyword)) $type = "quoted";
			else $type = "unordered";
		}
		$keyword = str_replace('"', '', $keyword);
		$keyword = preg_replace('/\(|\)/', '', $keyword);

		// Remove \* or \? that followed by other \? or \*, clean other symbols and remove multiple dashes
		$keyword = preg_replace("/(\?|\*)+(?=\?|\*)|[^-\w\d\s\?\*,{}]|(?<=-)-+/", "", $keyword);
		// Remove non words separating dashes
		$keyword = preg_replace("/(?<!\b)-+(?!\b)/", "", $keyword);
		// Super Trim! ©
		$keyword = preg_replace("/^\s+|\s+$|(?<=\s)\s+/", "", $keyword);

		$regex = $keyword;

		// Sostitute dashes with loose matching of \s or -
		$regex = preg_replace('/\b-\b/', '(?:\s|-)?', $keyword);

		// Identify modifiers and Substitute them
		$regex = preg_replace('/\*|\?(?!:)|(\{\d(?:,\d)?\})/', '[a-zA-Zàèéòù]$0', $regex);

		// if type is unordered, return array
		if ($type == "unordered"){
			$splitted = explode(' ', $regex);

			$term["pattern"] = array();

			foreach ($splitted as $word) {
				$term["pattern"][] = "/(?<=\b|\d)$word(?=\b|\d)/im";
			}
		}
		else $term["pattern"] = "/(?<=\b|\d)$regex(?=\b|\d)/im";

		$term["type"] = $type;

		$result[] = $term;
	}

	return $result;
}

function opbg_parse_text_with_query($text, $query){
	$query = opbg_keywords_to_query($query);

	if ($query == false) return false;

	$text = trim($text);
	$text = preg_replace("/\s\s+/", " ", $text);

	$results = array();

	foreach ($query as $term) {
		$buff = array();

		if ($term["type"] !== "unordered") {
			if (preg_match_all($term["pattern"], $text, $buff) > 0) $results = array_merge($results, $buff[0]);
		}
		elseif ($term["type"] === "unordered") {
			$buff2 = array();

			$are_all_present = false;

			foreach ($term["pattern"] as $pattern) {
				if (preg_match_all($pattern, $text, $buff2) > 0) {
					$buff = array_merge($buff, $buff2[0]);

					$are_all_present = true;
				}
				else {
					$are_all_present = false;
					break;
				}
			}

			if ($are_all_present === true) $results = array_merge($results, $buff);
		}

		//var_dump($results);
	}

	if (empty($results)) return false;

	$counted = array_count_values($results);

	arsort($counted);

	return $counted;
}

function opbg_array_to_textfield($keywords) {
	$result = '';

	foreach ($keywords as $keyword => $matches) {
		$result .= $keyword.': '.$matches."\n";
	}

	return $result;
}

function opbg_get_social_scores_of_url($url) {
	$response = wp_remote_get( 'http://sharedcount.appspot.com/?url='.rawurlencode($url) );

	$response = json_decode(wp_remote_retrieve_body( $response ));

	bk1_debug::log($response);

	$response = array(
		'Facebook' => $response->Facebook->total_count,
		'Twitter' => $response->Twitter,
		'Google+' => $response->GooglePlusOne,
		'LinkedIn' => $response->LinkedIn,
		'total' => $response->Facebook->total_count + $response->Twitter + $response->GooglePlusOne + $response->LinkedIn
	);

	return opbg_array_to_textfield($response);
}

/**** CSS AND JAVASCRIPTS ****/

add_action( 'admin_enqueue_scripts', function() {
	global $pagenow;
	$WPLessPlugin = WPLessPlugin::getInstance();

	bk1_debug::log('enqueuing');

	wp_enqueue_style('opgb-admin-style', get_stylesheet_directory_uri().'/style/admin-style.less' );
	wp_enqueue_style('font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css' );
	wp_enqueue_script('opbg_admin', get_stylesheet_directory_uri().'/js/admin.js', array('jquery'), false, true);

	if($pagenow === 'index.php'){
		bk1_debug::log('enqueuing admin_dashboard.js');
		wp_enqueue_script('admin_dashboard', get_stylesheet_directory_uri().'/js/admin_dashboard.js', array('opbg_admin'), false, true);
		wp_enqueue_script('moment', 'http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment.min.js', array('jquery'), false, true);
	}


	if ($pagenow === 'admin.php' AND is_pods_detail_page()){
		bk1_debug::log('enqueuing ScrollToFixed.js');
		wp_enqueue_script('ScrollToFixed', get_stylesheet_directory_uri().'/js/ScrollToFixed.js', array('jquery'), false, true);
		$pods_manage_page_type = 'detail';
	}

	if ($pagenow === 'admin.php' AND is_pods_list_page()){

		$pods_manage_page_type = 'list';

		bk1_debug::log(get_current_admin_page_pod());

		$current_pods_name = get_current_admin_page_pod();

		$current_pods = pods( $current_pods_name );

		$list_fields_manage = array_values($current_pods->pod_data['options']['ui_fields_manage']);

		$fields = array_keys($current_pods->fields);

		wp_localize_script( 'opbg_admin', 'pods_list_page_data', array(
			'pods_list_page_data_nonce' => wp_create_nonce( 'pods_list_page_data_nonce'),
			'list_fields_manage' => $list_fields_manage,
			'all_fields' => $fields,
			'current_pods' => $current_pods_name,
			'pods_manage_page_type' => $pods_manage_page_type
			)
		);

	}

	$WPLessPlugin->processStylesheets();

});

/**** ADMIN CUSTOMIZATION ****/

// Clean the Dashboard
function opbg_dashboard_setup()
{
	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	global $wp_meta_boxes;

	unset($wp_meta_boxes['dashboard']);

	require_once('includes/dashboard_summary.php');

	build_dashboard_summary();
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

add_action( 'admin_menu', function(){
	global $current_user;

	if (!in_array('administrator', $current_user->roles)){
		remove_menu_page( 'profile.php' );
	}
} );

/**** ACTIONS AND FILTERS ****/

// Redirect to admin on login
add_filter( 'login_redirect', function($redirect_to, $request, $user){
	return admin_url().'index.php';
}, 10, 3);

add_filter( 'heartbeat_received', function($response, $data){
	global $pagenow;

	// Make sure we only run our query if the proper key is present
	//bk1_debug::log('heartbeat_received');
	if(@$data['dashboard_heartbeat'] === 'upgrade_dashboard_summary' ) {

		$current_status = opbg_get_resource_summary(false, $data['dashboard_actual_period']);

		$difference = array_diff_assoc($data['dashboard_actual_status'], $current_status);

		bk1_debug::log('Computing data difference:');
		bk1_debug::log($difference);

		if ( !empty($difference) ) {

			bk1_debug::log('Dashboard is not upgraded!');

			$response['is_database_changed'] = true;
		}
	}

	bk1_debug::log('sending heartbeat response');

	return $response;

}, 10, 2 );

/**** AJAX ****/

add_action( 'wp_ajax_pods-quick-edit', function() {
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "pods_list_page_data_nonce")) {
		exit("No naughty business please");
	}

	bk1_debug::log($_REQUEST);

	$pods_item = pods($_REQUEST['pods_name'], $_REQUEST['pods_item_id']);

	$success = $pods_item->save($_REQUEST['field'], $_REQUEST['value']);

	$response = array();

	if($success != false){
		$response['success'] = true;

		// Bug: needed a way to reset pod and show the new value
		//$response['value'] = $pods_item->display($_REQUEST['field']);

		if ($_REQUEST['pods_name'] == 'resources'){
			$response['value'] = "Not Pertinent";
		}
		elseif ($_REQUEST['pods_name'] == 'sources'){
			$response['value'] = "*";
		}
	}
	else{
		$response['success'] = false;
	}

	//bk1_debug::log($response);

	header( "Content-Type: application/json" );

	echo json_encode($response);

	die();
});

// Controller for ajax interactions in dashboard
add_action( 'wp_ajax_dashboard_widget_control', function(){

	if ( !wp_verify_nonce( $_REQUEST['nonce'], "dashboard_widget_control_nonce")) {
	  exit("No naughty business please");
	}

	$success = false;

	//remote_resources_fetching_status
	if ($_REQUEST['button_id'] === 'remote-fetching-toggle'){

		if ( $_REQUEST['message'] === 'off'){
			$success = update_option( 'resources_fetching_status', false );

			$status = 'inactive';
		}
		elseif ( $_REQUEST['message'] === 'on'){
			$success = update_option( 'resources_fetching_status', true );

			bk1_debug::log($success);

			$status = 'active';
		}

		opbg_log_database_status('toggled fetching');
		update_option( 'arrived_ifttt_posts', 0);
	}

	elseif ($_REQUEST['button_id'] === 'sampling-filtering-toggle'){

		bk1_debug::log('sampling-filtering-toggle');

		if ( $_REQUEST['message'] === 'off'){
			$success = update_option( 'sampling_filtering_status', false );

			$status = 'inactive';

			bk1_debug::log('message off');
			bk1_debug::log($success);
		}
		elseif ( $_REQUEST['message'] === 'on'){
			$success = update_option( 'sampling_filtering_status', true );

			$status = 'active';

			bk1_debug::log('message on');
			bk1_debug::log($success);
		}

		opbg_log_database_status('toggled filtering');
		update_option('sampled_in_resources', 0);
		update_option('sampled_out_resources', 0);
	}

	elseif ($_REQUEST['button_id'] === 'alexa-filtering-toggle'){

		if ( $_REQUEST['message'] === 'off'){
			$success = update_option( 'alexa_filtering_status', false);

			$status = 'inactive';
		}
		elseif ( $_REQUEST['message'] === 'on'){
			$success = update_option( 'alexa_filtering_status', true);

			$status = 'active';
		}

		opbg_log_database_status('toggled filtering');
		update_option('alexa_in_resources', 0);
		update_option('alexa_out_resources', 0);
	}

	elseif ($_REQUEST['button_id'] === 'get-dashboard-summary') {
		bk1_debug::log('period change toggled');

		$status = opbg_get_resource_summary(false, $_REQUEST['message'] ? $_REQUEST['message'] : 'total');

		$success = true;
	}

	header( "Content-Type: application/json" );

	$response = array('success' => $success, 'status' => $status);

	//bk1_debug::log($response);

	echo json_encode($response);

	die();

});

/**** PODS HOOKS ****/

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

// If site/page is blacklisted, set all new resources with that url as non pertinent
add_action('pods_api_pre_edit_pod_item_sources', function ($pieces, $id){

	$source	= pods('sources', $id);


	if (!in_array('blacklisted', $pieces['fields_active'], true)){
		bk1_debug::log('blacklist not in pieces');
		return false;
	}

	$old_blacklist = $source->field('blacklisted');

	$new_blacklist = $pieces['fields']['blacklisted']['value'];

	/*if ($new_blacklist === $old_blacklist){
		bk1_debug::log('blacklist not changed');
		return false;
	}*/

	bk1_debug::log('New blacklist: '.$new_blacklist);

	$resource = pods('resources')->find(array("limit" => -1, 'where' => array('status = 1 AND source.id = '.$id)));

	if ($resource->total() === 0){
		bk1_debug::log('No uncategorized resources with this source');
		return false;
	}

	$new_blacklist = explode("\n", $new_blacklist);

	$host = opbg_sanitize_host_url($source->field('url'));

	while($resource->fetch()):

		if (in_array('*', $new_blacklist, true)){
			$resource->save('status', 0);
			bk1_debug::log('Whole site is blacklisted');
		}
		else {

			foreach ($new_blacklist as $path):
				$path = trim($path);

				if ($path[0] !== '/') $path = '/'.$path;

				if (substr($path, -1) === '/') $path = substr($path, 0, -1);

				bk1_debug::log('resource url: '. $resource->field('url'));
				bk1_debug::log('blacklist url: '. $host.$path);

				if (strpos($resource->field('url'), $host.$path) !== false){
					$resource->save('status', 0);
					bk1_debug::log('This resource is blacklisted');
				}
				else {
					bk1_debug::log('This resource is safe');
				}
			endforeach;
		}

	endwhile;

}, 10, 3);

/**** DATABASE FUNCTIONS ****/

// Get a summary of resurces for every topic
function opbg_get_resource_summary($sorted_by = false, $period = 'total'){

	$resources 	= pods('resources');
	$excluded 	= pods('excluded_resources');

	$response 	= array();

	bk1_debug::log('summary period: '.$period);

	if ($period !== "total"){
		$period = explode(' ', $period);

		$from = $period[0];
		$to = $period[1];
	}
	else $period = false;

	if ($sorted_by != false):
		$taxonomy = pods($sorted_by, array('limit' => -1));

		$taxonomy_field = $sorted_by.'.id';

		if ($taxonomy->total() > 0):
			while($taxonomy->fetch()):
				$stats = array();

				$stats['total'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id()) ) )->total_found();

				if ($stats['total'] > 0){

					$stats['new'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 1) ) )->total_found();

					$stats['categorized'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 2) ) )->total_found();

					$stats['not-pertinent'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 0) ) )->total_found();
				}
				else {
					$stats['new'] = $stats['categorized'] = $stats['excluded'] = $stats['total'];
				}
				$response[$taxonomy->field('name')] = $stats;

			endwhile;
		endif;
	elseif ($period !== false):
		bk1_debug::log('generating resource summary by range:');

		$date_query = "pub_time >= \"$from\" AND pub_time <= \"$to\"";

		bk1_debug::log($date_query);

		$response['total'] = $resources->find(array('where' => $date_query/*, 'expires' => 60*/) )->total_found();

		$response['new'] = $resources->find(array('where' => "status = 1 AND ".$date_query/*, 'expires' => 60*/) )->total_found();

		$response['categorized'] = $resources->find(array('where' => "status = 2 AND ".$date_query/*, 'expires' => 60*/) )->total_found();

		$response['not-pertinent'] = $resources->find(array('where' => "status = 0 AND ".$date_query/*, 'expires' => 60*/) )->total_found();

		$response['excluded'] = $excluded->find(array('where' => $date_query) )->total_found();

	elseif ($period === false):
		bk1_debug::log('generating resource summary total');
		$response['total'] = $resources->find()->total_found();

		$response['new'] = $resources->find(array('where' => "status = 1") )->total_found();

		$response['categorized'] = $resources->find(array('where' => "status = 2") )->total_found();

		$response['not-pertinent'] = $resources->find(array('where' => "status = 0") )->total_found();

		$response['excluded'] = $excluded->find()->total_found();
	endif;

	bk1_debug::log($response);

	return $response;
}

/* Checking if resource and source are already existing */
function opbg_is_resource_existing($item){
	$sources	= pods('sources');

	$resources	= pods('resources');

	$excluded	= pods('excluded_resources');

	$host		= opbg_sanitize_host_url($item['url']);

	$sources->find(array('limit' => 1, 'where' => array('url' => $host) ) )->fetch();

	$source 	= $sources;

	$alexa_rank = opbg_generate_alexa_score($host);

	bk1_debug::log("Alexa rank: ".$alexa_rank);

	if ($alexa_rank > pods('opbg_database_settings')->field('alexa_threshold') OR $alexa_rank === 0){
		opbg_add_excluded_resource($item, 1);
		return false; // The site above alexa threshold
	}

	if ($excluded->find(array('where' => array('url' => $item['url']) ) )->total_found() > 1){
		bk1_debug::log('Resource already excluded');
		return false;
	}

	// If source already exist
	if ($source->exists()):

		$source->save('alexa', $alexa_rank);

		bk1_debug::log('source already exists');
		//bk1_debug::log($sources->row());

		$blacklisted = trim($source->field('blacklisted'));

		bk1_debug::log('source blacklisted paths:');
		bk1_debug::log($blacklisted);

		if (!empty($blacklisted)):

			if ($blacklisted === '*'):
				bk1_debug::log('Whole site is blacklisted');
				opbg_add_excluded_resource($item, 6);
				return false; // The whole site is blacklisted
			else:

				$blacklisted = explode("\n", $blacklisted);

				bk1_debug::log($blacklisted);

				foreach ($blacklisted as $path) {
					$path = trim($path);

					if ($path[0] !== '/') $path = '/'.$path;

					if (substr($path, -1) === '/') $path = substr($path, 0, -1);

					if (strpos($item['url'], $host.$path) !== false){
						bk1_debug::log('This page is blacklisted');
						opbg_add_excluded_resource($item, 6);
						return false;
					}
				}

				bk1_debug::log('This page is not blacklisted');
			endif;
		endif;

		// Check if resource exist
		$resources->find(array('limit' => 1, 'where' => array('t.url' => $item['url']) ) )->fetch();
		//$same_url = $resources->exists();

		//$resources->find(array('limit' => 1, 'where' => array('t.title' => $item['title']) ) )->fetch();
		//$same_title = $resources->exists();
		// If resource already exist, add topic to it and exit
		if ($resources->exists()):

			bk1_debug::log('resource already exists');
			//bk1_debug::log($resources->field('feeds.id'));

			//bk1_debug::log($topic_id);
			bk1_debug::log($resources->field('topics.id'));
			$resource_topics = $resources->field('topics.id');
			if (is_array($resource_topics)){
				if (!in_array($item['topics'], $resource_topics)){
					$resources->add_to('topics', $item['topics']);
					bk1_debug::log('saving resource under another topic');
				}
				else{
					bk1_debug::log('topic already present');
				}
			}

			$source_topics = $source->field('topics.id');
			if (is_array($source_topics)){
				if (!in_array($item['topics'], $source_topics)){
					$source->add_to('topics', $resource_data['topics']);
					bk1_debug::log('saving source under another feed');
				}
			}
			return false;
		endif;

		bk1_debug::log('resource is new');

		// Returns id of an already existing source
		$source_id = $source->id();

	else:
		bk1_debug::log('source doesn\'t exist, creating a new one');
		// If source doesn't exist, create a new one
		update_option( 'saving_source', $host);
		$source_id = $source->add(array('url' => $host, 'topics' => $item['topics'], 'is_pertinent' => 0, 'alexa' => $alexa_rank) );
		update_option( 'saving_source', false);
		bk1_debug::log('source id: '.$source_id);
		//bk1_debug::log(pods('sources')->fetch($source_id));

	endif;

	return $source_id;
}

// Convert feed item to resource
function opbg_add_new_resource_from_feed_item($item){

	bk1_debug::log('converting the item '.$item['title']);

	/* Checking the item */

	// Random Sampling
	$is_filter_active = get_option( 'resource_filtering_status', false );
	if ($is_filter_active){
		$max = 10000;
		if (mt_rand(0, $max) >= pods('opbg_database_settings')->field('sampling_threshold')/100*$max ) {
			bk1_debug::log('resource sampled out');
			$filtered_out = get_option('filtered_out_resources', 0);
			update_option( 'filtered_out_resources', ++$filtered_out );
			opbg_add_excluded_resource($item, 0);
			return false;
		}
		else {
			$filtered_in = get_option('filtered_in_resources', 0);
			update_option( 'filtered_in_resources', ++$filtered_in );
		}
	}

	$item['body'] = opbg_load_resource_body($item);
	if ($item['body'] === false) return false;
	bk1_debug::log('loaded body');

	// Check if base keywords are present
	$item['keywords_matched'] = opbg_check_base_keywords($item);
	if ($item['keywords_matched'] === false) return false;
	bk1_debug::log('Checked base keys');

	// Check if topic specific keywords are present
	$topics_and_keywords = opbg_check_topics_keywords($item);
	if ($topics_and_keywords === false) return false;
	bk1_debug::log('Checked topic keys');

	$item['keywords_matched'] = array_merge($item['keywords_matched'], $topics_and_keywords['keywords']);

	//$item['keywords_matched'] = opbg_keywords_frequency_to_string($item['keywords_matched']);

	$item['keywords_matched'] = opbg_array_to_textfield($item['keywords_matched']);

	$item['topics'] = $topics_and_keywords['topics'];

	// Check the source for duplicated, alexa ranking, blacklisting
	$item['source'] = opbg_is_resource_existing($item);
	if ($item['source'] === false) return false;

	bk1_debug::log('Resource not already existing');

	$item['status'] = 1;
	$item['context'] = false;
	$item['is_correct'] = false;
	$item['type'] = false;
	$item['comments'] = false;

	bk1_debug::log('Resource parsed successfuly!');
	bk1_debug::log($item['url']);

	/* Save new entry */

	$resources = pods('resources');

	bk1_debug::log('saving resource');
	update_option( 'saving_resource', $item['url']);
	if ($resources->add($item)){
		bk1_debug::log('resource saved!');
		pods('opbg_database_settings')->save('last_feed_check', $item['pub_time']);
		//update_option('is_database_updated', true);
	} else {
		bk1_debug::log('resource saving failed!');
		wp_mail( get_option( 'admin_email' ), get_option( 'blogname' ).': There was a error saving the post into a resource', 'There was a error saving the post into a resource');
	}
	update_option( 'saving_resource', false);

	return true;
}

function opbg_add_excluded_resource($item, $reason)
{
	$excluded_resources = pods('excluded_resources');

	if (!(pods('excluded_resources')->find(array("where" => array("url" => $item['url'])))->total_found() > 0) ) {
		update_option( 'saving_excluded', $item['url']);
		$excluded_id = $excluded_resources->add(array(
				"title" => $item['title'],
				"pub_time" => $item['pub_time'],
				"url" => $item['url'],
				"reason" => $reason
			)
		);
		update_option( 'saving_excluded', false);
	}
	else {
		bk1_debug::log('excluded and duplicated');
	}

	pods('opbg_database_settings')->save('last_feeds_check', $item['pub_time']);

	bk1_debug::log('excluded url: '.$item['url'].' for reason '.$reason);
}

function opbg_fetch_feeds_items () {

	//return false;

	if (get_option( 'resources_fetching_status', false ) == false) {
		bk1_debug("Resource fetching off");
		return false;
	}

	$start = microtime(true);

	opbg_clean_incomplete_database_data();

	add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 1;' ));

	$feeds = pods('opbg_database_settings')->field('feed_urls');
	$last_check = strtotime(pods('opbg_database_settings')->field('last_feeds_check'));

	bk1_debug::log("last check: ".pods('opbg_database_settings')->field('last_feeds_check'));

	$feeds = trim($feeds);

	if ($feeds == false) {
		bk1_debug::log('no feeds url set');
		return false;
	}

	$feeds = explode('\n', $feeds);

	$parsed = array();

	foreach ($feeds as $feed) {
		$feed = fetch_feed( $feed );

		if ( is_wp_error( $feed ) ) {
			bk1_debug::log('feed was unreacheable.');
			return false;
		}

		$items = $feed->get_items();

		//bk1_debug::log(count($items));

		foreach ($items as $item) {

			//echo "item time: ".$item->get_date('Y-m-d H:i:s')." last check: ".$last_check." Delta: ".(strtotime($item->get_date('Y-m-d H:i:s')) - $last_check)."\n";

			if (strtotime($item->get_date('Y-m-d H:i:s')) >= $last_check) {

				$parsed[$item->get_link()] = array(
					"url" => $item->get_link(),
					"title" => $item->get_title(),
					"excerpt" => $item->get_description(),
					"pub_time" => $item->get_date('Y-m-d H:i:s')
				);
			}
		}
	}

	unset($feed);

	if (empty($parsed)) {
		bk1_debug::log('No new Items');
		bk1_debug::log('Items fetch execution time: '.(microtime(true) - $start));
		return false;
	}

	bk1_debug::log('Parsed: '.count($parsed));

	usort($parsed, function ($a, $b){
		$el1 = strtotime($a["pub_time"]);
		$el2 = strtotime($b["pub_time"]);

		if ($el1 == $el2) return 0;

		return ($el1 > $el2) ? 1 : -1;
	});

	$i = 1;

	foreach ($parsed as $item) {

		bk1_debug::log('Item #'.$i);
		echo 'Item #'.$i++."\n";

		//var_dump($item);

		opbg_add_new_resource_from_feed_item($item);
	}

	bk1_debug::log('Items fetch execution time: '.(microtime(true) - $start));
	echo 'Social scores execution time: '.(microtime(true) - $start)."\n\n";
}

function opbg_assign_social_score_to_items(){

	$start = microtime(true);

	$resources = pods('resources')->find(array('limit' => -1, "where" => "DATEDIFF(NOW(), pub_time) >= 20 AND social_scores IS NULL"));

	bk1_debug::log("Social scores to be assigned: ".$resources->total_found());
	echo "Social scores to be assigned: ".$resources->total_found()."\n\n";

	$i = 1;

	if($resources->total() > 0){
		while($resources->fetch()){
			$resources->save('social_scores', opbg_get_social_scores_of_url($resources->field('url')));

			bk1_debug::log('Item #'.$i);
			bk1_debug::log('Item url: '.$resources->field('url'));
			echo 'Item #'.$i++."\n";
			echo 'Item url: '.$resources->field('url')."\n";
		}
	}

	bk1_debug::log('Social scores execution time: '.(microtime(true) - $start));
	echo 'Social scores execution time: '.(microtime(true) - $start)."\n\n";
}

function opbg_assign_alexa_score($source) {

	if (isset($source) AND $source->exists()){

		$grank = opbg_generate_alexa_score($source->field('url'));

		return $source->save('alexa', $grank);
	}
	else {

		$sites = pods('sources')->find(array('limit' => -1, "where" => "alexa IS NULL"));

		while($sites->fetch()){

			$url = $sites->field('url');
			$grank = opbg_generate_alexa_score($url);
			$sites->save('alexa', $grank);
		}
	}
}

function opbg_generate_alexa_score($url = false){
	if ($url !== false){
		$xml = simplexml_load_file('http://data.alexa.com/data?cli=10&dat=snbamz&url='.$url);
		$grank = isset($xml->SD[1]->POPULARITY) ? (int)$xml->SD[1]->POPULARITY->attributes()->TEXT : 0;

		return $grank;
	}
	else {
		bk1_debug::log('No url to calculate alexa score.');
	}
}

function opbg_check_base_keywords($item){

	$keys = pods('opbg_database_settings')->field('keys_base');

	if ($keys == false) return false;

	$matches = opbg_parse_text_with_query($item['body'], $keys);

	if ($matches === false) {
		bk1_debug::log("No base keywords, site excluded");
		opbg_add_excluded_resource($item, 2);

		return false;
	}

	bk1_debug::log("Base keywords found:");
	bk1_debug::log($matches);

	return $matches;
}

function opbg_check_topics_keywords($item){
	$topics_pod = pods('topics', array("limit" => -1));

	$topics_found = false;
	$exclusion_found = false;

	$keywords   = array();
	$exclusion_keys = array();
	$topics     = array();

	while($topics_pod->fetch()){
		$keys_in	= $topics_pod->field("keys_in");
		$keys_out	= $topics_pod->field("keys_out");

		$matches_in  = opbg_parse_text_with_query($item['body'], $keys_in);
		$matches_out = opbg_parse_text_with_query($item['body'], $keys_out);

		if ( $matches_out == false ) {
			if ( $matches_in != false ) {
				$topics_found   = true;
				$keywords       = array_merge($keywords, $matches_in);
				$topics[]       = $topics_pod->id();
			}
		}
		else {

			$exclusion_found = true;
			$exclusion_keys = array_merge($exclusion_keys, $matches_out);
		}
	}

	if ($topics_found === false) {
		if ($exclusion_found === true) {
			bk1_debug::log("Exclusion keywords found:");
			bk1_debug::log($exclusion_keys);
			opbg_add_excluded_resource($item, 4);
		}
		else {
			bk1_debug::log("No topics found:");
			opbg_add_excluded_resource($item, 3);
		}

		return false;
	}

	bk1_debug::log("topics keywords found:");
	bk1_debug::log($keywords);

	return array("keywords" => $keywords, "topics" => $topics);
}

function opbg_clean_incomplete_database_data(){
	$resource_url = get_option( 'saving_resource', false);
	if ($resource_url === false) {
		bk1_debug::log('Cleaning incomplete resource');
		pods('resources')->find(array('where' => array('url' => $resource_url) ))->delete();
		update_option( 'saving_resource', false);
	}

	$source_url = get_option( 'saving_source', false);
	if ($source_url === false) {
		bk1_debug::log('Cleaning incomplete source');
		pods('sources')->find(array('where' => array('url' => $source_url) ))->delete();
		update_option( 'saving_source', false);
	}

	$excluded_url = get_option( 'saving_excluded', false);
	if ($excluded_url === false) {
		bk1_debug::log('Cleaning incomplete excluded resource');
		pods('excluded_resources')->find(array('where' => array('url' => $excluded_url) ))->delete();
		update_option( 'saving_excluded', false);
	}
}


?>
