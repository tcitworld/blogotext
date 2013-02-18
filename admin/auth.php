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
}

$GLOBALS['BT_ROOT_PATH'] = '../';
require_once '../inc/inc.php';
error_reporting($GLOBALS['show_errors']);

$max_attemps = 10; // max attempts before blocking login page
$wait_time = 30;   // time to wait before unblocking login page, in minutes

if (check_session() === TRUE) { // return to index if session is already open.
	header('Location: index.php');
}

// Acces LOG
if (isset($_POST['nom_utilisateur'])) {
	// IP
	$ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
	// Proxy IPs, if exists.
	$ip .= (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? '_'.htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
	$browser = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '' ; // navigateur
	$referer = (isset($_SERVER['HTTP_REFERER'])) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'none'; // url d'origine
	$curent_time = date('r'); // heure selon RFC 2822 : Wed, 18 Jan 2012 20:42:12 +0100
	$username = htmlspecialchars($_POST['nom_utilisateur']); // nom de login tenté.

	$data = "\n\n\n".'<?php'."\n";
	$data .= '	// '.$curent_time . "\n";
	$data .= '	// '.'IP      : ' . $ip . "\n";
	$data .= '	// '.'ORIGINE : ' . $referer . "\n";
	$data .= '	// '.'BROWSER : ' . $browser . "\n";
	$data .= '	// '.'LOGIN   : ' . $username . "\n";
	$data .= '?>';

	file_put_contents('xauthlog.php', $data, FILE_APPEND);
}// end log


// Auth checking :
if (isset($_POST['_verif_envoi']) and valider_form() === TRUE) { // OK : getting in.
	$ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
	$_SESSION['rand_sess_id'] = $_POST['nom_utilisateur'].ww_hach_sha($_POST['mot_de_passe'], $GLOBALS['salt']).md5($_SERVER['HTTP_USER_AGENT'].$ip); // set special hash
	usleep(100000); // 100ms sleep to avoid bruteforce

	if (!empty($_POST['stay_logged'])) { // if user wants to stay logged
// first method : uses only server sessions, but may not work every where
//		$_SESSION['stay_logged_mode'] = 1; // if user wants to stay logged : 1
//		session_set_cookie_params(365*24*60*60); // Set session cookie expiration on client side (one year)
//		session_regenerate_id(true);  // Send cookie with new expiration date to browser.
// secondth method : uses a cookie. A bit less safe.
		setcookie('BT-admin-stay-logged', '1', time()+365*42*60*60, null, null, false, true);
		$uuid = ww_hach_sha($GLOBALS['mdp'].$GLOBALS['identifiant'].$GLOBALS['salt'], md5($_SERVER['HTTP_USER_AGENT'].$ip.$GLOBALS['salt']));
		setcookie('BT-admin-uuid', $uuid, time()+365*42*60*60, null, null, false, true);

	} else {
		$_SESSION['stay_logged_mode'] = 0;
		session_regenerate_id(true);
	}
	fichier_ip();
	header('Location: index.php');

} else { // On sort…
		// …et affiche la page d'auth
		afficher_top('Identification');
		echo '<div id="axe">'."\n";
		echo '<div id="pageauth">'."\n";
		echo '<h1>'.$GLOBALS['nom_application'].'</h1>'."\n";
		echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return decompte()">'."\n";
		echo '<div id="auth">'."\n";
		echo '<p><label for="nom_utilisateur">'.ucfirst($GLOBALS['lang']['label_identifiant']).'</label>'."\n";
		echo '<input class="text" type="text" id="nom_utilisateur" name="nom_utilisateur" value="" /></p>'."\n";
		echo '<p><label for="mot_de_passe">'.ucfirst($GLOBALS['lang']['label_motdepasse']).'</label>';
		echo '<input class="text" type="password" id="mot_de_passe" name="mot_de_passe" value="" /></p>'."\n";
		if (isset($GLOBALS['connexion_captcha']) and ($GLOBALS['connexion_captcha'] == "1")) {
			echo js_reload_captcha(1);
			echo '<p><label for="word">'.ucfirst($GLOBALS['lang']['label_word_captcha']).'</label>';
			echo '<input class="text" type="text" id="word" name="word" value="" /></p>'."\n";
			echo '<p><a href="#" onclick="new_freecap();return false;" title="'.$GLOBALS['lang']['label_changer_captcha'].'"><img src="../inc/freecap/freecap.php" id="freecap"></a></p>'."\n";
		}

		echo '<p class="sinline"><input type="checkbox" id="stay_logged" name="stay_logged" /><label for="stay_logged">'.$GLOBALS['lang']['label_stay_logged'].'</label>'."\n";
		echo '</p>'."\n";
		echo '<input class="inpauth blue-square" type="submit" name="submit" value="'.$GLOBALS['lang']['connexion'].'" />'."\n";
		echo '<input type="hidden" name="_verif_envoi" value="1" />'."\n";
		echo '</div>'."\n";
		echo '</form>'."\n";
}

function valider_form() {
	$mot_de_passe_ok = $GLOBALS['mdp'].$GLOBALS['identifiant'];
	$mot_de_passe_essai = ww_hach_sha($_POST['mot_de_passe'], $GLOBALS['salt']).$_POST['nom_utilisateur'];
	// first test password
	if ($mot_de_passe_essai == $mot_de_passe_ok and $_POST['nom_utilisateur'] == $GLOBALS['identifiant']) { // avoids "string a + string bc" to be equal to "string ab + string c"
		$passwd_is_ok = 1;
	} else {
		$passwd_is_ok = 0;
	}
	// then test captcha
	if (isset($GLOBALS['connexion_captcha']) and ($GLOBALS['connexion_captcha'] == "1")) { // si captcha activé
		if (!empty($_SESSION['freecap_word_hash']) and !empty($_POST['word']) and (sha1(strtolower($_POST['word'])) == $_SESSION['freecap_word_hash']) ) {
			$captcha_is_ok = 1;
		} else {
			$captcha_is_ok = 0;
		}
		if (sha1(strtolower($_POST['word'])) == $_SESSION['freecap_word_hash']) {
			$_SESSION['freecap_word_hash'] = FALSE;
		}
	} else { // si captcha pas activé
		$captcha_is_ok = 1;
	}
	// then return : is both captcha and password are ok.
	if ($passwd_is_ok == 1 and $captcha_is_ok == 1) {
		return TRUE;
	} else {
		return FALSE;
	}
}

footer();
?>
