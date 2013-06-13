<?php
/**
 * Template Name: Prova Page
 *
 * @package WordPress
 * @subpackage Overseer
 * @since Overseer 1.0
 */

get_header();

$prova = pods('prove', 1);

//echo $prova->total();

//sism_debug($prova->row());

$data = array(
	'name' => 'New book name',
	'description' => 'Awesome book, read worthy!'
);
sism_debug($prova->row());
//sism_debug($prova->add(array('name' => 'nuovo', 'field' => 'capo')));
while ($prova->fetch()){

}

//sism_debug(json_encode($prova));


get_footer(); ?>
