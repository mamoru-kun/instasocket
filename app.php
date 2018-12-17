<?php
require_once __DIR__.'/vendor/autoload.php';
require_once 'Config.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$GLOBALS['config'] = new Config();

$worker = new Worker('websocket://0.0.0.0:'.$GLOBALS['config']->PORT);
Worker::$daemonize = !$GLOBALS['config']->DEBUG_MODE;

function console_log($str) {
  $data = date('Y.m.d H:i:s');
  echo "[$data] $str\n";
}

function getInstagramPosts() {
  $url = "https://api.instagram.com/v1/users/self/media/recent/?access_token=".$GLOBALS['config']->ACCESS_TOKEN."&count=".$GLOBALS['config']->COUNT;
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
    console_log("getInstagramPosts(): got posts collection");
  } else {
    console_log("getInstagramPosts(): got error:");
    print_r($result);
  }
  return $result;
}

function compare_function($a, $b) {
  return $a['id'] === $b['id'] ? 0 : 1;
}

$GLOBALS['posts'] = getInstagramPosts();

$worker->onWorkerStart = function($worker) {
  if ($GLOBALS['posts']['meta']['code'] === 200) {
    Timer::add($GLOBALS['config']->REFRESH_DELAY, function() use($worker) {
      $freshdata = getInstagramPosts();
      if ($freshdata['meta']['code'] === 200) {
        $diff = array_udiff($freshdata['data'], $GLOBALS['posts']['data'], 'compare_function');

        if (sizeof($diff) > 0) {
          console_log("Got ".sizeof($diff)." new posts!");
          foreach($worker->connections as $conn) {
            $conn->send(json_encode($freshdata['data']));
          }
        } else {
          console_log("No changes at all.");
        }
        $GLOBALS['posts'] = $freshdata;
      }
    });
  } else {
    console_log("Got no post so I wont update it.");
  }
};

$worker->onConnect = function($conn) {
  console_log("New connection from ".$conn->getRemoteIp());
  $conn->onWebSocketConnect = function($conn, $http_header) {
    $connection_valid = $GLOBALS['config']->ALLOWED_ORIGINS !== '*'
      ? in_array($_SERVER['HTTP_ORIGIN'], explode(',', $GLOBALS['config']->ALLOWED_ORIGINS))
      : true;
    if ($GLOBALS['config']->DEBUG_MODE) {
      console_log("The origin is " . $_SERVER['HTTP_ORIGIN']);
      console_log("The allowed origin is " . $GLOBALS['config']->ALLOWED_ORIGINS);
      console_log("The origin is " . ($connection_valid ? "" : "not ") . "valid!");
    }
    if (!$connection_valid) {
      console_log("Closing the connection because origin ".$_SERVER['HTTP_ORIGIN']." is invalid!");
      return $conn->close();
    }

    $data = $GLOBALS['posts'];

    if ($data['meta']['code'] !== 200) {
      $conn->send(json_encode($data['meta']));
      console_log("Sent an error ".$data['meta']['code']);
    } else {
      $conn->send(json_encode($data['data']));
      console_log("Sent data");
    }
  };
};

$worker->onClose = function($conn) {
  console_log($conn->getRemoteIp()." connection closed");
};

Worker::runAll();
