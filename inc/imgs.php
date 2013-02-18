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
 * Notez que la liste des fichiers n’est pas intrégrée dans la base de données SQLITE.
 * Ceci pour une raison simple : les fichiers (contrairement aux articles et aux commentaires)
 *  sont indépendants. Utiliser une BDD pour les lister est beaucoup moins rapide
 *  qu’utiliser un fichier txt normal.
 * Pour le stockage, j’utilise un tableau PHP que j’enregistre directement dans un fichier :
 *  base64_encode(serialize($tableau)) # un peu pompée sur Shaarli, by Sebsauvage.
 * 
 */


/* -----------------------------------------------------------------
   FONCTIONS POUR GESTION DES FICHIER, (ou images)
   ---------------------------------------------------------------*/

/*
   À partir du chemin vers une image, trouve la miniature correspondante.
   retourne le chemin de la miniature.
   le nom d’une image est " image.ext ", celui de la miniature sera " image-thb.ext "
*/
function chemin_thb_img($filepath) {
	$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
	// prend le nom, supprime l’extension et le point, ajoute le " -thb.jpg ", puisque les miniatures de BT sont en JPG.
	$miniature = substr($filepath, 0, -(strlen($ext)+1)).'-thb.jpg'; // "+1" is for the "." between name and ext.
	return $miniature;
}


/*
	Pour les vignettes dans le mur d’images.
	Avec le tableau contenant les images, retournes le HTML du mur d’image.
*/
function afficher_liste_images($images) {
	$dossier = $GLOBALS['racine'].$GLOBALS['dossier_images'];
	$dossier_relatif = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
	$out = '';
	if (!empty($images)) {
		$out .= '<div class="image-wall">'."\n";

		foreach ($images as $image) {
			if ( ($miniature = chemin_thb_img($dossier_relatif.'/'.$image['bt_filename'])) and file_exists($miniature) ) {
				$img_src = $miniature;
			} else {
				$img_src = $dossier_relatif.'/'.$image['bt_filename'];
			}

			$out .= '<div class="image_bloc">'."\n";
				$description = (empty($image['bt_content'])) ? $image['bt_filename'] : $image['bt_content'];
				$out .= "\t".'<span class="spantop black">';
					$out .= '<a class="lien lien-edit" href="fichiers.php?file_id='.$image['bt_id'].'&amp;edit">&nbsp;</a>';
					$out .= '<a class="lien lien-voir" href="'.$dossier.'/'.$image['bt_filename'].'">&nbsp;</a>';
					$out .= '<a class="lien lien-supr" href="fichiers.php?file_id='.$image['bt_id'].'&amp;suppr&amp;av='.time().'&amp;type=img">&nbsp;</a>';
				$out .= '</span>'."\n";
				$out .= "\t".'<span class="spanmiddle black"><span> '.date_formate($image['bt_id'], '0').', '.heure_formate($image['bt_id']).' </span></span>'."\n";
				$out .= "\t".'<span class="spanbottom black"><span> '.$description.' </span></span>'."\n";
				$out .= "\t".'<img src="'.$img_src.'" id="'.$image['bt_id'].'" alt="'.$image['bt_filename'].'" />'."\n";
			$out .= '</div>'."\n\n";
		}
		$out .= '</div>';
	}
	echo $out;
}


// filepath : image to create a thumbnail from

function create_thumbnail($filepath) {
	// if GD library is loaded by PHP, do. Else, do nothing (thumbnails are not required)
	if (extension_loaded('gd')) {
		$maxwidth = '160';
		$maxheight = '160';
		$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

		// largeur et hauteur maximale
		// Cacul des nouvelles dimensions
		list($width_orig, $height_orig) = getimagesize($filepath);
		if ($width_orig == 0 or $height_orig == 0) return;
		if ($maxwidth and ($width_orig < $height_orig)) {
			$maxwidth = ($maxheight / $height_orig) * $width_orig;
		} else {
			$maxheight = ($maxwidth / $width_orig) * $height_orig;
		}

		// open file with correct format
		$thumb = imagecreatetruecolor($maxwidth, $maxheight);
		imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
		switch ($ext) {
			case 'jpeg':
			case 'jpg': $image = imagecreatefromjpeg($filepath); break;
			case 'png': $image = imagecreatefrompng($filepath); break;
			case 'gif': $image = imagecreatefromgif($filepath); break;
			default : return;
		}

		// resize
		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $maxwidth, $maxheight, $width_orig, $height_orig);
		imagedestroy($image);

		// enregistrement en JPG (meilleur compression) des miniatures
		$destination = chemin_thb_img($filepath); // construit le nom de fichier de la miniature
		imagejpeg($thumb, $destination, 70); // compression à 70%
		imagedestroy($thumb);
	}
}

