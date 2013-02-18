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

function redirection($url) {
	header('Location: '.$url);
	exit;
}

/// DECODAGES //////////

function get_id($file) {
	$retour = substr($file, 0, 14);
	return $retour;
}

function decode_id($id) {
	$retour = array(
		'annee' => substr($id, 0, 4),
		'mois' => substr($id, 4, 2),
		'jour' => substr($id, 6, 2),
		'heure' => substr($id, 8, 2),
		'minutes' => substr($id, 10, 2),
		'secondes' => substr($id, 12, 2)
		);
	return $retour;
}

function get_path($id) {
	$dec = decode_id($id);
	$retour = $dec['annee'].'/'.$dec['mois'].'/'.$id.'.'.$GLOBALS['ext_data'];
	return $retour;
}

// used sometimes, like in the email that is send.
function get_blogpath($id) {
	$date = decode_id($id);
	$path = $GLOBALS['racine'].'index.php?d='.$date['annee'].'/'.$date['mois'].'/'.$date['jour'].'/'.$date['heure'].'/'.$date['minutes'].'/'.$date['secondes'].'-'.titre_url(get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $id, 'return'));
	return $path;
}

function ww_hach_sha($text, $salt) {
	$out = hash("sha512", $text.$salt);	// PHP 5
	return $out;
}

function article_anchor($id) {
	$anchor = 'id'.substr(md5($id), 0, 6);
	return $anchor;
}

function traiter_tags($tags) {
	$tags_array = explode(',' , trim($tags, ','));
	$tags_array = array_unique(array_map('trim', $tags_array));
	sort($tags_array);
	return implode(', ' , $tags_array);
}

// tri un tableau non pas comme "sort()" sur l’ID, mais selon une sous clé d’un tableau.
function tri_selon_sous_cle($table, $cle) {
	foreach ($table as $key => $item) {
		 $ss_cles[$key] = $item[$cle];
	}
	if (isset($ss_cles)) {
		array_multisort($ss_cles, SORT_DESC, $table);
	}
	return $table;
}



function check_session() {
	$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
	@session_start();
	ini_set('session.cookie_httponly', TRUE);
	// use a cookie to remain logged in
	if (isset($_COOKIE['BT-admin-stay-logged']) and $_COOKIE['BT-admin-stay-logged'] == '1') {
		$uuid = ww_hach_sha($GLOBALS['mdp'].$GLOBALS['identifiant'].$GLOBALS['salt'], md5($_SERVER['HTTP_USER_AGENT'].$ip.$GLOBALS['salt']));

		if (isset($_COOKIE['BT-admin-uuid']) and $_COOKIE['BT-admin-uuid'] == $uuid) {
			$_SESSION['rand_sess_id'] = md5($uuid);
			session_set_cookie_params(365*24*60*60); // set new expiration time to the browser
			session_regenerate_id(true);  // Send cookie
			return TRUE;
		}
	}
	if ( (!isset($_SESSION['rand_sess_id'])) or ($_SESSION['rand_sess_id'] != $GLOBALS['identifiant'].$GLOBALS['mdp'].md5($_SERVER['HTTP_USER_AGENT'].$ip)) ) {
		return FALSE;
	} else {
		return TRUE;
	}
}


// this will look if session expired and kill it.
function operate_session() {
	if (check_session() === FALSE) { // session is not good
		fermer_session(); // destroy it
	} else {
		return TRUE;
	}
}

function fermer_session() {
	unset($_SESSION['nom_utilisateur'],$_SESSION['rand_sess_id']);
	setcookie('BT-admin-stay-logged', 0);
	setcookie('BT-admin-uuid', NULL);
	session_destroy(); // destroy session
	session_regenerate_id(true); // change l'ID au cas ou
	redirection('auth.php');
	exit();
}

function remove_url_param($param) {
	$msg_param_to_trim = (isset($_GET[$param])) ? '&'.$param.'='.$_GET[$param] : '';
	$query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
	return $query_string;
}


