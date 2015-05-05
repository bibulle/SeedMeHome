<?php
require_once "settings.php";
require_once "functions.php";
require_once "headers.php";

$user_role = isAuthenticate();

getHeader();
?>
	<div id="column1_top"></div>
     <div class="links">
	   <a class="link" href="list.php">Results</a>
	 </div>
<?php
	if ($user_role == "admin") {
?>
    <div id="column1" class="menu">
	<form id="uploadtorrent" target="upload_iframe" enctype="multipart/form-data" action="upload.php?item=torrent" method="post">
	    <fieldset class="field"><legend>Upload torrent</legend>
                <input class="input" name="torrentfile" type="file" /><br><br>
                <input class="submit" type="submit" value="Upload File" />
	    </fieldset>
	</form>
    </div>
<?php
	}
?>
    <div id="column1_detail" class="menu" style="position: absolute;display:none">
	    <fieldset id="detail_fieldset" class="field"><legend id="detail_legend" class="legend"></legend>
	      <div id="detail"></div>
	    </fieldset>
    </div>

    <div id="column2" class="contents">
	<fieldset class="field">
	    <legend>Download Queue</legend>
       	    <div id="queue"></div>
	</fieldset>
	<fieldset class="field">
            <legend>Done</legend>
            <div id="done"></div>
        </fieldset>
    </div>
	<div id="column2_top" style="float: left; width: 100%">
    <div id="bottom" class="log">
	<fieldset class="field">
            <legend>Logging</legend>
            <div id="log"></div>
        </fieldset>
    </div>
    </div>

<div id="result_div" class="popup" style="display:none;">
    <div id="result">
    </div>
</div>
<div id="movefile_div" class="popup" style="display:none;">
  <form id="movefile" target="upload_iframe" method="get" action="set.php">
    <center><div>
    <fieldset class="field"><legend>Move file</legend>
      <input class="input" type="hidden" name="do" id="do" value="moveFile" />
      <input class="input" type="hidden" name="filename" id="filename" value="" />
      <input class="input" type="text" name="targetname" id="targetname" value="" />
      <input class="submit" type="submit" value="Submit" />
    </fieldset>
    </div></center>
  </form>
</div>
<script language="JavaScript">
Event.observe(window, 'load', function() {
	Event.observe('column1_detail', 'click', function(event) {Effect.toggle('column1_detail', 'appear', { duration: 0.2 });});
	Event.observe('result_div'  , 'click', function(event) {Effect.toggle('result_div', 'appear', { duration: 0.2 });});
	// prepare the move file div
	Event.observe('movefile_div', 'click', function(event) {Effect.toggle('movefile_div', 'appear', { duration: 0.2 });});
	Event.observe('targetname'  , 'click', function(event) {Event.stop(event);});

	//start refreshing of the screen
	refreshStatus();
	refreshSeedQueue();
	refreshDoneFiles();
	refreshLog();

<?php
	if ($user_role == "admin") {
?>
	//watch for form submits
	$('uploadtorrent').observe('submit', onuploadtorrentFormSubmit);
<?php
	}
?>
});
</script>

<iframe id="upload_iframe" name="upload_iframe" style="width:0px; height:0px; border: 0px">
</iframe>

<?php 
getFooter();
?>
