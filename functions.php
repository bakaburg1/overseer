<?php

/**** SETUP ****/

//ini_set('error_reporting', E_ALL & ~E_NOTICE);
//ini_set('error_log', '/path/to/my/php.log');
/*define('WP_DEBUG', true);
if (WP_DEBUG) {
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
}
*/
ini_set('log_errors', 'Off');      // log to file (yes)
ini_set('display_errors', 'Off'); // log to screen (no)

require_once( 'deps/bk1-wp-utils/bk1-wp-utils.php' );
//require_once( 'deps/SEOstats/src/seostats.php' );
require_once( 'deps/wp-less/wp-less.php' );

bk1_debug::state_set('off');
bk1_debug::print_always_set('on');

// Redirect user to wp-admin
/*
add_action( 'init', function () {
	add_action( 'template_redirect', function(){
		if(!is_admin()) wp_redirect();
	}, 100);
}, 100 );
*/

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

	$parsed_page_query = wp_parse_args($page_query['query']);

	return $pagenow === 'admin.php' AND strpos($parsed_page_query['page'], 'pods-manage-'.$pods) !== false AND $parsed_page_query['action'] === 'edit';
}

/* CSS AND JAVASCRIPTS */

add_action( 'admin_init', function() {
	global $pagenow;

	wp_enqueue_style( 'opgb-admin-style', get_stylesheet_directory_uri().'/style/admin-style.less' );
	wp_enqueue_style( 'font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css' );
	wp_enqueue_script('opbg_admin', get_stylesheet_directory_uri().'/js/admin.js', array('jquery'), false, true);

	if($pagenow === 'index.php'){
		bk1_debug::log('enqueuing admin_dashboard.js');
		wp_enqueue_script('admin_dashboard', get_stylesheet_directory_uri().'/js/admin_dashboard.js', array('opbg_admin'), false, true);
	}
	
	if (is_pods_detail_page()){
		bk1_debug::log('enqueuing ScrollToFixed.js');
		wp_enqueue_script('ScrollToFixed', get_stylesheet_directory_uri().'/js/ScrollToFixed.js', array('jquery'), false, true);
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

// On post creation through ifttt call post to resource converter
add_action( 'wp_insert_post', function($post_id, $post){
	bk1_debug::log('wp_insert_post called!');
	bk1_debug::log('post id: '.$post_id.' and title: '.$post->post_title);
	bk1_debug::log('has tag ifttt: '.has_tag( 'ifttt', $post));
	bk1_debug::log('has right status: '.(get_post_status($post_id) !== 'trash'));
	if ($post->post_type === 'post' AND has_tag( 'ifttt', $post) AND get_post_status($post_id) !== 'trash') {
		opbg_add_new_resource_from_post($post);
	}
}, 10, 2);

// Redirect to admin on login
add_filter( 'login_redirect', function($redirect_to, $request, $user){
	return admin_url().'index.php';
}, 10, 3);


add_filter( 'xmlrpc_enabled', function($enabled){

	return get_option( 'remote_resources_fetching_status', false );
});

add_action( 'wp_ajax_remote_resources_fetching_toggle', function(){

	if ( !wp_verify_nonce( $_REQUEST['nonce'], "remote_resource_fetching_toggle_nonce")) {
      exit("No naughty business please");
	}

	bk1_debug::log('toggling resource fetching');

	$success = false;

	if ( $_REQUEST['remote_resources_fetching_status'] === 'off'){
		$success = update_option( 'remote_resources_fetching_status', false );

		$status = 'inactive';
	}
	elseif ( $_REQUEST['remote_resources_fetching_status'] === 'on'){
		$success = update_option( 'remote_resources_fetching_status', true );

		$status = 'active';
	}

	header( "Content-Type: application/json" );

	$response = array('success' => $success, 'status' => $status);

	echo json_encode($response);

	die();

});

add_filter( 'heartbeat_received', function($response, $data){
	// Make sure we only run our query if the proper key is present
	bk1_debug::log('heartbeat_received');
    if( $data['dashboard_heartbeat'] === 'upgrade_dashboard_summary' ) {
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

/*
add_action( 'xmlrpc_call', function($post){
	$user = wp_get_current_user();

	if (in_array($user->user_login, get_option( 'disabled_xml_rpc_users', $default = false )))

});*/

/**** PODS FUNCTIONS ****/

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
function opbg_is_resource_existing($resource_data){
	$sources	= pods('sources');

	$resources	= pods('resources');

	$host		= opbg_sanitize_host_url($resource_data['url']);

	bk1_debug::log($sources->find(array('limit' => 1, 'where' => array('url' => $host) ) )->fetch());

	// If source already exist
	if ($sources->exists()):

		bk1_debug::log('source already exists');
		//bk1_debug::log($sources->row());

		// Check if resource exist
		$resources->find(array('limit' => 1, 'where' => array('t.url' => $resource_data['url']) ) )->fetch();

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
		bk1_debug::log('resource existing, exiting');
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
