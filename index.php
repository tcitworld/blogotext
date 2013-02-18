<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2012 Timo Van Neerden <ti-mo@myopera.com>
#
# BlogoText is free software, you can redistribute it under the terms of the
# Creative Commons Attribution-NonCommercial 2.0 France Licence
#
# Also, any distributors of non-official releases MUST warn the final user of it, by any visible way before the download.
# *** LICENSE ***

/*****************************************************************************
 some misc routines
******************************************************************************/
// gzip compression
if (extension_loaded('zlib')) {
	ob_end_clean();
	ob_start("ob_gzhandler");
}
else {
	ob_start("ob_gzhandler");
}


$begin = microtime(TRUE);
error_reporting(-1);

session_start();
if (isset($_POST['allowcookie'])) { // si cookies autorisés, conserve les champs remplis
	if (isset($_POST['auteur'])) {  setcookie('auteur_c', $_POST['auteur'], time() + 365*24*3600, null, null, false, true); }
	if (isset($_POST['email'])) {   setcookie('email_c', $_POST['email'], time() + 365*24*3600, null, null, false, true); }
	if (isset($_POST['webpage'])) { setcookie('webpage_c', $_POST['webpage'], time() + 365*24*3600, null, null, false, true); }
	setcookie('subscribe_c', (isset($_POST['subscribe']) and $_POST['subscribe'] == 'on')?1:0, time() + 365*24*3600, null, null, false, true);
	setcookie('cookie_c', 1, time() + 365*24*3600, null, null, false, true);
} elseif (isset($_POST['auteur'])) { // cookies interdits : on en fait des vides (afin de vider les éventuels précédents cookies)
	setcookie('auteur_c', '', time()-42, null, null, false, true);
	setcookie('email_c', '', time()-42, null, null, false, true);
	setcookie('webpage_c', '', time()-42, null, null, false, true);
	setcookie('cookie_c', '', time()-42, null, null, false, true);
	setcookie('subscribe_c', '', time()-42, null, null, false, true);
}

if ( !file_exists('config/user.php') or !file_exists('config/prefs.php') ) {
	require_once 'inc/conf.php';
	header('Location: '.$GLOBALS['dossier_admin'].'/install.php');
}

$GLOBALS['BT_ROOT_PATH'] = '';

require_once 'inc/lang.php';
require_once 'config/user.php';
require_once 'config/prefs.php';
require_once 'inc/conf.php';
require_once 'inc/them.php';
require_once 'inc/fich.php';
require_once 'inc/html.php';
require_once 'inc/form.php';
require_once 'inc/comm.php';
require_once 'inc/conv.php';
require_once 'inc/util.php';
require_once 'inc/veri.php';
require_once 'inc/jasc.php';
require_once 'inc/sqli.php';

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);

/*****************************************************************************
 some misc requests
******************************************************************************/

// anti XSS : /index.php/%22onmouseover=prompt(971741)%3E or /index.php/ redirects all on index.php
// if there is a slash after the "index.php", the file is considered as a folder, but the code inside it still executed…
// You can also put escape with HTMLSPECIALCHARS the server[php_self] variable each time (less efficient…).
if ($_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME']) {
	header('Location: '.$_SERVER['SCRIPT_NAME']);
}
/* this-one bugs with the forward/backward link...
foreach ($_SERVER as $i => $var) { $_SERVER[$i] = htmlspecialchars($_SERVER[$i]); }
*/


// Random article :-)
if (isset($_GET['random'])) {
	$tableau = liste_base_articles('random', '', 'public', '1', '', 1);
	header('Location: '.$tableau[0]['bt_link']);
	exit;
}

// unsubscribe from comments-newsletter and redirect on main page
if ((isset($_GET['unsub']) and $_GET['unsub'] == 1) and (isset($_GET['comment']) and preg_match('#\d{14}#',($_GET['comment']))) and isset($_GET['mail']) ) {

	if (isset($_GET['all'])) {
		$res = unsubscribe(htmlspecialchars($_GET['comment']), $_GET['mail'], 1);
	} else {
		$res = unsubscribe(htmlspecialchars($_GET['comment']), $_GET['mail'], 0);
	}

	if ($res == TRUE) {
		header('Location: '.$_SERVER['PHP_SELF'].'?unsubsribe=yes');
	} else {
		header('Location: '.$_SERVER['PHP_SELF'].'?unsubsribe=no');
	}
}


