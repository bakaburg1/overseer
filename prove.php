start
<pre>

	<?php
/**
 * Template Name: prova
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * For example, it puts together the home page when no home.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Overseer
 *
 */
	/*
	$topics = opbg_get_resource_summary('topics');

	$results = array();

	foreach($topics as $topic=>$value){
		$results[$topic] = $value['total'];
	}
	asort($results);
	print_r($results);
	*/

	$resources = pods('resources')->find(array('limit' => -1));

	$results = array();

	$i = 0;

	while($resources->fetch()){
		$url = $resources->field('url');

		$response = wp_remote_get($url);

		$the_body = wp_remote_retrieve_body($response);

		if (preg_match_all("/gravidanz|preconcezional|prenatal|concepimento/i", $the_body) === 0) {
			$i++;
		}
	}

	echo "Resources with no matches: ".$i;

	delete_option('resource_filtering_status');
	delete_option('filtered_in_resources');
	delete_option('filtered_out_resources');
	?>


</pre>
finish