<?php
require_once __DIR__.'/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
require_once 'Config.php';

$worker = new Worker('websocket://0.0.0.0:'.Config::PORT);
$worker->count = 8;

function console_log($str) {
  $data = date('Y.m.d H:i:s');
  echo "[$data] $str\n";
}

function getInstagramPosts() {
  $url = "https://api.instagram.com/v1/users/self/media/recent/?access_token=".Config::ACCESS_TOKEN."&count=".Config::COUNT;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
  $data = curl_exec($ch);
  curl_close($ch);
  $result = json_decode($data, true);
  // print_r($result);
  if ($result['meta']['code'] === 200) {
    console_log("getInstagramPosts(): got new posts");
  } else {
    console_log("getInstagramPosts(): got error:");
    console_log($result['meta']['code']);
    console_log($result['meta']['error_message']);
  }
  return $result;
}

$GLOBALS['data'] = getInstagramPosts();

$worker->onWorkerStart = function($worker) {
  Timer::add(Config::REFRESH_DELAY, function() use($worker) {
    $freshdata = getInstagramPosts();
    if ($freshdata['meta']['code'] === 200) {
      $diff = array_diff(array_map('serialize', $freshdata['data']), array_map('serialize', $GLOBALS['data']['data']));

      if (sizeof($diff) > 0) {
        $GLOBALS['data'] = $freshdata;
        foreach($worker->connections as $connection) {
          $connection->send(json_encode($freshdata['data']));
        }
        console_log("Got ".sizeof($diff)." new posts!");
      } else {
        console_log("No changes at all.");
      }
    }
  });
};

$worker->onConnect = function($conn) {
  $conn->onWebSocketConnect = function($connection) {
    console_log("New connection");
    $data = $GLOBALS['data'];

    if ($data['meta']['code'] !== 200) {
      $connection->send(json_encode($data['meta']));
      console_log("Sent an error ".$data['meta']['code']);
    } else {
      $connection->send(json_encode($data['data']));
      console_log("Sent data");
    }
  };
};

$worker->onClose = function($conn) {
  console_log("Connection closed");
};

Worker::runAll();
