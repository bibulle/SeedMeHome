<?php

require_once 'xmlrpc.inc';

function rutorrent_stats($seedboxes) {
  $stats = array();
  foreach ($seedboxes as $name => $seedbox) {
    $stats[$name] = _get_seedbox_statistics($seedbox['url'], $seedbox['username'], $seedbox['password'], $seedbox['authtype'], $seedbox['rpc']);
  }
  return $stats;
}

function rutorrent_files($seedbox, $hash, $directory, $home_dir) {
  $files = array();
  
  $files = _get_seedbox_files($seedbox['url'], $seedbox['username'], $seedbox['password'], $seedbox['authtype'], $seedbox['rpc'], $hash, $directory, $home_dir);
  
  return $files;
}

function rutorrent_action($seedbox, $hash, $action) {
  $ret = array();
  
  switch ($action) {
    case 'start':
    	$actions=array("stop", "start");
    	break;
  
    case 'stop':
    	$actions=array("stop");
    	break;
  
    case 'remove':
      log_error("Remove no implemented");
    	$actions=array();
    	break;
  
    default :
    	log_error("Erreur d'action sur le torrent '".$action."'");
    	return;
  }
  
  foreach ($actions as $key => $value) {
    $ret = _get_seedbox_action($seedbox['url'], $seedbox['username'], $seedbox['password'], $seedbox['authtype'], $seedbox['rpc'], $hash, $value);
  }
  
  return $ret;
}

