<?php

// getting the current path
$script = $_SERVER['SCRIPT_FILENAME'];
$path = realpath(dirname($script));

//------------------------------------------------------------------------------
// SEEDMEHOME
//------------------------------------------------------------------------------
$users = array();
$users[] = array("login"=>"eric", "password"=>"eric", "role"=>"admin");
$users[] = array("login"=>"u1", "password"=>"pass", "role"=>"read");

//------------------------------------------------------------------------------
// SEEDBOX
//------------------------------------------------------------------------------
//Change below settings to match you seedbox configuration
#$seedbox_type = 'rutorrent';
$seedbox_type = 'transmission';
$seedbox_host = 'foobar.seebox.rt';
$seedbox_port = 1234;
$seedbox_user = 'yourname';
$seedbox_passwd = 'mfzpzeoqep';
$seedbox_home_dir = '/home/yourname';

//------------------------------------------------------------------------------
// FTP SeedBox
//------------------------------------------------------------------------------
// your ftp configuration
$ftp_server = "foobar.seebox.rt";
$ftp_user = $seedbox_user;
$ftp_password = $seedbox_passwd;
$ftp_path = "";

//------------------------------------------------------------------------------
// TARGETS
//------------------------------------------------------------------------------
// Target directory
#$target_ftp_server = "mafreebox.free.fr";
#$target_ftp_user = "freebox";
#$target_ftp_password = "blabla";
$target_path_movies = "D:\\files\\movies";
$target_path_tvshows = "D:\\files\\tvshows";

// How to know it's a TV show ? With those regexp
// Result must be : $1 Show name, $2 saison num, $3 Episode num, $4 Episode title (or empty), $5 File Extension
$target_tvshows_regexp = array(
	"/(.+)[ .]+[s]([0-9]+)[e]([0-9]+)[ -.]*(.*)[.]([^.]*)/i",
	"/(.+)[ .]+([0-9]+)[-.x]([0-9]+)[ -.]*(.*)[.]([^.]*)/i"
);
// How to build target directories ? With this format
$target_tvshows_format = '/%1$s Season %2$d/Episode %3$02d - %4$s.%5$s';
//$target_tvshows_format = '/%1$s/Season %2$d/Episode %3$02d - %4$s.%5$s';
//$target_tvshows_format = '/%1$s/Season %2$d - Episode %3$02d - %4$s.%5$s';

// May I clean the filenames ?
$target_file_cleaner = array(
  "Repack", "Multi", "BDrip", "DVDrip", "Xvid", "AC-3-UTT", "[-]*UTT[-]*", "[-]*LECHTI[-]*", "TrueFrench", "VOSTFR"
);
//$target_file_cleaner = array();

// extension added to target files during transfert (it could be long across partitions)
$target_moving_extension = ".moving";

//------------------------------------------------------------------------------
// MISCELLANEOUS
//------------------------------------------------------------------------------
//Select a style 'default' or 'sand'
$style = 'default';

//Path on the (web)server where downloads are stored, used to check freedisk
$disks = array( 
	'home'  => 'C:' ,
	'file1' => 'D:',
	'file2' => 'E:');

// Path vers le fichier de log
$debug_level=3; // 4 DEBUG, 3 INFO, 2 WARN, 1 ERROR
$log_file = "D:\\temp\\SeedMeHome.log";

// Add poll or not
//$poll_enable = 'yes';

// Add google analytics
//$google_analytics_id = "UA-XXXXXXXX-X";

//------------------------------------------------------------------------------
// OTHER
//------------------------------------------------------------------------------
// Path where files retrieve with FTP are placed
$ftp_download_path = $path.DIRECTORY_SEPARATOR."downloaded";
// Path where files .done and .curr from FTP are placed
$ftp_done_path = $path.DIRECTORY_SEPARATOR."done";

// Extension added to file during ftp transfert
$ftp_download_extension = ".ftpget";


// Download settings, no need to change these
$download_max_filesize = 3000000;
$downloaded_allowed_types = array(
   "text/xml"                => ".torrent",
);

?>
