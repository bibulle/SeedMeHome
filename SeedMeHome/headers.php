<?php

/**
 * Add header
 * @param boolean isJavascript  needed ? (only in index.php)
 * @param string  path to add before css or js to get it (for example ../) 
 */
function getHeader($relativePath = "") {
	global $style, $seedmehome_version, $google_analytics_id;
	if (!isset($style)) {
		$style="default";
	}
("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once "settings.php";
require_once "functions.php";
require_once "headers.php";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<META http-equiv=Content-Type content="text/html; charset=utf-8">
<?php

echo '<link rel="stylesheet" type="text/css" href="'.$relativePath.'styles/'.$style.'.css">';

?>
<script src="js-lib/prototype.js" type="text/javascript"></script>
<script src="js-lib/scriptaculous.js" type="text/javascript"></script>
<script src="javascript.js" type="text/javascript"></script>

<title>SeedMeHome</title>
<?php
  if (isset($google_analytics_id)) {
?>
  	<script type="text/javascript">

  var _gaq = _gaq || [];
  <?php echo "_gaq.push(['_setAccount', '$google_analytics_id']);";?>
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<?php
  }
?>

</HEAD>
<BODY>

 <center>
  <div class = "container"> 

    <div class="top">
	<div class="top_left" id="name">SeedMeHome<span class="small">version <?php echo $seedmehome_version;?></span></div>
	<div class="top_right" id="status1"></div>
	<div class="top_right" id="status2"></div>
    </div>

<?php
} 

function getFooter() {
?>
  </div>
 </center>
</BODY>
</HTML>
<?php	
}


?>
