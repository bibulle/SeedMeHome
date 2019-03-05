<?php zoids.gif
require_once "settings.php";
require_once "functions.php";
require_once "headers.php";

getHeader();
?>
	  	<div id="bottom" class="log">
		  <fieldset class="field">
		    <H1>
<?php
   if (!isset($_SERVER['REDIRECT_STATUS'])) {
   	$_SERVER['REDIRECT_STATUS'] = '403';
   }
   switch ($_SERVER['REDIRECT_STATUS']) {
   	case '401':
   		echo '401 : Unauthorized !';
   		break;
   	
   	case '403':
   		echo '403 : Access forbidden !';
   		break;
   	
	default:
   		echo $_SERVER['REDIRECT_STATUS'].' : Unknown error !';
   		break;
   } 

?>
		    </H1>
		  </fieldset>
		</div>
<?php
  getFooter(); 
?>
