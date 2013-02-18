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

header('Content-Type: text/html; charset=UTF-8');
echo "<?".'xml version="1.0" encoding="UTF-8"'."?>"."\n";

$GLOBALS['BT_ROOT_PATH'] = '';
error_reporting(-1);
$begin = microtime(TRUE);

require_once 'inc/lang.php';
require_once 'config/user.php';
require_once 'config/prefs.php';
require_once 'inc/conf.php';
require_once 'inc/fich.php';
require_once 'inc/html.php';
require_once 'inc/form.php';
require_once 'inc/comm.php';
require_once 'inc/conv.php';
require_once 'inc/util.php';
require_once 'inc/veri.php';
require_once 'inc/sqli.php';

$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);

echo '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">'."\n";
echo '<channel>'."\n";


// RSS DES LIENS + COMMENTAIRES + ARTICLES + ... SELON L'URL DEMANDÉ
/*
		L'url « mode » donne un sorte de code qui fonctionne comme le « chmod » : un chiffre qui correspond à un état.
		Les correspondances sont ici :
		- 1  - articles
		- 2  - commentaires
		- 4  - liens
		- 8  - fichiers
		- 16 - notes

		Si on a un code "6", alors c'est : liens + commentaires, parce que 4+2=6.
		Si on a un code "3", alors c'est : articles+commentaires, car 1+2=3.

		Cette manière de faire permet de donner à chaque nombre entier une signification sans ambiguïtés :
		1 = 1
		2 = 2
		3 = 2 + 1
		4 = 4
		5 = 4 + 1
		6 = 4 + 2
		7 = 4 + 2 + 1
		8 = 8
		9 = 8 + 1
		10 = 8 + 2
		11 = 8 + 2 + 1
		12 = 8 + 4
		...
		...

		L'url est composé d'élements que l'utilisateur veut voir dans son flux RSS : peu importe l'ordre maintenant, 4+2 = 2+4 = 6.
 */



if (!empty($_GET['mode'])) {
	echo '<title>Rss sur "'.$GLOBALS['nom_du_site'].'"</title>'."\n";
	echo '<link>'.$GLOBALS['racine'].'index.php'.'</link>'."\n"; 
	echo '<description>'.$GLOBALS['description'].'</description>'."\n"; 
	echo '<language>fr</language>'."\n"; 
	echo '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
	$int_code = 0;   // chmod-like CODE
	$str_code = '.'; // chmod-like CODE but in STR format
	if ( preg_match('#blog#', $_GET['mode']) ) { // 1 = articles
		$int_code += 1;
		$str_code .= '1.';
	}
	if ( preg_match('#comments#', $_GET['mode']) ) { // 2 = commentaires
		$int_code += 2;
		$str_code .= '2.';
	}
	if ( preg_match('#links#', $_GET['mode']) ) { // 4 = links
		$int_code += 4;
		$str_code .= '4.';
	}

	// si rien de bon dans l'url, on prend le blog, à défaut
	if ($int_code == 0) {
		$int_code = 1;
		$str_code .= '1.';
	}
	// get cached files by code
	if (@filemtime($GLOBALS['dossier_cache'].'/'.'cache_rss_'.$int_code.'.dat')<time()-(1800))
		$cache = FALSE;
	else {
		$cache = TRUE;
	}

	if ($cache === FALSE) { // FALSE -> on doit recréer le fichier cache.

		// on liste les éléments qu'on veut, en fonction du choix qu'on a dans le $str_code, donc dans le $GET[mode]
		$all1 = array(); $all2 = array(); $all4 = array();
		if ( preg_match('#.1.#', $str_code)) $all1 = liste_base_articles('', '', 'public', '1', 0, 20);
		if ( preg_match('#.2.#', $str_code)) $all2 = liste_base_comms('', '', 'public', '1', 0, 20);
		if ( preg_match('#.4.#', $str_code)) $all4 = liste_base_liens('', '', 'public', '1', 0, 20);

		// fusionne les tableaux
		$all = array_merge($all1, $all2, $all4);

		if (!empty($all)) {
			// tri le tableau fusionné selon les bt_id
			foreach ($all as $key => $item) {
				 $bt_id[$key] = (isset($item['bt_date'])) ? $item['bt_date'] : $item['bt_id'];
			}
			// trick : tri selon des sous-clés d'un tableau à plusieurs sous-niveaux (trouvé dans doc-PHP)
			array_multisort($bt_id, SORT_DESC, $all);

			// conserve les 20 dernières entrées seulement
			$all = array_slice($all, 0, 20);
		}

		// les affiche (dans le RSS la forme des articles / billets / liens est la même)
		$xml = '';
		foreach ($all as $elem) {
			$time = (isset($elem['bt_date'])) ? $elem['bt_date'] : $elem['bt_id'];
			$dec = decode_id($time);
			$xml .= '<item>'."\n";
				if ($elem['bt_type'] == 'article' or $elem['bt_type'] == 'link' or $elem['bt_type'] == 'note') {
					$xml .= '<title>'.$elem['bt_title'].'</title>'."\n";
				} else {
					$xml .= '<title>'.$elem['bt_author'].'</title>'."\n";
				}
				$xml .= '<link>'.$elem['bt_link'].'</link>'."\n";
				$xml .= '<guid isPermaLink="false">'.$GLOBALS['racine'].'index.php?mode=links&amp;id='.$elem['bt_id'].'</guid>'."\n";
				$xml .= '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";


				if ($elem['bt_type'] == 'link') {
					$xml .= '<description><![CDATA['.rel2abs($elem['bt_content']). '<br/><span style="font-style:italic;font-size:80%;">(par '.$elem['bt_author'].')</span>]]></description>'."\n";
				} else {
					$xml .= '<description><![CDATA['.rel2abs($elem['bt_content']).']]></description>'."\n";
				}

			$xml .= '</item>'."\n";
		}
		// on crée le fichier cache correspondant
		cache_file($GLOBALS['dossier_cache'].'/'.'cache_rss_'.$int_code.'.dat', $xml); // rss en fonction du numéro.
		echo $xml; // pas oublier de l'afficher

	} else { // autrement, le fichier trouvé dans le cache est OK, on en récupère le contenu qu'on affiche :
		readfile($GLOBALS['dossier_cache'].'/'.'cache_rss_'.$int_code.'.dat');
	}
}



