<?php

require_once 'TransmissionRPC.class.php';

/**
 * Get free space in a disk
 * @param String $disk
 */
function freediskspace($disk) {
	$size = disk_free_space($disk);
	return $size;
}

/**
 * get nice file size
 * @param $size
 */
function formatSize($size){
	switch (true){
		case ($size > 1099511627776):
			$size /= 1099511627776;
			$suffix = 'TB';
			break;
		case ($size > 1073741824):
			$size /= 1073741824;
			$suffix = 'GB';
			break;
		case ($size > 1048576):
			$size /= 1048576;
			$suffix = 'MB';
			break;
		case ($size > 1024):
			$size /= 1024;
			$suffix = 'KB';
			break;
		default:
			$suffix = 'B';
	}
	return round($size, 2).$suffix;
}

/**
 * Trad sevcond to Hour:minute
 * @param number $sec
 * @param boolean $padHours
 */
function sec2hms ($sec, $padHours = false)
{
	$hms = "";
	$hours = intval(intval($sec) / 3600);
	$hms .= ($padHours)
	? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
	: $hours. ':';
	$minutes = intval(($sec / 60) % 60);
	$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
	$seconds = intval($sec % 60);
	$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
	return $hms;
}

/**
 * Get all info from seedBox
 * @param String $host
 * @param Number $port
 * @param String $user
 * @param String $passwd
 */
function GetSeedBoxInfo ($host, $port, $user, $passwd, $ftp_done_path) {

	$ret = array();

	// if Host is empty, return and do not access anything
	if (!$host || ($host == '')) {
		return $ret;
	}

	$rpc = new TransmissionRPC();
	$rpc->url = 'http://'.$host.':'.$port.'/transmission/rpc';
	$rpc->username = $user;
	$rpc->password = $passwd;
	//$rpc->debug = true;

	$r = $rpc->session_stats( );

	if (isset($r->result) && ($r->result == 'success')) {
		$ret['server_info'] = $r->arguments;
	} else {
		log_error("Wrong server response");
		//var_dump($r);
		return;
	}

	return $ret;
}
/**
 * Get all info from seedBox
 * @param String $host
 * @param Number $port
 * @param String $user
 * @param String $passwd
 */
function GetSeedBoxSeeds ($host, $port, $user, $passwd, $ftp_done_path) {

	$ret = array();

	// if Host is empty, return and do not access anything
	if (!$host || ($host == '')) {
		return $ret;
	}

	$rpc = new TransmissionRPC();
	$rpc->url = 'http://'.$host.':'.$port.'/transmission/rpc';
	$rpc->username = $user;
	$rpc->password = $passwd;
	//$rpc->debug = true;

	$r = $rpc->get( array(), array("id", "name", "totalSize", "doneDate", "eta", "error", "errorString", "status", "rateDownload", "rateUpload", "percentDone", "timesCompleted", "files", "uploadRatio" ));

	if (isset($r->result) && ($r->result == 'success')) {
		// we got it
		$ret['server_torrents'] = $r->arguments;
	} else {
		log_error("Wrong server response");
		//var_dump($r);
		return;
	}

	// foreach torrent, we get ftp downloaded file to find match
	if (is_array($ret) && array_key_exists('server_torrents', $ret)) {
		foreach ($ret['server_torrents']->torrents as $num => $torrent) {
			$torrent->ftpSizeDone = 0;
			foreach ($torrent->files as $file) {
				$length = $file->length;
				$name = $file->name;

				// We are searching for curr and done file (ftp in progress and done)
				$name_ = preg_replace("|[\\\/]|", "_",$name);
				$ficcurr = $ftp_done_path.'/'.$name_.'.curr';
				$ficdone = $ftp_done_path.'/'.$name_.'.done';
				if (file_exists($ficcurr)) {
					$content = file_get_contents($ficcurr) ;
					if (preg_match("/([.0-9]+) ([0-9]+)/",$content,$regs)) {
						$torrent->ftpSizeDone+=$regs[2];
					}
				} elseif (file_exists($ficdone)) {
					$torrent->ftpSizeDone+=$length;
				}
			}
			$ret['server_torrents']->torrents[$num] = $torrent;
		}
	}

	return $ret;
}

