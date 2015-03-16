<?php

require_once "settings.php";
require_once "functions.php";

isAuthenticate();

log_debug("starting file upload : ".$_GET['item']);
log_debug($_FILES['torrentfile']['name']);

if (isset($_GET['item'])) {
	switch ($_GET['item']) {
		//		case 'id':
		//			filelog ("enqueuenewzbin ".$_POST['newzbinid']);
		//        	$result = SetQueue ($host, $port, $user, $passwd, "enqueuenewzbin", $_POST['newzbinid']);
		//			//Update screen with upload status
		//			echo '<script language="javascript" type="text/javascript">';
		//			echo 'window.top.window.onuploadidReady(\'newzbin id '.$_POST['newzbinid'].' successfully uploaded\'); </script>';
		//			break;
		case 'torrent':
			log_debug ('uploading torrent file');
			if (isset ($_FILES['torrentfile'])) {
				$upload_status = upload_file($_FILES['torrentfile']);
				//echo '<meta http-equiv="refresh" content="10">';
				log_debug ($upload_status);
			} else {
				$upload_status = 'Nothing selected to upload';
			}
			//show upload status on screen
			echo '<script language="javascript" type="text/javascript">';
			echo 'window.top.window.onuploadtorrentReady(\''.$upload_status.'\'); </script>';
			break;
	}
}

function upload_file ($torrent_file) {
	global $seedbox_host, $seedbox_port, $seedbox_user, $seedbox_passwd ;

	$error = validate_upload($torrent_file);
	if ($error) {
		//print ($error);
	} else { # cool, we can continue
		// Getting the content
		$fileData = file_get_contents($torrent_file['tmp_name']);

		// Just connect to transmission and send the file
		$rpc = new TransmissionRPC();
		$rpc->url = 'http://'.$seedbox_host.':'.$seedbox_port.'/transmission/rpc';
		$rpc->username = $seedbox_user;
		$rpc->password = $seedbox_passwd;

		$r = $rpc->add_metainfo($fileData);

		if (isset($r->result) && ($r->result == 'success')) {
			$error = "File upload OK";
		} else {
			log_error("Torrent upload : ".$r->result);
			$error = $r->result;
		}

	}
	return ($error);
}

function validate_upload($torrent_file) {
	$error = "";
	global $download_max_filesize;
	if ($torrent_file['error'] <> 0) {
		if ($torrent_file['error'] == 4) { # do we even have a file?
			$error = "You did not upload anything !";
		}
		if ($torrent_file['error'] == 2) { # Think the file is to big
			$error = "Filesize is bigger then allowed !";
		}
	} else { # check size and file type
		$ext = explode(".", $torrent_file['name']);
		$nr    = count($ext);
		$ext  = $ext[$nr-1];
		if ($ext <> 'torrent') {
			$error = "The file " .$torrent_file['name']. " is not a torrent file!";
		}
		if ($torrent_file['size'] > $download_max_filesize) {
			$error = "The file " .$torrent_file['name']. " is bigger than " .$download_max_filesize. " bytes!";
		}
	}
	return $error;
}
?>
