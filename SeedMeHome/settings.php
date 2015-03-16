<?php
if(!@include_once('mySettings.php') ) {
	
	@copy('mySettings-model.php', 'mySettings.php');
	include('install.php');
	exit();
}

// Getting the versions
$error_level = error_reporting() ;
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE) ;
$props = parse_ini_file("version.properties");
error_reporting($error_level) ;

if (isset($props["seedmehome.version.full"])) {
	$seedmehome_version = $props["seedmehome.version.full"];
} else {
	$seedmehome_version = "?.?";
}

?>
