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
 * On login captcha : if the captcha is unreadable, this helps you reload the captcha
 * without reloading the whole page (the other fields might been filed)
 *
*
*/
function js_reload_captcha($a) {
	$sc = '
function new_freecap() {
	if(document.getElementById) {
		thesrc = document.getElementById("freecap").src;
		thesrc = thesrc.substring(0,thesrc.lastIndexOf(".")+4);
		document.getElementById("freecap").src = thesrc+"?"+Math.round(Math.random()*100000);
	} else {
		alert("Sorry, cannot autoreload freeCap image\nSubmit the form and a new freeCap will be loaded");
	}
}';

	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS button to resize the height of a textarea.
 * I know, HTML/CSS has "resize" property, but Opera does not understand it yet. 
 * I don't give a shit about IE, but I am an Opera User, so I have this function.
*
*/
function js_resize($a) {
	$sc = '
function resize(id, dht) {
	var elem = document.getElementById(id);
	var ht = elem.offsetHeight;
	size = Number(ht)+Number(dht);
	elem.style.height = size+"px";
	return false;
}';

	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * inset BBCode tags into a form.
*
*/
function js_inserttag($a) {
	$sc = '
function insertTag(startTag, endTag, tag) {
	var field = document.getElementById(tag);
	var scroll = field.scrollTop;
	field.focus();
	if (window.ActiveXObject) {
		var textRange = document.selection.createRange();
		var currentSelection = textRange.text;
		textRange.text = startTag + currentSelection + endTag;
		textRange.moveStart("character", -endTag.length - currentSelection.length);
		textRange.moveEnd("character", -endTag.length);
		textRange.select();
	} else {
		var startSelection   = field.value.substring(0, field.selectionStart);
		var currentSelection = field.value.substring(field.selectionStart, field.selectionEnd);
		var endSelection     = field.value.substring(field.selectionEnd);
		if (currentSelection == "") { currentSelection = "TEXT"; }
		field.value = startSelection + startTag + currentSelection + endTag + endSelection;
		field.focus();
		field.setSelectionRange(startSelection.length + startTag.length, startSelection.length + startTag.length + currentSelection.length);
	}
	field.scrollTop = scroll;
}';

	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * unfold blocs, sort of "spoiler" type button
*
*/
function js_unfold($a) { 
	$sc='
function unfold(button) {
	var elem2hide = button.parentNode.getElementsByTagName(\'form\')[0];
	if (elem2hide.style.display !== \'\') {
		elem2hide.style.display = \'\';
	} else {
		elem2hide.style.display = \'none\';
	}
}';
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}	

/* 
 * When a file is uploaded, the input containing the html/bbcode code is clicable.
 * On clic, all text is selected.
*
*/
function js_select_text_on_focus($a) {
	$sc = '
function SelectAllText(id) {
	document.getElementById(id).focus();
	document.getElementById(id).select();
}';
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * In files list : you can hide the images (because ther is already a page 'images'.
*
*/
function js_hide_img_in_table($a) {
	$sc = '
function hideimages() {
	var tableau = document.getElementById(\'table-images\');
	var lignes = tableau.getElementsByTagName(\'tr\');
	var nbLignes = lignes.length;

	for (var i = 0; i < nbLignes; i++) {
		if (lignes[i].className == \'image\') {
			lignes[i].style.display = \'none\';
		}
	}
}';
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS : for image upload, switches between the FILE upload and URL specification 
 * (to send a file, one can give a file or an url from witch the file will be downloaded)
 * But not both can be given at the same time, so this JS helps activate one and desactivate the other.
*
*/
function js_switch_upload_form($a) {
	$sc = '
function switchUploadForm(where) {
	var formLink = document.getElementById(\'alternate-form-url\');
	var formFile = document.getElementById(\'alternate-form-file\');
	var formDrag = document.getElementById(\'alternate-form-dragndrop\');

	if (where == \'to_file\') {
		formLink.style.display = "none";
		formDrag.style.display = "none";
		formFile.style.display = "block";

		document.getElementById(\'img-others-infos\').style.display = "block";
		document.getElementById(\'img-submit\').style.display = "block";

		document.getElementById(\'fichier\').disabled = "";
		document.getElementById(\'fichier-url\').disabled = "0";
	}

	if (where == \'to_link\') {
		formFile.style.display = "none";
		formDrag.style.display = "none";
		formLink.style.display = "block"; 

		document.getElementById(\'img-others-infos\').style.display = "block";
		document.getElementById(\'img-submit\').style.display = "block";

		document.getElementById(\'fichier\').disabled = "0";
		document.getElementById(\'fichier-url\').disabled = "";
	}

	if (where == \'to_drag\') {
		formLink.style.display = "none";
		formFile.style.display = "none";
		formDrag.style.display = "block";

		document.getElementById(\'img-others-infos\').style.display = "none"; // not needed on DragNDrop
		document.getElementById(\'img-submit\').style.display = "none";

		document.getElementById(\'fichier\').disabled = "0";
		document.getElementById(\'fichier-url\').disabled = "0";
	}
}';
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS to add tags/labels ont links and articles.
*
*/

function js_addcategories($a) {
	$sc = '
function insertCatTag(inputId, tag) {
	var field = document.getElementById(inputId);
	if (field.value !== \'\') {
		field.value += \', \';
	}
	field.value += tag;
}';

	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

/*
 * JS to handle drag-n-drop : it listens to the event of draging files on a <div> and
 * opens web sockets with POST requests individualy for each file (in case many are draged-n-droped)
*
*/
function js_drag_n_drop_handle($a) {
	$max_file_size = return_bytes(ini_get('upload_max_filesize'));
	$sc = '

// pour le input des fichiers publics ou privés.
function statut_image() {
	var val = document.getElementById(\'statut-drag\').checked;
	if (val === false) {
		var al = \'\';
	}
	else {
		var al = \'on\';
	}
	return al;
}

// variables
var dropArea = document.getElementById(\'dragndrop-area\'); // drop area zone JS object
var count = document.getElementById(\'count\'); // text zone to display nb files done/remaining
var result = document.getElementById(\'result\'); // text zone where informations about uploaded files are displayed
var list = []; // file list
var nbDone = 0; // initialisation of nb files already uploaded during the process.


// init handlers
function initHandlers() {
	dropArea.addEventListener(\'drop\', handleDrop, false);
	dropArea.addEventListener(\'dragover\', handleDragOver, false);
	dropArea.addEventListener(\'mouseout\', handleMouseOut, false);
}

// drag over
function handleDragOver(event) {
	event.stopPropagation();
	event.preventDefault();
}

// drag drop
function handleDrop(event) {
	event.stopPropagation();
	event.preventDefault();
	processFiles(event.dataTransfer.files);
}

// process bunch of files
function processFiles(filelist) {
	if (!filelist || !filelist.length || list.length) return;
	result.innerHTML += \'\';
	for (var i = 0; i < filelist.length && i < 500; i++) { // limit is 500 files (only for not having an infinite loop)
		list.push(filelist[i]);
	}
	uploadNext();
}

// upload file
function uploadFile(file, status) {
	// prepare XMLHttpRequest
	var xhr = new XMLHttpRequest();
	xhr.open(\'POST\', \'_dragndrop.php\');
	xhr.onload = function() {
		result.innerHTML += this.responseText;
		uploadNext();
	};
	xhr.onerror = function() {
		result.innerHTML += this.responseText;
		uploadNext();
	};
	// prepare and send FormData
	var formData = new FormData();  
	formData.append(\'fichier\', file);
	formData.append(\'statut\', status);

	xhr.send(formData);
}

// upload next file
function uploadNext() {
	if (list.length) {
		var nb = list.length - 1;
		nbDone +=1;
		count.innerHTML = \'Files done: \'+nbDone+\' ; \'+\'Files left: \'+nb;

		var nextFile = list.shift();
		if (nextFile.size >= '.$max_file_size.') {
			result.innerHTML += \'<div class="f">File too big</div>\';
			uploadNext();
		} else {
			var status = statut_image();
			uploadFile(nextFile, status);
		}
	}
}

initHandlers();
';
	
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}

function js_html5_str_pad_time($a) {
$sc = '
function padz(field) {
	if (field.value.length == 1) {
		field.value = "0" + field.value;
	}
	if (field.value.length == 0) {
		field.value = "00";
	}
}
';
	if ($a == 1) {
		$sc = '<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;

}

