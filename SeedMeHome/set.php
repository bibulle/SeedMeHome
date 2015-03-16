<?php

require_once ('functions.php');
require_once "settings.php";

$user_role = isAuthenticate();

header('Content-type: text/html');

if (isset($_GET['do'])) {
	switch ($_GET['do']) {
		case 'pause':
			mustBeAdmin();
			torrent_action ($seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $_GET["id"], "stop");
			break;
		case 'restart':
			//pause or resume
			mustBeAdmin();
			torrent_action ($seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $_GET["id"], "start");
			break;
		case 'remove':
			mustBeAdmin();
			//remove from queue
			torrent_action ($seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $_GET["id"], "remove");
			break;
		case 'removeFile':
			mustBeAdmin();
			//suppression d'un fichier (ou repertoire)
			removeFile ($_GET["filename"]);
			break;
		case 'moveFile':
			mustBeAdmin();
			//deplacement d'un fichier (ou repertoire)
			moveFile ($_GET["filename"], $_GET["targetname"]);
			break;
		case 'pollFile':
			//vote for a file (or a directory)
			pollFile ($_GET["filename"], $_GET["state"], $_SERVER['PHP_AUTH_USER']);
			break;
		default :
			log_error("Erreur d'action set '".$_GET['do']."'");
			return;
	}
}

function removeFile( $filename ) {
	global $ftp_download_path, $target_path_movies, $target_path_tvshows;
	
	// Verify path (only in downloaded file or movie or tvshows)
	$t1 = preg_replace('/[\\\]/','[\\\\\\\\]',$ftp_download_path);
	$t2 = preg_replace('/[\\\]/','[\\\\\\\\]',$target_path_movies);
	$t3 = preg_replace('/[\\\]/','[\\\\\\\\]',$target_path_tvshows);
	$paths[] = '|^'.$t1.'|';
	$paths[] = '|^'.$t2.'|';
	$paths[] = '|^'.$t3.'|';
		
	// Verify path
	$ret_ok = FALSE;
	foreach($paths as $path) {
	  if (preg_match($path, $filename) && !preg_match("|[.][.]|", $filename)) {
		if (is_dir($filename)) {
			delete_directory($filename);
		} else if (is_file($filename)) {
			unlink($filename);
		}
		$ret_ok = TRUE;
		log_info("'".basename($filename)."' removed");
		break;
	  }
	}
	if (!$ret_ok) {
		log_warn("Suppression impossible : '".$filename."'");
	}
}

function delete_directory($dirname) {
	if (is_dir($dirname))
	$dir_handle = opendir($dirname);
	if (!$dir_handle)
	return false;
	while($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file))
			unlink($dirname."/".$file);
			else
			delete_directory($dirname.'/'.$file);
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	return true;
}
function moveFile( $filename, $targetname ) {
	global $ftp_download_path, $target_path_movies, $target_path_tvshows, $target_tvshows_regexp, $target_tvshows_format, $target_moving_extension, $log_file;
	
	// Verify path (only in downloaded file or movie or tvshows)
	$t1 = preg_replace('/[\\\]/','[\\\\\\\\]',$ftp_download_path);
	$t2 = preg_replace('/[\\\]/','[\\\\\\\\]',$target_path_movies);
	$t3 = preg_replace('/[\\\]/','[\\\\\\\\]',$target_path_tvshows);
	$paths[] = '|^'.$t1.'|';
	$paths[] = '|^'.$t2.'|';
	$paths[] = '|^'.$t3.'|';

	// Verify path
	$ret_ok = FALSE;
	foreach($paths as $path) {
	  if (preg_match($path, $filename) && !preg_match("|[.][.]|", $filename) && !preg_match("|[.][.]|", $targetname)) {
		// On calcul le path cible
		$target_filename = "$target_path_movies/$targetname";
		foreach ($target_tvshows_regexp as $regexp) {
			if (preg_match($regexp, $targetname, $matches)) {
				$target_filename =  $target_path_tvshows.sprintf($target_tvshows_format, trim($matches[1]), $matches[2], $matches[3], trim($matches[4]), trim($matches[5]), $matches[0]);
				// We clean end of it
				var_dump($target_filename);
				$temp = $target_filename;
				while ($target_filename != preg_replace('/(.*)[- .]+([.].*$)/','${1}${2}',$target_filename)) {
					$target_filename = preg_replace('/(.*)[- .]+([.].*$)/','${1}${2}',$target_filename);
				}
				var_dump($target_filename);
				break;
			}
		}
		
		@mkdir(dirname($target_filename), 0777 , TRUE);
		
		log_debug("moving '".basename($filename)."' to '$target_filename'");
		
		// We rename the source file to .moving
		$filename_tmp = $filename.$target_moving_extension;
		$ret = rename($filename, $filename_tmp);
		
		// We realy move the file (can be long) to a target .moving file
		$target_filename_tmp = $target_filename.$target_moving_extension;
		// We try to a do in two step (for partition)
		$ret = rename($filename_tmp, $target_filename_tmp);
		if (!$ret) {
			$cmd = 'mv "'.$filename_tmp.'" "'.$target_filename_tmp.'" >> '.$log_file.' 2>&1';
			$output = array();
			exec($cmd);
		}
		
		// We rename the target file .moving file to the real target
		$ret = rename($target_filename_tmp, $target_filename);
		if (is_dir($target_filename)) {
			chmod($target_filename, 0775);
		} else {
			chmod($target_filename, 0664);
		}
		log_info("'".basename($filename)."' moved to '$target_filename'");
		$ret_ok = TRUE;
		break;
	  }
	} 
	if (!$ret_ok) {
		log_warn("Deplacement impossible : '$filename' not in right dir");
	}
}


?>