// TRAITEMENT DU FORMAULAIRE D’ENVOIE DE FICHIER (ENVOI, ÉDITION, SUPPRESSION)
function traiter_form_fichier($fichier) {
	// ajout de fichier
	if ( isset($_POST['upload']) ) {
		// par $_FILES
		if (isset($_FILES['fichier'])) {
			bdd_fichier($fichier, 'ajout-nouveau', 'upload', $_FILES['fichier']);
		}
		// par $_POST d’une url
		if (isset($_POST['fichier-url'])) {
			bdd_fichier($fichier, 'ajout-nouveau', 'download', $_POST['fichier-url']);
		}
		redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&msg=confirm_fichier_ajout');
	}
	// édition d’une entrée d’un fichier
	elseif ( isset($_POST['editer']) and !isset($_GET['suppr']) ) {
		$old_file_name = $_POST['filename']; // Name can be edited too. This is old name, the new one is in $fichier[].
		bdd_fichier($fichier, 'editer-existant', '', $old_file_name);
	}
	// suppression d’un fichier
	elseif ( (isset($_POST['supprimer']) and preg_match('/^\d{14}$/', $_POST['file_id'])) xor (isset($_GET['suppr']) and preg_match('/^\d{14}$/', $_GET['file_id'])) ) {
		$id = (isset($_POST['file_id'])) ? $_POST['file_id'] : $_GET['file_id'];
		bdd_fichier($fichier, 'supprimer-existant', '', $id);
	}

}

