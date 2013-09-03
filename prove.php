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



$labels = array("Title", "Pubblication Date", "Resource url", "Excerpt", "Status", "Topics", "Source", "Context", "Is correct?", "Resource type", "Comments?", "Social Networks");

$fields = pods('resources')->find()->fields();

$fields_names = array();

foreach ($fields as $field) {
	if(in_array($field['label'], $labels)){
		$fields_names[] = $field['name'];
	}
}

var_dump(array_values(pods('resources')->pod_data['options']['ui_fields_manage']));
?>
<script type="text/javascript">
window.fields = <?php echo json_encode(pods('resources')->fields) ?>
</script>