function _get_seedbox_statistics($url, $username, $password, $authtype, $rpc) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
  curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
  curl_setopt($ch, CURLOPT_USERAGENT, "DriverPacks.net seedbox status checker/1.0 (Linux)");
  curl_setopt($ch, CURLOPT_TIMEOUT, 4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_POST, 1);

  curl_setopt($ch, CURLOPT_HTTPAUTH, $authtype);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

  $f = "_rutorrent_stats_${rpc}_generate_request";
  $request = $f();
  curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request->post);
  $result = curl_exec($ch);

  // Detect errors.
  if (curl_errno($ch)) {
    return (object)array(
      'online' => FALSE,
      'offline_reason' => curl_error($ch),
    );
  }

  // Ensure authentication worked and that the RPC URL is correct.
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($http_code == 401) {
    return (object)array(
      'online' => FALSE,
      'offline_reason' => 'Authentication failed.',
    );
  }
  else if ($http_code == 404) {
    return (object)array(
      'online' => FALSE,
      'offline_reason' => 'Bad RPC URL: got a 404.',
    );
  }

  // Close connection.
  curl_close($ch);

  // Work with the data.
  $f = "_rutorrent_stats_${rpc}_decode_answer";
  $answer = $f($result);
  //var_dump($answer);
  $num_torrents = count(array_keys($answer));

  // Gather statistics.
  $f = "_rutorrent_stats_${rpc}_get_indices";
  $indices = $f();
  $size = 0;
  $do_speed = 0;
  $ul_speed = 0;
  $ul_size = 0;
  $ul_count = 0;
  $peers = 0;
  $active_torrents=0;
  $torrents = array();
  foreach ($answer as $torrent_details) {
    $torrent_size     = $torrent_details[$indices['size']    ];
    $torrent_size     = $torrent_details[$indices['size']    ];
    $torrent_ul_count = $torrent_details[$indices['ratio']] / 1000;
    $torrent_ul_size  = $torrent_size * $torrent_ul_count;

    $size     += $torrent_size;
    $do_speed += $torrent_details[$indices['do_speed']];
    $ul_speed += $torrent_details[$indices['ul_speed']];
    $ul_count += $torrent_ul_count;
    $ul_size  += $torrent_ul_size;
    $peers    += $torrent_details[$indices['peers']   ];
    
    if ( $torrent_details[$indices['state']] == 1 ) {
      $active_torrents++;
    }
    
    $isActive = $torrent_details[$indices['is_active']];
    $state = $torrent_details[$indices['state']];
    $complete = $torrent_details[$indices['complete']];
    $status=-1;
    if (($state == 0) || ($isActive == 0)) {
      $status = 16;  // Pause
    } else if (($state == 1) && ($complete == 0)) {
      $status = 4;   // Download
    } else {
      $status = 8;  // upload
    } 
    
    $torrents[] = (object)array(
      'id'         => $torrent_details[$indices['hash']],
      'hash'       => $torrent_details[$indices['hash']],
      'name'       => $torrent_details[$indices['name']],
      'percentDone'=> ($torrent_details[$indices['done']] / $torrent_details[$indices['size']]),
      'totalSize'  => $torrent_details[$indices['size']],
      'done'       => $torrent_details[$indices['done']],
      'rateUpload' => $torrent_details[$indices['ul_speed']],
      'rateDownload' => $torrent_details[$indices['do_speed']],
      'uploadRatio' => ($torrent_details[$indices['ratio']]  / 1000),
      'peers'      => $torrent_details[$indices['peers']],
      'status'      => $status,
      'directory'  => $torrent_details[$indices['directory']],
      'files'      => array(),
    );
    //var_dump($torrent_details[$indices['name']]);
    //var_dump($torrent_details[$indices['essai']]);
  }
  $stats = array(
    'online'           => ($num_torrents > 0),
    'activeTorrentCount' => $active_torrents,
    'torrentCount'     => $num_torrents,
    'size'             => round($size / (1000 * 1000 * 1000), 2), // GB
    'downloadSpeed'   => round($do_speed / 1000), // KBps
    'uploadSpeed'     => round($ul_speed / 1000), // KBps
    'upload_size'      => round($ul_size / (1000 * 1000 * 1000), 2), // GB
    'upload_count_avg' => round($ul_count / $num_torrents, 2),
    'peers'            => $peers,
    'torrents'         => $torrents,
  );

  return (object)$stats;
}
function _get_seedbox_files($url, $username, $password, $authtype, $rpc, $hash, $directory, $home_dir) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
  curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
  curl_setopt($ch, CURLOPT_USERAGENT, "DriverPacks.net seedbox status checker/1.0 (Linux)");
  curl_setopt($ch, CURLOPT_TIMEOUT, 3);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_POST, 1);

  curl_setopt($ch, CURLOPT_HTTPAUTH, $authtype);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

  $f = "_rutorrent_files_${rpc}_generate_request";
  $request = $f($hash);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request->post);
  $result = curl_exec($ch);
  
  // Detect errors.
  if (curl_errno($ch)) {
    return array(
      'online' => FALSE,
      'offline_reason' => curl_error($ch),
    );
  }

  // Ensure authentication worked and that the RPC URL is correct.
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($http_code == 401) {
    return array(
      'online' => FALSE,
      'offline_reason' => 'Authentication failed.',
    );
  }
  else if ($http_code == 404) {
    return array(
      'online' => FALSE,
      'offline_reason' => 'Bad RPC URL: got a 404.',
    );
  }

  // Close connection.
  curl_close($ch);

  // Work with the data.
  $f = "_rutorrent_stats_${rpc}_decode_answer";
  $answer = $f($result);
  //var_dump($answer);
  $num_torrents = count(array_keys($answer));

  // Gather statistics.
  $f = "_rutorrent_files_${rpc}_get_indices";
  $indices = $f();

  $files = array();
  foreach ($answer as $torrent_details) {
    $file_name     = $torrent_details[$indices['path']];
    $file_bytesSize = $torrent_details[$indices['size_bytes']];
    $file_chunkSize = $torrent_details[$indices['size_chunks']];
    $file_chunkComp = $torrent_details[$indices['completed_chunks']];
    
    $path=preg_replace("|^".$home_dir."(/)*|", "", $directory.'/'.$file_name);
    
    $files[] = (object)array(
      'name'       => $path,
      'length'     => $file_bytesSize,
      'bytesCompleted' => ( $file_bytesSize * $file_chunkComp / $file_chunkSize),
    );
  }

  return $files;
}
function _get_seedbox_action($url, $username, $password, $authtype, $rpc, $hash, $action) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
  curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
  curl_setopt($ch, CURLOPT_USERAGENT, "DriverPacks.net seedbox status checker/1.0 (Linux)");
  curl_setopt($ch, CURLOPT_TIMEOUT, 3);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_POST, 1);

  curl_setopt($ch, CURLOPT_HTTPAUTH, $authtype);
  curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

  $f = "_rutorrent_action_${rpc}_generate_request";
  
  $request = $f($hash, $action);
  var_dump($request);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request->post);
  $result = curl_exec($ch);
    
  // Detect errors.
  if (curl_errno($ch)) {
    return array(
      'online' => FALSE,
      'offline_reason' => curl_error($ch),
    );
  }
    
  // Ensure authentication worked and that the RPC URL is correct.
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($http_code == 401) {
    return array(
      'online' => FALSE,
      'offline_reason' => 'Authentication failed.',
    );
  }
  else if ($http_code == 404) {
    return array(
      'online' => FALSE,
      'offline_reason' => 'Bad RPC URL: got a 404.',
    );
  }
  
  // Close connection.
  curl_close($ch);

  // Work with the data.
  $f = "_rutorrent_stats_${rpc}_decode_answer";
  $answer = $f($result);
  var_dump($answer);

  return array();
}

