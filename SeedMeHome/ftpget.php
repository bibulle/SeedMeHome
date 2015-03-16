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

// On se connecte au ftp
$conn_id = ftp_connect($ftp_server);
if (!$conn_id) {
	log_error("Connection impossible (".$ftp_server.")");
	die();
}

$login_result = @ftp_login($conn_id, $ftp_user, $ftp_password);
if (!$login_result) {
	log_error("Connection impossible (".$ftp_server.", ".$ftp_user.")");
	exit;
}

// On recupere le type de serveur (ca pourrait servir...)
$real_systyp = ftp_systype($conn_id);
$systyp = $real_systyp;

// On recupere la liste des fichiers present sur le FTP
log_debug("Scanning (".$ftp_server.", ".$ftp_user.")");
$files = scanftpdir($ftp_path);

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
		unlink($ftp_done_path.DIRECTORY_SEPARATOR.$file);
		log_debug("Suppress '".$file."'");
	}
}

log_debug("end");

/**
 * Recupere le fichier distant en local
 * @param un dirinfo  (cf.analysedir)
 * @return TRUE ok; FALSE ko
 */
function getftp($dirinfo) {
	global $ftp_download_path, $ftp_download_extension, $conn_id, $ftp_done_path;

	@mkdir($ftp_download_path);

	$source=preg_replace("|//|","/", $dirinfo[3].'/'.$dirinfo[2]);
	$cibletmp = $ftp_download_path.DIRECTORY_SEPARATOR.$source.$ftp_download_extension;
	$cible = $ftp_download_path.DIRECTORY_SEPARATOR.$source;

	log_debug("...... ".$source);
	@mkdir(dirname($cibletmp), 0777, TRUE);
	touch($cibletmp);

	// On se deplace dans le bon repertoire ftp
	// On lance le download
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
/**
 * Scan d'un repertoire
 * @param string $dir
 * @return un tableau de dirinfo (cf.analysedir)
 */
function scanftpdir($dir='/') {
	global $conn_id,$filetyps,$exectyps,$ftp_server;

	// We clean '//' 
	$dir = preg_replace("|//|","/", $dir);
	log_debug("scanning   $dir");
		
	$ret = array();

	// On se deplace dans le bon dir
	$chdir=ftp_chdir($conn_id,$dir);

	// on recupere la liste brute (text)
	$dirlist = ftp_rawlist($conn_id,$dir);

	// On parcourt la liste
	for($i=0;$i<count($dirlist);$i++) {
		$dirinfo = analysedir($dirlist[$i], $dir);

		// Si c'est un repertoire (ou un lien)
		if (($dirinfo[0]==1) || ($dirinfo[0]==2)) {
			$newdir = "$dir/$dirinfo[2]";
			
			$ret = array_merge($ret, scanftpdir($newdir));

			// On revient au repertoire d'avant pour continuer
			ftp_chdir($conn_id,$dir);
		} elseif($dirinfo[0]==0) {
			
			// Si ce n'est pas un .part on l'ajoute
			if (!preg_match("/[.]part$/",$dirinfo[2])) {
				$ret[] = $dirinfo;
			}
		}

	}
	return $ret;
}
/**
 * Methode d'analyse d'une ligne recupere d'un dir ftp (genre "drwxrwxrwx 3 eric eric 4096 Jan 26  2009 Desktop")
 * @param la ligne $dirline
 * @param le dir courant
 * @return un table (0 = tyep (2 l, 1 d, 0 f, -1 ?), 1 = taille, 2 = nom, 3 = le repertoire, 4 le nom du fichier correspondant)
 */
function analysedir($dirline, $dir) {
	global $systyp,$ftp_server,$stop;

	if(preg_match("/([-dl])[rwxst-]{9}/",substr($dirline,0,10))) {
		$systyp = "UNIX";
	}
	$dirinfo[0] = 0;
	$dirinfo[1] = 1;
	$dirinfo[2] = "";
	$dirinfo[3] = $dir;
	if(substr($dirline,0,5) == "total") {
		$dirinfo[0] = -1;
	} elseif($systyp=="Windows_NT") {
		if(preg_match("/[-0-9]+ *[0-9:]+[PA]?M? +<DIR> {10}(.*)/",$dirline,$regs)) {
			$dirinfo[0] = 1;
			$dirinfo[1] = 0;
			$dirinfo[2] = $regs[1];
		} elseif(preg_match("/[-0-9]+ *[0-9:]+[PA]?M? +([0-9]+) (.*)/",$dirline,$regs)) {
			$dirinfo[0] = 0;
			$dirinfo[1] = $regs[1];
			$dirinfo[2] = $regs[2];
		}
	} elseif($systyp=="UNIX") {
		if(preg_match("/([-dl])[rwxst-]{9}.* ([0-9]*) [a-zA-Z]+ [0-9: ]*[0-9] (.+)/",$dirline,$regs)) {
			$dirinfo[1] = $regs[2];
			$dirinfo[2] = $regs[3];
			if($regs[1]=="d")  {
				$dirinfo[0] = 1;
			}else if($regs[1]=="l") {
				$dirinfo[0] = 2;
				$dirinfo[2] = preg_replace("|[ ]*->.*$|", "", $dirinfo[2]);
			}
		}
	}

	if(($dirinfo[2]==".")||($dirinfo[2]=="..")) $dirinfo[0]=0;

	return $dirinfo;
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