// TRAITEMENT DU FORMULAIRE DE FICHIER, CÔTÉ BDD
function bdd_fichier($fichier, $quoi, $comment, $sup_var) {
	if ($fichier['bt_type'] == 'image') {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'];
	} else {
		$dossier = $GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_fichiers'];
	}
	if (!is_dir($dossier)) {
		if (FALSE === creer_dossier($dossier, 0)) die($GLOBALS['lang']['err_file_write']);
	}
	// ajout d’un nouveau fichier
	if ($quoi == 'ajout-nouveau') {
		$prefix = '';
		while (file_exists($dossier.'/'.$prefix.$fichier['bt_filename'])) { // éviter d’écraser un fichier existant
			$prefix .= rand(0,9);
		}
		$dest = $prefix.$fichier['bt_filename'];
		$fichier['bt_filename'] = $dest; // redéfinit le nom du fichier.

		// copie du fichier physique
			// Fichier uploadé s’il y a (sinon fichier téléchargé depuis l’URL)
		$new_file = $sup_var['tmp_name'];
		if ( $comment == 'upload' ) {
			if (!move_uploaded_file($new_file, $dossier.'/'. $dest) ) {
				redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_ajout_2');
				exit;
			}
			else {
				$fichier['bt_checksum'] = sha1_file($dossier.'/'. $dest);
			}
		}
			// fichier spécifié par URL
		elseif ( $comment == 'download' and copy($sup_var, $dossier.'/'. $dest) ) {
			$fichier['bt_checksum'] = sha1_file($dossier.'/'. $dest);
			$fichier['bt_filesize'] = filesize($dossier.'/'. $dest);
		} else {
			redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_ajout');
			exit;
		}

		// si fichier par POST ou par URL == OK, on l’ajoute à la base. (si pas OK, on serai déjà sorti par le else { redirection() }.
		if ($fichier['bt_type'] == 'image') { // miniature si c’est une image
			create_thumbnail($dossier.'/'. $dest);
		}
		// ajout à la base.
		$GLOBALS['liste_fichiers'][] = $fichier;
		$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
		file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */');
	}

	// modification d’un fichier déjà existant
	elseif ($quoi == 'editer-existant') {
		$new_filename = $fichier['bt_filename'];
		$old_filename = $sup_var;

		if ($new_filename != $old_filename) { // nom du fichier a changé ? on déplace le fichier.
			$prefix = '';
			while (file_exists($dossier.'/'.$prefix.$new_filename)) { // évite d’avoir deux fichiers de même nom
				$prefix .= rand(0,9);
			}
			$new_filename = $prefix.$fichier['bt_filename'];
			$fichier['bt_filename'] = $new_filename; // update file name in $fichier array(), with the new prefix.

			// rename file on disk
			if (!rename($dossier.'/'.$old_filename, $dossier.'/'.$new_filename)) {
				redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&errmsg=error_fichier_rename');
			} else {
				// si c’est une image : renome la miniature si elle existe, sinon la crée
				if ($fichier['bt_type'] == 'image') {
					if (file_exists(chemin_thb_img($dossier.'/'.$old_filename) )) {
						$old_thb_name = chemin_thb_img($dossier.'/'.$old_filename);
						$new_thb_name = chemin_thb_img($dossier.'/'.$new_filename);
						rename($old_thb_name, $new_thb_name);
					} else {
						create_thumbnail($dossier.'/'.$new_filename);
					}
				}
			}
		}

		// modifie le fichier dans la BDD des fichiers.
		foreach ($GLOBALS['liste_fichiers'] as $key => $entry) {
			if ($entry['bt_id'] == $fichier['bt_id']) { 
				$GLOBALS['liste_fichiers'][$key] = $fichier; // trouve la bonne entrée dans la base.
			}
		}

		$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
		file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // écrit dans le fichier, la liste
		redirection($_SERVER['PHP_SELF'].'?file_id='.$fichier['bt_id'].'&edit&msg=confirm_fichier_edit');
	}

	// suppression d’un fichier (de la BDD et du disque)
	elseif ( $quoi == 'supprimer-existant' ) {
		$id = $sup_var;
		// FIXME ajouter un test de vérification de session (security coin)
		foreach ($GLOBALS['liste_fichiers'] as $fid => $fich) {
			if ($id == $fich['bt_id']) {
				$tbl_id = $fid;
				break;
			}
		}
		// remove physical file on disk if it exists
		if (is_file($dossier.'/'.$fichier['bt_filename']) and isset($tbl_id)) {
			$liste_fichiers = scandir($dossier); // liste les fichiers réels dans le dossier
			if (in_array($fichier['bt_filename'], $liste_fichiers) and !($fichier['bt_filename'] == '..' or $fichier['bt_filename'] == '.')) {
				if (TRUE === unlink($dossier.'/'.$fichier['bt_filename'])) { // fichier physique effacé
					if ($fichier['bt_type'] == 'image' and file_exists(chemin_thb_img($dossier.'/'.$fichier['bt_filename']) )) { // supprimer aussi la miniature si elle existe.
						unlink(chemin_thb_img($dossier.'/'.$fichier['bt_filename']));
					}
					unset($GLOBALS['liste_fichiers'][$tbl_id]); // efface le fichier dans la liste des fichiers.
					$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
					file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // enregistre la liste
					redirection($_SERVER['PHP_SELF'].'?msg=confirm_fichier_suppr');

				} else { // erreur effacement fichier physique
					redirection($_SERVER['PHP_SELF'].'?errmsg=error_fichier_suppr&what=file_suppr_error');
				}
			}
		}

		// the file in DB does not exists on disk => remove entry from DB
		if (isset($tbl_id)) {
			unset($GLOBALS['liste_fichiers'][$tbl_id]); // remove entry from files-list.
		}
		$GLOBALS['liste_fichiers'] = tri_selon_sous_cle($GLOBALS['liste_fichiers'], 'bt_id');
		file_put_contents($GLOBALS['fichier_liste_fichiers'], '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_fichiers']))).' */'); // enregistre la liste
		redirection($_SERVER['PHP_SELF'].'?msg=error_fichier_suppr&what=but_no_such_file_on_disk2');
	}
}