function torrent_action ($host, $port, $user, $passwd, $id, $action) {

	$rpc = new TransmissionRPC();
	$rpc->url = 'http://'.$host.':'.$port.'/transmission/rpc';
	$rpc->username = $user;
	$rpc->password = $passwd;

	switch ($action) {
		case 'start':
			$r = $rpc->start(0+$id);
			break;

		case 'stop':
			$r = $rpc->stop(0+$id);
			break;

		case 'remove':
			$r = $rpc->remove(0+$id, true);
			break;

		default :
			log_error("Erreur d'action sur le torrent '".$action."'");
			return;
	}

	if (isset($r->result) && ($r->result == 'success')) {
		var_dump($r);
	} else {
		log_error("Reponse serveur erroné");
		var_dump($r);
		return;
	}


}

function read_file($file, $lines) {
	//global $fsize;
	$handle = @fopen($file, "r");

	$text = array();
	if ($handle) {
		$linecounter = $lines;
		$pos = -2;
		$beginning = false;
		while ($linecounter > 0) {
			$t = " ";
			while ($t != "\n") {
				if(fseek($handle, $pos, SEEK_END) == -1) {
					$beginning = true;
					break;
				}
				$t = fgetc($handle);
				$pos --;
			}
			$linecounter --;
			if ($beginning) {
				rewind($handle);
			}
			$text[$lines-$linecounter-1] = fgets($handle);

			// On enleve les caractere qui font planter
			$text[$lines-$linecounter-1] = preg_replace("/[\r\n]/","",$text[$lines-$linecounter-1]);
			$text[$lines-$linecounter-1] = htmlentities($text[$lines-$linecounter-1]);

			if ($beginning) break;
		}
		fclose ($handle);
	} else {
		log_error("file cannot be open : '".$file."'");
	}

	return array_reverse($text);
}

function read_dir($dir, $ind, $user, $poll_this) {
	global $target_file_cleaner, $ftp_download_extension, $target_moving_extension, $user_role, $line_cpt, $poll_enable;

	$results = array();
	$cpt = 0;

	if ($handle = @opendir($dir)) {
		$list = array();
		while (false !== ($file = readdir($handle))) {
			$list[] = $file;
		}
		natcasesort($list);
		foreach ($list as $file) {
			if ($file != "." && $file != "..") {
				$result["name"] = $file;
				
				$filespec = $dir."/".$file;
				$result["index"] = $ind;
				$result["type"] = "dir";
				if (is_file($filespec)) {
					$result["type"] = "file";
				}
				
				$filespec = $dir."/".$file;
				$source_url = urlencode($filespec);
				$result["source_url"] = $source_url;
				
				$target_file=$filespec;
				// target file name cleaning
				foreach($target_file_cleaner as $regexp) {
					$target_file = preg_replace('/[ .-]*'.$regexp.'([ .-]*)/i','$1',$target_file);
				}
				$target_url = urlencode($target_file);
				$result["target_url"] = $target_url;
				
				if ($user_role == 'admin') {
					$result["editable"] = "yes";
				} else {
					$result["editable"] = "no";
				}

				if (preg_match("/".$ftp_download_extension."$/",$filespec) || preg_match("/".$target_moving_extension."$/",$filespec)) {
					$result["temp"] = "yes";
				} else {
					$result["temp"] = "no";
				}
				
				// poll
				if ($poll_enable && $poll_this) {
					// load polls
					$polls = loadPolls();

					$result["mypoll"] = 0;
					$result["allpoll"] = 0;
					
					if (array_key_exists($file, $polls)) {
						if (array_key_exists($user, $polls[$file])) {
							$result["mypoll"] = $polls[$file][$user];
						}
						foreach ($polls[$file] as $u => $val) {
							$result["allpoll"] += $val;
						}
					}
					
					
				}
				$results[] = $result;
				if (!is_file($filespec)) {
					$results = array_merge($results, read_dir($filespec, $ind+1, $user, $poll_this));
				}
			}
		}
		closedir($handle);
	}

	return $results;
}


