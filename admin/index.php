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

if ( !file_exists('../config/user.php') || !file_exists('../config/prefs.php') ) {
	header('Location: install.php');
	exit;
}

$begin = microtime(TRUE);
$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();

// open bases
$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);
$GLOBALS['liste_fichiers'] = open_file_db_fichiers($GLOBALS['fichier_liste_fichiers']);


afficher_top($GLOBALS['lang']['label_resume']);
echo '<div id="top">'."\n";
afficher_msg(ucfirst($GLOBALS['lang']['label_resume']));
echo moteur_recherche($GLOBALS['lang']['search_everywhere']);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";

$total_artic     = liste_base_articles('nb', '', 'admin', '', '0', '');
$total_links     = liste_base_liens('nb', '', 'admin', '', '0', '');
$total_comms     = liste_base_comms('nb', '', 'admin', '', '0', '');
//
$total_nb_fichiers = sizeof($GLOBALS['liste_fichiers']);
//

echo '<div id="axe">'."\n";
echo '<div id="mainpage">'."\n";

if (!empty($_GET['q'])) {
	$nb_commentaires = sizeof(liste_base_comms('recherche', htmlspecialchars($_GET['q']), 'admin', '', 0, ''));
	$nb_articles = sizeof(liste_base_articles('recherche', urldecode($_GET['q']), 'admin', '', 0, ''));
	$nb_liens = sizeof(liste_base_liens('recherche', urldecode($_GET['q']), 'admin', '', 0, ''));
	$nb_files = sizeof(liste_base_files('recherche', urldecode($_GET['q']), ''));


	echo '<h2>'.$GLOBALS['lang']['recherche'].' "<span style="font-style: italic">'.htmlspecialchars($_GET['q']).'</span>" :</h2>'."\n";
	echo '<ul id="resultat-recherche">';
	echo "\t".'<li><a href="commentaires.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_commentaires($nb_commentaires).'</a></li>';
	echo "\t".'<li><a href="articles.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_articles($nb_articles).'</a></li>';
	echo "\t".'<li><a href="links.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_liens($nb_liens).'</a></li>';
	echo "\t".'<li><a href="fichiers.php?q='.htmlspecialchars($_GET['q']).'">'.nombre_fichiers($nb_files).'</a></li>';
	echo '</ul>';
}







function brute_size($filtre, $data_type) {
	return $nb;
}

// transforme les valeurs numériques d’un tableau pour les ramener la valeur max du tableau à $maximum. Les autres valeurs du tableau sont à l’échelle
function scaled_size($tableau, $maximum) {
	$ratio = max(array_values($tableau))/$maximum;

	$return = array();
	foreach ($tableau as $key => $value) {
		if ($ratio != 0) {
			$return[$key] = array('nb'=> $value , 'nb_scale' => floor($value/$ratio));
		} else {
			$return[$key] = array('nb'=> $value , 'nb_scale' => 0);
		}
	}
	return $return;
}

// compte le nombre d’éléments dans la base, pour chaque mois les 12 derniers mois. 
/*
 * retourne un tableau YYYYMM => nb;
 *
*
*/
function get_tableau_date($data_type) {
	$table_months = array();
	for ($i = 11 ; $i >= 0 ; $i--) {
		$table_months[date('Ym', mktime(0, 0, 0, date("m")-$i, 1, date("Y")))] = 0;
	}

	// met tout ça au format YYYYMMDDHHIISS où DDHHMMSS vaut 00000000 (pour correspondre au format de l’ID de BT qui est \d{14}
	$max = max(array_keys($table_months)).date('dHis');
	$min = min(array_keys($table_months)).'00000000';
	if ($data_type == 'articles') {
		$query = "SELECT bt_date FROM $data_type WHERE bt_date BETWEEN $min AND $max";
	} else {
		$query = "SELECT bt_id FROM $data_type WHERE bt_id BETWEEN $min AND $max";
	}

	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute();

		// pour chaque ligne, regarde le mois de l’article. Dans le tableau final, incrémente la colonne qui match le mois.
		while ($ligne = $req->fetch()) {
			foreach ($table_months as $key => $nombre) {
				if ($key == substr($ligne[0], 0, 6)) {
					//echo $key.' '.$table_months[$key].'<br/>';
					$table_months[$key] += 1;
				}
			}

		}

	} catch (Exception $e) {
		die('Erreur 86459: '.$e->getMessage());
	}

//	echo '<pre>';die(print_r($table_months));
	return $table_months;

}

// print sur chaque div pour les articles.
echo '<div id="graphs">'."\n";
echo '<div class="graphique" id="articles"><h3>'.ucfirst($GLOBALS['lang']['label_articles']).' :</h3>'."\n";
$table = scaled_size(get_tableau_date('articles'), 130);
foreach ($table as $month => $data) {
	echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><span class="month-nb">'.$data['nb'].'</span><a href="articles.php?filtre='.$month.'"><span class="month-name">'.substr($month,4,2).'/'.substr($month,2,2).'</span></a></div>';
}
echo '</div>'."\n";

if (!$total_comms == 0) {
	// print sur chaque div pour les com.
	echo '<div class="graphique" id="commentaires"><h3>'.ucfirst($GLOBALS['lang']['label_commentaires']).' :</h3>'."\n";
	$table = scaled_size(get_tableau_date('commentaires'), 50);
	foreach ($table as $month => $data) {
		echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><a href="commentaires.php?filtre='.$month.'"><span class="month-name">'.mois_en_lettres(substr($month,4,2), 1).' '.substr($month,0,4).' : </span><span class="month-nb">'.nombre_commentaires($data['nb']).'</span></a></div>';
	}
	echo '<a href="commentaires.php" class="comm-total">Total : '.nombre_commentaires($total_comms).'</a>'."\n";
	echo '</div>'."\n";
}

if (!$total_links == 0) {
	// print sur chaque div pour les liens.
	echo '<div class="graphique" id="links"><h3>'.ucfirst($GLOBALS['lang']['label_links']).' :</h3>'."\n";
	$table = scaled_size(get_tableau_date('links'), 50);
	foreach ($table as $month => $data) {
		echo '<div class="month"><div class="month-bar" style="height: '.$data['nb_scale'].'px; margin-top:'.max(20-$data['nb_scale'], 0).'px"></div><a href="links.php?filtre='.$month.'"><span class="month-name">'.mois_en_lettres(substr($month,4,2), 1).' '.substr($month,0,4).' : </span><span class="month-nb">'.nombre_liens($data['nb']).'</span></a></div>';
	}

	echo '<a href="links.php" class="links-total">Total : '.nombre_liens($total_links).'</a>'."\n";
	echo '</div>'."\n";
}

echo '</div>'."\n";


echo '<div id="miniatures"><h3>'.ucfirst($GLOBALS['lang']['label_images']).' :</h3>'."\n";
	$nb = 0;
	foreach ($GLOBALS['liste_fichiers'] as $key => $file) {
		if ($file['bt_type'] == 'image' and $file['bt_statut'] == 1) {
			if ($nb < 30-1) {
				$nb++;
			} else {
				break;
			}
			$file = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'.$file['bt_filename'];
			$file_thb = chemin_thb_img($file);

			echo '<a class="miniature" href="'.$file.'" style="background-image: url('.$file_thb.');"></a>'."\n";
		}
	}
	echo '<a class="miniature" href="fichiers.php?filtre=image">&nbsp;</a>'."\n";

echo '</div>'."\n";

footer('show_last_ip', $begin);
?>