// POST FILE
/*
 * On post of a file (always on admin sides)
 * gets posted informations and turn them into
 * an array
 *
 */
function init_post_fichier() { //no $mode : it's always admin.
		$image = array();
		// on edit : get file info from form
		if (isset($_POST['is_it_edit']) and $_POST['is_it_edit'] == 'yes') {
			$file_id = htmlspecialchars($_POST['file_id']);
				$filename = pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_FILENAME);
				$ext = strtolower(pathinfo(htmlspecialchars($_POST['filename']), PATHINFO_EXTENSION));
				$checksum = htmlspecialchars($_POST['sha1_file']);
				$size = htmlspecialchars($_POST['filesize']);
				$type = detection_type_fichier($ext);
		// on new post, get info from the file itself
		} else {
			$file_id = date('YmdHis');
			if (!empty($_FILES['fichier']) and ($_FILES['fichier']['error'] == 0)) { // ajout de fichier par upload
				$filename = pathinfo($_FILES['fichier']['name'], PATHINFO_FILENAME);
				$ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
				$checksum = sha1_file($_FILES['fichier']['tmp_name']);
				$size = $_FILES['fichier']['size'];
				$type = detection_type_fichier($ext);
			} elseif ( !empty($_POST['fichier-url']) ) { // ajout par une URL d’un fichier distant
				$filename = pathinfo(parse_url($_POST['fichier-url'], PHP_URL_PATH), PATHINFO_FILENAME);
				$ext = strtolower(pathinfo(parse_url($_POST['fichier-url'], PHP_URL_PATH), PATHINFO_EXTENSION));
				$checksum = '';// is calculated further in the process
				$size = '';// same (even if we could use "filesize" with the URL, it would over-use data-transfer)
				$type = detection_type_fichier($ext);
			} else {
				// ERROR
				redirection($_SERVER['PHP_SELF'].'?errmsg=error_image_add');
				return FALSE;
			}
		}
		// nom du fichier : si nom donné, sinon nom du fichier inchangé
		if (!empty($_POST['nom_entree'])) {
			// on supprimme les caractères spéciaux du nom donné
			$filename = diacritique(htmlspecialchars($_POST['nom_entree']), '' , '0').'.'.$ext;
		} else {
			// on supprimme les caractères spéciaux du nom du fichier
			$filename = diacritique(htmlspecialchars($filename), '' , '0').'.'.$ext;
		}
		$statut = (isset($_POST['statut'])) ? '0' : '1';
		$fichier = array (
			'bt_id' => $file_id,
			'bt_type' => $type,
			'bt_fileext' => $ext,
			'bt_filesize' => $size,
			'bt_filename' => $filename, // le nom du final du fichier peut changer à la fin, si le nom est déjà pris par exemple 
			'bt_content' => stripslashes(protect_markup(clean_txt($_POST['description']))),
			'bt_wiki_content' => stripslashes(protect_markup(clean_txt($_POST['description']))),
			'bt_checksum' => $checksum,
			'bt_statut' => $statut,
		);
		return $fichier;
}



