<?php

require_once ('functions.php');
require_once "settings.php";

$user_role = isAuthenticate();

// For debug purpose, html answer for direct call else json
if (array_key_exists('HTTP_REFERER', $_SERVER)) {
	header('Content-type: application/json');
} else {
	header('Content-type: text/html');
}
if (isset($_GET['do'])) {
	// add user role
	$info['user_role'] = $user_role;
	switch ($_GET['do']) {
		
		case 'getStatus':
				
			// Getting info from torrent
			$info = array_merge($info, GetSeedBoxInfo($seedbox_type, $seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $ftp_done_path));
				
			// Getting free space on disk
			foreach ($disks as $name => $path) {
				$info['freedisk'][] = array ( 'name' => $name, 'size' => @freediskspace($path));
				//var_dump($info);
			}

			// Getting server time
			$info['eta'] = date("d/m/Y H:i:s");
			break;

		case 'getSeedQueue':
				
			// Getting info from torrent
			$info = array_merge($info, GetSeedBoxSeeds($seedbox_type, $seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $seedbox_home_dir, $ftp_done_path));
			break;

		case 'getLog':
			// Reading log file
			$lines = read_file($log_file, 20);
			$info['log_entries'] = $lines;
			//var_dump($info);
			break;

		case 'getDoneFiles':
			// Reading file that have been retrieve throught ftp
			$lines = read_dir($ftp_download_path, 1, $_SERVER['PHP_AUTH_USER'], false);
			$info['files'] = $lines;
			break;
			
		case 'getMovies':
		  // get the sort
		  if (isset($_GET['do']) && ($_GET['movies_sort'] == "false")) {
		    $sort = true;
      } else {
		    $sort = false;
      }
			// Reading file that have been retrieve throught ftp
			$lines = read_dir($target_path_movies_old, 1, $_SERVER['PHP_AUTH_USER'], true);

			// Reading file that have been retrieve throught ftp
			if (isset($target_ftp_server)) {
        // On se connecte au ftp
        $conn_id = connectFTP($target_ftp_server, $target_ftp_user, $target_ftp_password);
        if (!$conn_id) {
        	exit;
        }
        $systyp = systypeFtp($conn_id);
        
        $lst = scanftpdir($target_path_movies, $conn_id, $systyp, true);
			  $lines = array_merge($lines, read_dir_ftp($lst, $target_path_movies, $_SERVER['PHP_AUTH_USER'], true));
			  
			  if ($sort) {
			    usort($lines, function($a, $b) {
            return $a["date"] < $b["date"];
          });
        }
        
        @ftp_close($conn_id);

      }

			$info['files'] = $lines;
			break;
			
		case 'getTvShows':
 			$lines = read_dir($target_path_tvshows_old, 1, $_SERVER['PHP_AUTH_USER'], true);


			// Reading file that have been retrieve throught ftp
			if (isset($target_ftp_server)) {
        // On se connecte au ftp
        $conn_id = connectFTP($target_ftp_server, $target_ftp_user, $target_ftp_password);
        if (!$conn_id) {
        	exit;
        }
        $systyp = systypeFtp($conn_id);
        
        $lst = scanftpdir($target_path_tvshows, $conn_id, $systyp, true);
        $lines = array_merge($lines, read_dir_ftp($lst, $target_path_tvshows, $_SERVER['PHP_AUTH_USER'], true));
        
        @ftp_close($conn_id);

      }
			
			$info['files'] = $lines;
			break;
			
		default :
			log_error("Erreur d'action get '".$_GET['do']."'");
			return;
	}
	echo json_encode($info);
}



?>
