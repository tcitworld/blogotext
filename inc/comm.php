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


/* Transforms numbers in words */
function en_lettres($captchavalue) {
	switch($captchavalue) {
		case 0 : $lettres = $GLOBALS['lang']['0']; break;
		case 1 : $lettres = $GLOBALS['lang']['1']; break;
		case 2 : $lettres = $GLOBALS['lang']['2']; break;
		case 3 : $lettres = $GLOBALS['lang']['3']; break;
		case 4 : $lettres = $GLOBALS['lang']['4']; break;
		case 5 : $lettres = $GLOBALS['lang']['5']; break;
		case 6 : $lettres = $GLOBALS['lang']['6']; break;
		case 7 : $lettres = $GLOBALS['lang']['7']; break;
		case 8 : $lettres = $GLOBALS['lang']['8']; break;
		case 9 : $lettres = $GLOBALS['lang']['9']; break;
		default: $lettres = ""; break;
	}
return $lettres;
}

function protect($text) {
	$return = htmlspecialchars(stripslashes(clean_txt($text)));
	return $return;
}


// FIXME : ajouter security coin.

/* generates the comment form, with params from the admin-side and the visiter-side */
function afficher_form_commentaire($article_id, $mode, $erreurs='', $comm_id='') {
	$GLOBALS['form_commentaire'] = '';
	if (isset($_POST['_verif_envoi']) and !empty($erreurs)) {
		$GLOBALS['form_commentaire'] = '<div id="erreurs"><strong>'.$GLOBALS['lang']['erreurs'].'</strong> :'."\n" ;
		$GLOBALS['form_commentaire'].= '<ul><li>'."\n";
		$GLOBALS['form_commentaire'].=  implode('</li><li>', $erreurs);
		$GLOBALS['form_commentaire'].=  '</li></ul></div>'."\n";
		$defaut = array(
			'auteur' => protect($_POST['auteur']),
			'email' => protect($_POST['email']),
			'webpage' => protect($_POST['webpage']),
			'commentaire' => protect($_POST['commentaire']),
		);

	} elseif (isset($mode) and $mode == 'admin') {
		if (empty($comm_id)) {
			$defaut = array(
				'auteur' => $GLOBALS['auteur'],
				'email' => $GLOBALS['email'],
				'webpage' => $GLOBALS['racine'],
				'commentaire' => '',
				);
		} else {
			$actual_comment = $comm_id;
			$defaut = array(
				'auteur' => protect($actual_comment['bt_author']),
				'email' => protect($actual_comment['bt_email']),
				'webpage' => protect($actual_comment['bt_webpage']),
				'commentaire' => htmlspecialchars($actual_comment['bt_wiki_content']),
				'status' => protect($actual_comment['bt_statut']),
				);
		}

	} elseif (isset($_POST['previsualiser'])) { // parses the comment, but does not save it in a file
		$defaut = array(
			'auteur' => protect($_POST['auteur']),
			'email' => protect($_POST['email']),
			'webpage' => protect($_POST['webpage']),
			'commentaire' => protect($_POST['commentaire']),
		);
		$comm['bt_content'] = formatage_commentaires(stripslashes(htmlspecialchars(clean_txt($_POST['commentaire']), ENT_NOQUOTES)));
		$comm['bt_id'] = date('YmdHis');
		$comm['bt_email'] = protect($_POST['email']);
		$comm['bt_author'] = protect($_POST['auteur']);
		$comm['bt_webpage'] = protect($_POST['webpage']);
		$comm['anchor'] = article_anchor($comm['bt_id']);
		$comm['auteur_lien'] = ($comm['bt_webpage'] != '') ? '<a href="'.$comm['bt_webpage'].'" class="webpage">'.$comm['bt_author'].'</a>' : $comm['bt_author'];
		$GLOBALS['form_commentaire'] .= '<div id="erreurs"><ul><li>Prévisualisation&nbsp;:</li></ul></div>'."\n";
		$GLOBALS['form_commentaire'] .= '<div id="previsualisation">'."\n";
		$GLOBALS['form_commentaire'] .= conversions_theme_commentaire(charger_template($GLOBALS['theme_article'], $GLOBALS['boucles']['commentaires'], 'liste'), $comm);
		$GLOBALS['form_commentaire'] .= '</div>'."\n";
	} else {
		if (isset($_POST['_verif_envoi'])) {
			header('Location: '.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'#top'); // redirection anti repostage;
		}
		$auteur_c = (isset($_COOKIE['auteur_c'])) ? protect($_COOKIE['auteur_c']) : '' ;
		$email_c = (isset($_COOKIE['email_c'])) ? protect($_COOKIE['email_c']) : '' ;
		$webpage_c = (isset($_COOKIE['webpage_c'])) ? protect($_COOKIE['webpage_c']) : '' ;
		$defaut = array(
			'auteur' => "$auteur_c",
			'email' => $email_c,
			'webpage' => $webpage_c,
			'commentaire' => '',
			'captcha' => '',
		);
	}

	// prelim vars for Generation of comment Form
	$label_email = ($GLOBALS['require_email'] == 1) ? $GLOBALS['lang']['comment_email_required'] : $GLOBALS['lang']['comment_email']; 
	$required = ($GLOBALS['require_email'] == 1) ? 'required=""' : '';
	$cookie_checked = (isset($_COOKIE['cookie_c']) and $_COOKIE['cookie_c'] == 1) ? ' checked="checked"' : '';
	$subscribe_checked = (isset($_COOKIE['subscribe_c']) and $_COOKIE['subscribe_c'] == 1) ? ' checked="checked"' : '';

	// COMMENT FORM ON ADMIN SIDE : +edit +always_open –captcha –previsualisation –verif
	if ($mode == 'admin') {
		$rand = ($mode == 'admin') ? substr(md5(rand(1000,9999)),0,5) : '';
		// begin with some additional stuff on comment "edit".
		if (isset($actual_comment)) { // edit
			$form = "\n".'<form id="form-commentaire-'.$actual_comment['bt_id'].'" class="form-commentaire" method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'#erreurs" style="display:none;">'."\n";
			$form .= "\t".'<fieldset class="syst">'."\n";
			$form .= "\t\t".hidden_input('is_it_edit', 'yes');
			$form .= "\t\t".hidden_input('comment_id', $actual_comment['bt_id']);
			$form .= "\t\t".hidden_input('status', $actual_comment['bt_statut']);
			$form .= "\t\t".hidden_input('ID', $actual_comment['ID']);
			$form .= "\t".'</fieldset><!--end syst-->'."\n";
		} else {
			$form = "\n".'<form id="form-commentaire" class="form-commentaire" method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'#erreurs" >'."\n";
		}
		$form .= "\t".'<fieldset class="field">'."\n";
		$form .= "\t\t".hidden_input('comment_article_id', $article_id);
		$form .= "\t".'<p class="formatbut">'."\n";
		$form .= "\t\t".'<button id="button01" class="but" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(\'[b]\',\'[/b]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="button02" class="but" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(\'[i]\',\'[/i]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="button03" class="but" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(\'[u]\',\'[/u]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="button04" class="but" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(\'[s]\',\'[/s]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<span class="spacer"></span>'."\n";
		$form .= "\t\t".'<button id="button09" class="but" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(\'[\',\'|http://]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="button10" class="but" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(\'[quote]\',\'[/quote]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="button12" class="but" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(\'[code]\',\'[/code]\',\'commentaire'.$rand.'\');"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<span class="spacer"></span>'."\n";
		$form .= "\t\t".'<button id="pmm" type="button" class="pm but" onclick="resize(\'commentaire'.$rand.'\', -40); return false;"><span class="c"></span></button>'."\n";
		$form .= "\t\t".'<button id="pmp" type="button" class="pm but" onclick="resize(\'commentaire'.$rand.'\', 40); return false;"><span class="c"></span></button>'."\n";
		$form .= "\t".'</p><!--end formatbut-->'."\n";
		$form .= "\t\t".'<textarea class="commentaire text" name="commentaire" required="" placeholder="'.$GLOBALS['lang']['label_commentaire'].'" id="commentaire'.$rand.'" cols="50" rows="10" tabindex="2" >'.$defaut['commentaire'].'</textarea>'."\n";
		$form .= "\t".'</fieldset>'."\n";

		$form .= "\t".'<fieldset class="infos">'."\n";
		$form .= "\t\t".label('auteur'.$rand, $GLOBALS['lang']['comment_nom'].' :');
		$form .= "\t\t".'<input type="text" name="auteur" placeholder="'.$GLOBALS['lang']['comment_nom'].'" required="" id="auteur'.$rand.'" value="'.$defaut['auteur'].'" size="25"  tabindex="2" class="text" /><br/>'."\n";
		$form .= "\t\t".label('email'.$rand, $label_email.' :');
		$form .= "\t\t".'<input type="email" name="email" placeholder="'.$label_email.' " id="email'.$rand.'" '.$required.' value="'.$defaut['email'].'" size="25"  tabindex="2" class="text" /><br/>'."\n";
		$form .= "\t\t".label('webpage'.$rand, $GLOBALS['lang']['comment_webpage'].' :');
		$form .= "\t\t".'<input type="url" name="webpage" placeholder="'.$GLOBALS['lang']['comment_webpage'].'" id="webpage'.$rand.'" value="'.$defaut['webpage'].'" size="25"  tabindex="2" class="text" /><br/>'."\n";
		$form .= ($mode != 'admin') ? "\t\t".label('captcha', $GLOBALS['lang']['comment_captcha'].' <b>'.en_lettres($_SESSION['captx']).'</b> + <b>'.en_lettres($_SESSION['capty']).'</b> ?') : '';
		$form .= ($mode != 'admin') ? "\t\t".'<input type="text" id="captcha'.$rand.'" name="captcha" placeholder="'.$GLOBALS['lang']['comment_captcha_usenumbers'].'" value="" size="25" tabindex="2" class="text" /><br/>'."\n" : '';
		$form .= "\t\t".hidden_input('_verif_envoi', '1');
		if (isset($actual_comment)) { // edit
			$checked = ($actual_comment['bt_statut'] == '0') ? 'checked ' : '';
			$form .= "\t".'<label for="activer_comm'.$rand.'">'.$GLOBALS['lang']['label_comm_priv'].'</label>'.'<input type="checkbox" id="activer_comm'.$rand.'" name="activer_comm" '.$checked.'/>';
			$form .= "\t".'</fieldset><!--end info-->'."\n";
			$form .= "\t".'<fieldset class="buttons">'."\n";
			$form .= "\t\t".hidden_input('ID', $actual_comment['ID']);
			$form .= "\t\t".'<input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" tabindex="2" />'."\n";
			$form .= "\t\t".'<input class="submit red-square" type="submit" name="supprimer_comm" value="'.$GLOBALS['lang']['supprimer'].'" onclick="return window.confirm(\''.$GLOBALS['lang']['question_suppr_comment'].'\')" tabindex="2" />'."\n";
		} else {
			$form .= "\t".'</fieldset><!--end info-->'."\n";
			$form .= "\t".'<fieldset class="buttons">'."\n";
			$form .= "\t\t".'<input class="submit blue-square" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" tabindex="2" />'."\n";
		}
		$form .= "\t".'</fieldset><!--end buttons-->'."\n";
		$GLOBALS['form_commentaire'] .= $form;
		$GLOBALS['form_commentaire'] .= '</form>'."\n";

	// COMMENT ON PUBLIC SIDE
	} else {
		// Formulaire commun
		$form = "\n".'<form id="form-commentaire" class="form-commentaire" method="post" action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'#erreurs" >'."\n";

		$form .= "\t".'<fieldset class="field">'."\n";
		$form .= "\t".'<p class="formatbut">'."\n";
		$form .= "\t\t".'<button id="button01" type="button" title="'.$GLOBALS['lang']['bouton-gras'].'" onclick="insertTag(\'[b]\',\'[/b]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button02" type="button" title="'.$GLOBALS['lang']['bouton-ital'].'" onclick="insertTag(\'[i]\',\'[/i]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button03" type="button" title="'.$GLOBALS['lang']['bouton-soul'].'" onclick="insertTag(\'[u]\',\'[/u]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button04" type="button" title="'.$GLOBALS['lang']['bouton-barr'].'" onclick="insertTag(\'[s]\',\'[/s]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<span class="spacer"></span>'."\n";
		$form .= "\t\t".'<button id="button09" type="button" title="'.$GLOBALS['lang']['bouton-lien'].'" onclick="insertTag(\'[\',\'|http://]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button10" type="button" title="'.$GLOBALS['lang']['bouton-cita'].'" onclick="insertTag(\'[quote]\',\'[/quote]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="button12" type="button" title="'.$GLOBALS['lang']['bouton-code'].'" onclick="insertTag(\'[code]\',\'[/code]\',\'commentaire\');"><span></span></button>'."\n";
		$form .= "\t\t".'<span class="spacer"></span>'."\n";
		$form .= "\t\t".'<button id="pmm" type="button" class="pm" onclick="resize(\'commentaire\', -40); return false;"><span></span></button>'."\n";
		$form .= "\t\t".'<button id="pmp" type="button" class="pm" onclick="resize(\'commentaire\', 40); return false;"><span></span></button>'."\n";
		$form .= "\t".'</p><!--end formatbut-->'."\n";
		$form .= "\t\t".'<textarea class="commentaire" name="commentaire" required="" placeholder="'.$GLOBALS['lang']['label_commentaire'].'" id="commentaire" cols="50" rows="10" tabindex="2">'.$defaut['commentaire'].'</textarea>'."\n";
		$form .= "\t".'</fieldset>'."\n";

		$form .= "\t".'<fieldset class="infos">'."\n";
		$form .= "\t\t".label('auteur', $GLOBALS['lang']['comment_nom'].' :');
		$form .= "\t\t".'<input type="text" name="auteur" placeholder="'.$GLOBALS['lang']['comment_nom'].'" required="" id="auteur" value="'.$defaut['auteur'].'" size="25"  tabindex="2" class="text" /><br/>'."\n";
		$form .= "\t\t".label('email', $label_email.' :');
		$form .= "\t\t".'<input type="email" name="email" placeholder="'.$label_email.'" id="email" '.$required.' value="'.$defaut['email'].'" size="25"  tabindex="2"/><br/>'."\n";
		$form .= "\t\t".'<input type="email" id="email-adress" name="email-adress" placeholder="email" value="" size="25" class="text" />'."\n";
		$form .= "\t\t".label('webpage', $GLOBALS['lang']['comment_webpage'].' :');
		$form .= "\t\t".'<input type="text" name="webpage" placeholder="'.$GLOBALS['lang']['comment_webpage'].'" id="webpage" value="'.$defaut['webpage'].'" size="25"  tabindex="2"/><br/>'."\n";
		$form .= "\t\t".label('captcha', $GLOBALS['lang']['comment_captcha'].' <b>'.en_lettres($_SESSION['captx']).'</b> + <b>'.en_lettres($_SESSION['capty']).'</b> ?');
		$form .= "\t\t".'<input type="text" id="captcha" name="captcha" placeholder="'.$GLOBALS['lang']['comment_captcha_usenumbers'].'" value="" size="25" tabindex="2" class="text" /><br/>'."\n";
		$form .= "\t\t".hidden_input('_verif_envoi', '1');
		$form .= "\t".'</fieldset><!--end info-->'."\n";
		$form .= "\t".'<fieldset class="cookie"><!--begin cookie asking -->'."\n";
		$form .= "\t\t".'<input class="check" type="checkbox" id="allowcookie" name="allowcookie"'.$cookie_checked.' tabindex="2" />'.label('allowcookie', $GLOBALS['lang']['comment_cookie']).'<br/>'."\n";
		$form .= "\t\t".'<input class="check" type="checkbox" id="subscribe" name="subscribe"'.$subscribe_checked.' tabindex="2" />'.label('subscribe', $GLOBALS['lang']['comment_subscribe'])."\n";
		$form .= "\t".'</fieldset><!--end cookie asking-->'."\n";
		$form .= "\t".'<fieldset class="buttons">'."\n";
		$form .= "\t\t".'<input class="submit" type="submit" name="enregistrer" value="'.$GLOBALS['lang']['envoyer'].'" tabindex="2" />'."\n";
		$form .= "\t\t".'<input class="submit" type="submit" name="previsualiser" value="'.$GLOBALS['lang']['preview'].'" tabindex="2" />'."\n";
		$form .= "\t".'</fieldset><!--end buttons-->'."\n";

		// ALLOW COMMENTS : ON
		if (get_entry($GLOBALS['db_handle'], 'articles', 'bt_allow_comments', $article_id, 'return') == '1' and $GLOBALS['global_com_rule'] == '0') {
			$GLOBALS['form_commentaire'] .= $form;
			if ($GLOBALS['comm_defaut_status'] == '0') { // petit message en cas de moderation a-priori
				$GLOBALS['form_commentaire'] .= "\t\t".'<div class="need-validation">'.$GLOBALS['lang']['remarque'].' :'."\n" ;
				$GLOBALS['form_commentaire'] .= "\t\t\t".$GLOBALS['lang']['comment_need_validation']."\n";
				$GLOBALS['form_commentaire'] .= "\t\t".'</div>'."\n";
			}
			$GLOBALS['form_commentaire'] .= '</form>'."\n";
		}
		// ALLOW COMMENTS : OFF
		else {
			$GLOBALS['form_commentaire'] .= '<p>'.$GLOBALS['lang']['comment_not_allowed'].'</p>'."\n";
		}
	}
}