function afficher_form_fichier($erreurs, $fichiers, $what) { // ajout d’un fichier
	$max_file_size = taille_formate(return_bytes(ini_get('upload_max_filesize')));
	if ($erreurs) {
		erreurs($erreurs);
	}
	$form = '<form id="form-image" class="bordered-formbloc" enctype="multipart/form-data" method="post" action="'.$_SERVER['PHP_SELF'].'">'."\n";
	
	if (empty($fichiers)) { // si PAS fichier donnée : formulaire nouvel envoi.
		$form .= '<fieldset class="pref" >'."\n";
		$form .= legend($GLOBALS['lang']['label_fichier_ajout'], 'legend-addfile');

		$form .= '<p class="gray-section" id="alternate-form-file">'."\n";
		$form .= "\t".'<label for="fichier">'.ucfirst($GLOBALS['lang']['label_fichier']).' :</label>'."\n";
		$form .= "\t".'<input name="fichier" id="fichier" type="file" required="" class="text" />'."\n";
		$form .= "\t".'<span class="upload-info">('.$GLOBALS['lang']['max_file_size'].$max_file_size.')</span>'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_link\'); return false;" href="#">'.$GLOBALS['lang']['img_specifier_url'].'</a>'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_drag\'); return false;" href="#">'.$GLOBALS['lang']['img_use_dragndrop'].'</a>'."\n";
		$form .= '</p>'."\n";
		$form .= '<p class="gray-section" id="alternate-form-url">'."\n";
		$form .= "\t".'<label for="fichier-url">'.ucfirst($GLOBALS['lang']['label_link']).' :</label>'."\n";
		$form .= "\t".'<input name="fichier-url" id="fichier-url" required="" placeholder="'.$GLOBALS['lang']['label_link'].'" type="text" class="text" disabled="" />'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_file\'); return false;" href="#">'.$GLOBALS['lang']['img_upload_un_fichier'].'</a>'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_drag\'); return false;" href="#">'.$GLOBALS['lang']['img_use_dragndrop'].'</a>'."\n";
		$form .= '</p>'."\n";

		$form .= '<div id="alternate-form-dragndrop">'."\n";
		$form .= '<p class="gray-section" id="dragndrop-area">'."\n";
		$form .= "\t".'<span id="dragndrop-mssg">'.$GLOBALS['lang']['img_drop_files_here'].'</span>'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_file\'); return false;" href="#">'.$GLOBALS['lang']['img_upload_un_fichier'].'</a>'."\n";
		$form .= "\t".'<br/><a class="specify-link" onclick="switchUploadForm(\'to_link\'); return false;" href="#">'.$GLOBALS['lang']['img_specifier_url'].'</a>'."\n";
		$form .= '</p>'."\n";
		$form .= '<p class="sinline gray-section" id="statut-dnd">'."\n";
		$form .= "\t".'<input type="checkbox" id="statut-drag" name="statut-drag"/>'.'<label for="statut-drag">'.$GLOBALS['lang']['label_files_priv'].'</label>';
		$form .= '</p>'."\n";
		$form .= '<div id="count"></div>'."\n";
		$form .= '<div id="result"></div>'."\n";
		$form .= '</div>'."\n";

		$form .= '<div class="gray-section" id="img-others-infos">'."\n";
		$form .= '<p>'."\n";
		$form .= "\t".label('nom_entree', ucfirst($GLOBALS['lang']['img_nom_donnee']))."\n";
		$form .= "\t".'<input type="text" id="nom_entree" name="nom_entree" placeholder="'.$GLOBALS['lang']['img_nom'].'" value="" size="60" class="text" />'."\n";
		$form .= '</p>'."\n";
		$form .= '<p>'."\n";
		$form .= "\t".label('description', ucfirst($GLOBALS['lang']['pref_desc']).' :')."\n";
		$form .= '</p>'."\n";
		$form .= "\t".'<textarea class="description text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['pref_desc'].'" ></textarea>'."\n";
		$form .= '<p class="sinline">'."\n";
		$form .= "\t".'<input type="checkbox" id="statut" name="statut"/>'.'<label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label>';
		$form .= '</p>'."\n";
		$form .= '</div>'."\n";

		$form .= '<div id="img-submit">'."\n";
		$form .= '<input class="submit blue-square" type="submit" name="upload" value="'.$GLOBALS['lang']['img_upload'].'" />'."\n";
		$form .= hidden_input('_verif_envoi', '1');
		$form .= '</div>'."\n";

		$form .= '</fieldset>'."\n";
	}
	// si ID dans l’URL, il s’agit également du seul fichier dans le tableau fichiers, d’où le [0]
	elseif (!empty($fichiers) and isset($_GET['file_id']) and preg_match('/\d{14}/',($_GET['file_id']))) {

		if ($fichiers[0]['bt_type'] == 'image') {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_images'];
		} else {
			$dossier = $GLOBALS['racine'].$GLOBALS['dossier_fichiers'];
		}

		$form .= '<fieldset class="edit-fichier">'."\n";
		$form .= legend($GLOBALS['lang']['label_votre_fichier'], 'legend-fichier');
		$form .= '<p>'.ucfirst($GLOBALS['lang']['label_fichier']).' <a href="'.$dossier.'/'.$fichiers[0]['bt_filename'].'">'.$fichiers[0]['bt_filename'].'</a> :'.'</p>'."\n";

		// la partie listant les infos du fichier.
		$form .= '<ul id="fichier-meta-info">'."\n";
			$form .= "\t".'<li><b>Nom du fichier :</b> '.$fichiers[0]['bt_filename'].'</li>'."\n";
			$form .= "\t".'<li><b>Type (extension) :</b> '.$fichiers[0]['bt_type'].' ('.$fichiers[0]['bt_fileext'].')</li>'."\n";
			if ($fichiers[0]['bt_type'] == 'image') { // si le fichier est une image, on ajout ses dimensions en pixels
				list($width, $height) = getimagesize($GLOBALS['BT_ROOT_PATH'].$GLOBALS['dossier_images'].'/'. $fichiers[0]['bt_filename']);
				$form .= "\t".'<li><b>Dimentions de l’image :</b> '.$width.'px × '.$height.'px'.'</li>'."\n";
			}
			$form .= "\t".'<li><b>Envoyée le :</b> '.date_formate($fichiers[0]['bt_id']).', '.heure_formate($fichiers[0]['bt_id']).'</li>'."\n";
			$form .= "\t".'<li><b>Poids du fichier :</b> '.taille_formate($fichiers[0]['bt_filesize']).'</li>'."\n";
			$visibility = ($fichiers[0]['bt_statut'] == 1) ? 'Publique' : 'Privée';
			$form .= "\t".'<li><b>Somme de contrôle (sha1) :</b> '.$fichiers[0]['bt_checksum'].'</li>'."\n";
			$form .= "\t".'<li><b>Visibilité :</b> '.$visibility.'</li>'."\n";
		$form .= '</ul>'."\n";

		// la partie des codes d’intégration (bbcode, etc.)
		$form .= '<p>'.ucfirst('codes d’intégration :').'</p>'."\n";
		$form .= '<p id="interg-codes">'."\n";
		$form .= '<input onfocus="SelectAllText(\'file_url\')" id="file_url" class="text" type="text" value=\''.$dossier.'/'.$fichiers[0]['bt_filename'].'\' />'."\n";
		if ($fichiers[0]['bt_type'] == 'image') { // si le fichier est une image, on ajout BBCode pour [IMG] et le code en <img/>
			$alte = trim(diacritique(strip_tags($fichiers[0]['bt_content']), '', ''));
			$alt = (!empty($alte)) ? $alte : $fichiers[0]['bt_filename'];
			$form .= '<input onfocus="SelectAllText(\'image_html\')" id="image_html" class="text" type="text" value=\'<img src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" alt="'.$alt.'" width="'.$width.'" height="'.$height.'" style="max-width: 100%; height: auto;" />\' />'."\n";
			$form .= '<input onfocus="SelectAllText(\'image_bbcode_img\')" id="image_bbcode_img" class="text" type="text" value=\'[img]'.$dossier.'/'.$fichiers[0]['bt_filename'].'[/img]\' />'."\n";
		} else {
			$form .= '<input onfocus="SelectAllText(\'file_html\')" id="file_html" class="text" type="text" value=\'<a href="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" />'.$fichiers[0]['bt_filename'].'</a>\' />'."\n";
			$form .= '<input onfocus="SelectAllText(\'fichier_bbcode_url\')" id="fichier_bbcode_url" class="text" type="text" value=\'[url]'.$dossier.'/'.$fichiers[0]['bt_filename'].'[/url]\' />'."\n";
		}

		$form .= '</p>'."\n";
		// codes d’intégrations pour les médias
		// Video
		if ($fichiers[0]['bt_type'] == 'video') {
			$form .= '<div style="text-align: center;"><video src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" type="video/'.$fichiers[0]['bt_fileext'].'" load controls="controls"></video></div>'."\n";
		}
		// image
		if ($fichiers[0]['bt_type'] == 'image') {
			$form .= '<div style="text-align: center;"><a href="'.$dossier.'/'.$fichiers[0]['bt_filename'].'"><img src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" alt="'.$fichiers[0]['bt_filename'].'" style="max-width: 400px; border:1px dotted gray;" /></a></div>'."\n";
		}
		// audio
		if ($fichiers[0]['bt_type'] == 'music') {
			$form .= '<div style="text-align: center;"><audio src="'.$dossier.'/'.$fichiers[0]['bt_filename'].'" type="audio/'.$fichiers[0]['bt_fileext'].'" load controls="controls"></audio></div>'."\n";
		}

		// la partie avec l’édition du contenu.
		$form .= '<p>'."\n";
		$form .= "\t".label('nom_entree', ucfirst($GLOBALS['lang']['img_nom_donnee']))."\n";
		$form .= "\t".'<input type="text" id="nom_entree" name="nom_entree" placeholder="" value="" size="60" class="text" />'."\n";
		$form .= '</p>'."\n";
		$form .= '<p>'."\n";
		$form .= "\t".label('description', ucfirst($GLOBALS['lang']['pref_desc']).' :')."\n";
		$form .= '</p>'."\n";
		$form .= "\t".'<textarea class="description text" id="description" name="description" cols="60" rows="5" placeholder="'.$GLOBALS['lang']['pref_desc'].'" >'.$fichiers[0]['bt_wiki_content'].'</textarea>'."\n";
		$form .= "\t".'<p class="sinline">';
		$checked = ($fichiers[0]['bt_statut'] == 0) ? 'checked ' : '';
		$form .= "\t".'<input type="checkbox" id="statut" name="statut" '.$checked.'/>' . '<label for="statut">'.$GLOBALS['lang']['label_file_priv'].'</label>';
		$form .= "\t".'</p>'."\n";
		$form .= "\t".'<input class="submit blue-square" type="submit" name="editer" value="'.$GLOBALS['lang']['envoyer'].'" />'."\n";
		$form .= "\t".'<input class="submit red-square" type="submit" name="supprimer" value="'.$GLOBALS['lang']['supprimer'].'" onclick="return window.confirm(\''.$GLOBALS['lang']['question_suppr_fichier'].'\')" />'."\n";
		$form .= hidden_input('_verif_envoi', '1');
		$form .= hidden_input('is_it_edit', 'yes');
		$form .= hidden_input('file_id', $fichiers[0]['bt_id']);
		$form .= hidden_input('filename', $fichiers[0]['bt_filename']);
		$form .= hidden_input('sha1_file', $fichiers[0]['bt_checksum']);
		$form .= hidden_input('filesize', $fichiers[0]['bt_filesize']);
		$form .= '</fieldset>';
		$form .= js_select_text_on_focus(1);
	}
	$form .= '</form>'."\n";
	$form .= js_switch_upload_form(1);
	$form .= js_drag_n_drop_handle(1);

	echo $form;
}



