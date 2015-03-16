<?php
require_once "settings.php";
require_once "functions.php";
require_once "headers.php";

isAuthenticate();

getHeader();
?>
	  	<div id="bottom" class="log">
		  <fieldset class="field">You must edit your settings file : "mySettings.php"</fieldset>
		</div>
<?php
  getFooter(); 
?>