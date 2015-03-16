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
			$info = array_merge($info, GetSeedBoxInfo($seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $ftp_done_path));
				
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
			$info = array_merge($info, GetSeedBoxSeeds($seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd, $ftp_done_path));
			break;

		case 'getLog':
			// Reading log file
			$lines = read_file($log_file, 20);
			$info['log_entries'] = $lines;
			break;

		case 'getDoneFiles':
			// Reading file that have been retrieve throught ftp
			$lines = read_dir($ftp_download_path, 1, $_SERVER['PHP_AUTH_USER'], false);
			$info['files'] = $lines;
			break;
			
		case 'getMovies':
			// Reading file that have been retrieve throught ftp
			$lines = read_dir($target_path_movies, 1, $_SERVER['PHP_AUTH_USER'], true);
			$info['files'] = $lines;
			break;
			
		case 'getTvShows':
			// Reading file that have been retrieve throught ftp
			$lines = read_dir($target_path_tvshows, 1, $_SERVER['PHP_AUTH_USER'], true);
			$info['files'] = $lines;
			break;
			
		default :
			log_error("Erreur d'action get '".$_GET['do']."'");
			return;
	}
	echo json_encode($info);
}



?>
