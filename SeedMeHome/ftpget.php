<?php

require_once ('functions.php');
require_once "settings.php";

// recuperation du path courant
$script = $_SERVER['SCRIPT_FILENAME'];
$path = realpath(dirname($script));

$lock_file = $path.DIRECTORY_SEPARATOR.basename($script, '.php').'.lock';


// On test le fichier lock pour qu'il n'y est qu'un seul process
$fp = fopen($lock_file, "w+");
if (!flock($fp, LOCK_EX | LOCK_NB)) {
	log_debug("Couldn't get the lock !");
	die();
}
log_debug("Start");

$conn_id = connectFTP($ftp_server, $ftp_user, $ftp_password);
if (!$conn_id) {
  exit();
}
$systyp = systypeFtp($conn_id);

// On recupere la liste des fichiers present sur le FTP
log_debug("Scanning (".$ftp_server.", ".$ftp_user.")");
$files = scanftpdir($ftp_path, $conn_id, $systyp);
#var_dump($files);

// List a attended done file
$ftp_done_files = array();

// On parcourt cette liste pour voir si on les a deja recupere
for($i=0;$i<count($files);$i++) {
	$file = $files[$i];

	$ftp_done_files[] = preg_replace("|.*[\\\/]|","",getdonefilename($file));
	
	// Si le fichier done existy, on passe au suivant
	if (file_exists(getdonefilename($file))) {
		continue;
	}

	// On lance le transfert FTP
	if (getFtp($file)) {

		// On ajoute dans le repertoire done
		log_info("Ftp done : ".preg_replace("|//|","/", $file[3]."/".$file[2]));
		@mkdir($ftp_done_path);
		touch(getdonefilename($file));
	}
}

// We just suppress old done files
$done_files = scandonedir($ftp_done_path);

for($i=0;$i<count($done_files);$i++) {
	$file = $done_files[$i];
	if (!in_array($file, $ftp_done_files)) {
		// We check it's old (two days)
		$filetime = filemtime($ftp_done_path.DIRECTORY_SEPARATOR.$file);
		$timenow = time();

		if (($timenow - $filetime) >= 3600*48) {
			unlink($ftp_done_path.DIRECTORY_SEPARATOR.$file);
			log_debug("Suppress '".$file."'");
		}
	} else {
		touch($ftp_done_path.DIRECTORY_SEPARATOR.$file);
	}
}

log_debug("end");

/**
 * Recupere le fichier distant en local
 * @param un dirinfo  (cf.analysedir)
 * @return TRUE ok; FALSE ko
 */
function getftp($dirinfo) {
	global $ftp_download_path, $ftp_download_extension, $conn_id, $ftp_done_path, $ftp_path;

	@mkdir($ftp_download_path);

	$source=preg_replace("|//|","/", $dirinfo[3].'/'.$dirinfo[2]);
	$target = preg_replace("|^".$ftp_path."(/*)|","",$source); 
	$cibletmp = $ftp_download_path.DIRECTORY_SEPARATOR.$target.$ftp_download_extension;
	$cible = $ftp_download_path.DIRECTORY_SEPARATOR.$target;

	log_debug("...... ".$source);
	@mkdir(dirname($cibletmp), 0777, TRUE);
	touch($cibletmp);

	// On se deplace dans le bon repertoire ftp
	// On lance le download
	##var_dump($cibletmp);
	#var_dump($source);
	$ret = ftp_nb_get($conn_id, $cibletmp, $source, FTP_BINARY, FTP_AUTORESUME);
	while ($ret == FTP_MOREDATA) {
		 
		// On cree le fichier pour le suivi
		clearstatcache();
		$size = filesize($cibletmp);
		
		@mkdir($ftp_done_path);
		$fp = fopen(getcurrentfilename($dirinfo), "w");
		fwrite($fp, ($size/$dirinfo[1])." ".$size);
		fclose($fp);
		
		// We try to wait
		//usleep(2000000);
		
		// Continue downloading...
		$ret = ftp_nb_continue($conn_id);
		
	}
	if ($ret != FTP_FINISHED) {
		log_error("There was an error downloading the file...".$source);
		return FALSE;
	}
	

	// On met le truc dans le bon rep.
	@mkdir(dirname($cible), 0777, TRUE);
	@unlink($cible);
	@rename($cibletmp, $cible);

	// On vire le current
	@unlink(getcurrentfilename($dirinfo));
	


	return TRUE;
}
/**
 * Calcul le done file name d'un fichier
 * @param un dirinfo  (cf.analysedir)
 */
function getcurrentfilename($dirinfo) {
	global $ftp_done_path;

	$name = preg_replace("|[\\\/]|","_", $dirinfo[3].DIRECTORY_SEPARATOR.$dirinfo[2]);
	$name = preg_replace("|^[_]*|","", $name);
	
	return $ftp_done_path.DIRECTORY_SEPARATOR.$name.'.curr';
}
/**
 * Calcul le done file name d'un fichier
 * @param un dirinfo  (cf.analysedir)
 */
function getdonefilename($dirinfo) {
	global $ftp_done_path;

	$name = preg_replace('|[\\\/]|','_', $dirinfo[3].DIRECTORY_SEPARATOR.$dirinfo[2]);
	$name = preg_replace("|^[_]*|","", $name);
		
	return $ftp_done_path.DIRECTORY_SEPARATOR.$name.'.done';
}

function scandonedir($dirname) {
	
	$ret = array();
	
	if (is_dir($dirname)) {
		$dir_handle = opendir($dirname);

		if ($dir_handle) {
			while($file = readdir($dir_handle)) {
				if ($file != "." && $file != "..") {
					$ret = array_merge($ret, scandonedir($file));
				}
			}
			closedir($dir_handle);
		}
	} else {
		$ret[] = $dirname;
	}

	return $ret;
}

?>
