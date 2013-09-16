<?php

/**** SETUP ****/

/*ini_set('error_reporting', E_ALL & ~E_NOTICE);
define('WP_DEBUG', true);
if (WP_DEBUG) {
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
}*/

ini_set('log_errors', 'off');      // log to file (yes)
ini_set('display_errors', 'off'); // log to screen (no)

require_once( 'deps/bk1-wp-utils/bk1-wp-utils.php' );
//require_once( 'deps/SEOstats/src/seostats.php' );
require_once( 'deps/wp-less/wp-less.php' );

bk1_debug::state_set('on');
bk1_debug::print_always_set('on');

add_action( 'init', function(){
	update_option('sampling_threshold', 25);
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
            'filtering_status'      => get_option( 'resource_filtering_status', false),
            'sampling_threshold'	=> get_option( 'sampling_threshold'),
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

/* CSS AND JAVASCRIPTS */

add_action( 'admin_enqueue_scripts', function() {
	global $pagenow;

	wp_enqueue_style( 'opgb-admin-style', get_stylesheet_directory_uri().'/style/admin-style.less' );
	wp_enqueue_style( 'font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' );
	wp_enqueue_script('opbg_admin', get_stylesheet_directory_uri().'/js/admin.js', array('jquery'), false, true);

	if($pagenow === 'index.php'){
		bk1_debug::log('enqueuing admin_dashboard.js');
		wp_enqueue_script('admin_dashboard', get_stylesheet_directory_uri().'/js/admin_dashboard.js', array('opbg_admin'), false, true);
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


/*add_filter( 'xmlrpc_enabled', function($enabled){

	return get_option( 'remote_resources_fetching_status', false );
});*/

add_filter( 'heartbeat_received', function($response, $data){
	global $pagenow;
	// Make sure we only run our query if the proper key is present
	//bk1_debug::log('heartbeat_received');
    if( $pagenow === 'index.php' AND $data['dashboard_heartbeat'] === 'upgrade_dashboard_summary' ) {
    	if ( get_option('are_new_resources', false) ){
	    	$response['dashboard_summary_data'] = opbg_get_resource_summary();
	    	bk1_debug::log('sending resources upgrade');
	    	bk1_debug::log($response);
	    	update_option('are_new_resources', false);
    	}
    }

    //bk1_debug::log('sending heartbeat response');

    return $response;

}, 10, 2 );

// On post creation through ifttt call post to resource converter
add_action( 'wp_insert_post', function($post_id, $post){
	bk1_debug::log('wp_insert_post called!');
	bk1_debug::log('post id: '.$post_id.' and title: '.$post->post_title);
	bk1_debug::log('has tag ifttt: '.has_tag( 'ifttt', $post));
	bk1_debug::log('has right status: '.(get_post_status($post_id) !== 'trash'));
	if ($post->post_type === 'post' AND has_tag( 'ifttt', $post) AND get_post_status($post_id) !== 'trash') {
		if (get_option( 'resources_fetching_status', false ) === true){
			opbg_add_new_resource_from_post($post);
		}
		else {
			wp_delete_post($post_id, true);
		}
	}
}, 10, 2);

/**** AJAX ****/

add_action( 'wp_ajax_pods-quick-edit', function() {
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "pods_list_page_data_nonce")) {
		exit("No naughty business please");
	}

	//bk1_debug::log($_REQUEST);

	$pods_item = pods($_REQUEST['pods_name'], $_REQUEST['pods_item_id']);

	$success = $pods_item->save($_REQUEST['field'], $_REQUEST['value']);

	$response = array();

	//bk1_debug::log($pods_item->find()->fetch($success));

	if($success !== false){
		//$pods_item->find()->fetch($_REQUEST['pods_item_id']);
		$response['success'] = true;
		$response['value'] = $pods_item->display($_REQUEST['field']);
		if ($_REQUEST['pods_name'] === 'resources'){
			//$statuses = ['Not Pertinent']
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

add_action( 'wp_ajax_dashboard_widget_control', function(){

	if ( !wp_verify_nonce( $_REQUEST['nonce'], "dashboard_widget_control_nonce")) {
      exit("No naughty business please");
	}

	bk1_debug::log('toggling resource fetching');
	bk1_debug::log($_REQUEST);

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
	}
	if ($_REQUEST['button_id'] === 'resource-filtering-toggle'){

		if ( $_REQUEST['message'] === 'off'){
			$success = update_option( 'resource_filtering_status', false );

			$status = 'inactive';
		}
		elseif ( $_REQUEST['message'] === 'on'){
			$success = update_option( 'resource_filtering_status', true );

			$status = 'active';
		}

		opbg_log_database_status('toggled filtering');
	}

	header( "Content-Type: application/json" );

	$response = array('success' => $success, 'status' => $status);

	bk1_debug::log($response);

	echo json_encode($response);

	die();

});

/*
add_action( 'xmlrpc_call', function($post){
	$user = wp_get_current_user();

	if (in_array($user->user_login, get_option( 'disabled_xml_rpc_users', $default = false )))

});*/

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

	$resource = pods('resources')->find(array("limit" => -1, 'where' => array('status = 1 AND source.id = '.$id)));

	bk1_debug::log('New blacklist: '.$new_blacklist);

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
		else{

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

/**** PODS FUNCTIONS ****/

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

				if ($stats['total'] > 0){

					$stats['new'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 1) ) )->total_found();

					$stats['categorized'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 2) ) )->total_found();

					$stats['excluded'] = $resources->find( array('where' => array($taxonomy_field => $taxonomy->id(), 'status' => 0) ) )->total_found();

				}
				else {
					$stats['new'] = $stats['categorized'] = $stats['excluded'] = $stats['total'];
				}
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
function opbg_is_resource_existing($resource_data){
	$sources	= pods('sources');

	$resources	= pods('resources');

	$host		= opbg_sanitize_host_url($resource_data['url']);

	bk1_debug::log($sources->find(array('limit' => 1, 'where' => array('url' => $host) ) )->fetch());

	// If source already exist
	if ($sources->exists()):

		bk1_debug::log('source already exists');
		//bk1_debug::log($sources->row());

		$blacklisted = trim($sources->field('blacklisted'));

		bk1_debug::log('source blacklisted paths:');
		bk1_debug::log($blacklisted);

		if (!empty($blacklisted)):

			if ($blacklisted === '*'):
				bk1_debug::log('Whole site is blacklisted');
				return false; // The whole site is blacklisted
			else:

				$blacklisted = explode("\n", $blacklisted);

				bk1_debug::log($blacklisted);

				foreach ($blacklisted as $path) {
					$path = trim($path);

					if ($path[0] !== '/') $path = '/'.$path;

					if (substr($path, -1) === '/') $path = substr($path, 0, -1);

					if (strpos($resource_data['url'], $host.$path) !== false){
						bk1_debug::log('This page is blacklisted');
						return false;
					}
				}

				bk1_debug::log('This page is not blacklisted');
			endif;
		endif;

		// Check if resource exist
		$resources->find(array('limit' => 1, 'where' => array('t.url' => $resource_data['url']) ) )->fetch();
		$same_url = $resources->exists();

		$resources->find(array('limit' => 1, 'where' => array('t.title' => $resource_data['title']) ) )->fetch();
		$same_title = $resources->exists();
		// If resource already exist, add topic to it and exit
		if ($resources->exists()):

			bk1_debug::log('resource already exists');
			//bk1_debug::log($resources->field('feeds.id'));

			//bk1_debug::log($topic_id);
			bk1_debug::log($resources->field('topics.id'));
			$resource_topics = $resources->field('topics.id');
			if (is_array($resource_topics)){
				if (!in_array($resource_data['topics'], $resource_topics)){
					$resources->add_to('topics', $resource_data['topics']);
					bk1_debug::log('saving resource under another topic');
				}
				else{
					bk1_debug::log('topic already present');
				}
			}

			$source_topics = $sources->field('topics.id');
			if (is_array($source_topics)){
				if (!in_array($resource_data['topics'], $source_topics)){
					$sources->add_to('topics', $resource_data['topics']);
					bk1_debug::log('saving source under another feed');
				}
			}

			return false;
		endif;

		bk1_debug::log('resource is new');

		// Returns id of an already existing source
		$source_id = $sources->id();

	else:
		bk1_debug::log('source doesn\'t exist, creating a new one');
		// If source doesn't exist, create a new one
		$source_id = $sources->add(array('url' => $host, 'topics' => $resource_data['topics'], 'is_pertinent' => 0) );
		bk1_debug::log('source id: '.$source_id);
		//bk1_debug::log(pods('sources')->fetch($source_id));
	endif;

	return $source_id;
}

// Convert posts to resources
function opbg_add_new_resource_from_post($post){

	bk1_debug::log('converting the post '.$post->post_title);

	if ($threshold = get_option( 'resource_filtering_status', false ) === true){
		$max = 10000;
		if (rand(0, $max) >= get_option( 'sampling_threshold')/100*$max ) {
			bk1_debug::log('resource filtered out');
			return false;
		}
	}

	$resources = pods('resources');

	$topics = pods('topics');

	$resource_data = array();

	/* IFTTT post data parsing */

	$content = explode("\n", trim(preg_replace("/(<\s*--[\w]*-->)/", "\n$1", html_entity_decode($post->post_content))));

    bk1_debug::log('log post content');
	bk1_debug::log($content);

	foreach ($content as $field):
		$buffer = array();
		preg_match("/<\s*--(?P<key>[\w]*)-->(?P<value>.*)/", $field, $buffer);
		$buffer = array_map('trim', $buffer);
		$resource_data[ $buffer[ 'key' ] ] = $buffer[ 'value' ];
	endforeach;

	/* Completing other post fields */

	$resource_data['title'] = $post->post_title;

	$resource_data['pub_time'] = date_create($post->post_date)->format('Y-m-d H:i:s');

	$resource_data['status'] = 1;

	// Transform the topic name in it's ID
	$topics->find(array('where' => array('name' => $resource_data['topics'] )))->fetch();
	$resource_data['topics'] = $topics->id();

	bk1_debug::log('Resource parsed prior source check');
	bk1_debug::log($resource_data);

	// Check if resource is existing, and return the source ID. If resource is already existing, false is returning
	$resource_data['source'] = opbg_is_resource_existing($resource_data);

	// If duplicated, skip resource
	if ($resource_data['source'] === false):
		wp_delete_post( $post->ID, true );
		bk1_debug::log('resource existing or blacklisted, exiting');
		return false;
	endif;

	/* Save new entry */
	bk1_debug::log('saving resource');
	if ($resources->add($resource_data)){
	    bk1_debug::log('resource saved!');
	    bk1_debug::log($resource_data);
		update_option( 'are_new_resources', true );
		wp_delete_post( $post->ID, true );
	}else {
	    bk1_debug::log('resource saving failed!');
		wp_mail( get_option( 'admin_email' ), get_option( 'blogname' ).': There was a error saving the post into a resource', 'There was a error saving the post into a resource');
	}

	return true;
}

?>
