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

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

// open base
$GLOBALS['db_handle'] = open_base($GLOBALS['db_location']);


// TRAITEMENT
$erreurs_form = array();
if (isset($_POST['_verif_envoi'])) {
	$billet = init_post_article();
	$erreurs_form = valider_form_billet($billet);
	if (empty($erreurs_form)) {
		traiter_form_billet($billet);
	}
}

// RECUP INFOS ARTICLE SI DONNÉE
$post = '';
$article_id = '';
if (isset($_GET['post_id'])) {
	$article_id = htmlspecialchars($_GET['post_id']);
	$posts = liste_base_articles('id', $article_id, 'admin', '', 0, '');
//	echo '<pre>'; print_r($posts); die();
	if (isset($posts[0])) $post = $posts[0];
}

// TITRE PAGE
if ( !empty($post) ) {
	$titre_ecrire_court = $GLOBALS['lang']['titre_maj'];
	$titre_ecrire = $titre_ecrire_court.' : '.$post['bt_title'];
} else {
	$post = '';
	$titre_ecrire_court = $GLOBALS['lang']['titre_ecrire'];
	$titre_ecrire = $titre_ecrire_court;
}

// DEBUT PAGE
afficher_top($titre_ecrire);
echo '<div id="top">'."\n";
afficher_msg($titre_ecrire_court);
afficher_menu(pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME));
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div class="reminder"><span>'.'Pensez à enregistrer votre article.'.'</span></div>'."\n";
// SUBNAV
if ($post != '') {
	echo '<div id="subnav">'."\n";
		echo '<p id="mode"><a href="commentaires.php?post_id='.$article_id.'" id="lien-comments">'.ucfirst(nombre_commentaires($post['bt_nb_comments'])).'</a></p>'."\n";
		echo '<p id="voir-en-ligne"><a href="'.$post['bt_link'].'">'.$GLOBALS['lang']['lien_article'].'</a></p>'."\n";
	echo '</div>'."\n";
}
 	
echo '<div id="page">'."\n";

// EDIT
if ($post != '') {
	apercu($post);
}
afficher_form_billet($post, $erreurs_form);

echo '<script type="text/javascript">';
echo js_resize(0);
echo js_inserttag(0);
echo js_addcategories(0);
echo js_html5_str_pad_time(0);
echo '</script>';

footer('', $begin);
?>
