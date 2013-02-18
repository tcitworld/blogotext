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

/*
	This file is called by the drag'n'drop script. It is an underground working script,
	It is not intended to be called directly in your browser.

*/

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

operate_session();
$begin = microtime(TRUE);

$GLOBALS['liste_fichiers'] = open_file_db_fichiers($GLOBALS['fichier_liste_fichiers']);

$liste_fileid = array();
foreach ($GLOBALS['liste_fichiers'] as $key => $file) {
	$liste_fileid[] = $file['bt_id'];
}

if (isset($_FILES['fichier'])) {

	$ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
	$filename = pathinfo($_FILES['fichier']['name'], PATHINFO_FILENAME);
	$id = date('YmdHis');
	$time = time();

	while (in_array($id, $liste_fileid)) {
		$time--;
		$id = date('YmdHis', $time);
	}


	$fichier = array (
		'bt_id' => $id,
		'bt_type' => detection_type_fichier($ext),
		'bt_fileext' => $ext,
		'bt_filesize' => $_FILES['fichier']['size'],
		'bt_filename' => diacritique(htmlspecialchars($filename), '' , '0').'.'.$ext,
		'bt_content' => '',
		'bt_wiki_content' => '',
		'bt_checksum' => '',
		'bt_statut' => (isset($_POST['statut']) and $_POST['statut'] == 'on') ? '0' : '1',
	);

	bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);

	echo '
<div class="success">
	<p>
		Your file: '.$_FILES['fichier']['name'].' has been successfully received. (<a class="lien lien-edit" href="fichiers.php?file_id='.$fichier['bt_id'].'&amp;edit">Lien</a>)<br/>
		Type: '.$_FILES['fichier']['type'].'<br/>
		Size: '.taille_formate($_FILES['fichier']['size']).'
	</p>
</div>';
exit;
} else {
	echo '<div class="failure">An error occurred';
	echo '</div>';
}

