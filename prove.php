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
	//add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 1;' ));

	$keys_in = 'farmac*, fenobarbital?, barbitur*, valpro*, carbamazepin?, ace-inibitor*, chemio-terap*, radio-terap*, retinoid*, talidomide, anti-depressiv?, anti-?pilett*, farmac* epiless*, anti-tumoral?, benzo-diazepin?, corticosteroid*, farmac* antinfiammator*), farmac* anti-infiammator*, antibiotic?, antifung*, immuno-soppressor*, ergot*, metimazol?, tiamazol?, paroxetin?';
	$keys_out = '"sono incinta", contracce*, anticoncezional?, ticket, farmacia, preservativ?, dermatolog*, "pillola del giorno dopo", sexy';
	$basekeys = pods('opbg_database_settings')->field('keys_base');
	//var_dump($query);

	$sites = array(
		'http://forum.pianetamamma.it/nascera-nel-mese-di/130473-mamme-di-agosto-2014-a-146.html#post2740954',
		'http://www.nostrofiglio.it/gravidanza/salute-benessere/Escherichia-coli-cistite-in-gravidanza.html',
		'http://news.supermoney.eu/salute/2014/02/influenza-2014-picco-sintomi-soggetti-a-rischio-cure-con-rimedi-naturali-0065688.html',
		'http://www.ilgossip.net/le-cause-dei-giramenti-di-testa-nelle-donne-e-quali-terapie-sono-efficaci-2-48087.html',
		'http://www.tempi.it/diminuiscono-gli-aborti-ma-aumentano-quelli-con-la-ru486-e-quelli-clandestini',
		'http://www.assicuriamocibene.it/2014/02/17/le-prescrizioni-farmacologiche-per-finalita-diverse-da-quelle-indicate-nel-bugiardino-sono-lecite/',
		'http://www.wdonna.it/emorroidi-cura-e-prevenzione/41355'
		);


 /*$sites[0] = "http://www.comedonchisciotte.org/site/modules.php?file=viewtopic&name=Forums&t=68001";

	foreach ($sites as $site) {
		var_dump($site);
		$parsed = opbg_parse_text_with_query(opbg_load_resource_body(array('url'=> $site)), $basekeys);
		var_dump($parsed);

		$parsed = opbg_parse_text_with_query(opbg_load_resource_body(array('url'=> $site)), $keys_in);
		var_dump($parsed);

		$parsed = opbg_parse_text_with_query(opbg_load_resource_body(array('url'=> $site)), $keys_out);
		var_dump($parsed);
	}*/

	echo pods('resources')->find(array('where' => 'social_scores IS NULL'))->total_found()."\n";

	$ress = pods('resources')->find(array('where' => 'social_scores IS false'));
	
	var_dump($ress->total_found());
	
	while($ress->fetch()){
		var_dump($ress->field('social_scores'));
	}
?>
</pre>
stop