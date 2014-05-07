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

	ini_set('log_errors', 'on');      // log to file (yes)
	ini_set('display_errors', 'on'); // log to screen (no)
	
	$res = pods('resources')->find(array('limit' => -1, "where" => "social_scores IS NOT NULL"));
	
	while ($res->fetch()) {

		$scores = $res->field('social_scores');

		if (preg_match("/Facebook/", $scores) !== 1){
			$new_scores = opbg_get_social_scores_of_url($res->field('url'));
			$res->save('social_scores', $new_scores);
			continue;
		}
		
		$scores = explode("\n", $scores);
		
		$new_scores = array();
		
		foreach($scores as &$sn) {
		    $sn = explode(": ", $sn);
		    
		    if (!in_array($sn[0], array("total", "others"))){
		        $new_scores[$sn[0]] = $sn[1];
		    }
		    if ($sn[0] == "total") $total = $sn[1];
		    if ($sn[0] == "others"){
		    	if ($sn[1] == 401) $sn[1] = 0;
		    	$others = $sn[1];

		    } 	    
		}
		unset($sn);
		
		$new_scores['others'] = $others;
		$new_scores['total'] = $others + $total;
		
		var_dump(opbg_array_to_textfield($new_scores));

		$res->save('social_scores', opbg_array_to_textfield($new_scores));

		echo "\n\n";
	}

?>
</pre>
stop