// affichage de la liste des fichiers
function afficher_liste_fichiers($tableau, $modele='') {
	$dossier = $GLOBALS['racine'].$GLOBALS['dossier_fichiers'];
	$out = '';
	if (!empty($tableau)) {
		// affichage sous forme d’un tableau (comme les articles)
		if ($modele == 'tableau') {

			$i = 0;
			$out .= '<table id="table-images">'."\n";
			$out .= '<tr>';
			// LEGENDE DES COLONNES
			$out .= '<th>'.ucfirst($GLOBALS['lang']['label_fichiers']).'</th>'."\n"; // nom
			$out .= '<th>'.$GLOBALS['lang']['label_type'].'</th>'."\n"; // type
			$out .= '<th>'.$GLOBALS['lang']['label_extension'].'</th>'."\n"; // extension
			$out .= '<th>'.$GLOBALS['lang']['label_taille_fichier'].'</th>'."\n"; // type
			$out .= '<th>'.$GLOBALS['lang']['label_date'].' - '.$GLOBALS['lang']['label_time'].'</th>'."\n"; // date & heure
			$out .= '</tr>';
			foreach ($tableau as $image) {
				$out .= '<tr class="'.$image['bt_type'].'">'."\n";
				// TITRE
				$out .= "\t".'<td class="titre">';
				// ICONE SELON STATUT
				$class = ($image['bt_statut'] == '1') ? 'on' : 'off';
				$out .= '<a class="'.$class.'" href="'.$_SERVER['PHP_SELF'].'?file_id='.$image['bt_id'].'&amp;edit" title="'.trim(diacritique(strip_tags($image['bt_content']), '', '')).'">'.$image['bt_filename'].'</a>';
				$out .= '</td>'."\n";
				// TYPE DE FICHIER
				$out .= "\t".'<td><a class="black" href="fichiers.php?filtre='.urlencode($image['bt_type']).'">'.$image['bt_type'].'</a></td>'."\n";
				// EXTENSION DE FICHIER
				$out .= "\t".'<td><a class="black" href="fichiers.php?extension='.urlencode($image['bt_fileext']).'">'.$image['bt_fileext'].'</a></td>'."\n";
				// TAILLE DU FICHIER
				$out .= "\t".'<td>'.taille_formate($image['bt_filesize']).'</td>'."\n";
				// DATE & HEURE
				$out .= "\t".'<td><a class="black" href="'.$_SERVER['PHP_SELF'].'?filtre='.substr($image['bt_id'],0,8).'">'.date_formate($image['bt_id']).'</a> - '.heure_formate($image['bt_id']).'</td>'."\n";
				$out .= '</tr>'."\n\n";
				$i++;
			}
			$out .= '</table>'."\n";
		}
		// affichage sous la forme d’icônes, comme les images.
		else {
			$old_filetype = '';
			$tableau = tri_selon_sous_cle($tableau, 'bt_type');
			$out .= '<div class="files-wall">'."\n";
			foreach ($tableau as $file) {
			if ($old_filetype != $file['bt_type']) {
				$out .= '<h2>'.ucfirst($file['bt_type']).' :</h2>';
			}
			$icon_src = 'style/filetypes/'.$file['bt_type'].'.png';

			$out .= '<div class="image_bloc">'."\n";
				$description = (empty($file['bt_content'])) ? '' : ' ('.$file['bt_content'].')';
				$out .= "\t".'<span class="spantop black">';
				$out .= '<a class="lien lien-edit" href="fichiers.php?file_id='.$file['bt_id'].'&amp;edit">&nbsp;</a>';
				$out .= '<a class="lien lien-supr" href="fichiers.php?file_id='.$file['bt_id'].'&amp;suppr&amp;av='.time().'&amp;type=img">&nbsp;</a>';
				$out .= '</span>'."\n";
				$out .= "\t".'<a class="lien" href="'.$dossier.'/'.$file['bt_filename'].'"><img src="'.$icon_src.'" id="'.$file['bt_id'].'" alt="'.$file['bt_filename'].'" /></a><br/><span class="description">'.$file['bt_filename']."</span>\n";
			$out .= '</div>'."\n\n";
			$old_filetype = $file['bt_type'];
		}
		$out .= '</div>';

		}
	}
	echo $out;
}


// gère le filtre de recherche sur les images : recherche par chaine (GET[q]), par type, par statut ou par date.
// pour le moment, il n’est utilisé que du côté Admin (pas de tests sur les statut, date, etc.).
function liste_base_files($tri_selon, $motif, $nombre) {
	$tableau_sortie = array();

	switch($tri_selon) {

		case 'statut':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if ($file['bt_statut'] == $motif) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'date':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (($pos = strpos($file['bt_id'], $motif)) !== FALSE and $pos == 0) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'type':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if ($file['bt_type'] == $motif) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'extension':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (($file['bt_fileext'] == $motif)) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		case 'recherche':
			foreach ($GLOBALS['liste_fichiers'] as $id => $file) {
				if (strpos($file['bt_content'].' '.$file['bt_filename'], $motif)) {
					$tableau_sortie[$id] = $file;
			}	}
			break;

		default :
			$tableau_sortie = $GLOBALS['liste_fichiers'];
	}

	if (isset($nombre) and is_numeric($nombre) and $nombre > 0) {
		$tableau_sortie = array_slice($tableau_sortie, 0, $nombre);
	}

	return $tableau_sortie;
}



