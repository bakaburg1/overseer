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
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 *
 */

    /*$topics = opbg_get_resource_summary('topics');

    $results = array();

    foreach($topics as $topic=>$value){
        $results[$topic] = $value['total'];
    }
    asort($results);
    print_r($results);*/

    $resources = pods('resources')->find(array('where' => 't.status = ""'));

	echo $resources->total_found();
?>

</pre>
finish