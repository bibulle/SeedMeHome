<?php

require_once (dirname(__FILE__)."/Imdbphp/Search.php");
require_once (dirname(__FILE__)."/Imdbphp/Movie.php");

// For debug purpose, html answer for direct call else json
if (array_key_exists('HTTP_REFERER', $_SERVER)) {
	header('Content-type: application/json');
} else {
	header('Content-type: text/html');
}

// We just add an id to a film
if (isset($_GET['title']) && isset($_GET['id'])) {

  $initial_title = $_GET['title'];

  // Load the cache
  $cache = loadCache();
  
  $cache['details'][$initial_title] = null;
  $cache['ids'][$initial_title] = $_GET['id'];

   saveCache($cache);

} else if (isset($_GET['title'])) {

  $initial_title = $_GET['title'];

  // Load the cache
  $cache = loadCache();
  
  //$cache['details'] = array();
 

  
  if (isset($cache['details'][$initial_title])) {
    $res = $cache['details'][$initial_title];
  } else {
    if (isset($cache['ids'][$initial_title])) {
      $id = $cache['ids'][$initial_title];
    } else {
      $id = searchForId($initial_title);
    } 
	  $res = getMovie($id);
	
	  if (isset($res)) {
	  	$res['initial_title']=$initial_title;
	  }
  
    $cache['details'][$initial_title] = $res;
    $cache['ids'][$initial_title] = $res['id'];

    saveCache($cache);
  }

  
	echo json_encode($res);
}

function loadCache() {
  $handle = @fopen("movie.db", "rb");

  if (!$handle) {
    return array("details" => array(), "ids" => array());
  }
  
  $contents = '';
  while (!feof($handle)) {
    $contents .= fread($handle, 8192);
  }
  fclose($handle);

  return unserialize($contents);
}

function saveCache($cache) {
  
  $handle = fopen("movie.db", "w");

  if (flock($handle, LOCK_EX)) { // do an exclusive lock
    ftruncate($handle, 0); // truncate file
    fwrite($handle, serialize($cache));
    flock($handle, LOCK_UN); // release the lock
  }

  fclose($handle);

}

function searchForId($title) {
	$searcher = getImdbSearcher();
	$res = $searcher->getSearchResults($title);

	$json = json_decode($res);
	
	if (isset($json->data->results[0]->list[0]->tconst)) {
		$id = $json->data->results[0]->list[0]->tconst;
	} else {
		$id = null;
	}
	
	return $id; 
}


function getMovie($id) {
	
	if (!isset($id)) {
		return;
	}
	$movie = getImdbMovieSearcher($id);

	$res = $movie->getMainDetails();
	$json = json_decode($res);	
	
	$ret['id']=$json->data->tconst;
	$ret['title']=$json->data->title;
	$ret['year']=$json->data->year;
	
	if (isset($json->data->certificate)) {
		$ret['certificate']=$json->data->certificate->certificate;
	}
	$ret['genres']=$json->data->genres;
	if (isset($json->data->image->url)) {
	  $ret['imageUrl']=$json->data->image->url;
	}
	$ret['title']=$json->data->title;
	if (isset($json->data->plot->outline)) {
	  $ret['plot']=$json->data->plot->outline;
	}
	
	$old_error_level = error_reporting();
	error_reporting($old_error_level ^ E_WARNING);
	try {
	  $res = $movie->getPlotSummary();
	  $json = json_decode($res);	
	
	  if (isset($json->data->plots)) {
		  $ret['plot']=$json->data->plots[0]->text;
	  }
	} catch (Exception $e) {
    //echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
 	error_reporting($old_error_level);

	return $ret;
}

function getImdbSearcher() {
	$old_error_level = error_reporting();
	error_reporting($old_error_level ^ E_NOTICE);
	
	$searcher = new Imdbphp_Search();
	$searcher->setLocale('fr_FR');
	
	error_reporting($old_error_level);
	
	return $searcher;
}
function getImdbMovieSearcher($id) {
	$old_error_level = error_reporting();
	error_reporting($old_error_level ^ E_NOTICE);
	
	$movie = new Imdbphp_Movie($id);
	$movie->setLocale('fr_FR');
	
	error_reporting($old_error_level);
	
	return $movie;
}

?>