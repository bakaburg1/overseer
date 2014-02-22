<pre>

<?php
/**
 * Template Name: report
 * @package WordPress
 * @subpackage Overseer
 *
 */
/*$resources_time_range = 'pub_time > "2014-01-01"';

$resources = pods('resources')->find(array('where' => $resources_time_range));

echo 'Total pages january: '.$resources->total_found()."<br>";*/

$start_date = "2013-10-01";
$end_date = "2013-12-31";

echo "Report from ".date_create($start_date)->format('d M Y')." to ".date_create($end_date)->format('d M Y')."\n\n";

$resources_time_range = "pub_time >= '$start_date' AND pub_time <= '$end_date'";

$sources_time_range = "t.created >= '$start_date' AND t.created <= '$end_date'";

$resources = pods('resources')->find(array('where' => $resources_time_range));

echo 'Total pages in given period: '.$resources->total_found()."<br>";

$resources = pods('resources')->find(array('where' => $resources_time_range.' AND status IN (0, 2)'));

echo 'Total pages screened: '.$resources->total_found()."<br>";

$sources = pods('sources')->find(array('where' => $sources_time_range.' AND resources.status IN (0,2)'));

echo 'Total sites screened: '.$sources->total_found()."<br>";

$pertinent = $resources->find(array('where' => $resources_time_range.' AND status = 2'))->total_found();

echo 'Total pertinent pages: '.$pertinent."<br>";

$pregnancy = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context = 0'))->total_found();
$preconception = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context = 1'))->total_found() ;
$both = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context = 2'))->total_found();

$prec_and_both = $preconception + $both;

echo 'Pregnancy: '.round($pregnancy/$pertinent * 100).'%; preconception: '.round($preconception/$pertinent * 100).'%; both: '.round($both/$pertinent * 100).'%<br>';

echo 'Pages about preconceptional period: '.$prec_and_both."\n";

$facebook = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND social_networks = ("Facebook")'))->total_found();
$twitter = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND social_networks = ("Twitter")'))->total_found();
$gplus = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND social_networks = "Google+"'))->total_found();
$linkedin = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND social_networks = "Linkedin"'))->total_found();
$other_sn = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND social_networks = "Others"'))->total_found();

$discussions = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND type = 1'))->total_found();

echo 'Discussions: '.$discussions."<br>";

$correct = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND is_correct = 2'))->total_found();
$p_correct = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND is_correct = 1'))->total_found();
$not_correct = $resources->find(array('where' => $resources_time_range.' AND status = 2 AND context IN (1, 2) AND is_correct = 0'))->total_found();

echo 'Correct: '.round($correct / $prec_and_both * 100).'%; partially: '.round($p_correct / $prec_and_both * 100).'%; totally not: '.round($not_correct / $prec_and_both * 100).'%<br>';

$resources->find(array('limit' => -1, 'where' => $resources_time_range.' AND status = 2 AND context IN (1, 2)'));

$sn = array();
$sn['facebook'] = 0;
$sn['twitter'] = 0;
$sn['gplus'] = 0;
$sn['linkedin'] = 0;
$sn['comments'] = 0;
$sn['others'] = 0;

//var_dump($resources->total_found());

while ($resources->fetch()) {
	$res_sn = $resources->field('social_networks');

	if($res_sn){
		if(in_array('Facebook', $res_sn)) ++$sn['facebook'];
		if(in_array('Twitter', $res_sn)) ++$sn['twitter'];
		if(in_array('Google+', $res_sn)) ++$sn['gplus'];
		if(in_array('Linkedin', $res_sn)) ++$sn['linkedin'];
		if(in_array('Others', $res_sn)) ++$sn['others'];
	}

	if($resources->field('comments') === "0") ++$sn['comments'];
}

foreach ($sn as $key => $value){
	echo '<td>'.$key.': '.round($value / $prec_and_both * 100).'%; </td>';
}

$sources = pods('sources')->find(array('limit' => -1, 'where' => $sources_time_range.' AND resources.status = 2 AND resources.context IN (1, 2)'));

//echo $sources->total_found();

$sources_by_correctnes = array();

while($sources->fetch()){

	$source_url = $sources->field('url');

	//$source_resources = $sources->field('resources');
	
	$source_resources = pods('resources')->find(array('limit' => -1, "where" => 'source.id = '.$sources->id().' AND is_correct IS NOT NULL'));

	$correctness_level = 0;

	$total_res_for_source = $source_resources->total_found();
	
    if ($total_res_for_source > 0) {
	    while ($source_resources->fetch()) $correctness_level += (int)$source_resources->field('is_correct') / 2;
    }

	$correctness_level = $correctness_level / $total_res_for_source;
	
	//var_dump($correctness_level);

	$sources_by_correctness[] = array('url' => $source_url, 'correctness' => $correctness_level, 'pages' => $total_res_for_source);

    //var_dump($sources_by_correctness);
}