/**
 * Ecritude dans un  fichier de log (en niveau 1)
 * @param string $message
 */
function log_error ($message) {
	filelog ($message, 1);
}
/**
 * Ecritude dans un  fichier de log (en niveau 2)
 * @param string $message
 */
function log_warn ($message) {
	filelog ($message, 2);
}
/**
 * Ecritude dans un  fichier de log (en niveau 3)
 * @param string $message
 */
function log_info ($message) {
	filelog ($message, 3);
}
/**
 * Ecritude dans un  fichier de log (en niveau 4)
 * @param string $message
 */
function log_debug ($message) {
	filelog ($message, 4);
}
/*-------------------------------
 * Methode permettant d'écrire dans le fichier de log
 *   plutot utiliser log_debug, log_info, log_warn et log_error
 *-------------------------------*/
/**
 * Ecritude dans un  fichier de log
 *    plutot utiliser log_debug, log_info, log_warn et log_error
 * @param string $message
 * @param int $level
 */
function filelog ($message, $level) {
	global $debug_level, $log_file;

	date_default_timezone_set('Europe/Paris');
	$LOG_LEVEL_STR = array (1=>"E", 2=>"W", 3=>"I", 4=>"D");

	if ( $level <= $debug_level) {
		// On calcul la date
		$now = new DateTime();
		$nowF = $now->format('Y-m-d H:i:s');
		// On supprime le retour chariot à la fin du message
		$message = preg_replace("|[\r\n]*$|", "", $message);

		// On ecrit le message
		$f = fopen ($log_file,"a");
		flock($f, LOCK_EX);
		fwrite ($f, $nowF." ".$LOG_LEVEL_STR[$level]." ".$message."\n");
		//echo $nowF." ".$LOG_LEVEL_STR[$level]." ".$message." <br>\n";
		fclose ($f);
	}
}

/**
 * Function to verify if the user is authenticate
 * return the role of the user
 */
function isAuthenticate() {
	global $users;

	// Is it protected ?
	if (isset($users) && (is_array($users)) && (!empty($users))) {
		// Are we authenticate
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			authenticate();
			exit();
		} else {
			foreach ($users as $user) {
				if (isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER'] == $user['login']) && isset($_SERVER['PHP_AUTH_PW']) && ($_SERVER['PHP_AUTH_PW'] == $user['password'])) {
					return $user['role'];
				}
			}
			authenticate();
			exit();
		}
	}

}

/**
 * ask for authentication
 */
function authenticate() {
	header('WWW-Authenticate: Basic realm="My Realm"');
	header('HTTP/1.0 401 Unauthorized');
	$_SERVER['REDIRECT_STATUS'] = '401';
	include('403.php');
}

/**
 * exit with 304 if not admin
 */
function mustBeAdmin() {
	global $user_role;
	if ($user_role != "admin") {
		include('403.php');
		exit;
	}
}

function loadPolls() {
  $handle = @fopen("polls.db", "rb");

  if (!$handle) {
    return array();
  }
  
  $contents = '';
  while (!feof($handle)) {
    $contents .= fread($handle, 8192);
  }
  fclose($handle);

  return unserialize($contents);
}

function savePolls($polls) {
  
  $handle = fopen("polls.db", "w");

  if (flock($handle, LOCK_EX)) { // do an exclusive lock
    ftruncate($handle, 0); // truncate file
    fwrite($handle, serialize($polls));
    flock($handle, LOCK_UN); // release the lock
  }

  fclose($handle);

}

function pollFile($filename, $state, $user) {
	var_dump($filename, $state, $user);
	
	$polls = loadPolls();
	
	if ($state == 1) {
		$state=0;
	} else {
		$state=1;
	}
	
	$polls[$filename][$user]=$state;
	var_dump($polls);
	savePolls($polls);
}


?>
