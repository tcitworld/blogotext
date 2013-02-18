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


/// menu haut panneau admin /////////
function afficher_menu($active) {
	echo '<div id="nav">'."\n";
	echo "\t".'<a href="index.php" id="lien-index"', ($active == 'index.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['label_resume'].'</a>'."\n";
	echo "\t".'<a href="articles.php" id="lien-liste"', ($active == 'articles.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['mesarticles'].'</a>'."\n";
	echo "\t".'<a href="ecrire.php" id="lien-nouveau"', ($active == 'ecrire.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['nouveau'].'</a>'."\n";
	echo "\t".'<a href="commentaires.php" id="lien-lscom"', ($active == 'commentaires.php') ? ' class="current"' : '', '>'.$GLOBALS['lang']['titre_commentaires'].'</a>'."\n";
	echo "\t".'<a href="fichiers.php" id="lien-fichiers"', ($active == 'fichiers.php') ? ' class="current"' : '', '>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</a>'."\n";
	echo "\t".'<a href="links.php" id="lien-links"', ($active == 'links.php') ? ' class="current"' : '', '>'.ucfirst($GLOBALS['lang']['label_links']).'</a>'."\n";
	echo "\t".'<div id="nav-top">'."\n";
	echo "\t\t".'<a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a>'."\n";
	echo "\t\t".'<a href="'.$GLOBALS['racine'].'" id="lien-site">'.$GLOBALS['lang']['lien_blog'].'</a>'."\n";
	echo "\t\t".'<a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a>'."\n";
	echo "\t".'</div>'."\n";
	echo '</div>'."\n";
}

function confirmation($message) {
	echo '<div class="confirmation"><span>'.$message.'</span></div>'."\n";
}

function no_confirmation($message) {
	echo '<div class="no_confirmation"><span>'.$message.'</span></div>'."\n";
}

function legend($legend, $class='') {
	return '<legend class="'.$class.'">'.$legend.'</legend>'."\n"; 
}

function label($for, $txt) {
	return '<label for="'.$for.'">'.$txt.'</label>'."\n"; 
}

function info($message) {
	return '<p class="info">'.$message.'</p>'."\n";
}

function erreurs($erreurs) {
	if ($erreurs) {
		$texte_erreur = '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
		$texte_erreur .= '<ul><li>';
		$texte_erreur .= implode('</li><li>', $erreurs);
		$texte_erreur .= '</li></ul></div>'."\n";
	} else {
		$texte_erreur = '';
	}
	echo $texte_erreur; 
}

function erreur($message) {
	  echo '<p class="erreurs">'.$message.'</p>'."\n";
}

function question($message) {
	  echo '<p id="question">'.$message.'</p>';
}

function afficher_msg($titre) {
	if (strlen($titre) != 0) { echo '<h1>'.$titre.'</h1>'."\n";
	} else { echo '<h1>'.$GLOBALS['nom_application'].'</h1>'."\n"; }
	// message vert
	if (isset($_GET['msg'])) {
		if (array_key_exists(htmlspecialchars($_GET['msg']), $GLOBALS['lang'])) {
			confirmation($GLOBALS['lang'][$_GET['msg']]);
		}
	}
	// message rouge
	if (isset($_GET['errmsg'])) {
		if (array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
			no_confirmation($GLOBALS['lang'][$_GET['errmsg']]);
		}
	}
}

function apercu($article) {
	if (isset($article)) {
		$apercu = '<h2>'.$article['bt_title'].'</h2>'."\n";
		$apercu .= '<div><strong>'.$article['bt_abstract'].'</strong></div>'."\n";
		$apercu .= '<div>'.rel2abs_admin($article['bt_content']).'</div>'."\n";
		echo '<div id="apercu">'."\n".$apercu.'</div>'."\n\n";
	}
}

function moteur_recherche($placeholder) {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return = '<form action="'.$_SERVER['PHP_SELF'].'" method="get" id="search">'."\n";
	$return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" class="text" placeholder="'.$placeholder.'" />'."\n";
	if (isset($_GET['mode'])) {
		$return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'" />'."\n";
	}
	$return .= '<input class="silver-square" id="input-rechercher" type="submit" value="'.$GLOBALS['lang']['rechercher'].'" />'."\n";
	$return .= '</form>'."\n\n";
	return $return;
}

function afficher_top($titre) {
	if (isset($GLOBALS['lang']['id'])) {
		$lang_id = $GLOBALS['lang']['id'];
	} else {
		$lang_id = 'fr';
	}
	$txt = '<!DOCTYPE html>'."\n";
	$txt .= '<head>'."\n";
	$txt .= '<meta charset="'.$GLOBALS['charset'].'" />'."\n";
	$txt .= '<link type="text/css" rel="stylesheet" href="style/style-style.css" />'."\n";
	$txt .= '<title> '.$GLOBALS['nom_application'].' | '.$titre.'</title>'."\n";
	$txt .= '</head>'."\n";
	$txt .= '<body>'."\n\n";
	echo $txt;
}

function footer($index='', $begin_time='') {
	if ($index != '') {
		$file = '../config/ip.php';
		if (file_exists($file) and is_readable($file)) {
			include($file);
			$new_ip = htmlspecialchars($_SERVER['REMOTE_ADDR']);
			$last_time = strtolower(date_formate($GLOBALS['old_time'])).', '.heure_formate($GLOBALS['old_time']);
			if ($new_ip == $GLOBALS['old_ip']) {
				$msg = '<br/>'.$GLOBALS['lang']['derniere_connexion_le'].' '.$GLOBALS['old_ip'].' ('.$GLOBALS['lang']['cet_ordi'].'), '.$last_time;
			} else {
				$msg = '<br/>'.$GLOBALS['lang']['derniere_connexion_le'].' '.$GLOBALS['old_ip'].' '.$last_time;
			}
		} else {
			$msg = '';
		}
	} else {
		$msg = '';
	}
	if ($begin_time != ''){
		$end = microtime(TRUE);
		$dt = round(($end - $begin_time),6);
		$msg2 = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.$GLOBALS['sgdb'];
	} else {
		$msg2 = '';
	}

	echo '</div>'."\n";
	echo '</div>'."\n";
	echo '<p id="footer"><a href="'.$GLOBALS['appsite'].'">'.$GLOBALS['nom_application'].' '.$GLOBALS['version'].'</a>'.$msg2.$msg.'</p>'."\n";
	echo '</body>'."\n";
	echo '</html>'."\n";
}

// needs to generate a GLOBALS[] because function is called in index.php, and calender displayed further in the process
// $tableau here is needed to match cells of the calender where articles were posted
function afficher_calendrier($annee, $ce_mois, $ce_jour='') {
	if (isset($_GET['mode']) and !empty($_GET['mode'])) {
		$qstring = 'mode='.htmlspecialchars($_GET['mode']).'&amp;';
	} else {
		$qstring = '';
	}
	$jours_semaine = array(
		$GLOBALS['lang']['lu'],
		$GLOBALS['lang']['ma'],
		$GLOBALS['lang']['me'],
		$GLOBALS['lang']['je'],
		$GLOBALS['lang']['ve'],
		$GLOBALS['lang']['sa'],
		$GLOBALS['lang']['di']
	);
	$premier_jour = mktime('0', '0', '0', $ce_mois, '1', $annee);
	$jours_dans_mois = date('t', $premier_jour);
	$decalage_jour = date('w', $premier_jour-'1');
	$prev_mois =      $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.str2($ce_mois-1);
	if ($prev_mois == $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.'00') {
		$prev_mois =   $_SERVER['PHP_SELF'].'?'.$qstring.'d='.($annee-'1').'/'.'12';
	}
	$next_mois =      $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.str2($ce_mois+1);
	if ($next_mois == $_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.'13') {
		$next_mois =   $_SERVER['PHP_SELF'].'?'.$qstring.'d='.($annee+'1').'/'.'01';
	}

	// On verifie si il y a un ou des articles du jour dans le mois courant
	// $tableau contient les articles/comm/liens du mois. Il contient seulent un ID, donc un "oui" ou un "non" pour chaque jour.
	$tableau = array(); $all1 = array(); $all2 = array(); $all3 = array();
	$where = (!empty($_GET['mode'])) ? $_GET['mode'] : 'blog';
	if ( preg_match(   '#links#', $where) ) { $all1 = table_list_date($annee.$ce_mois, 1, 'public', 'links');}
	if ( preg_match(    '#blog#', $where) ) { $all2 = table_list_date($annee.$ce_mois, 1, 'public', 'articles'); }
	if ( preg_match('#comments#', $where) ) { $all3 = table_list_date($annee.$ce_mois, 1, 'public', 'commentaires'); }

	$tableau = (array_merge($all1, $all2, $all3));
	$jour_fichier = array();
	if (!empty($tableau)) {
		foreach ($tableau as $article) {
			if (substr($article[0], 0, 6) == $annee.$ce_mois) {
				$jour_fichier[]= substr($article[0], 6, 2);
			}
		}
		$jour_fichier = array_unique($jour_fichier);
	}
	$GLOBALS['calendrier'] = '<table id="calendrier">'."\n";
	$GLOBALS['calendrier'].= '<caption>';
	if ( $annee.$ce_mois > $GLOBALS['date_premier_message_blog']) {
		$GLOBALS['calendrier'].= '<a href="'.$prev_mois.'">&#171;</a>&nbsp;';
	}


	// Si on affiche un jour on ajoute le lien sur le mois
	if ($ce_jour) {
		$GLOBALS['calendrier'].= '<a href="'.$_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.$ce_mois.'">'.mois_en_lettres($ce_mois).' '.$annee.'</a>';
	} else {
		$GLOBALS['calendrier'].= mois_en_lettres($ce_mois).' '.$annee;
	}
	// On ne peut pas aller dans le futur
	if ( ($ce_mois != date('m')) || ($annee != date('Y')) ) {
		$GLOBALS['calendrier'].= '&nbsp;<a href="'.$next_mois.'">&#187;</a>';
	}
	$GLOBALS['calendrier'].= '</caption>'."\n";
	$GLOBALS['calendrier'].= '<tr><th><abbr>';
	$GLOBALS['calendrier'].= implode('</abbr></th><th><abbr>', $jours_semaine);
	$GLOBALS['calendrier'].= '</abbr></th></tr><tr>';
	if ($decalage_jour > 0) {
		for ($i = 0; $i < $decalage_jour; $i++) {
			$GLOBALS['calendrier'].=  '<td></td>';
		}
	}
	// Indique le jour consulte
	for ($jour = 1; $jour <= $jours_dans_mois; $jour++) {
		if ($jour == $ce_jour) {
			$class = ' class="active"';
		} else {
			$class = '';
		}
		if ( (isset($jour_fichier)) and in_array($jour, $jour_fichier) ) {
			$lien = '<a href="'.$_SERVER['PHP_SELF'].'?'.$qstring.'d='.$annee.'/'.$ce_mois.'/'.str2($jour).'">'.$jour.'</a>';
		} else {
			$lien = $jour;
		}
		$GLOBALS['calendrier'].= '<td'.$class.'>';
		$GLOBALS['calendrier'].= $lien;
		$GLOBALS['calendrier'].= '</td>';
		$decalage_jour++;
		if ($decalage_jour == 7) {
			$decalage_jour = 0;
			$GLOBALS['calendrier'].=  '</tr>';
			if ($jour < $jours_dans_mois) {
				$GLOBALS['calendrier'].= '<tr>';
			}
		}
	}
	if ($decalage_jour > 0) {
		for ($i = $decalage_jour; $i < 7; $i++) {
			$GLOBALS['calendrier'].= '<td> </td>';
		}
		$GLOBALS['calendrier'].= '</tr>';
	}
	$GLOBALS['calendrier'].= '</table>';
}

function encart_commentaires() {
	$tableau = liste_base_comms('', '', 'public', '1', 0, 5);
	if (isset($tableau)) {
		$listeLastComments = '<ul class="encart_lastcom">';
		foreach ($tableau as $i => $comment) {
			$comment['contenu_abbr'] = strip_tags($comment['bt_content']);
			$comment['article_titre'] = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $comment['bt_article_id'], 'return');
			if (strlen($comment['contenu_abbr']) >= 60) {
				$abstract = explode("|", wordwrap($comment['contenu_abbr'], 60, "|"), 2);
				$comment['contenu_abbr'] = $abstract[0]."…";
			}
			$comment['article_lien'] = get_blogpath($comment['bt_article_id']).'#'.article_anchor($comment['bt_id']);
			$listeLastComments .= '<li title="'.date_formate($comment['bt_id']).'"><b>'.$comment['bt_author'].'</b> '.$GLOBALS['lang']['sur'].' <b>'.$comment['article_titre'].'</b><br/><a href="'.$comment['article_lien'].'">'.$comment['contenu_abbr'].'</a>'.'</li>';
		}
		$listeLastComments .= '</ul>';
		return $listeLastComments;
	} else {
		return $GLOBALS['lang']['no_comments'];
	}
}

function encart_categories() {
	if ($GLOBALS['activer_categories'] == '1') {
		$liste = list_all_tags('articles');
		$uliste = '<ul>'."\n";
		foreach($liste as $tag) {
			$tagurl = urlencode(trim($tag['tag']));
			$uliste .= "\t".'<li><a href="'.$_SERVER['PHP_SELF'].'?tag='.$tagurl.'">'.ucfirst($tag['tag']).'</a></li>'."\n";
		}
		$uliste .= '</ul>'."\n";
		return $uliste;
	}
}

function lien_pagination() {
	if (isset($GLOBALS['nb_elements_client_side'])) {
		$nb = $GLOBALS['nb_elements_client_side']['nb'];
		$nb_page = $GLOBALS['nb_elements_client_side']['nb_page'];

	} else {
		$nb = 1;
		$nb_page = 1;
	}
	$page_courante = (isset($_GET['p']) and is_numeric($_GET['p'])) ? $_GET['p'] : 0;
	$qstring = remove_url_param('p');

	if ($page_courante <=0) {
		$lien_precede = '&#8826; '.$GLOBALS['lang']['label_precedent'];
		$lien_suivant = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p=1">'.$GLOBALS['lang']['label_suivant'].' &#8827;</a>';
		if ($nb < $nb_page) { // évite de pouvoir aller dans la passé s’il y a moins de 10 posts
			$lien_suivant = $GLOBALS['lang']['label_suivant'].' &#8827;';
		}

	}
	elseif ($nb < $nb_page) { // évite de pouvoir aller dans l’infini en arrière dans les pages, nottament pour les robots.
		$lien_precede = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante-1).'">&#8826; '.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = $GLOBALS['lang']['label_suivant'].' &#8827;';
	}
	else {
		$lien_precede = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante-1).'">&#8826; '.$GLOBALS['lang']['label_precedent'].'</a>';
		$lien_suivant = '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?'.$qstring.'&amp;p='.($page_courante+1).'">'.$GLOBALS['lang']['label_suivant'].' &#8827;</a>';
	}


	return '<p class="pagination">'.$lien_precede.' – '.$lien_suivant.'</p>';

}

