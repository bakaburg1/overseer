start
<pre>

<?php
/**
 * Template Name: Assign Alexa Scores
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * For example, it puts together the home page when no home.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @package WordPress
 * @subpackage Overseer
 *
 */

opbg_assign_alexa_score();
?>

<table>
	<tr>
		<th>url</th>
		<th>rank</th>
	</tr>
	<?php
    $sites = pods('sources')->find(array('limit' => -1));

	while ($sites->fetch()) {
        $alexa = $sites->field('alexa');

        echo "<tr>\n";
        echo "<td>".$sites->field('url')."</td>";
        echo "<td>".(($alexa != 0) ? $alexa : '')."</td>";
        echo "</tr>\n";
    }
    ?>

</pre>
finish