// A partir d'un commentaire posté, détermine les emails
// à qui envoyer la notification de nouveau commentaire.
function send_emails($id_comment) {
	// disposant de l'email d'un commentaire, on détermine l'article associé, le titre, l’auteur du comm et l’email de l’auteur du com.
	$article = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_article_id', $id_comment, 'return');
	$article_title = get_entry($GLOBALS['db_handle'], 'articles', 'bt_title', $article, 'return');
	$comm_author = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_author', $id_comment, 'return');
	$comm_author_email = get_entry($GLOBALS['db_handle'], 'commentaires', 'bt_email', $id_comment, 'return');

	// puis la liste de tous les commentaires de cet article
	$liste_commentaires = array();
	try {
		$query = "SELECT bt_email,bt_subscribe,bt_id FROM commentaires WHERE bt_statut='1' AND bt_article_id=? ORDER BY bt_id";
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($article));
		$liste_commentaires = $req->fetchAll();
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	// Récupérre la liste (sans doublons) des emails des commentateurs, ainsi que leurs souscription à la notification d'email.
	// si plusieurs comm avec la même email, alors seul le dernier est pris en compte.
	// si l’auteur même du commentaire est souscrit, il ne recoit pas l’email de son propre commentaire.
	$emails = array();
	foreach ($liste_commentaires as $i => $comment) {
		if (!empty($comment['bt_email']) and ($comm_author_email != $comment['bt_email'])) {
			$emails[$comment['bt_email']] = $comment['bt_subscribe'].'-'.get_id($comment['bt_id']);
		}
	}
	// ne conserve que la liste des mails dont la souscription est demandée (= 1)
	$to_send_mail = array();
	foreach ($emails as $mail => $is_subscriben) {
		if ($is_subscriben[0] == '1') { // $is_subscriben is seen as a array of chars here, first char is 0 or 1 for subscription.
			$to_send_mail[$mail] = substr($is_subscriben, -14);
		}
	}
	$subject = 'New comment on "'.$article_title.'" - '.$GLOBALS['nom_du_site'];
	$headers  = 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset="UTF-8"'."\r\n";
	$headers .= 'From: no.reply_'.$GLOBALS['email']."\r\n".'X-Mailer: BlogoText - PHP/'.phpversion();

	// for debug
	//header('Content-type: text/html; charset=UTF-8');
	//die(($to. $subject. $message. $headers));
	//echo '<pre>';print_r($emails);
	//echo '<pre>';print_r($to_send_mail);
	//die();
	// envoi les emails.
	foreach ($to_send_mail as $mail => $is_subscriben) {
		$comment = substr($is_subscriben, -14);
		$unsublink = get_blogpath($article).'&amp;unsub=1&amp;comment='.$comment.'&amp;mail='.sha1($mail);
		$message = '<html>';
		$message .= '<head><title>'.$subject.'</title></head>';
		$message .= '<body><p>A new comment by <b>'.$comm_author.'</b> has been posted on <b>'.$article_title.'</b> form '.$GLOBALS['nom_du_site'].'.<br/>';
		$message .= 'You can see it by following <a href="'.get_blogpath($article).'#'.article_anchor($id_comment).'">this link</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on that post, you can follow this link: <a href="'.$unsublink.'">'.$unsublink.'</a>.</p>';
		$message .= '<p>To unsubscribe from the comments on all the posts, follow this link: <a href="'.$unsublink.'&amp;all=1">'.$unsublink.'&amp;all=1</a>.</p>';
		$message .= '<p>Also, do not reply to this email, since it is an automatic generated email.</p><p>Regards.</p></body>';
		$message .= '</html>';
		mail($mail, $subject, $message, $headers);
	}
	return TRUE;
}

// met à 0 la subscription d'un auteur à un article. (met à 0 celui dans le dernier commentaire qu'il a posté sur un article)
function unsubscribe($file_id, $email_sha, $all) {
	// récupération de quelques infos sur le commentaire
	try {
		$query = "SELECT bt_email,bt_subscribe,bt_id FROM commentaires WHERE bt_id=?";
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($file_id));
		$result = $req->fetchAll();

	} catch (Exception $e) {
		die ('Erreur BT #12725 : '. $e->getMessage());
	}
	try {
		if (!empty($result[0])) {
			$comment = $result[0];
			// (le test SHA1 sur l'email sert à vérifier que c'est pas un lien forgé pouvant désinscrire une email de force
			if ( ($email_sha == sha1($comment['bt_email'])) and ($comment['bt_subscribe'] == 1) ) {
				if ($all == 1) {
					// mettre à jour de tous les commentaire qui ont la même email.
					$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_email=?";
					$array = $comment['bt_email'];
				} else {
					// mettre à jour le commentaire
					$query = "UPDATE commentaires SET bt_subscribe=0 WHERE bt_id=?";
					$array = $comment['bt_id'];
				}
				$req = $GLOBALS['db_handle']->prepare($query);
				$req->execute(array($array));
				return TRUE;
			}
			elseif ($comment['bt_subscribe'] == 0) {
				return TRUE;
			}
		}
	} catch (Exception $e) {
		die('Erreur BT 89867 : '.$e->getMessage());
	}
	return FALSE; // si il y avait été TRUE, on serait déjà sorti de la fonction
}

