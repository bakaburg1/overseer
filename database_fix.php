<pre>

<?php
/**
 * Template Name: database fix
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

    ini_set('log_errors', 'on');      // log to file (yes)
    ini_set('display_errors', 'on'); // log to screen (no)

/*

	$resources = pods('resources')->find(array('limit' => 20));

	$reload = 0;

	echo "<div class='total'>".$resources->total_found()."</div>";

	while($resources->fetch()){
		$url = $resources->field('url');
		$alexa_rank = ($resources->field('source.alexa') === '' OR  $resources->field('source.alexa') === null OR $resources->field('source.alexa') === false) ? false : (int)$resources->field('source.alexa');
		$keywords = (int)$resources->field('keywords_matched');

		//echo "$url\t$alexa\t$keywords\n";
		//var_dump($resources->field('source.alexa'));
		//var_dump($alexa_rank);

		if ($alexa_rank > pods('opbg_database_settings')->field('alexa_threshold') OR $alexa_rank === 0) {
			$resources->delete();
			echo "Alexa score $alexa_rank, deleting\n";
			$reload = 1;
			continue;
		}

		if ($keywords === 0){
			echo "$keywords keywords found";

			$matches = opbg_check_keywords_in_resource($url);

			if ($matches >= 1){
				$resources->save('keywords_matched', $matches);
				echo ",$matches present, updating\n";
			}
			else {
				$resources->delete();
				echo ", deleting\n";
				$reload = 1;
			}
			continue;
		}
	}

*/

	$sources = pods('sources', array('limit' => 30, "where" => "(resources.id IS NULL) OR (t.alexa NOT BETWEEN 1 AND 120000) OR (t.url IS NULL)"));

	//$sources = pods('sources', array('limit' => 15, "where" => "(resources.id IS NULL)"));

	echo "<div class='total'>".$sources->total_found()."</div>";

	$total = $sources->total_found();

	//echo "url\tresources\talexa\n";

	while($sources->fetch()){
		$url = $sources->field('url');
		$resources = count($sources->field('resources'));
		$alexa = $sources->field('alexa');

		//echo "$url\t$resources\t$alexa\n";

		$sources->delete();

		//var_dump($sources->field('resources'));
	}

	?>


</pre>
<?php echo $sources->pagination( array( 'type' => 'paginate' ) ); ?>
<script src="http://code.jquery.com/jquery-2.0.3.min.js"></script>

<script type="text/javascript">
jQuery(document).ready(function ($) {

	//link = $('.pods-pagination-paginate .next').attr('href');

	console.log($('.total').text() !== "0");
	console.log($('.reload').eq(0).text() === "1");

	//reload = <?php echo $reload ?>;

	//return false;

	$total = <?php echo $total ?>;

	if ($total !== 0) {

		window.location = window.location

		//if (reload === 1) window.location = window.location;
		//else window.location = link;
	}

});

</script>
stop
?>
</pre>
stop