function _rutorrent_stats_httprpc_generate_request() {
  $args = array(
    'mode' => 'list',
    'cmd' => array(
      'd.get_hash=',
      'd.get_name=',
      'd.get_bytes_done=',
      'd.get_up_total=',
      'd.get_up_rate=',
      'd.get_ratio=',
      'd.get_peers_connected=',
      'd.is_active'
    )
  );

  // Build POST request from RPC args.
  $post = array();
  foreach ($args as $key => $value) {
    if (is_array($value)) {
      foreach (array_values($value) as $v) {
        $post[] = urlencode($key) . "=" . urlencode($v);
      }
    }
    else {
      $post[] = urlencode($key) . "=" . urlencode($value);
    }
  }
  $post = implode("&", $post);

  $request = new StdClass();
  $request->post = $post;
  $request->headers = array(
    'Accept: application/json, text/javascript',
  );

  return $request;
}

function _rutorrent_stats_httprpc_decode_answer($answer) {  
  $decoded = json_decode($answer);
  $data = (array) $decoded->t;
  return $data;
}

function _rutorrent_stats_httprpc_get_indices() {
  return array(
    'size'     => 8,
    'ul_speed' => 11,
    'ul_count' => 10,
    'peers'    => 15,
  );
}

function _rutorrent_stats_xmlrpc_generate_request() {
  $request = new StdClass();
  $request->post = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
  <methodName>d.multicall</methodName>
  <params>
    <param><value><string>main</string></value></param>
    <param><value><string>d.get_hash=</string></value></param>
    <param><value><string>d.get_name=</string></value></param>
    <param><value><string>d.get_size_bytes=</string></value></param>
    <param><value><string>d.get_bytes_done=</string></value></param>
    <param><value><string>d.get_up_rate=</string></value></param>
    <param><value><string>d.get_down_rate=</string></value></param>
    <param><value><string>d.get_ratio=</string></value></param>
    <param><value><string>d.get_peers_connected=</string></value></param>
    <param><value><string>d.get_complete=</string></value></param>
    <param><value><string>d.get_state=</string></value></param>
    <param><value><string>d.get_directory=</string></value></param>
    <param><value><string>d.is_active=</string></value></param>
  </params>
</methodCall>
EOF;
  $request->headers = array(
    'Content-type: text/xml',
  );

  return $request;
}

function _rutorrent_files_xmlrpc_generate_request($hash) {
  $request = new StdClass();
  $request->post = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
  <methodName>f.multicall</methodName>
  <params>
    <param><value><string>$hash</string></value></param>
    <param><value><string></string></value></param>
    <param><value><string>f.get_path=</string></value></param>
    <param><value><string>f.get_size_bytes=</string></value></param>
    <param><value><string>f.get_completed_chunks=</string></value></param>
    <param><value><string>f.get_size_chunks=</string></value></param>
  </params>
</methodCall>
EOF;
  $request->headers = array(
    'Content-type: text/xml',
  );

  return $request;
}

function _rutorrent_action_xmlrpc_generate_request($hash, $method) {
  $request = new StdClass();
  $request->post = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
  <methodName>d.$method</methodName>
  <params>
    <param><value><string>$hash</string></value></param>
    <param><value><string>1</string></value></param>
  </params>
</methodCall>
EOF;
  $request->headers = array(
    'Content-type: text/xml',
  );

  return $request;
}

function _rutorrent_stats_xmlrpc_decode_answer($answer) {
  //include('xmlrpc.inc');

  // PHP's xmlrpc_decode() doesn't support 64-bit ints, nor do we need it
  // here, hence use the hack described at
  // http://www.php.net/manual/en/function.xmlrpc-decode.php#93080
  $answer = str_replace('i8>', 'double>', $answer);
  
  // Now parse what we've got back
  $message = xmlrpc_message($answer);
  if (!xmlrpc_message_parse($message)) {
    print 'Parse error!';
  }
  $data = $message->params[0];

  return $data;
}

function _rutorrent_stats_xmlrpc_get_indices() {
  return array(
    'hash'       => 0,
    'name'       => 1,
    'size'       => 2,
    'done'       => 3,
    'ul_speed'   => 4,
    'do_speed'   => 5,
    'ratio'      => 6,
    'peers'      => 7,
    'complete'   => 8,
    'state'      => 9,
    'directory'  => 10,
    'is_active'  => 11,
  );
}
function _rutorrent_files_xmlrpc_get_indices() {
  return array(
    'path'       => 0,
    'size_bytes' => 1,
    'completed_chunks' => 2,
    'size_chunks' => 3,
  );
}
