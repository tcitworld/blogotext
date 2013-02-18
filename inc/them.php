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

// TEMPLATE VARS
/*
 * Vars used in them files, aimed to get
 * replaced with some specific data
 *
 */
$GLOBALS['boucles'] = array(
	'posts' => 'BOUCLE_posts',
	'commentaires' => 'BOUCLE_commentaires',
);

$GLOBALS['balises'] = array(
	'charset' => '{charset}',
	'version' => '{version}',
	'app_name' => '{app_name}',
	'style' => '{style}',
	'racine_du_site' => '{racine_du_site}',
	'rss' => '{rss}',
	'rss_comments' => '{rss_comments}',
	// Navigation
	'pagination' => '{pagination}',
	// Blog
	'blog_nom' => '{blog_nom}',
	'blog_description' => '{blog_description}',
	'blog_auteur' => '{blog_auteur}',
	'blog_email' => '{blog_email}',
	// Formulaires
	'form_recherche' => '{recherche}',
	'form_calendrier' => '{calendrier}',
	'form_commentaire' => '{formulaire_commentaire}',
	// Encarts
	'comm_encart' => '{commentaires_encart}',
	'cat_encart' => '{categories_encart}',

	// Article
	'article_titre' => '{article_titre}',
	'article_chapo' => '{article_chapo}',
	'article_contenu' => '{article_contenu}',
	'article_heure' => '{article_heure}',
	'article_date' => '{article_date}',
	'article_motscles' => '{article_motscles}',
	'article_lien' => '{article_lien}',
	'article_tags' => '{article_tags}',
	'article_tags_plain' => '{article_tags_plain}',
	'nb_commentaires' => '{nombre_commentaires}',

	// Commentaire
	'commentaire_auteur' => '{commentaire_auteur}',
	'commentaire_auteur_lien' => '{commentaire_auteur_lien}',
	'commentaire_contenu' => '{commentaire_contenu}',
	'commentaire_heure' => '{commentaire_heure}',
	'commentaire_date' => '{commentaire_date}',
	'commentaire_email' => '{commentaire_email}',
	'commentaire_webpage' => '{commentaire_webpage}',
	'commentaire_anchor' => '{commentaire_ancre}', // the id="" content
	'commentaire_lien' => '{commentaire_lien}',
	'commentaire_gravatar' => '{commentaire_gravatar_link}', // only contains http://2.gravatar.com/avatar/md5($email)

	// Liens
	'lien_auteur' => '{lien_auteur}',
	'lien_titre' => '{lien_titre}',
	'lien_url' => '{lien_url}',
	'lien_date' => '{lien_date}',
	'lien_heure' => '{lien_heure}',
	'lien_description' => '{lien_description}',
	'lien_permalink' => '{lien_permalink}',
	'lien_id' => '{lien_id}',
);

