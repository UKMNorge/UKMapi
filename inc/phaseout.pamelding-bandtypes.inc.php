<?php
$BANDTYPES = ukmAPIBT();

function ukmAPIBT() {
	## MULIGE TYPER INNSLAG
	$BANDTYPES['regular'][] = array('bt_id'=>2, 'name'=>'Film', 'ico'=>'video', 'title'=>'Driver du og lager film, og har lyst til &aring; vise verden hva du driver med? Klikk her');
	$BANDTYPES['regular'][] = array('bt_id'=>3, 'name'=>'Utstilling', 'ico'=>'utstilling', 'title'=>'Maler du bilder, er du fotograf, bygger du skulpturer? Klikk her');
	$BANDTYPES['regular'][] = array('bt_id'=>1, 'name'=>'Musikk', 'ico'=>'scene', 'title'=>'Synger du i dusjen, spiller du i band eller spiller du klaver? Det er plass til alle p&aring; UKM! Klikk her');
	$BANDTYPES['regular'][] = array('bt_id'=>1, 'name'=>'Dans', 'ico'=>'dans', 'title'=>'Jazz? Lyrisk? Hip-hop? Breakdance? Ballett? L&aelig;rt en dans p&aring; skolen? Laget din egen? Klikk her');
	$BANDTYPES['regular'][] = array('bt_id'=>1, 'name'=>'Teater', 'ico'=>'teater', 'title'=>'Bor det en skuespiller i deg som vil ut og opp p&aring; scenen? Nasjonalteateret neste, UKM f&oslash;rst! Klikk her');
	$BANDTYPES['regular'][] = array('bt_id'=>1, 'name'=>'Litteratur', 'ico'=>'litteratur', 'title'=>'Har du skrevet historien om deg selv i atten bind? Eller p&aring to linjer? Har du en korttekst i SMS-format? Klikk her');

	$BANDTYPES['regular'][] = array('bt_id'=>1, 'name'=>'Annet p&aring; scenen', 'ico'=>'annet', 'title'=>'Driver du med en form for scenekunst som ikke passer i de andre kategoriene? Sjonglering? Flammesluking? Slangetemming? Klikk her');

	$BANDTYPES['regular'][] = array('bt_id'=>6, 'name'=>'Matkultur', 'ico'=>'matkultur', 'title'=>'Lyst til &aring; imponere et dommerpanel med din egen kokkelering? Klikk her');

	$BANDTYPES['work'][] = array('bt_id'=>5, 'name'=>'Nettredaksjon / Videoproduksjon', 'ico'=>'nettredaksjon', 'title'=>'Har du lyst til &aring; jobbe som journalist under m&oslash;nstringen? Eller til &aring; v&aelig;re kameramann under en forestilling? Eller produsere hele showet? Klikk her og finn ut hvilke muligheter som finnes p&aring; din m&oslash;nstring!');
	$BANDTYPES['work'][] = array('bt_id'=>4, 'name'=>'Konferansier', 'ico'=>'konferansier', 'title'=>'Ikke redd for &aring; prate i store forsamlinger? Lyst til &aring; gj&oslash;re det med mikrofon og publikum i din hule h&aring;nd? Klikk her');
	$BANDTYPES['work'][] = array('bt_id'=>8, 'name'=>'Arrang&oslash;r', 'ico'=>'arrangor', 'title'=>'Lyst til &aring; bidra til &aring; lage m&oslash;nstring sammen med din lokale arrang&oslash;r? Klikk her');
	$BANDTYPES['work'][] = array('bt_id'=>9, 'name'=>'Sceneteknikk', 'ico'=>'sceneteknikk', 'title'=>'Liker du deg bedre bak scenen enn p&aring;? Har du lyst til &aring; bygge scene og rigge lyd og lys sammen med profesjonelle folk? Klikk her');
	
	return $BANDTYPES;
}
?>