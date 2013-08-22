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
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 *
 */



$post = new stdClass;
$post->post_content = "<--excerpt-->Ho dovuto affrotare la nascita della figlia di una cara amica.. La nuova <b>gravidanza</b> della mia migliore amica... Per quanto felicissima.. Sappiamo tutte che dentro di noi tornano i ricordi e quei sentimenti... <--url--> http://forum.alfemminile.com/forum/perteenfant/__f129158_perteenfant-Ciao-amichette-mie-amour.html <--topics--> Gravidanza";
$post->post_title = "Ciao amichette mie!!";
$post->post_date = '2013-07-26 23:23:26';

$resources = pods('resources');

$topics = pods('topics');

$resource_data = array();

/* IFTTT post data parsing */

$content = explode("\n", trim(preg_replace("/(<--[\w]*-->)/", "\n$1", $post->post_content)));

foreach ($content as $field):
	$buffer = array();
	preg_match("/<--(?P<key>[\w]*)-->(?P<value>.*)/", $field, $buffer);
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

// Check if resource is existing, and return the source ID. If resource is already existing, false is returning
$resource_data['source'] = opbg_is_resource_existing1($resource_data);

// If duplicated, skip resource
if ($resource_data['source'] === false):
	// wp_delete_post( $post->ID, true );
	bk1_debug::log('resource existing, exiting');
	return false;
endif;

/* Save new entry */

var_dump($resources->fetch($resources->add($resource_data)));

?>