//Commentaires
function conversions_theme($texte) {
	if (isset($GLOBALS['charset'])) {			$texte = str_replace($GLOBALS['balises']['charset'], $GLOBALS['charset'], $texte); }
	if (isset($GLOBALS['version'])) {			$texte = str_replace($GLOBALS['balises']['version'], $GLOBALS['version'], $texte); }
	if (isset($GLOBALS['nom_application'])) {	$texte = str_replace($GLOBALS['balises']['app_name'], $GLOBALS['nom_application'], $texte); }
	if (isset($GLOBALS['nom_du_site'])) {		$texte = str_replace($GLOBALS['balises']['blog_nom'], $GLOBALS['nom_du_site'], $texte); }
	if (isset($GLOBALS['theme_style'])) {		$texte = str_replace($GLOBALS['balises']['style'], $GLOBALS['theme_style'], $texte); }
	if (isset($GLOBALS['description'])) {		$texte = str_replace($GLOBALS['balises']['blog_description'], $GLOBALS['description'], $texte); }
	if (isset($GLOBALS['racine'])) {				$texte = str_replace($GLOBALS['balises']['racine_du_site'], $GLOBALS['racine'], $texte); }
	if (isset($GLOBALS['auteur'])) {				$texte = str_replace($GLOBALS['balises']['blog_auteur'], $GLOBALS['auteur'], $texte); }
	if (isset($GLOBALS['email'])) {				$texte = str_replace($GLOBALS['balises']['blog_email'], $GLOBALS['email'], $texte); }

	if ( !isset($_GET['d']) and !isset($_GET['id']) ) { $texte = str_replace($GLOBALS['balises']['pagination'], lien_pagination(), $texte); }
		else { $texte = str_replace($GLOBALS['balises']['pagination'], '', $texte); }

	// Formulaires
	$texte = str_replace($GLOBALS['balises']['form_recherche'], moteur_recherche(''), $texte) ;
	if (isset($GLOBALS['calendrier'])) {				$texte = str_replace($GLOBALS['balises']['form_calendrier'], $GLOBALS['calendrier'], $texte); }
	if (isset($GLOBALS['form_commentaire'])) {		$texte = str_replace($GLOBALS['balises']['form_commentaire'], $GLOBALS['form_commentaire'], $texte); }
		else {													$texte = str_replace($GLOBALS['balises']['form_commentaire'], '', $texte);}
	if (isset($GLOBALS['rss'])) {							$texte = str_replace($GLOBALS['balises']['rss'], $GLOBALS['rss'], $texte) ; }
		else {													$texte = str_replace($GLOBALS['balises']['rss'], '', $texte); }
	if (isset($GLOBALS['balises']['comm_encart'])) {$texte = str_replace($GLOBALS['balises']['comm_encart'], encart_commentaires(), $texte);}
	if (isset($GLOBALS['balises']['cat_encart'])) {	$texte = str_replace($GLOBALS['balises']['cat_encart'], encart_categories(), $texte);}
	if (isset($GLOBALS['rss_comments'])) {				$texte = str_replace($GLOBALS['balises']['rss_comments'], $GLOBALS['rss_comments'], $texte);}

	return $texte;
}


// Commentaire
function conversions_theme_commentaire($texte, $commentaire) {
	if (isset($commentaire['bt_content'])) {	$texte = str_replace($GLOBALS['balises']['commentaire_contenu'], $commentaire['bt_content'], $texte); }
	if (isset($commentaire['bt_id'])) {			$texte = str_replace($GLOBALS['balises']['commentaire_date'], date_formate($commentaire['bt_id']), $texte); }
	if (isset($commentaire['bt_id'])) {			$texte = str_replace($GLOBALS['balises']['commentaire_heure'], heure_formate($commentaire['bt_id']), $texte); }
	if (isset($commentaire['bt_email'])) {		$texte = str_replace($GLOBALS['balises']['commentaire_email'], $commentaire['bt_email'], $texte); }
	if (isset($commentaire['bt_email'])) {		$texte = str_replace($GLOBALS['balises']['commentaire_gravatar'], 'http://2.gravatar.com/avatar/'.md5($commentaire['bt_email']), $texte); }
	if (isset($commentaire['bt_author'])) {	$texte = str_replace($GLOBALS['balises']['commentaire_auteur_lien'], $commentaire['auteur_lien'], $texte); }
	if (isset($commentaire['bt_author'])) {	$texte = str_replace($GLOBALS['balises']['commentaire_auteur'], $commentaire['bt_author'], $texte); }
	if (isset($commentaire['bt_webpage'])) {	$texte = str_replace($GLOBALS['balises']['commentaire_webpage'], $commentaire['bt_webpage'], $texte); }
	if (isset($commentaire['anchor'])) {		$texte = str_replace($GLOBALS['balises']['commentaire_anchor'], $commentaire['anchor'], $texte); }
	if (isset($commentaire['bt_link'])) {		$texte = str_replace($GLOBALS['balises']['commentaire_lien'], $commentaire['bt_link'], $texte); }
	return $texte;
}

