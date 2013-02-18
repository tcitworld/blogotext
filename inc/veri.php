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

function valider_form_commentaire($commentaire, $captcha, $valid_captcha, $mode) {
	$erreurs = array();
	if (isset($_GET['post_id'])) {
		if (!strlen(trim($commentaire['bt_author'])))  {
				$erreurs[] = $GLOBALS['lang']['err_comm_auteur'];
		}
	}
	if (!isset($_GET['post_id'])) {
		if (!strlen(trim($commentaire['bt_author']))) {
			$erreurs[] = $GLOBALS['lang']['err_comm_auteur'];
		}
		if ($commentaire['bt_author'] == $GLOBALS['auteur'] and empty($_SESSION['rand_sess_id'])) {
			$erreurs[] = $GLOBALS['lang']['err_comm_auteur_name'];
		}
	}

	if (!empty($commentaire['bt_email']) or $GLOBALS['require_email'] == 1) {
		if (!preg_match('#^[-_a-zA-Z0-9!%+~\'*"\[\]{}.=]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$#i', trim($commentaire['bt_email'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_email'] ;
		}
	}
	if (!strlen(trim($commentaire['bt_content'])) or $commentaire['bt_content'] == "<p></p>") {
		$erreurs[] = $GLOBALS['lang']['err_comm_contenu'];
	}
	if ( (!preg_match('/\d{14}/',$commentaire['bt_article_id']))
		or !is_numeric($commentaire['bt_article_id']) ) {
		$erreurs[] = $GLOBALS['lang']['err_comm_article_id'];
	}

	if (trim($commentaire['bt_webpage']) != "") {
		if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($commentaire['bt_webpage'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
		}
	}
	if ($mode != 'admin') {
		if ( $captcha != $valid_captcha or $captcha != is_numeric($captcha)) {
			$erreurs[] = $GLOBALS['lang']['err_comm_captcha'];
		}
	}
	return $erreurs;
}

function valider_form_billet($billet) {
	$date = decode_id($billet['bt_id']);
	$erreurs = array();
	if (!strlen(trim($billet['bt_title']))) {
		$erreurs[] = $GLOBALS['lang']['err_titre'];
	}
//	if (!strlen(trim($billet['bt_abstract']))) {
//		$erreurs[] = $GLOBALS['lang']['err_chapo'];
//	}
	if (!strlen(trim($billet['bt_content']))) {
		$erreurs[] = $GLOBALS['lang']['err_contenu'];
	}
	if (!preg_match('/\d{4}/',$date['annee'])) {
		$erreurs[] = $GLOBALS['lang']['err_annee'];
	}
	if ( (!preg_match('/\d{2}/',$date['mois'])) or ($date['mois'] > '12') ) {
		$erreurs[] = $GLOBALS['lang']['err_mois'];
	}
	if ( (!preg_match('/\d{2}/',$date['jour'])) or ($date['jour'] > date('t', mktime(0, 0, 0, $date['mois'], 1, $date['annee'])))  ) {
		$erreurs[] = $GLOBALS['lang']['err_jour'];
	}
	if ( (!preg_match('/\d{2}/',$date['heure'])) or ($date['heure'] > 23) ) {
		$erreurs[] = $GLOBALS['lang']['err_heure'];
	}
	if ( (!preg_match('/\d{2}/',$date['minutes'])) or ($date['minutes'] > 59) ) {
		$erreurs[] = $GLOBALS['lang']['err_minutes'];
	}
	if ( (!preg_match('/\d{2}/',$date['secondes'])) or ($date['secondes'] > 59) ) {
		$erreurs[] = $GLOBALS['lang']['err_secondes'];
	}
	return $erreurs;
}

function valider_form_preferences() {
	$erreurs = array();
	if (!strlen(trim($_POST['auteur']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_auteur'];
	}
	if ($GLOBALS['require_email'] == 1) { 
		if (!preg_match('#^[\w.+~\'*-]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($_POST['email']))) {
			$erreurs[] = $GLOBALS['lang']['err_prefs_email'] ;
		}
	}
	if (!preg_match('#^(https?://).*/$#', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
	}
	if (!strlen(trim($_POST['identifiant']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
	}
	if ( ($_POST['identifiant']) !=$GLOBALS['identifiant'] and (!strlen($_POST['mdp'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_mdp'];
	}
	if ( (strlen(trim($_POST['mdp']))) and (ww_hach_sha($_POST['mdp'], $GLOBALS['salt']) != $GLOBALS['mdp']) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_oldmdp'];
	}
	if ( (strlen($_POST['mdp'])) and (strlen($_POST['mdp_rep']) < '6') ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_mdp'];
	}
	if ( (strlen($_POST['mdp_rep'])) and (!strlen($_POST['mdp'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_newmdp'] ;
	}
	return $erreurs;
}

function valider_form_image($image) {
	$erreurs = array();
	if (!isset($_POST['is_it_edit'])) { // si nouveau fichier, test sur fichier entrant

		if (isset($_FILES['fichier'])) { // new file provided by upload

			if (($_FILES['fichier']['error'] == UPLOAD_ERR_INI_SIZE) or ($_FILES['fichier']['error'] == UPLOAD_ERR_FORM_SIZE)) {
				$erreurs[] = 'Fichier trop gros';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_PARTIAL) {
				$erreurs[] = 'dépot interrompu';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_NO_FILE) {
				$erreurs[] = 'aucun fichier déposé';
			} if (!in_array(strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION)), $GLOBALS['files_ext']['image'])) {
				$erreurs[] = 'Ce n’est pas une image. Pour des fichiers autres, utilisez le <a href="fichiers.php">formulaire des fichiers</a>.';
			}
		}
		elseif (isset($_POST['fichier-url']) ) { // new image provided by URL download
			if ( !empty($_POST['fichier-url']) ) {
				$erreurs[] = 'aucun fichier déposé';
			}
			if (preg_match('#(\.png|\.gif|\.jpeg|\.jpg)$#i', $_POST['fichier-url'])) {
				$erreurs[] = 'Ce n’est pas une image. Pour des fichiers autres, utilisez le <a href="fichiers.php">formulaire des fichiers</a>.';
			}
		}


	} else { // on edit
		if ($image['bt_filename'] == '') {
			$erreurs[] = 'nom de fichier invalide';
		}

	}
	return $erreurs;
}

function valider_form_fichier($fichier) {
	$erreurs = array();
	if (!isset($_POST['is_it_edit'])) { // si nouveau fichier, test sur fichier entrant

		if (isset($_FILES['fichier'])) {
			if (($_FILES['fichier']['error'] == UPLOAD_ERR_INI_SIZE) or ($_FILES['fichier']['error'] == UPLOAD_ERR_FORM_SIZE)) {
				$erreurs[] = 'Fichier trop gros';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_PARTIAL) {
				$erreurs[] = 'dépot interrompu';
			} elseif ($_FILES['fichier']['error'] == UPLOAD_ERR_NO_FILE) {
				$erreurs[] = 'aucun fichier déposé';
			}
		}
		elseif (isset($_POST['ficheir-url'])) {
			if ( !empty($_POST['fichier-url']) ) {
				$erreurs[] = 'aucun fichier déposé';
			}
		}

	} else { // on edit
		if ($fichier['bt_filename'] == '') {
			$erreurs[] = 'nom de fichier invalide';
		}
	}
	return $erreurs;
}

function valider_form_link() {
	$erreurs = array();
	if (!preg_match('#\d{14}#', $_POST['bt_id'])) {
		$erreurs[] = 'Erreur id.';
	}
	return $erreurs;
}

?>