function liste_tags_article($billet, $html_link) {
	if (!empty($billet['bt_categories'])) {
		$tag_list = explode(',', $billet['bt_categories']);
		$nb_tags = sizeof($tag_list);
		$liste = '';
		if ($html_link == 1) {
			foreach($tag_list as $tag) {
				$tag = trim($tag);
				$tagurl = urlencode(trim($tag));
				$liste .= '<a href="'.$_SERVER['PHP_SELF'].'?tag='.$tagurl.'">'.$tag.'</a>, ';
			}
			$liste = trim($liste, ', ');
		} else {
			foreach($tag_list as $tag) {
				$tag = trim($tag);
				$tag = diacritique($tag, 0, 0);
				$liste .= $tag.', ';
			}
			$liste = trim($liste, ', ');
		}
	} else {
		$liste = '';
	}
	return $liste;
}

// AFFICHE LA LISTE DES ARTICLES, DANS LA PAGE ADMIN
function afficher_liste_articles($tableau) {
	if (!empty($tableau)) {
		$i = 0;
		$out = '<table id="billets">'."\n";
		$out .= '<tr>';
			// LEGENDE DES COLONNES
			$out .= '<th>'.$GLOBALS['lang']['label_titre'].'</th>'."\n";
			$out .= '<th>'.$GLOBALS['lang']['label_date'].'</th>'."\n";
			$out .= '<th>'.$GLOBALS['lang']['label_time'].'</th>'."\n";
			$out .= '<th>&nbsp;</th>'."\n";
			$out .= '<th>&nbsp;</th>'."\n";
		$out .= '</tr>';

		foreach ($tableau as $article) {
			// ICONE SELON STATUT
			$class = ($article['bt_statut'] == '1') ? 'on' : 'off';
			$out .= '<tr>'."\n";
			// TITRE
			$out .= '<td class="titre">';
			$out .= '<a class="'.$class.'" href="ecrire.php?post_id='.$article['bt_id'].'" title="'.$article['bt_abstract'].'">'.$article['bt_title'].'</a>';
			$out .= '</td>'."\n";
			// DATE
			$out .= '<td><a class="black" href="'.$_SERVER['PHP_SELF'].'?filtre='.substr($article['bt_date'],0,8).'">'.date_formate($article['bt_date']).'</a></td>'; 
			$out .= '<td>'.heure_formate($article['bt_date']).'</td>'."\n";
			// NOMBRE COMMENTS
			if ($article['bt_nb_comments'] == 1) {
				$texte = $article['bt_nb_comments'].' '.$GLOBALS['lang']['label_commentaire'];
			} elseif ($article['bt_nb_comments'] > 1) {
				$texte = $article['bt_nb_comments'].' '.$GLOBALS['lang']['label_commentaires'];
			} else {
				$texte = '&nbsp;';
			}
			$out .= '<td class="nb-commentaires"><a href="commentaires.php?post_id='.$article['bt_id'].'">'.$texte.'</a></td>'."\n";
			// STATUT
			if ( $article['bt_statut'] == '1') {
				$out .= '<td class="lien"><a href="'.$article['bt_link'].'">'.$GLOBALS['lang']['lien_article'].'</a></td>';
			} else {
				$out .= '<td class="lien"><a href="'.$article['bt_link'].'">'.$GLOBALS['lang']['preview'].'</a></td>';
			}
			$out .= '</tr>'."\n";
			$i++;
		}

		$out .= '</table>'."\n\n";
		echo $out;
	} else {
		echo info($GLOBALS['lang']['note_no_article']);
	}
}