/*****************************************************************************
 Show one post : 1 blogpost (with comments)
******************************************************************************/
// Single Blog Post
if ( isset($_GET['d']) and preg_match('#^\d{4}/\d{2}/\d{2}/\d{2}/\d{2}/\d{2}#', $_GET['d']) ) {
	$article_id = $_GET['d'];
	$tab = explode('/', $article_id);
	$id = substr($tab['0'].$tab['1'].$tab['2'].$tab['3'].$tab['4'].$tab['5'], '0', '14');
	afficher_calendrier($tab['0'], $tab['1'], $tab['2']);
	echo afficher_article($id);
}

// single link post
elseif ( isset($_GET['id']) and is_numeric($_GET['id']) ) {
	$link_id = $_GET['id'];

	$tableau = liste_base_liens('id', $link_id, 'public', '1', '', '');
	if (!empty($tableau[0]['bt_id']) and preg_match('/\d{14}/', $tableau[0]['bt_id'])) {
		$tab = decode_id($tableau[0]['bt_id']);
		afficher_calendrier($tab['annee'], $tab['mois'], $tab['jour']);
	} else {
		afficher_calendrier(date('Y'), date('m'));
	}
	afficher_index($tableau);
}

/*****************************************************************************
 show by lists of more than one post
******************************************************************************/
else {
	$all = array(); $all1 = array(); $all2 = array(); $all3 = array();
	$annee = date('Y'); $mois = date('m'); $jour = '';

	if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] >= 1) {
		$page = $GLOBALS['max_bill_acceuil'] * $_GET['p'];
	} else { $page = 0; }


	if ( (isset($_GET['d']) and preg_match('/^\d{4}\/\d{2}(\/\d{2})?/', $_GET['d']) or !empty($_GET['mode'])) and !isset($_GET['q']) )  {

			/*****************************************************************************
			 Show by date or mode : 
				- by date : all elements of one date (month, day…) are displayed.
				- by mode : the elements of one sort are displayer by number, then by month
				- if both mode and date are asked, both filters are applied, but only one type of data (links, comments, blogpost…) are listed. 
			******************************************************************************/

			// sélection sur date & optionnellement mode
			if ( isset($_GET['d']) and preg_match('/^\d{4}\/\d{2}(\/\d{2})?/', $_GET['d']) and !isset($_GET['q']) )  {

					$tab = explode('/', $_GET['d']);
					if ( preg_match('/\d{4}/',($tab['0'])) ) {
						$annee = $tab['0'];
					}
					if ( isset($tab['1']) and (preg_match('/\d{2}/',($tab['1']))) ) {
						$mois = $tab['1'];
					}
					if ( isset($tab['2']) and (preg_match('/\d{2}/',($tab['2']))) ) {
						$jour = $tab['2'];
					}

					if (empty($_GET['mode'])) {
						// juste date donnée : seulement les articles du blog sont affichés (pas les liens ni les commentaires)
						$all/*2*/ = liste_base_articles('date', $annee.$mois.$jour, 'public', '1', '', '');
					} else {
						// recoupage par mode qui n’est pas vide
						    if ( preg_match('#links#', $_GET['mode']) ) { $all/*1*/ = liste_base_liens('date', $annee.$mois.$jour, 'public', '1', '', ''); }
						elseif ( preg_match('#blog#', $_GET['mode']) ) { $all/*2*/ = liste_base_articles('date', $annee.$mois.$jour, 'public', '1', '', ''); }
						elseif ( preg_match('#comments#', $_GET['mode']) ) { $all/*3*/ = liste_base_comms('date', $annee.$mois.$jour, 'public', '1', '', ''); }

					}
			}
			// mode est donnée, pas date
			if (isset($_GET['mode']) and empty($_GET['d'])) {
						// notons que pour les liens, on affiche 5 fois plus d’éléments.
						if ( preg_match('#links#', $_GET['mode']) ) { $GLOBALS['max_bill_acceuil']*=5; $page*=5; $all/*1*/ = liste_base_liens('', '', 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
					elseif ( preg_match('#blog#', $_GET['mode']) ) { $all/*2*/ = liste_base_articles('', '', 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
					elseif ( preg_match('#comments#', $_GET['mode']) ) { $all/*3*/ = liste_base_comms('', '', 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
			}
			/*
			// fusionne les tableaux
			$tableau = array_merge($all1, $all2, $all3);

			// tri le tableau fusionné selon les bt_id (selon une des clés d'un sous tableau. Sûrement plus simple il y a, mais dans Doc PHP ceci est).
			foreach ($tableau as $key => $item) {
				 $bt_id[$key] = $item['bt_id'];
			}
			if (isset($bt_id)) {
				array_multisort($bt_id, SORT_DESC, $tableau);
			}
			*/
			if (empty($_GET['d'])) { // si date, on garde tout quelque soit le nombre d’éléments, sinon on coupe.
				$all = array_slice($all, 0, $GLOBALS['max_bill_acceuil']);
			}
			afficher_calendrier($annee, $mois, $jour);
			$GLOBALS['nb_elements_client_side'] = array('nb' => count($all), 'nb_page' => $GLOBALS['max_bill_acceuil']); // Needed in lien_pagination(), very ugly
			afficher_index($all);

	}

	/*****************************************************************************
	 Show by search query : 
		- if mode is set : search in one ore more databases, else search only in blog.
	******************************************************************************/
	// search query
	elseif (isset($_GET['q'])) {
		if (!empty($_GET['mode'])) {
			if ( preg_match(   '#links#', $_GET['mode']) ) { $all1 = liste_base_liens('recherche', $_GET['q'], 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
			if ( preg_match(    '#blog#', $_GET['mode']) ) { $all2 = liste_base_articles('recherche', $_GET['q'], 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
			if ( preg_match('#comments#', $_GET['mode']) ) { $all3 = liste_base_comms('recherche', htmlspecialchars($_GET['q']), 'public', '1', $page, $GLOBALS['max_bill_acceuil']); }
			// fusionne les tableaux
			$tableau = array_merge($all1, $all2, $all3);

			// sort the tableau by "bt_id" index
			foreach ($tableau as $key => $item) {
				 $bt_id[$key] = $item['bt_id'];
			}
			if (isset($bt_id)) {
				array_multisort($bt_id, SORT_DESC, $tableau);
			}
		} else {
			$tableau = liste_base_articles('recherche', $_GET['q'], 'public', '1', $page, $GLOBALS['max_bill_acceuil']);
		}
		afficher_calendrier(date('Y'), date('m'));
		$GLOBALS['nb_elements_client_side'] = array('nb' => count($tableau), 'nb_page' => $GLOBALS['max_bill_acceuil']); // Needed in lien_pagination(), very ugly I know
		afficher_index($tableau);
	}

	// display blog by tag
	elseif (!empty($_GET['tag'])) {
		$tableau = liste_base_articles('tags', html_entity_decode($_GET['tag']), 'public', 1, $page, $GLOBALS['max_bill_acceuil']); // entity_decode : &quot; => ".
		afficher_calendrier(date('Y'), date('m'));
		$GLOBALS['nb_elements_client_side'] = array('nb' => count($tableau), 'nb_page' => $GLOBALS['max_bill_acceuil']);
		afficher_index($tableau);
	}

	// display regular blog page
	else {
		$tableau = liste_base_articles('', '', 'public', '1', $page, $GLOBALS['max_bill_acceuil']);
		afficher_calendrier(date('Y'), date('m'));
		$GLOBALS['nb_elements_client_side'] = array('nb' => count($tableau), 'nb_page' => $GLOBALS['max_bill_acceuil']);
		afficher_index($tableau);
	}

}


 $end = microtime(TRUE);
 echo '<!-- Rendered in '.round(($end - $begin),6).' seconds -->';

?>
