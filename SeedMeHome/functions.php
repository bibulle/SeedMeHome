<?php

require_once 'rutorrent-stats-master/rutorrent-stats.inc';
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
function GetSeedBoxInfo ($type, $host, $port, $user, $passwd, $ftp_done_path) {

	$ret = array();
  $r = new stdClass();
	// if Host is empty, return and do not access anything
	if (!$host || ($host == '')) {
		return $ret;
	}

  if ( strtolower($type) == "transmission" ) {
  	$rpc = new TransmissionRPC();
  	$rpc->url = 'http://'.$host.':'.$port.'/transmission/rpc';
  	$rpc->username = $user;
  	$rpc->password = $passwd;
  	//$rpc->debug = true;

  	$r = $rpc->session_stats( );
		//var_dump($r);
  } else {
    $seedboxes = array(
      'SeediBox' => array(
        'url'      => 'http://'.$host.':'.$port.'/manager/rutorrent/plugins/rpc/rpc.php',
        'username' => $user,
        'password' => $passwd,
        'authtype' => CURLAUTH_DIGEST,
        'rpc'      => 'xmlrpc',
      ),
    );

    //var_dump($seedboxes);
    $stats = rutorrent_stats($seedboxes)["SeediBox"];
		//var_dump($stats);
    if ( isset($stats->offline_reason) ) {
      $r->result = 'error';
      $r->offline_reason = $stats->offline_reason;
    } else {
      $r->result = 'success';
      $stats->torrents = [];
      $r->arguments = $stats;
    }
		//var_dump($r);
  }

	if (isset($r->result) && ($r->result == 'success')) {
		$ret['server_info'] = $r->arguments;
	} else {
		log_error("Wrong server response : ".$r->offline_reason);
		var_dump("Wrong server response : ".$r->offline_reason);
		//var_dump($r);
		return $ret;
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
function GetSeedBoxSeeds ($type, $host, $port, $user, $passwd, $home_dir, $ftp_done_path) {

	$ret = array();
  $r = new stdClass();

	// if Host is empty, return and do not access anything
	if (!$host || ($host == '')) {
		return $ret;
	}

  if ( strtolower($type) == "transmission" ) {
  	$rpc = new TransmissionRPC();
  	$rpc->url = 'http://'.$host.':'.$port.'/transmission/rpc';
  	$rpc->username = $user;
  	$rpc->password = $passwd;
  	//$rpc->debug = true;

  	$r = $rpc->get( array(), array("id", "name", "totalSize", "doneDate", "eta", "error", "errorString", "status", "rateDownload", "rateUpload", "percentDone", "timesCompleted", "files", "uploadRatio" ));
  } else {
    $seedboxes = array(
      'SeediBox' => array(
        'url'      => 'http://'.$host.':'.$port.'/manager/rutorrent/plugins/rpc/rpc.php',
        'username' => $user,
        'password' => $passwd,
        'authtype' => CURLAUTH_DIGEST,
        'rpc'      => 'xmlrpc',
      ),
    );

    $stats = rutorrent_stats($seedboxes)["SeediBox"];
		//var_dump($stats);
    if ( isset($stats->offline_reason) ) {
      $r->result = 'error';
      $r->offline_reason = $stats->offline_reason;
    } else {
      $r->result = 'success';
      $r->arguments = new stdClass();
      $r->arguments->torrents = $stats->torrents;
      
      foreach ($r->arguments->torrents as $key=>$value) {
      	 $files = rutorrent_files($seedboxes["SeediBox"], $value->hash, $value->directory, $home_dir);
      	 
      	 
      	 //var_dump($files);
      	 
      	$value->files = $files;
      }
      
    }
  }
	//var_dump($r);
	if (isset($r->result) && ($r->result == 'success')) {
		// we got it
		$ret['server_torrents'] = $r->arguments;
	} else {
		log_error("Wrong server response : ".$r->offline_reason);
		var_dump("Wrong server response : ".$r->offline_reason);
		//var_dump($r);
		return [];
	}

	// foreach torrent, we get ftp downloaded file to find match
	if (is_array($ret) && array_key_exists('server_torrents', $ret) && array_key_exists('torrents', $ret['server_torrents']) ) {
		foreach ($ret['server_torrents']->torrents as $num => $torrent) {
			$torrent->ftpSizeDone = 0;
			foreach ($torrent->files as $file) {
				$length = 0;
				if (property_exists($file, "length")) {
					$length = $file->length;
				}
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

function torrent_action ($type, $host, $port, $user, $passwd, $id, $action) {

  $r = new stdClass();

  if ( strtolower($type) == "transmission" ) {
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
  } else {
    $seedboxes = array(
      'SeediBox' => array(
        'url'      => 'http://'.$host.':'.$port.'/manager/rutorrent/plugins/rpc/rpc.php',
        'username' => $user,
        'password' => $passwd,
        'authtype' => CURLAUTH_DIGEST,
        'rpc'      => 'xmlrpc',
      ),
    );
    $ret = rutorrent_action($seedboxes["SeediBox"], $id, $action);
		var_dump($ret);
    if ( isset($stats->offline_reason) ) {
      $r->result = 'error';
      $r->offline_reason = $ret->offline_reason;
    } else {
      $r->result = 'success';
      
    }
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
			$text[$lines-$linecounter-1] = utf8_encode(fgets($handle));

			// On enleve les caractere qui font planter
			$text[$lines-$linecounter-1] = preg_replace("/[\r\n]/","",$text[$lines-$linecounter-1]);
			//$text[$lines-$linecounter-1] = htmlentities($text[$lines-$linecounter-1]);

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

function read_dir_ftp($lst, $homeDir, $user, $poll_this) {
	global $target_file_cleaner, $ftp_download_extension, $target_moving_extension, $user_role, $line_cpt, $poll_enable;

	$results = array();
	$cpt = 0;

  foreach ($lst as $dirinfo) {
    if ($dirinfo[2] == "..") {
      continue;
    } 
		$result["name"] = $dirinfo[2];
		//var_dump($dirinfo[2]." ".$dirinfo[3]." ".count (explode("/", $dirinfo[3])));
		$result["index"] =  1+count (explode("/", $dirinfo[3])) - count (explode("/", $homeDir));
		//var_dump($result["index"]." ".$dirinfo[2]." ".$dirinfo[3]." ".count (explode("/", $dirinfo[3])));

		$result["type"] = "dir";
		if ($dirinfo[0] == 0) {
			$result["type"] = "file";
		}

		$result["date"] = $dirinfo[4];
		$result["dateS"] = strtolower($dirinfo[4]->format('j M Y'));

    $filespec = $dirinfo[3]."/".$dirinfo[2];
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
					
			if (array_key_exists($dirinfo[2], $polls)) {
				if (array_key_exists($user, $polls[$dirinfo[2]])) {
					$result["mypoll"] = $polls[$dirinfo[2]][$user];
				}
				foreach ($polls[$dirinfo[2]] as $u => $val) {
					$result["allpoll"] += $val;
				}
			}
		}
		
		$results[] = $result;
  }
  
	// Just sort it
  foreach ($results as $result) {
    $paths[] = $result["source_url"];
    $map[$result["source_url"]] = $result;
  }		
  natcasesort($paths);
  
  foreach ($paths as $path) {
    $sortedResults[] = $map[$path];
  }



	return $sortedResults;
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


/** ===================== FTP =======================*/
function connectFTP($ftp_server, $ftp_user, $ftp_password) {
  // On se connecte au ftp
  $conn_id = ftp_connect($ftp_server);
  if (!$conn_id) {
  	log_error("Connection impossible (".$ftp_server.") : Connection refused");
  	die();
  }
  
  $login_result = @ftp_login($conn_id, $ftp_user, $ftp_password);
  if (!$login_result) {
  	log_error("Connection impossible (".$ftp_server.", ".$ftp_user.")");
  	exit;
  }
  return($conn_id);
}

function systypeFtp($conn_id) {
  // On recupere le type de serveur (ca pourrait servir...)
  $real_systyp = ftp_systype($conn_id);
  $systyp = $real_systyp;
  
  return $systyp;
}

/**
 * Methode d'analyse d'une ligne recupere d'un dir ftp (genre "drwxrwxrwx 3 eric eric 4096 Jan 26  2009 Desktop")
 * @param la ligne $dirline
 * @param le dir courant
 * @return un table (0 = tyep (2 l, 1 d, 0 f, -1 ?), 1 = taille, 2 = nom, 3 = le repertoire, 4 le nom du fichier correspondant)
 */
function analysedir($dirline, $dir, $systyp) {
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
		if(preg_match("/([-dl])[rwxst-]{9}[ ]+([0-9]*)[ ]+[0-9a-zA-Z]+[ ]+[0-9a-zA-Z]+[ ]+[0-9]+[ ]+([^ ]+[ ]+[^ ]+[ ]+[^ ]+) (.+)/",$dirline,$regs)) {
			$dirinfo[1] = $regs[2];
			$dirinfo[2] = $regs[4];
			if($regs[1]=="d")  {
				$dirinfo[0] = 1;
			}else if($regs[1]=="l") {
				$dirinfo[0] = 2;
				$dirinfo[2] = preg_replace("|[ ]*->.*$|", "", $dirinfo[2]);
			}
			
			// calculate date
		  $dirdate=DateTime::createFromFormat("Y M d H:i", date("Y")." ".$regs[3]);
      if (!$dirdate) {
        $dirdate=DateTime::createFromFormat("M d Y", $regs[3]);
      }
		  //var_dump($dirdate);
			$dirinfo[4] = $dirdate;
		  //$dirdate=date_parse_from_format("Y M d H:i", date("Y")." ".$regs[3]);
      //if ($dirdate["error_count"] != 0) {
      //  $dirdate=date_parse_from_format("M d Y", $regs[3]);
      //}
			//$dirinfo[4] = $dirdate;

		}
	}

	if(($dirinfo[2]==".")||($dirinfo[2]=="..")) $dirinfo[0]=0;

	return $dirinfo;
}

/**
 * Scan d'un repertoire
 * @param string $dir
 * @return un tableau de dirinfo (cf.analysedir)
 */
function scanftpdir($dir='/', $conn_id=null, $systyp=null, $addDirectories=false) {

	// We clean '//' 
	$dir = preg_replace("|//|","/", $dir);
	log_debug("scanning   $dir");
		
	$ret = array();

  if (!$conn_id) {
    return $ret;
  }
	// On se deplace dans le bon dir
	$chdir=@ftp_chdir($conn_id,$dir);
	if (!$chdir) {
	  log_error("ftp path not found : $dir");
	  return $ret;
  }

	// on recupere la liste brute (text)
	//$dirlist = ftp_rawlist($conn_id,$dir);
	$dirlist = ftp_rawlist($conn_id, ".");
	//var_dump($dirlist);

	// On parcourt la liste
	for($i=0;$i<count($dirlist);$i++) {
		$dirinfo = analysedir($dirlist[$i], $dir, $systyp);

		// Si c'est un repertoire (ou un lien)
		if (($dirinfo[0]==1) || ($dirinfo[0]==2)) {
			$newdir = "$dir/$dirinfo[2]";
			
			if ($addDirectories) {
			   $ret[] = $dirinfo;
      }
      
			$ret = array_merge($ret, scanftpdir($newdir, $conn_id, $systyp, $addDirectories));

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
 * Creation d'un repertoire
 * @param un path 
 * @ une connection FTP
 * @return TRUE ok; FALSE ko
 */
 function mkDirFtp($path, $conn_id) 
  { 
   $dir=split("/", $path); 
   $path=""; 
   $ret = true; 
   
   for ($i=0;$i<count($dir);$i++) 
   { 
       $path.="/".$dir[$i]; 
       //echo "$path\n"; 
       if(!@ftp_chdir($conn_id,$path)){ 
         @ftp_chdir($conn_id,"/"); 
         if(!@ftp_mkdir($conn_id,$path)){ 
          $ret=false; 
          break; 
         } 
       } 
   } 
   return $ret; 
  } 

/**
 * Copie d'un fichier local dans un Ftp
 * @param un path source
 * @param un pat cible
 * @ une connection FTP
 * @return TRUE ok; FALSE ko
 */
function putFtp($filename_tmp, $target_filename_tmp, $conn_id=null) {
  $ret = false;

  // Si c'est un repertoire, 
  if (is_dir($filename_tmp)) {
    //var_dump("DIR : ".$filename_tmp);
    
    // On le cree
    mkDirFtp($target_filename_tmp, $conn_id);
    
    // on ajoute les fils
    if ($handle = @opendir($filename_tmp)) {
		  while (false !== ($file = readdir($handle))) {
		    if (($file != ".") && ($file != "..")) {
		  	 //var_dump($file." ".$target_filename_tmp."/".$file);
		  	 $ret = putFtp($filename_tmp."/".$file, $target_filename_tmp."/".$file, $conn_id);
        }
		  }                  
		}
		$ret = true;

    
  } else {
    //var_dump("FIC : ".$filename_tmp);

    // On cree le repertoire fils
    mkDirFtp(dirname($target_filename_tmp), $conn_id);

    ini_set ('max_execution_time', 0); // Aucune limite d'execution
    // On lance l'upload
    $ret = ftp_nb_put($conn_id, $target_filename_tmp, $filename_tmp, FTP_BINARY, FTP_AUTORESUME);
    while ($ret == FTP_MOREDATA) {
		 
		  // Continue uploading...
      $ret = ftp_nb_continue($conn_id);
		
    }
    ini_set ('max_execution_time', 30); // Aucune limite d'execution
    if ($ret != FTP_FINISHED) {
      log_error("There was an error uploading the file...".$filename_tmp);
  		return FALSE;
    }
    $ret = true;
  }


  return $ret;
}

/**
 * Suppression d'un fichier dans un Ftp
 * @param un path 
 * @ une connection FTP
 * @return TRUE ok; FALSE ko
 */
function delFtp($conn_id, $filename_tmp, $systyp) {
  $ret = false;

  // try to delete the file
  $ret = ftp_delete($conn_id, $filename_tmp);
  if (!$ret) {
    // it could be a directory
    $ret = ftp_rmdir($conn_id, $filename_tmp);
    if (!$ret) {
      // it should not be empty
      $lst = scanftpdir($filename_tmp, $conn_id, $systyp, true);
      
      foreach ($lst as $dirinfo) {
        if ($dirinfo[2] == "..") {
          continue;
        } 
        delFtp($conn_id, $dirinfo[3]."/".$dirinfo[2], $systyp);
      }
      $ret = ftp_rmdir($conn_id, $filename_tmp);  
    }
  }


  // Si c'est un repertoire, 
//  if (is_dir($filename_tmp)) {
//    var_dump("DIR : ".$filename_tmp);
    
    // On le cree
//    mkDirFtp($target_filename_tmp, $conn_id);
    
    // on ajoute les fils
//    if ($handle = @opendir($filename_tmp)) {
//		  while (false !== ($file = readdir($handle))) {
//		    if (($file != ".") && ($file != "..")) {
//		  	 //var_dump($file." ".$target_filename_tmp."/".$file);
//		  	 $ret = putFtp($filename_tmp."/".$file, $target_filename_tmp."/".$file, $conn_id);
//        }
//		  }
//		}
//		$ret = true;
//
    
//  } else {
//    var_dump("FIC : ".$filename_tmp);
//
//    ini_set ('max_execution_time', 0); // Aucune limite d'execution
//    // On lance l'upload
//    $ret = ftp_nb_put($conn_id, $target_filename_tmp, $filename_tmp, FTP_BINARY, FTP_AUTORESUME);
//    while ($ret == FTP_MOREDATA) {
		 
		  // Continue uploading...
//      $ret = ftp_nb_continue($conn_id);
		
//    }
//    ini_set ('max_execution_time', 30); // Aucune limite d'execution
//    if ($ret != FTP_FINISHED) {
//      log_error("There was an error uploading the file...".$filename_tmp);
//  		return FALSE;
//    }
//    $ret = true;
//  }


  return $ret;
}


?>
