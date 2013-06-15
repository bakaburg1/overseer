<?php
/**
 * Template Name: Prova Page
 *
 * @package WordPress
 * @subpackage Overseer
 * @since Overseer 1.0
 */

get_header();

$new_resources = 0;

$feed_xml = false;

$topics = pods('topics', array('limit' => -1));

if ($topics->total() > 0):
	while ($topics->fetch()):
		$feeds = $topics->field('feeds.url');
		$feeds = array_map('trim', $feeds);
	endwhile;
endif;



get_footer(); ?>
