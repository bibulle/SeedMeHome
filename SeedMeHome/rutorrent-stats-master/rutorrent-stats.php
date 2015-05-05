<?php

//$seedboxes = array(
//  'GGSHQ' => array(
//    'url'      => 'http://neon.ggshq.com/plugins/httprpc/action.php',
//    'username' => 'your username',
//    'password' => 'your password',
//    'authtype' => CURLAUTH_BASIC,
//    'rpc'      => 'httprpc',
//  ),
//  'SeedMonster' => array(
//    'url'      => 'http://89.149.223.192/rutorrent/plugins/rpc/rpc.php',
//    'username' => 'your username',
//    'password' => 'your password',
//    'authtype' => CURLAUTH_DIGEST,
//    'rpc'      => 'xmlrpc',
//  ),
//);
$seedboxes = array(
  //'GGSHQ' => array(
  //  'url'      => 'http://88.191.188.21/manager/rutorrent/plugins/httprpc/action.php',
  //  'username' => '1user',
  //  'password' => 'a7qO2Pey',
  //  'authtype' => CURLAUTH_BASIC,
  //  'rpc'      => 'httprpc',
  //),
  'SeediBox' => array(
    'url'      => 'http://88.191.188.21/manager/rutorrent/plugins/rpc/rpc.php',
    'username' => '1user',
    'password' => 'a7qO2Pey',
    'authtype' => CURLAUTH_DIGEST,
    'rpc'      => 'xmlrpc',
  ),
);

require('rutorrent-stats.inc');
$stats = rutorrent_stats($seedboxes);
print json_encode($stats);
exit;