usort($sources_by_correctness, function($a, $b) {
	if($a['correctness'] === $b['correctness']) return 0;

	return ($a['correctness'] > $b['correctness']) ? -1 : 1;
});

//var_dump($sources_by_correctnes);

?>
<table>
	<tr>
		<th>url</th><th>level of correctness</th><th>pages</th>
	</tr>
	<?php

	foreach ($sources_by_correctness as $source){
		echo "<tr>";
		echo "<td>".$source['url']."</td><td>".round($source['correctness'] * 100)."%</td><td>".$source['pages']."</td>";
		echo "</tr>";
	}
	?>

</table>
<?php

$topics = pods('topics')->find(array('limit' => -1));

$sorted_topics = array();

while ($topics->fetch()){
	$topic_id = $topics->id();

	$topic_query = 'topics.id = '.$topic_id;

	$topic_name = $topics->display('name');

	$pages = $resources->find(array('limit' => -1, 'where' => $resources_time_range.' AND '.$topic_query.' AND status = 2 AND context IN (1,2)'))->total_found();

	if ($pages !== "0"){
		$sn = array();
		$sn['facebook'] = 0;
		$sn['twitter'] = 0;
		$sn['gplus'] = 0;
		$sn['linkedin'] = 0;
		$sn['comments'] = 0;
		$sn['others'] = 0;

		while ($resources->fetch()) {
			$res_sn = $resources->field('social_networks');
			if($res_sn){
				if(in_array('Facebook', $res_sn, true)) ++$sn['facebook'];
				if(in_array('Twitter', $res_sn, true)) ++$sn['twitter'];
				if(in_array('Google+', $res_sn, true)) ++$sn['gplus'];
				if(in_array('Linkedin', $res_sn, true)) ++$sn['linkedin'];
				if(in_array('Others', $res_sn, true)) ++$sn['others'];
			}

			if($resources->field('comments') === "0") ++$sn['comments'];
		}

		$sn['facebook'] = $sn['facebook'] / $pages;
		$sn['twitter'] = $sn['twitter'] / $pages;
		$sn['gplus'] = $sn['gplus'] / $pages;
		$sn['linkedin'] = $sn['linkedin'] / $pages;
		$sn['comments'] = $sn['comments'] / $pages;
		$sn['others'] = $sn['others'] / $pages;

		$discussions = $resources->find(array('where' => $resources_time_range.' AND '.$topic_query.' AND status = 2 AND context IN (1,2) AND type = 1'))->total_found();

		$is_correct = array();

		$is_correct['correct'] = $resources->find(array('where' => $resources_time_range.' AND '.$topic_query.' AND status = 2 AND context IN (1,2) AND is_correct = 2'))->total_found() / $pages;
		$is_correct['partially'] = $resources->find(array('where' => $resources_time_range.' AND '.$topic_query.' AND status = 2 AND context IN (1,2) AND is_correct = 1'))->total_found() / $pages;
		$is_correct['not'] = $resources->find(array('where' => $resources_time_range.' AND '.$topic_query.' AND status = 2 AND context IN (1,2) AND is_correct = 0'))->total_found() / $pages;

		$sorted_topics[] = array(
			'name' => $topic_name,
			'pages' => $pages,
			'correctness' => $is_correct,
			'sn' => $sn,
			'discussions' => $discussions
		);
	}
	else {
		$sorted_topics[] = array(
			'name' => $topic_name,
			'pages' => 0
		);
	}
}

usort($sorted_topics, function($a, $b) {
	if($a['pages'] === $b['pages']) return 0;

	return ($a['pages'] > $b['pages']) ? -1 : 1;
});

?>
<table>
	<tr>
		<th>topics</th>
		<th>pagine</th>
		<th>correct</th>
		<th>part correct</th>
		<th>not correct</th>
		<th>facebook</th>
		<th>twitter</th>
		<th>gplus</th>
		<th>linkedin</th>
		<th>comments</th>
		<th>other sn</th>
		<th>discussion</th>
	</tr>
	<?php

	foreach ($sorted_topics as $topic){
		echo "<tr>";
		echo "<td>".$topic['name']."</td>";
		echo "<td>".$topic['pages']."</td>";
		if ($topic['pages']) {
			foreach ($topic['correctness'] as $key => $value) {
				echo "<td>".round($value * 100)."%</td>";
			}
			foreach ($topic['sn'] as $key => $value) {
				echo "<td>".round($value * 100)."%</td>";
			}
			echo "<td>".$topic['discussions']."</td>";
		}
		echo "</tr>";
	}
	?>

</table>
<?php

?>

</pre>