// RSS DU BLOG
else {
	/* si y'a un ID en paramètre : rss sur fil commentaires de l'article "ID" */
	if (isset($_GET['id']) and preg_match('#^[0-9]{14}$#', $_GET['id'])) {
		$article_id = htmlspecialchars($_GET['id']);
		$billet = liste_base_articles('id', $article_id, 'public', '1', 0, '');
		echo '<title>Fil des commentaires sur "'.$billet[0]['bt_title'].'" - '.$GLOBALS['nom_du_site'].'</title>'."\n";
		echo '<link>'.$billet[0]['bt_link'].'</link>'."\n"; 
		echo '<description>'.$GLOBALS['description'].'</description>'."\n"; 
		echo '<language>fr</language>'."\n"; 
		echo '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
		$liste = liste_base_comms('assos_art', $article_id, 'public', '1', 0, '');
		if (!empty($liste)) {
			foreach ($liste as $comment) {
				$dec = decode_id($comment['bt_id']);
				echo '<item>'."\n";
					echo '<title>'.$comment['bt_author'].'</title>'."\n";
					echo '<guid isPermaLink="false">'.$comment['bt_link'].'</guid>'."\n";
					echo '<link>'.$comment['bt_link'].'</link>'."\n";
					echo '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";
					echo '<description><![CDATA['.($comment['bt_content']).']]></description>'."\n";
				echo '</item>'."\n";
			}
		} else {
			echo '<item>'."\n";
				echo '<title>'.$GLOBALS['lang']['note_no_comment'].'</title>'."\n";
				echo '<guid isPermaLink="false">'.$GLOBALS['racine'].'index.php</guid>'."\n";
				echo '<link>'.$GLOBALS['racine'].'index.php</link>'."\n";
				echo '<pubDate>'.date('r').'</pubDate>'."\n";
				echo '<description>'.$GLOBALS['lang']['no_comments'].'</description>'."\n";
			echo '</item>'."\n";
		}
	}
	/* sinon, fil rss sur les articles (par défaut) */
	/* Ceci se fait toujours à partir d'un fichier que l'on place en cache. */
	else {
		// Mise en cache des fichiers (rss.php et rss.php?full) : évite les surcharges serveur.
		$fichierCache = $GLOBALS['dossier_cache'].'/'.'cache_rss.dat';
		$fichierCacheFull = $GLOBALS['dossier_cache'].'/'.'cache_rss_full.dat';
		// si la page n'existe pas dans le cache ou si elle a expiré (30 minutes)
		if (@filemtime($fichierCache)<time()-(1800)) {
			// génération de la page, que l'on placera dans un fichier statique
			$xml = '<title>'.$GLOBALS['nom_du_site'].'</title>'."\n";
			$xml .= '<link>'.$GLOBALS['racine'].'</link>'."\n"; 
			$xml .= '<description>'.$GLOBALS['description'].'</description>'."\n"; 
			$xml .= '<language>fr</language>'."\n"; 
			$xml .= '<copyright>'.$GLOBALS['auteur'].'</copyright>'."\n";
			$xml_full = $xml;
			$liste = liste_base_articles('', '', 'public', '1', 0, 20);
			foreach ($liste as $billet) {
				$time = (isset($billet['bt_date'])) ? $billet['bt_date'] : $billet['bt_id'];
				$dec = decode_id($time);
				$item = '<item>'."\n";
				 $item .= '<title>'.$billet['bt_title'].'</title>'."\n";
				 $item .= '<guid isPermaLink="false">'.$billet['bt_link'].'</guid>'."\n";
				 $item .= '<link>'.$billet['bt_link'].'</link>'."\n";
				 $item .= '<pubDate>'.date('r', mktime($dec['heure'], $dec['minutes'], $dec['secondes'], $dec['mois'], $dec['jour'], $dec['annee'])).'</pubDate>'."\n";
				 $iitem = $item;
				 $item .= '<description><![CDATA['.nl2br($billet['bt_abstract']).']]></description>'."\n";
					// on génère du même coup le fichier RSS avec les articles complets
					$item_full = $iitem.'<description><![CDATA[<b>'.nl2br($billet['bt_abstract']).'</b><br/>'.rel2abs($billet['bt_content']).']]></description>'."\n";
					$xml_full .= $item_full.'</item>'."\n";
				$xml .= $item.'</item>'."\n";
			}
			cache_file($fichierCache, $xml); // rss
			cache_file($fichierCacheFull, $xml_full); // rss_full

			if (isset($_GET['full'])) { //on l'affiche
				echo $xml_full;
			} else {
				echo $xml;
			}
		} else {
			if (isset($_GET['full'])) { //on l'affiche
				readfile($fichierCacheFull);
			} else {
				readfile($fichierCache);
			}
		}
	}
}

$end = microtime(TRUE);
echo "\n".'<!-- generated in '.round(($end - $begin),6).' seconds -->'."\n";
echo '</channel>'."\n";
echo '</rss>';
?>
