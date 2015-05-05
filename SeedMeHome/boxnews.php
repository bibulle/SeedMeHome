<?php

require_once ('functions.php');
require_once "settings.php";

/*-- Max number of results --*/
$MAX_NB_RES = 8;

/*-- Path to wallpaper image --*/
$PATHTOWALLPAPER = "img/";
$PATHTOWALLPAPERTARGET = "/Disque dur/Photos/";
$WALLPAPERNAME = "wallpaper.png";

/*-- Writings --*/
$FONT = "styles/OldSansBlack.ttf";
$FONTSIZE = 24;
$MARGINTOP = 270;
$MARGINLEFT = 15;
$PADDING = 10;
$SHADOW_OFFSET = 1;


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

$conn_id = connectFTP($target_ftp_server, $target_ftp_user, $target_ftp_password);
if (!$conn_id) {
  var_dump("FTP connection error");
  exit();
}
$systyp = systypeFtp($conn_id);

// On recupere la liste des fichiers present sur le FTP
log_debug("Scanning (".$ftp_server.", ".$ftp_user.")");
$files = scanftpdir($target_path_movies, $conn_id, $systyp);
$lines = read_dir_ftp($files, $target_path_movies, null, true);

usort($lines, function($a, $b) {
  return $a["date"] < $b["date"];
});

$updatelist = "";
for($i=0;$i<min($MAX_NB_RES,count($lines));$i++) {
  $updatelist=$updatelist.$lines[$i]["date"]->format('d M')." : ".$lines[$i]["name"];
  
  $updatelist = $updatelist."\n";
  //var_dump($lines [$i]);
}
$updatelist=trim($updatelist);

// create the initial updated wallpaper
$original_wallpaper = $path.DIRECTORY_SEPARATOR.$PATHTOWALLPAPER.$WALLPAPERNAME;
$updated_wallpaper = $path.DIRECTORY_SEPARATOR."updated_".$WALLPAPERNAME;
copy($original_wallpaper, $updated_wallpaper);

// Set the content-type
//header('Content-Type: image/png');

// Create the image
$im = imagecreatefrompng($updated_wallpaper);
//$im = imagecreatefromjpeg($updated_wallpaper);
//$im = imagecreatetruecolor(1920, 1200);

// Check size of the whole stuff for the background
$bbox = imageftbbox($FONTSIZE, 0, $path.DIRECTORY_SEPARATOR.$FONT, $updatelist);
$boxwidth = abs($bbox[0]) + abs($bbox[2]); // distance from left to right
$boxheight = abs($bbox[1]) + abs($bbox[5]); // distance from top to bottom

// Create some colors
$alphawhite = imagecolorallocatealpha($im, 255, 255, 255, 64);
$grey = imagecolorallocate($im, 158, 158, 158);
$black = imagecolorallocate($im, 0, 0, 0);

// Create nice alpha background
$coordRecX1 = $MARGINLEFT;
$coordRecY1 = $MARGINTOP;
$coordRecX2 = $boxwidth + $MARGINLEFT + $PADDING*2;
$coordRecY2 = $boxheight + $MARGINTOP + $PADDING*2;

imagefilledrectangle($im, $coordRecX1, $coordRecY1, $coordRecX2, $coordRecY2, $alphawhite);

// Add some shadow to the text
$coordTxtX1 = $coordRecX1+$PADDING;
$coordTxtY1 = intval($FONTSIZE*1.2) +$coordRecY1+$PADDING; 
if ($SHADOW_OFFSET) {
    imagettftext($im, $FONTSIZE, 0, $coordTxtX1+$SHADOW_OFFSET, $coordTxtY1+$SHADOW_OFFSET, $grey, $path.DIRECTORY_SEPARATOR.$FONT, $updatelist);
}
// Add the text
imagettftext($im, $FONTSIZE, 0, $coordTxtX1, $coordTxtY1, $black, $path.DIRECTORY_SEPARATOR.$FONT, $updatelist);

// Generate then destroy image
imagepng($im, $updated_wallpaper);
imagedestroy($im);

// push to ftp
// On se connecte au ftp
$conn_id = connectFTP($target_ftp_server, $target_ftp_user, $target_ftp_password);
if (!$conn_id) {
 	exit;
}
$target_filename_tmp=$PATHTOWALLPAPERTARGET.DIRECTORY_SEPARATOR.$WALLPAPERNAME."_tmp";
$target_filename=$PATHTOWALLPAPERTARGET.DIRECTORY_SEPARATOR.$WALLPAPERNAME;
$ret = putFtp($updated_wallpaper, $target_filename_tmp, $conn_id);
if (!$ret) {
  exit;
}

// Rename the target file without the extension
$ret = ftp_rename ( $conn_id , $target_filename_tmp, $target_filename);
if (!$ret) {
 log_error("Error rename (".$target_filename_tmp.")");
 @ftp_close ( $conn_id );
 exit;
}

@ftp_close ( $conn_id );
       
// Everything ok, delete the file
$ret = unlink($updated_wallpaper);
if (!$ret) {
 log_error("Error unlink (".$updated_wallpaper.")");
 exit;
}


?>