// Article
function conversions_theme_article($texte, $billet) {
	if (isset($billet['bt_url_rss_comments'])) { $texte = str_replace($GLOBALS['balises']['rss_comments'], $billet['bt_url_rss_comments'], $texte); }
	if (isset($billet['bt_title'])) {		$texte = str_replace($GLOBALS['balises']['article_titre'], $billet['bt_title'], $texte); }
	if (isset($billet['bt_abstract'])) {	$texte = str_replace($GLOBALS['balises']['article_chapo'], $billet['bt_abstract'], $texte); }
	if (isset($billet['bt_content'])) {		$texte = str_replace($GLOBALS['balises']['article_contenu'], $billet['bt_content'], $texte); }
	if (isset($billet['bt_date'])) {			$texte = str_replace($GLOBALS['balises']['article_date'], date_formate($billet['bt_date']), $texte); }
	if (isset($billet['bt_date'])) {			$texte = str_replace($GLOBALS['balises']['article_heure'], heure_formate($billet['bt_date']), $texte); }
	if (isset($billet['bt_keywords'])) {	$texte = str_replace($GLOBALS['balises']['article_motscles'], $billet['bt_keywords'], $texte); }
	// comments closed (globally or only for this article) and no comments => say « comments closed »
	if ( ($billet['bt_allow_comments'] == 0 or $GLOBALS['global_com_rule'] == 1 ) and $billet['bt_nb_comments'] == 0 ) { $texte = str_replace($GLOBALS['balises']['nb_commentaires'], $GLOBALS['lang']['note_comment_closed'], $texte); }
	// comments open OR ( comments closed AND comments exists ) => say « nb comments ».
	if ( !($billet['bt_allow_comments'] == 0 or $GLOBALS['global_com_rule'] == 1 ) or $billet['bt_nb_comments'] != 0 ) { $texte = str_replace($GLOBALS['balises']['nb_commentaires'], nombre_commentaires($billet['bt_nb_comments']), $texte); }
	if (isset($billet['bt_auteur'])) {		$texte = str_replace($GLOBALS['balises']['commentaire_auteur'], $billet['bt_auteur'], $texte); }
	if (isset($billet['lien'])) {				$texte = str_replace($GLOBALS['balises']['article_lien'], $billet['lien'], $texte); }
	if (isset($billet['bt_categories'])) {	$texte = str_replace($GLOBALS['balises']['article_tags'], liste_tags_article($billet, '1'), $texte); }
	if (isset($billet['bt_categories'])) {	$texte = str_replace($GLOBALS['balises']['article_tags_plain'], liste_tags_article($billet, '0'), $texte); }
	return $texte;
}

// Liens
function conversions_theme_lien($texte, $lien) {
	if (isset($GLOBALS['rss_links'])) {	$texte = str_replace($GLOBALS['balises']['rss_links'], $GLOBALS['rss_links'], $texte); }
	if (isset($lien['bt_author'])) {		$texte = str_replace($GLOBALS['balises']['lien_auteur'], $lien['bt_author'], $texte); }
	if (isset($lien['bt_title'])) {		$texte = str_replace($GLOBALS['balises']['lien_titre'], $lien['bt_title'], $texte); }
	if (isset($lien['bt_link'])) {		$texte = str_replace($GLOBALS['balises']['lien_url'], $lien['bt_link'], $texte); }
	if (isset($lien['bt_id'])) {			$texte = str_replace($GLOBALS['balises']['lien_date'], date_formate($lien['bt_id']), $texte); }
	if (isset($lien['bt_id'])) {			$texte = str_replace($GLOBALS['balises']['lien_heure'], heure_formate($lien['bt_id']), $texte); }
	if (isset($lien['bt_id'])) {			$texte = str_replace($GLOBALS['balises']['lien_permalink'], $lien['bt_id'], $texte); }
	if (isset($lien['bt_content'])) {	$texte = str_replace($GLOBALS['balises']['lien_description'], $lien['bt_content'], $texte); }
	if (isset($lien['ID'])) {				$texte = str_replace($GLOBALS['balises']['lien_id'], $lien['ID'], $texte); }
	return $texte;
}


function charger_template($fichier_theme, $balise, $renvoi) {
	if ($theme_page = file_get_contents($fichier_theme)) {
		if ($renvoi == 'liste') {
			$template_liste = parse_theme($theme_page, $balise);
			return $template_liste;
		} elseif ($renvoi == 'debut') {
			$balise_debut = strpos($theme_page, '{'.$balise.'}');
			$debut = conversions_theme(substr($theme_page, 0, $balise_debut));
			return $debut;
		} elseif ($renvoi == 'fin') {
			$balise_fin = strpos($theme_page, '{/'.$balise.'}') + strlen($balise) + 3;
			$fin = conversions_theme(substr($theme_page, $balise_fin));
			return $fin;
		}
	} else {
		echo 'Fichier theme liste introuvable ou illisible';
	}
}


