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
 */
/*
$resource_url = 'http://www.paginemamma.it/it/573/gravidanza/professione-mamma/detail_195575_igiene-intima-in-gravidanza.aspx?c1=39&c3=8465';
	$feed_id = 2;
	$topic_id = 1;

	$sources	= pods('sources');

	$resources	= pods('resources');

	$host		= opbg_sanitize_host_url($resource_url);

	$sources->find(['limit' => 1, 'where' => ['t.url' => $host]])->fetch();

	// If source already exist
	if ($sources->exists()):

		bk1_debug::log('source already exists');
		bk1_debug::log($sources->row());

		// Check if resource exist
		$resources->find(['limit' => 1, 'where' => ['t.url' => $resource_url]]);
		$resources->fetch();

		bk1_debug::log($resources->exists());
		// If resource already exist, add topic and feed to it and exit
		if ($resources->exists()):


			bk1_debug::log('resource already exists');
			bk1_debug::log($resources->field('topics'));
			if (in_array($topic_id, $resources->field('topics'))){
				//$resources->add_to('topics', $topic_id);
			}
			bk1_debug::log($resources->field('feeds'));
			if (in_array($feed_id, $resources->field('feeds'))){
				//$resources->add_to('feeds', $feed_id);
			}
			bk1_debug::log($sources->field('topics'));
			if (in_array($topic_id, $sources->field('topics'))){
				//$sources->add_to('topic', $topic_id);
			}

			//return false;
		endif;

		bk1_debug::log('resource is new');

		// Returns id of an already existing source
		$source_id = $sources->id();

	else:
		bk1_debug::log('source doesn\'t exist, creating a new one');
		// If source doesn't exist, create a new one
		$source_id = 108;
		bk1_debug::log('source id: '.$source_id);
		bk1_debug::log(pods('sources')->fetch($source_id));
	endif;

	bk1_debug::log_print();
*/
$feeds = pods('feeds');
$feeds->fetch(2);
var_dump($feeds->field('resources.title'));
?>
