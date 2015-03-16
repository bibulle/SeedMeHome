<?php
require_once "settings.php";
require_once "functions.php";

isAuthenticate();

if (isset($_GET['url'])) {
	
	$image = file($_GET['url']);
	
	foreach ($image as $part) {
		echo $part;
	}
}

?>