function parse_theme($fichier, $balise) {
	if (isset($fichier)) {
		if (strpos($fichier, '{'.$balise.'}') !== FALSE) {
			$sizeitem = strlen('{'.$balise.'}');
			$debut = strpos($fichier, '{'.$balise.'}') + $sizeitem;
			$fin = strpos($fichier, '{/'.$balise.'}');
			$lenght = $fin - $debut;
			$return = substr($fichier, $debut, $lenght); 
			return $return;
		} else {
			return '';
		}
	} else {
		erreur('Impossible de lire le fichier');
	}
}


function afficher_index($tableau) {
	if ($debut = charger_template($GLOBALS['theme_liste'], $GLOBALS['boucles']['posts'], 'debut') and
		($fin = charger_template($GLOBALS['theme_liste'], $GLOBALS['boucles']['posts'], 'fin')) ) {
		echo $debut; // affichage du début
		if (!empty($tableau)) {
			liste_post($tableau); // affichage des boucles
		} else {
			erreur($GLOBALS['lang']['note_no_article']);
		}
	echo $fin; // affichage de la fin
	}
}


function liste_post($liste) { // la liste contenir soit des articles, soit des liens, bref n'importe quoi, ou un mix de tout ça.
	foreach ($liste as $cle => $post) {
		switch($post['bt_type']) {
			case 'article':
				$template = file_get_contents($GLOBALS['theme_post_artc']);
				$liste_posts = conversions_theme_article($template, $post);
				break;
			case 'link':
			case 'note':
				$template = file_get_contents($GLOBALS['theme_post_link']);
				$liste_posts = conversions_theme_lien($template, $post);
				break;
			case 'comment':
				$template = file_get_contents($GLOBALS['theme_post_comm']);
				$liste_posts = conversions_theme_commentaire($template, $post);
				break;
			default:
				$liste_posts = ' [nothing-them.php l.243.]  ';
		}
		echo $liste_posts;
	}
}


// only used by the main page of the blog (not on admin)
function afficher_article($id) {
	// 'admin' connected is allowed to see draft articles, but not 'public'. Same for article posted with a date in the future.
	if (!empty($_SESSION['rand_sess_id'])) {
		$billets = liste_base_articles('id', $id, 'admin', '', 0, 1);
	} else {
		$billets = liste_base_articles('id', $id, 'public', 1, 0, 1);
	}
	if (isset($billets[0])) {
		$billet = $billets[0];
	} else {
		$billet = NULL;
	}

	if ( !empty($billet) ) {
		// TRAITEMENT
		$erreurs_form = array();
		if (isset($_POST['_verif_envoi']) and ($billet['bt_allow_comments'] == '1' )) {
			// COMMENT POST INIT
			$comment = init_post_comment($id, 'public');
			if (isset($_POST['enregistrer'])) {
				$erreurs_form = valider_form_commentaire($comment, $_POST['captcha'], ($_SESSION['captx']+$_SESSION['capty']), 'public');
			}
		}
		if (empty($erreurs_form)) {
			afficher_form_commentaire($id, 'public');
			if (isset($_POST['enregistrer']) and empty($_POST['email-adress'])) {
				traiter_form_commentaire($comment, 'public');
			}
		} else {
			afficher_form_commentaire($id, 'public', $erreurs_form);
		}
		$theme_page = file_get_contents($GLOBALS['theme_article']);
		$debut = charger_template($GLOBALS['theme_article'], $GLOBALS['boucles']['commentaires'], 'debut');
		$template_comments = charger_template($GLOBALS['theme_article'], $GLOBALS['boucles']['commentaires'], 'liste');
		$fin = charger_template($GLOBALS['theme_article'], $GLOBALS['boucles']['commentaires'], 'fin');
		echo conversions_theme_article($debut, $billet);
		$commentaires = liste_base_comms('assos_art', $id, '', '1', 0, '');

		if (!empty($commentaires)) {
			foreach ($commentaires as $element) {
				$comm = conversions_theme_commentaire($template_comments, $element);
				echo $comm;
			}
		}
		echo conversions_theme($fin);

	} else {
		afficher_index(NULL);
	}
}

